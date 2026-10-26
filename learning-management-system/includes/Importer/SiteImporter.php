<?php
/**
 * Full site importer class.
 *
 * @package Masteriyo\Importer
 */

namespace Masteriyo\Importer;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Exporter\SiteExporter;
use Masteriyo\FileHandler;
use Masteriyo\Jobs\CoursesImportJob;
use ZipArchive;

/**
 * Imports a full site export archive through the per-type import endpoints.
 *
 * @phpstan-import-type SiteExportType from SiteExporter
 * @phpstan-type SiteImportResult array{status: 'completed'|'partial'|'progress'|'failed', message: string}
 */
class SiteImporter {

	/**
	 * Extraction folder, relative to the Masteriyo upload directory.
	 */
	const IMPORT_DIRECTORY = 'import/site';

	/**
	 * Import endpoint per export type.
	 *
	 * @var array<SiteExportType, string>
	 */
	const ROUTES = array(
		'settings' => 'settings/import',
		'users'    => 'users/import',
		'courses'  => 'courses/import',
	);

	/**
	 * Import an archive, then delete it.
	 *
	 * @param string $file Path to the uploaded archive.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import( string $file ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'missing_zip_package', __( 'Zip Export not supported.', 'learning-management-system' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$lock = 'masteriyo_site_import_' . get_current_user_id();

		// This user's imports share one extraction folder, which a pending course job still reads.
		if ( CoursesImportJob::is_task_in_progress() || ! \WP_Upgrader::create_lock( $lock ) ) {
			wp_delete_file( $file );

			return new \WP_Error(
				'masteriyo_site_import_in_progress',
				__( 'Import in progress. Please wait...', 'learning-management-system' ),
				array( 'status' => 409 )
			);
		}

		try {
			$zip = new ZipArchive();

			if ( true !== $zip->open( $file ) ) {
				return self::invalid_file_error();
			}

			$result = $this->import_archive( $zip );

			$zip->close();

			return $result;
		} finally {
			wp_delete_file( $file );
			\WP_Upgrader::release_lock( $lock );
		}
	}

	/**
	 * Check the manifest and import each entry the archive holds.
	 *
	 * @param ZipArchive $zip Open archive.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function import_archive( ZipArchive $zip ) {
		$manifest_error = $this->check_manifest( $zip );

		if ( $manifest_error ) {
			return $manifest_error;
		}

		$results = array();

		foreach ( SiteExporter::ENTRIES as $type => $entry ) {
			if ( false !== $zip->locateName( $entry ) ) {
				$results[ $type ] = $this->import_entry( $zip, $type, $entry );
			}
		}

		if ( empty( $results ) ) {
			return self::invalid_file_error();
		}

		return self::build_response( $results );
	}

	/**
	 * Refuse an archive that is not a versioned site export, or comes from a newer Masteriyo.
	 *
	 * @param ZipArchive $zip Open archive.
	 *
	 * @return \WP_Error|null Null when the archive can be imported.
	 */
	private function check_manifest( ZipArchive $zip ): ?\WP_Error {
		$manifest = json_decode( (string) $zip->getFromName( 'manifest.json' ), true );

		if ( ! is_array( $manifest ) || SiteExporter::MANIFEST_TYPE !== ( $manifest['type'] ?? '' ) ) {
			return self::invalid_file_error();
		}

		$version = $manifest['version'] ?? null;

		if ( ! is_string( $version ) || '' === $version ) {
			return self::invalid_file_error();
		}

		if ( version_compare( $version, masteriyo_get_version(), '>' ) ) {
			return new \WP_Error(
				'masteriyo_site_export_newer_version',
				sprintf(
					/* translators: %s: Masteriyo version. */
					__( 'This file was exported from Masteriyo %s. Update Masteriyo on this site to that version or later, then import it again.', 'learning-management-system' ),
					$version
				),
				array( 'status' => 400 )
			);
		}

		return null;
	}

