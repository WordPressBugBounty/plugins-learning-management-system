<?php

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;


use ActionScheduler_Store;
use Masteriyo\Helper\Utils;
use Masteriyo\Importer\CourseImporter;

/**
 * Class CoursesImportJob
 *
 * Handles the import of courses.
 *
 * @since 2.15.0
 * @package Masteriyo\Jobs
 */
class CoursesImportJob {
	/**
	 * Name of the action.
	 *
	 * @since 2.15.0
	 */
	const NAME = 'masteriyo/job/courses_import';

	/**
	 * Group name of the action.
	 *
	 * @since 2.15.0
	 */
	const GROUP_NAME = 'masteriyo-courses-import';

	/**
	 * Register the action hook handler.
	 *
	 * @since 2.15.0
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Handle the import action.
	 *
	 * @since 2.15.0
	 *
	 * @param string $file_path Export file to import.
	 * @param int    $user_id   User who started the import; the posts are created in their name.
	 */
	public function handle( $file_path, $user_id = 0 ) {
		$previous_user = get_current_user_id();

		if ( $user_id ) {
			wp_set_current_user( $user_id );
		}

		try {
			$importer = new CourseImporter();

			$importer->import( $file_path );
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'courses-import' ) );
		} finally {
			// Other jobs in the same queue run must not inherit the importer's capabilities.
			wp_set_current_user( $previous_user );
		}
	}

	/**
	 * Check if a task is currently in progress (enqueued or running).
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if a task is in progress, false otherwise.
	 */
	public static function is_task_in_progress( $task_name = self::NAME ) {
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
	 * @since 2.15.0
	 *
	 * @param string $task_name The name of the task to check.
	 *
	 * @return bool True if the task has been completed, false otherwise.
	 */
	public static function is_task_completed( $task_name = self::NAME ) {
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
}
