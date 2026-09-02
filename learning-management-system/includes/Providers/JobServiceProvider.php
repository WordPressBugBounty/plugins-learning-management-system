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
use Masteriyo\Jobs\WebhookDeliveryJob;
use Masteriyo\Jobs\SendTrackingInfoJob;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Jobs\CheckCourseEndDateJob;
use Masteriyo\Jobs\CheckEnrollmentExpirationJob;
use Masteriyo\Jobs\CreateCourseContentJob;
use Masteriyo\Jobs\CreateLessonsContentJob;
use Masteriyo\Jobs\CreateQuizzesForSectionsJob;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Jobs\CoursesExportJob;
use Masteriyo\Jobs\CoursesImportJob;
use Masteriyo\Jobs\SampleContentSeedJob;
use Masteriyo\Jobs\SendAddonsTrackingInfoJob;
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
	 * Check if the service provider provides a specific service.
	 *
	 * @since 1.6.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(),
			true
		);
	}

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.0
	 */
	public function register(): void {
		// Register any services or dependencies here.
	}

	/**
	 * Bootstraps the application by scheduling a recurring action and registering the job.
	 *
	 * This method is called after all service providers are registered.
	 *
	 * @since 1.6.0
	 */
	public function boot(): void {
		$this->register_send_course_completion_reminder_email_job();
		$this->register_webhook_delivery_job();

		// Course creation using AI.
		$this->register_create_course_content_job();
		$this->register_create_lessons_content_job();
		$this->register_create_quizzes_for_sections_job();

		// Check the course end date job.
		$this->register_check_course_end_date_job();

		$this->register_check_enrollment_expiration_job();

		// Register courses export/import job.
		$this->register_courses_export_job();
		$this->register_courses_import_job();

		// Register sample content seed job.
		$this->register_sample_content_seed_job();

		add_action( 'init', array( $this, 'unregister_multiple_course_completion_reminder_email_jobs' ) );
	}

	/**
	 * Unregister multiple course completion reminder email jobs.
	 *
	 * @since 2.7.1
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
					continue;
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
	 * @since 2.6.10
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
	 * @since 2.15.0
	 */
	public function register_courses_export_job() {
		( new CoursesExportJob() )->register();
	}

	/**
	 * Register courses_import_job.
	 *
	 * @since 2.15.0
	 */
	public function register_courses_import_job() {
		( new CoursesImportJob() )->register();
	}

	/**
	 * Register sample_content_seed_job.
	 */
	public function register_sample_content_seed_job() {
		( new SampleContentSeedJob() )->register();
	}

	/**
	 * Register check_enrollment_expiration_job.
	 *
	 * Registers the enrollment expiration job which uses a hybrid approach:
	 * 1. Per-enrollment scheduled actions for precise expiration timing.
	 * 2. Daily batch job as a safety net for any missed expirations.
	 */
	public function register_check_enrollment_expiration_job() {
		$job = new CheckEnrollmentExpirationJob();

		$job->register();
		$job->init_enrollment_hooks();

		add_action( 'init', array( $this, 'maybe_schedule_enrollment_expiration_job' ) );
	}

	/**
	 * Reconcile the recurring batch job to exactly one chain (runs on `init`).
	 *
	 * Counting (rather than the old `as_next_scheduled_action() === false` guard)
	 * is race-safe and self-heals duplicate chains; `$unique = true` is a backstop.
	 */
	public function maybe_schedule_enrollment_expiration_job() {
		$hook = CheckEnrollmentExpirationJob::NAME;

		$pending = as_get_scheduled_actions(
			array(
				'hook'     => $hook,
				'group'    => 'masteriyo',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 2,
			),
			'ids'
		);

		// Exactly one recurring chain — nothing to do.
		if ( 1 === count( $pending ) ) {
			return;
		}

		// Zero or more than one pending — reset to a single chain.
		masteriyo_get_logger()->info(
			sprintf( 'Reconciling enrollment-expiration batch job: found %d pending chain(s), resetting to one.', count( $pending ) ),
			array( 'source' => 'enrollment-expiration' )
		);
		as_unschedule_all_actions( $hook, array(), 'masteriyo' );
		as_schedule_recurring_action( time(), DAY_IN_SECONDS, $hook, array(), 'masteriyo', true );
	}
}