	/**
	 * Summarize the per-type results.
	 *
	 * @param non-empty-array<SiteExportType, SiteImportResult> $results Result per imported type.
	 *
	 * @return \WP_REST_Response
	 */
	private static function build_response( array $results ): \WP_REST_Response {
		$statuses = array_column( $results, 'status' );

		if ( array_intersect( array( 'failed', 'partial' ), $statuses ) ) {
			$message = __( 'Some data could not be imported.', 'learning-management-system' );
		} elseif ( in_array( 'progress', $statuses, true ) ) {
			$message = __( 'Import in progress. Please wait...', 'learning-management-system' );
		} else {
			$message = __( 'Import successful.', 'learning-management-system' );
		}

		return new \WP_REST_Response(
			array(
				'status'  => in_array( 'progress', $statuses, true ) ? 'progress' : 'completed',
				'message' => $message,
				'results' => $results,
			)
		);
	}

	/**
	 * Extract one entry and hand it to its import endpoint.
	 *
	 * @param ZipArchive     $zip   Open archive.
	 * @param SiteExportType $type  Export type.
	 * @param string         $entry Archive entry name.
	 *
	 * @return SiteImportResult
	 */
	private function import_entry( ZipArchive $zip, string $type, string $entry ): array {
		$directory = $this->get_extraction_directory();

		if ( ! $zip->extractTo( $directory, $entry ) ) {
			return array(
				'status'  => 'failed',
				'message' => self::invalid_file_error()->get_error_message(),
			);
		}

		$path     = $directory . $entry;
		$response = masteriyo_dispatch_import_export_request(
			self::ROUTES[ $type ],
			array(
				'file' => array(
					'tmp_name' => $path,
					'name'     => $entry,
				),
			)
		);

		// The course importer deletes its own file, from a background job for large imports.
		if ( 'courses' !== $type || $response->is_error() ) {
			wp_delete_file( $path );
		}

		if ( $response->is_error() ) {
			return array(
				'status'  => 'failed',
				'message' => $response->as_error()->get_error_message(),
			);
		}

		$data   = $response->get_data();
		$status = $data['status'] ?? '';

		return array(
			'status'  => in_array( $status, array( 'progress', 'partial' ), true ) ? $status : 'completed',
			'message' => (string) ( $data['message'] ?? '' ),
		);
	}

	/**
	 * Report on the courses part of an import running in the background.
	 *
	 * The course importer deletes its file only after a complete import, so a file left behind means it failed.
	 *
	 * @return array{status: 'progress'|'completed'|'failed', message: string}
	 */
	public function get_progress_status(): array {
		if ( CoursesImportJob::is_task_in_progress() ) {
			return array(
				'status'  => 'progress',
				'message' => __( 'Import in progress. Please wait...', 'learning-management-system' ),
			);
		}

		if ( file_exists( self::get_extraction_path() . SiteExporter::ENTRIES['courses'] ) ) {
			return array(
				'status'  => 'failed',
				'message' => __( 'Importing courses failed. See Masteriyo → Tools → Logs for details.', 'learning-management-system' ),
			);
		}

		return array(
			'status'  => 'completed',
			'message' => __( 'Import successful.', 'learning-management-system' ),
		);
	}

	/**
	 * Return this user's extraction folder, creating it under a web-protected parent.
	 *
	 * @return string Path with a trailing slash.
	 */
	private function get_extraction_directory(): string {
		$file_handler = new FileHandler();

		$file_handler->protect_directory( self::IMPORT_DIRECTORY );

		$directory = self::get_extraction_path();

		wp_mkdir_p( $directory );

		return $directory;
	}

	/**
	 * Path of this user's extraction folder.
	 *
	 * One folder per user keeps the entry names fixed without letting two users collide.
	 *
	 * @return string Path with a trailing slash.
	 */
	private static function get_extraction_path(): string {
		return trailingslashit( ( new FileHandler() )->get_base_dir() . self::IMPORT_DIRECTORY ) . wp_hash( get_current_user_id() . '|site-import' ) . '/';
	}

	/**
	 * Error for an upload that is not a full site export.
	 *
	 * @return \WP_Error
	 */
	private static function invalid_file_error(): \WP_Error {
		return new \WP_Error(
			'masteriyo_invalid_site_export',
			__( 'Invalid import file structure.', 'learning-management-system' ),
			array( 'status' => 400 )
		);
	}
}
