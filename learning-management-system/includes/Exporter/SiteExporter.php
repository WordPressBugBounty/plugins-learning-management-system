<?php
/**
 * Full site exporter class.
 *
 * @package Masteriyo\Exporter
 */

namespace Masteriyo\Exporter;

defined( 'ABSPATH' ) || exit;

use Masteriyo\AdminFileDownloadHandler;
use Masteriyo\FileHandler;
use Masteriyo\Jobs\CoursesExportJob;
use ZipArchive;

/**
 * Bundles the settings, users and courses exports into one archive.
 *
 * @phpstan-type SiteExportType 'settings'|'users'|'courses'
 * @phpstan-type SiteExportStatus array{status: 'idle'|'progress'|'completed', message: string, download_url?: string}
 */
class SiteExporter {

	/**
	 * Archive folder, relative to the Masteriyo upload directory.
	 */
	const EXPORT_DIRECTORY = 'export/site';

	/**
	 * The ID used to generate the archive's download URL.
	 */
	const FILE_PATH_ID = 'export_site_zip';

	/**
	 * Marks an archive's manifest as a full site export.
	 */
	const MANIFEST_TYPE = 'masteriyo-site-export';

	/**
	 * Archive entry per export type, in the order they are imported.
	 *
	 * @var array<SiteExportType, string>
	 */
	const ENTRIES = array(
		'settings' => 'settings.json',
		'users'    => 'users.csv',
		'courses'  => 'courses.json',
	);

	/**
	 * Export the given types, or schedule the archive for after the background course export.
	 *
	 * @param list<SiteExportType> $types Types to export.
	 *
	 * @return SiteExportStatus|\WP_Error
	 */
	public function export( array $types ) {
		delete_option( self::get_pending_option_name() );

		if ( in_array( 'courses', $types, true ) ) {
			$response = masteriyo_dispatch_import_export_request( 'courses/export' );

			if ( $response->is_error() ) {
				$error = $response->as_error();

				if ( 'course_not_found' !== $error->get_error_code() ) {
					return $error;
				}

				$types = array_values( array_diff( $types, array( 'courses' ) ) );
			} elseif ( 'progress' === ( $response->get_data()['status'] ?? '' ) ) {
				update_option( self::get_pending_option_name(), $types, false );

				return array(
					'status'  => 'progress',
					'message' => __( 'Export in progress. Please wait...', 'learning-management-system' ),
				);
			}
		}

		return $this->create_archive( $types );
	}

