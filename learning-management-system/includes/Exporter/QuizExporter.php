<?php
/**
 * Class for exporting quizzes.
 *
 * @since 1.6.15
 *
 * @package Masteriyo\Exporter
 */

namespace Masteriyo\Exporter;

defined( 'ABSPATH' ) || exit;

use Masteriyo\AdminFileDownloadHandler;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

/**
 * Class QuizExporter
 * Responsible for exporting quiz data.
 *
 * @since 1.6.15
 */
class QuizExporter {

	/**
	 * The ID used to generate download URL for exported quizzes file.
	 *
	 * @since 1.16.0
	 */
	const FILE_PATH_ID = 'export_quizzes';

	/**
	 * Main function to initiate the export process.
	 *
	 * @since 1.6.15
	 *
	 * @param int|null $quiz_id The ID of the quiz to export.
	 *
	 * @return array|null Information about the exported file or null if failed.
	 */
	public function export( $quiz_id = null ) {
		wp_raise_memory_limit( 'admin' );

		if ( ! $this->remove_old_export_file() ) {
			return null;
		}

		$quiz_args      = array( 'post_type' => PostType::QUIZ );
		$questions_args = array( 'post_type' => PostType::QUESTION );

		if ( ! is_null( $quiz_id ) ) {
			$quiz_args['include']          = array( $quiz_id );
			$questions_args['post_parent'] = $quiz_id;
		}

		$quizzes   = $this->get_posts( $quiz_args );
		$questions = $this->get_posts( $questions_args );

		if ( empty( $quizzes ) && empty( $questions ) ) {
			return null;
		}

		$data = array(
			'manifest'  => $this->get_export_meta_data(),
			'quizzes'   => $quizzes,
			'questions' => $questions,
		);

		return $this->write_data_to_json( $data );
	}

	/**
	 * Fetches posts and their metadata based on given post types.
	 *
	 * @since 1.6.15
	 *
	 * @param array $post_types The post types to fetch.
	 *
	 * @return array The fetched posts along with their metadata.
	 */
	protected function get_posts( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'posts_per_page' => -1,
				'post_status'    => PostStatus::ANY,
				'author'         => current_user_can( 'manage_masteriyo_settings' ) ? null : get_current_user_id(),
			)
		);

		$posts = get_posts( $args );

		$posts_with_meta = array();

		foreach ( $posts as $post ) {
			$post_array = (array) $post;
			$post_meta  = get_post_meta( $post->ID );

			if ( PostType::QUIZ === $post->post_type ) {
				$post_array['bank_data'] = masteriyo_get_questions_bank_data_by_quiz_id( $post->ID );
			}

			$post_array['meta'] = $post_meta;
			$posts_with_meta[]  = $post_array;
		}

		return $posts_with_meta;
	}

	/**
	 * Writes the provided data to a JSON file.
	 *
	 * @since 1.6.15
	 *
	 * @param array $data The data to be written.
	 *
	 * @return array|null Information about the exported file or null if failed.
	 */
	protected function write_data_to_json( $data ) {
		list( $filesystem, $export_folder ) = masteriyo_get_filesystem_and_folder();

		if ( ! $filesystem ) {
			return null;
		}

		$export_file_info = $this->create_export_file( $filesystem, $export_folder );

		if ( ! $export_file_info ) {
			return null;
		}

		$filepath = $export_file_info['filepath'];

		$content = wp_json_encode( $data );

		$filesystem->put_contents( $filepath, $content, FILE_APPEND );

		$filesystem->chmod( $filepath, 0644 );

		return $export_file_info;
	}

	/**
	 * Creates a new JSON file for the export.
	 *
	 * @since 1.6.15
	 *
	 * @param object $filesystem The WordPress filesystem object.
	 * @param string $export_folder The folder to place the export file.
	 *
	 * @return array|null Information about the exported file or null if failed.
	 */
	protected function create_export_file( $filesystem, $export_folder ) {
		$filename = sprintf( 'masteriyo-export-quizzes-%s-%s.json', get_current_user_id(), gmdate( 'Y-m-d-H-i-s' ) );
		$filepath = trailingslashit( $export_folder ) . $filename;

		if ( ! $filesystem->touch( $filepath ) ) {
			return null;
		}

		$download_url = AdminFileDownloadHandler::get_download_url( self::FILE_PATH_ID, $filename );

		return array(
			'filepath'     => $filepath,
			'filename'     => $filename,
			'download_url' => $download_url,
		);
	}

	/**
	 * Return the folder name where exported courses files are stored.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public static function get_file_path() {
		$upload_dir = wp_upload_dir();
		$export_dir = DIRECTORY_SEPARATOR . MASTERIYO_UPLOAD_DIR;

		return $upload_dir['basedir'] . $export_dir;
	}

	/**
	 * Removes old export files if any.
	 *
	 * @since 1.6.15
	 *
	 * @return bool True if successfully removed, otherwise false.
	 */
	protected function remove_old_export_file() {
		list( $filesystem, $export_folder ) = masteriyo_get_filesystem_and_folder();

		if ( ! $filesystem ) {
			return false;
		}

		$export_files = $filesystem->dirlist( $export_folder );
		$prefix       = sprintf( 'masteriyo-export-quizzes-%s-', get_current_user_id() );

		foreach ( $export_files as $file ) {
			if ( strpos( $file['name'], $prefix ) === 0 ) {
				$filesystem->delete( trailingslashit( $export_folder ) . $file['name'] );
			}
		}

		return true;
	}

	/**
	 * Return export meta data.
	 *
	 * @since 1.6.15
	 *
	 * @return array Meta data for the export.
	 */
	protected function get_export_meta_data(): array {
		return array(
			'version'    => masteriyo_get_version(),
			'created_at' => gmdate( 'D, d M Y H:i:s +0000' ),
			'base_url'   => home_url(),
		);
	}
}
