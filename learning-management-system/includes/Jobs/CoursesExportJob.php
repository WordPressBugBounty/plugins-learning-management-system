<?php

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;


use ActionScheduler_Store;
use Masteriyo\Exporter\CourseExporter;

/**
 * Class CoursesExportJob
 *
 * Handles the export of courses in an optimized and efficient manner.
 *
 * @since 1.14.0
 * @package Masteriyo\Jobs
 */
class CoursesExportJob {
	/**
	 * Name of the action.
	 *
	 * @since 1.14.0
	 */
	const NAME = 'masteriyo/job/courses_export';

	/**
	 * Name of the action to append post data.
	 *
	 * @since 1.14.0
	 */
	const NAME_APPEND_POST_DATA = 'masteriyo/job/courses_export/append_post_data';

	/**
	 * Group name of the action.
	 *
	 * @since 1.14.0
	 */
	const GROUP_NAME = 'masteriyo-courses-export';

	/**
	 * Chunk size.
	 *
	 * @since 1.14.0
	 */
	const CHUNK_SIZE = 50;

	/**
	 * Remaining post types.
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	private $remaining_post_types = array();

	/**
	 * Register the action hook handler.
	 *
	 * @since 1.14.0
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle' ) );
		add_action( self::NAME_APPEND_POST_DATA, array( $this, 'handle_append_post_data' ) );
	}

	/**
	 * Handle the export action.
	 *
	 * @since 1.14.0
	 *
	 * @param int $current_user_id The current user ID.
	 */
	public function handle( $current_user_id ) {
		try {
			$args = json_decode( get_option( 'masteriyo_exporting_courses_args_' . $current_user_id, null ), true );

			if ( is_array( $args ) ) {
				list($course_ids, $current_post_type, $remaining_post_types, $file_path) = $args;

				$this->remaining_post_types = $remaining_post_types;

				if ( ! empty( $remaining_post_types ) ) {
					$next_post_type = array_shift( $remaining_post_types );
					$args           = array( $course_ids, $next_post_type, $remaining_post_types, $file_path );
					update_option( 'masteriyo_exporting_courses_args_' . $current_user_id, wp_json_encode( $args ) );
				}

				$post_type_ids = CourseExporter::get_post_type_data( $course_ids, $current_post_type );
				$item_count    = count( $post_type_ids );

				if ( $item_count > self::CHUNK_SIZE ) {
					$chunks     = array_chunk( $post_type_ids, self::CHUNK_SIZE );
					$all_chunks = $chunks;

					$chunk = array_shift( $chunks );
					$args  = array( $chunk, $current_post_type, $file_path, $chunks, $all_chunks, $this->remaining_post_types );

					update_option( 'masteriyo_exporting_post_type_append_args_' . $current_user_id, wp_json_encode( $args ) );

					as_enqueue_async_action( self::NAME_APPEND_POST_DATA, array( $current_user_id ), self::GROUP_NAME );
				} else {
					$this->export_post_type( $current_post_type, $course_ids, $file_path, $this->remaining_post_types );

					if ( ! empty( $this->remaining_post_types ) ) {
						as_enqueue_async_action( self::NAME, array( $current_user_id ), self::GROUP_NAME );
					} else {
						$this->finalize_export( $file_path );
						$this->clean_up( $current_user_id );
					}
				}
			} else {
				masteriyo_get_logger()->error( __( 'Invalid export arguments.', 'learning-management-system' ), array( 'source' => 'courses-export' ) );
			}
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'courses-export' ) );
		}
	}

	/**
	 * Job handler for appending posts data.
	 *
	 * @since 1.14.0
	 */
	public function handle_append_post_data( $current_user_id ) {
		$args = json_decode( get_option( 'masteriyo_exporting_post_type_append_args_' . $current_user_id, null ), true );

		if ( is_array( $args ) ) {
			list( $post_type_ids, $post_type, $file_path, $remaining_chunks, $all_chunks, $remaining_post_types ) = $args;

			$is_first_chunk = count( $all_chunks ) === count( $remaining_chunks ) + 1;
			$is_last_chunk  = empty( $remaining_chunks );

			if ( $is_first_chunk ) {
				$label = CourseExporter::get_post_type_label( $post_type );
				if ( $label ) {
					self::start_post_type_section( $file_path, $label );
				}
			}

			if ( ! $is_first_chunk ) {
				CourseExporter::append( $file_path, ',' );
			}

			self::append_posts_data( $post_type_ids, 'chunk_lesson', $file_path );

			if ( ! empty( $remaining_chunks ) ) {
				$next_chunk = array_shift( $remaining_chunks );
				$args       = array( $next_chunk, $post_type, $file_path, $remaining_chunks, $all_chunks, $remaining_post_types );

				update_option( 'masteriyo_exporting_post_type_append_args_' . $current_user_id, wp_json_encode( $args ) );

				as_enqueue_async_action( self::NAME_APPEND_POST_DATA, array( $current_user_id ), self::GROUP_NAME );
			}

			if ( $is_last_chunk ) {
				delete_option( 'masteriyo_exporting_post_type_append_args_' . $current_user_id );
				$this->end_post_type_section( $file_path, $post_type, $remaining_post_types );

				if ( ! empty( $remaining_post_types ) ) {
					as_enqueue_async_action( self::NAME, array( $current_user_id ), self::GROUP_NAME );
				} else {
					$this->finalize_export( $file_path );
					$this->clean_up( $current_user_id );
				}
			}
		} else {
			masteriyo_get_logger()->error( __( 'Invalid export arguments for appending post data.', 'learning-management-system' ), array( 'source' => 'courses-export' ) );
		}
	}

	/**
	 * Finalize the export process.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path for export.
	 */
	protected function finalize_export( $file_path ) {
		$this->remaining_post_types = array();
		CourseExporter::append( $file_path, '}' );
	}

	/**
	 * Clean up after the export process is complete.
	 *
	 * @since 1.14.0
	 *
	 * @param int $current_user_id The current user ID.
	 */
	protected function clean_up( $current_user_id ) {
		delete_option( 'masteriyo_exporting_courses_args_' . $current_user_id );
	}

	/**
	 * Export a specific post type.
	 *
	 * @since 1.14.0
	 *
	 * @param string $post_type        The post type.
	 * @param array  $course_ids       The course IDs.
	 * @param string $file_path        The file path for export.
	 * @param array  $remaining_post_types The remaining post types.
	 */
	protected function export_post_type( $post_type, $course_ids, $file_path, $remaining_post_types ) {
		$label = CourseExporter::get_post_type_label( $post_type );

		if ( $label ) {
			self::start_post_type_section( $file_path, $label );

			self::append_posts_data( $course_ids, $post_type, $file_path );

			$this->end_post_type_section( $file_path, $post_type, $remaining_post_types );
		}
	}

	/**
	 * Start a new section for a post type in the JSON file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path for export.
	 * @param string $label     The label for the post type.
	 */
	public static function start_post_type_section( $file_path, $label ) {
		CourseExporter::append( $file_path, '"' . esc_js( $label ) . '": [' );
	}

	/**
	 * Append posts data to the export file in chunks.
	 *
	 * @since 1.14.0
	 *
	 * @param array  $course_ids The course IDs.
	 * @param string $post_type  The post type.
	 * @param string $file_path  The file path for export.
	 * @param array  $remaining_post_types The remaining post types.
	 */
	public static function append_posts_data( $course_ids, $post_type, $file_path ) {
		$get_attachments = 'chunk_lesson' === $post_type ? true : $post_type;
		$post_type       = 'chunk_lesson' === $post_type ? '' : $post_type;

		$posts_data_generator = CourseExporter::get_posts_data( $course_ids, $post_type, $get_attachments );

		$is_first_post = true;

		foreach ( $posts_data_generator as $post ) {
			if ( empty( $post ) || is_wp_error( $post ) ) {
				continue;
			}

			if ( ! $is_first_post ) {
				CourseExporter::append( $file_path, ',' );
			}

			CourseExporter::append( $file_path, wp_json_encode( $post ) );

			$is_first_post = false;
		}
	}

	/**
	 * End the current section for a post type in the JSON file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path        The file path for export.
	 */
	protected function end_post_type_section( $file_path, $post_type, $remaining_post_types ) {
		$this->remaining_post_types = $remaining_post_types;
		$separator                  = count( $remaining_post_types ) > 0 ? '],' : ']';

		CourseExporter::append( $file_path, $separator );
	}

	/**
	 * Check if a task is currently in progress (enqueued or running).
	 *
	 * @since 1.14.0
	 *
	 * @return bool True if a task is in progress, false otherwise.
	 */
	public static function is_task_in_progress( $task_name ) {
		$tasks_in_progress = as_get_scheduled_actions(
			array(
				'group'  => self::GROUP_NAME,
				'hook'   => $task_name,
				'status' => array(
					ActionScheduler_Store::STATUS_PENDING,
					ActionScheduler_Store::STATUS_RUNNING,
				),
			)
		);
		return ! empty( $tasks_in_progress );
	}

	/**
	 * Check if a task has been completed (completed, failed or canceled).
	 *
	 * @since 1.14.0
	 *
	 * @param string $task_name The name of the task to check.
	 *
	 * @return bool True if the task has been completed, false otherwise.
	 */
	public static function is_task_completed( $task_name ) {
		$tasks_in_progress = as_get_scheduled_actions(
			array(
				'group'  => self::GROUP_NAME,
				'hook'   => $task_name,
				'status' => array(
					ActionScheduler_Store::STATUS_COMPLETE,
					ActionScheduler_Store::STATUS_FAILED,
					ActionScheduler_Store::STATUS_CANCELED,
				),
			)
		);
		return ! empty( $tasks_in_progress );
	}

	/**
	 * Check if the export task is currently in progress.
	 *
	 * @return bool
	 */
	public static function is_tasks_in_progress() {
		return CoursesExportJob::is_task_in_progress( CoursesExportJob::NAME ) || CoursesExportJob::is_task_in_progress( CoursesExportJob::NAME_APPEND_POST_DATA );
	}

	/**
	 * Check if the export task is completed or not.
	 *
	 * @return boolean
	 */
	public static function is_tasks_completed() {
		return CoursesExportJob::is_task_completed( CoursesExportJob::NAME ) && CoursesExportJob::is_task_completed( CoursesExportJob::NAME_APPEND_POST_DATA );
	}
}