	/**
	 * Report on an export, building its archive once the background course export is done.
	 *
	 * @return SiteExportStatus|\WP_Error
	 */
	public function get_progress_status() {
		$types = get_option( self::get_pending_option_name() );

		if ( ! is_array( $types ) ) {
			return self::idle_status();
		}

		if ( CoursesExportJob::is_tasks_in_progress() ) {
			return array(
				'status'  => 'progress',
				'message' => __( 'Export in progress. Please wait...', 'learning-management-system' ),
			);
		}

		// Kept on failure so a retried status request reports the same error.
		if ( CoursesExportJob::is_export_unfinished( get_current_user_id() ) ) {
			return new \WP_Error(
				'masteriyo_site_export_courses_failed',
				__( 'Exporting courses failed. See Masteriyo → Tools → Logs for details.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		// Only the request that deletes the option builds: a second build would delete the first archive.
		// A failed build is not put back, since a later rebuild would delete any newer archive.
		if ( ! delete_option( self::get_pending_option_name() ) ) {
			return self::idle_status();
		}

		return $this->create_archive( $types );
	}

	/**
	 * Status when no export is waiting.
	 *
	 * @return array{status: 'idle', message: ''}
	 */
	private static function idle_status(): array {
		return array(
			'status'  => 'idle',
			'message' => '',
		);
	}

	/**
	 * Return the folder where archives are stored.
	 *
	 * @return string
	 */
	public static function get_file_path(): string {
		return wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . MASTERIYO_UPLOAD_DIR . DIRECTORY_SEPARATOR . self::EXPORT_DIRECTORY;
	}

	/**
	 * Option holding the types of an export waiting on the background course export.
	 *
	 * @return string
	 */
	private static function get_pending_option_name(): string {
		return 'masteriyo_site_export_pending_' . get_current_user_id();
	}

	/**
	 * Zip the exported files with a manifest.
	 *
	 * @param list<SiteExportType> $types Types to export.
	 *
	 * @return SiteExportStatus|\WP_Error
	 */
	private function create_archive( array $types ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'missing_zip_package', __( 'Zip Export not supported.', 'learning-management-system' ) );
		}

		$sources = $this->collect_sources( $types );

		if ( is_wp_error( $sources ) ) {
			return $sources;
		}

		if ( empty( $sources['files'] ) ) {
			return new \WP_Error(
				'masteriyo_site_export_empty',
				__( 'There is nothing to export.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$archive = $this->write_archive( $sources['files'] );

		// They sit under a public uploads URL and hold settings secrets and user details.
		array_map( 'wp_delete_file', $sources['temporary'] );

		if ( is_wp_error( $archive ) ) {
			return $archive;
		}

		return array(
			'status'       => 'completed',
			'download_url' => AdminFileDownloadHandler::get_download_url( self::FILE_PATH_ID, $archive['filename'] ),
			'message'      => sprintf(
				/* translators: %s: date and time. */
				__( 'Export completed on %s.', 'learning-management-system' ),
				masteriyo_rest_prepare_date_response( time() )
			),
		);
	}

	/**
	 * Write the archive: a manifest plus each exported file.
	 *
	 * @param array<string, string> $files Exported file paths keyed by archive entry.
	 *
	 * @return array{file_path: string, file_url: string, filename: string}|\WP_Error
	 */
	private function write_archive( array $files ) {
		$archive = $this->create_export_file();

		if ( is_wp_error( $archive ) ) {
			return $archive;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $archive['file_path'], ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return self::archive_write_error();
		}

		$zip->addFromString(
			'manifest.json',
			(string) wp_json_encode(
				array(
					'type'       => self::MANIFEST_TYPE,
					'version'    => masteriyo_get_version(),
					'created_at' => gmdate( 'D, d M Y H:i:s +0000' ),
					'base_url'   => home_url(),
					'entries'    => array_keys( $files ),
				)
			)
		);

		foreach ( $files as $entry => $path ) {
			$zip->addFile( $path, $entry );
		}

		// Files are read only now, so a missing or unreadable one fails here.
		return $zip->close() ? $archive : self::archive_write_error();
	}

	/**
	 * Error for an archive that could not be written.
	 *
	 * @return \WP_Error
	 */
	private static function archive_write_error(): \WP_Error {
		return new \WP_Error(
			'unable_to_create_zip',
			__( 'Unable to open export file (archive) for writing.', 'learning-management-system' )
		);
	}

	/**
	 * Run the per-type exports and collect the files they wrote.
	 *
	 * @param list<SiteExportType> $types Types to export.
	 *
	 * @return array{files: array<string, string>, temporary: list<string>}|\WP_Error Files keyed by archive entry.
	 */
	private function collect_sources( array $types ) {
		$files     = array();
		$temporary = array();

		foreach ( array( 'settings', 'users' ) as $type ) {
			if ( ! in_array( $type, $types, true ) ) {
				continue;
			}

			$response = masteriyo_dispatch_import_export_request( $type . '/export' );

			if ( $response->is_error() ) {
				$error = $response->as_error();

				// Every user is an administrator, and the users export leaves those out.
				if ( in_array( $error->get_error_code(), array( 'no_users', 'no_users_data' ), true ) ) {
					continue;
				}

				array_map( 'wp_delete_file', $temporary );

				return $error;
			}

			$path                            = $response->get_data()['filepath'];
			$files[ self::ENTRIES[ $type ] ] = $path;
			$temporary[]                     = $path;
		}

		$courses_file = in_array( 'courses', $types, true ) ? CourseExporter::get_full_file_path() : false;

		if ( $courses_file ) {
			$files[ self::ENTRIES['courses'] ] = $courses_file;
		}

		return array(
			'files'     => $files,
			'temporary' => $temporary,
		);
	}

	/**
	 * Create this user's archive file in a web-protected folder, replacing their previous one.
	 *
	 * @return array{file_path: string, file_url: string, filename: string}|\WP_Error
	 */
	private function create_export_file() {
		$file_handler = new FileHandler();
		$prefix       = sprintf( 'masteriyo-site-export-%d-', get_current_user_id() );

		$file_handler->protect_directory( self::EXPORT_DIRECTORY );

		$old_files = $file_handler->search_files( $prefix, self::EXPORT_DIRECTORY );

		if ( ! is_wp_error( $old_files ) ) {
			foreach ( $old_files as $old_file ) {
				$file_handler->delete( self::EXPORT_DIRECTORY, $old_file['name'] );
			}
		}

		// .htaccess does not protect the folder on Nginx, so the name must not be guessable.
		return $file_handler->create_file( self::EXPORT_DIRECTORY, $prefix . gmdate( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 32, false ) . '.zip' );
	}
}
