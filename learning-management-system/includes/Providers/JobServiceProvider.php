<?php
/**
 * Job service provider.
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use ActionScheduler;
use Masteriyo\Models\Setting;
use Masteriyo\Models\UserCourse;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Jobs\CheckCourseEndDateJob;
use Masteriyo\Jobs\CoursesExportJob;
use Masteriyo\Jobs\CoursesImportJob;
use Masteriyo\Jobs\CreateCourseContentJob;
use Masteriyo\Jobs\CreateLessonsContentJob;
use Masteriyo\Jobs\CreateQuizzesForSectionsJob;
use Masteriyo\Jobs\WebhookDeliveryJob;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Roles;

/**
 * Service provider for job-related services.
 *
 * @since 1.6.0
 */
class JobServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.0
	 */
	public function register() {
		// Register any services or dependencies here.
	}

	/**
	 * Bootstraps the application by scheduling a recurring action and registering the job.
	 *
	 * This method is called after all service providers are registered.
	 *
	 * @since 1.6.0
	 */
	public function boot() {
		$this->register_send_course_completion_reminder_email_job();
		$this->register_webhook_delivery_job();

		// Course creation using AI.
		$this->register_create_course_content_job();
		$this->register_create_lessons_content_job();
		$this->register_create_quizzes_for_sections_job();

		// Check the course end date job.
		$this->register_check_course_end_date_job();

		// Register courses export/import job.
		$this->register_courses_export_job();
		$this->register_courses_import_job();

		add_action( 'init', array( $this, 'unregister_multiple_course_completion_reminder_email_jobs' ) );
	}


	/**
	 * Register webhook delivery job.
	 *
	 * @since 1.6.9
	 */
	public function register_webhook_delivery_job() {
		( new WebhookDeliveryJob() )->init();
	}

	/**
	 * Register create_course_content_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_course_content_job() {
		( new CreateCourseContentJob() )->register();
	}

	/**
	 * Register create_lessons_content_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_lessons_content_job() {
		( new CreateLessonsContentJob() )->register();
	}

	/**
	 * Register create_quizzes_for_sections_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_quizzes_for_sections_job() {
		( new CreateQuizzesForSectionsJob() )->register();
	}

	/**
	 * Register check_course_end_date_job.
	 *
	 * @since 1.7.0
	 */
	public function register_check_course_end_date_job() {
		( new CheckCourseEndDateJob() )->register();
	}

	/**
	 * Register  courses_export_job.
	 *
	 * @since 1.14.0
	 */
	public function register_courses_export_job() {
		( new CoursesExportJob() )->register();
	}

	/**
	 * Register courses_import_job.
	 *
	 * @since 1.14.0
	 */
	public function register_courses_import_job() {
		( new CoursesImportJob() )->register();
	}

	/**
	 * Unregister multiple course completion reminder email jobs.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function unregister_multiple_course_completion_reminder_email_jobs() {
		$flag_key = '_masteriyo_ran_multiple_course_completion_reminder_jobs_check';

		if ( get_option( $flag_key ) ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'masteriyo_user_activities';

		$course_progresses = null;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			$course_progresses = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}masteriyo_user_activities
			WHERE activity_type='course_progress' AND activity_status IN('started', 'progress')"
			);
		}

		if ( $course_progresses ) {
			$hook = 'masteriyo/job/send_course_completion_reminder_email';

			foreach ( $course_progresses as $course_progress ) {
				$args       = array(
					'user_id'   => absint( $course_progress->user_id ),
					'course_id' => absint( $course_progress->item_id ),
				);
				$action_ids = as_get_scheduled_actions(
					array(
						'hook'     => $hook,
						'args'     => $args,
						'status'   => 'pending',
						'per_page' => -1,
						'group'    => 'masteriyo',
					),
					'ids'
				);

				if ( ! $action_ids ) {
					return;
				}

				// Run only 1st schedule and remove multiple duplicate schedule.
				array_shift( $action_ids );

				foreach ( $action_ids as $id ) {
					ActionScheduler::store()->cancel_action( $id );
				}
			}
		}
		update_option( $flag_key, true );
	}

		/**
	 * Register recurring course completion reminder email job.
	 *
	 * This method is responsible for scheduling a recurring action that will execute the
	 * 'masteriyo/job/send_course_completion_reminder_email' hook at a 7-day interval.
	 *
	 * @since 2.0.0
	 */
	public function register_send_course_completion_reminder_email_job() {
		$hook = 'masteriyo/job/send_course_completion_reminder_email';

		add_action(
			'masteriyo_new_setting',
			function( Setting $setting ) use ( $hook ) {
				global $wpdb;

				$table_name = $wpdb->prefix . 'masteriyo_user_activities';

				$course_progresses = null;

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
					$course_progresses = $wpdb->get_results(
						"SELECT * FROM {$wpdb->prefix}masteriyo_user_activities
							WHERE activity_type='course_progress' AND activity_status IN('started', 'progress')"
					);
				}

				if ( ! $course_progresses || ! $setting->get( 'emails.student.course_completion_reminder.enable' ) ) {
					as_unschedule_all_actions( $hook );
					return;
				}

				foreach ( $course_progresses as $course_progress ) {

					$user_id = absint( $course_progress->user_id );

					if ( ! get_user_by( 'ID', $user_id ) || ! in_array( Roles::STUDENT, get_userdata( $user_id )->roles, true ) ) {
						continue;
					}

					$args = array(
						'user_id'   => $user_id,
						'course_id' => absint( $course_progress->item_id ),
					);

					if ( as_has_scheduled_action( $hook, $args, 'masteriyo' ) ) {
						continue;
					}

					as_schedule_recurring_action( strtotime( '+7 days', strtotime( $course_progress->modified_at ) ), WEEK_IN_SECONDS, $hook, $args, 'masteriyo' );
				}
			}
		);

		add_action(
			'masteriyo_course_progress_status_changed',
			/**
			 * @param integer $id Course progress ID.
			 * @param string $old_status Old status.
			 * @param string $new_status New status.
			 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
			 */
			function( $id, $old_status, $new_status, $course_progress ) use ( $hook ) {
				if ( ! masteriyo_get_setting( 'emails.student.course_completion_reminder.enable' ) || ! is_user_logged_in() ) {
					return;
				}

				$user_id = $course_progress->get_user_id();

				if ( ! get_user_by( 'ID', $user_id ) || ! in_array( Roles::STUDENT, get_userdata( $user_id )->roles, true ) ) {
					return;
				}

				$args = array(
					'user_id'   => $user_id,
					'course_id' => $course_progress->get_course_id(),
				);

				if ( as_has_scheduled_action( $hook, $args, 'masteriyo' ) ) {
					as_unschedule_action( $hook, $args, 'masteriyo' );
				}

				if ( CourseProgressStatus::PROGRESS === $new_status ) {
					as_schedule_recurring_action( strtotime( '+7 days', $course_progress->get_modified_at()->getTimestamp() ), WEEK_IN_SECONDS, $hook, $args, 'masteriyo' );
				}
			},
			10,
			4
		);

		add_action(
			'masteriyo_new_user_course',
			function( $id, UserCourse $user_course ) use ( $hook ) {
				if ( ! masteriyo_get_setting( 'emails.student.course_completion_reminder.enable' ) || ! is_user_logged_in() ) {
					return;
				}

				$user_id = $user_course->get_user_id();

				if ( ! get_user_by( 'ID', $user_id ) || ! in_array( Roles::STUDENT, get_userdata( $user_id )->roles, true ) ) {
					return;
				}

				as_schedule_recurring_action(
					strtotime( '+7 days', time() ),
					WEEK_IN_SECONDS,
					$hook,
					array(
						'user_id'   => $user_id,
						'course_id' => $user_course->get_course_id(),
					),
					'masteriyo'
				);
			},
			10,
			2
		);
	}
}
