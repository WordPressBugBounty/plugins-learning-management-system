<?php

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;


use ActionScheduler_Store;
use Masteriyo\Importer\CourseImporter;

/**
 * Class CoursesImportJob
 *
 * Handles the import of courses.
 *
 * @since 1.14.0
 * @package Masteriyo\Jobs
 */
class CoursesImportJob {
	/**
	 * Name of the action.
	 *
	 * @since 1.14.0
	 */
	const NAME = 'masteriyo/job/courses_import';

	/**
	 * Group name of the action.
	 *
	 * @since 1.14.0
	 */
	const GROUP_NAME = 'masteriyo-courses-import';

	/**
	 * Register the action hook handler.
	 *
	 * @since 1.14.0
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle' ) );
	}

	/**
	 * Handle the export action.
	 *
	 * @since 1.14.0
	 */
	public function handle( $file_path ) {
		try {
			$importer = new CourseImporter();

			$importer->import( $file_path );
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'courses-import' ) );
		}
	}

	/**
	 * Check if a task is currently in progress (enqueued or running).
	 *
	 * @since 1.14.0
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
	 * @since 1.14.0
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
