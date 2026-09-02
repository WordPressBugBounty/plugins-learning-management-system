<?php
/**
 * Check Enrollment Expiration Job.
 *
 * This job handles enrollment expiration through two mechanisms:
 * 1. Per-enrollment scheduled actions for precise timing
 * 2. Daily batch job as a safety net for missed expirations
 *
 * @package Masteriyo\Jobs
 */

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;

use Masteriyo\PostType\PostType;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\CourseAccessMode;

/**
 * Class CheckEnrollmentExpirationJob
 *
 * This class handles the automatic revocation of expired enrollments.
 * It uses a combination of per-enrollment scheduled actions and a daily
 * batch job for reliability.
 *
 * @package Masteriyo\Jobs
 */
class CheckEnrollmentExpirationJob {

	/**
	 * The unique identifier for the daily batch check job.
	 *
	 * @var string
	 */
	const NAME = 'masteriyo/job/check_enrollment_expiration';

	/**
	 * The unique identifier for per-enrollment expiration check.
	 *
	 * @var string
	 */
	const SINGLE_ENROLLMENT_HOOK = 'masteriyo/job/expire_single_enrollment';

	/**
	 * The unique identifier for the chunked per-course (re)scheduling job.
	 *
	 * Dispatched when a course's expiration settings change so the potentially
	 * heavy cancel/reschedule work runs in the background instead of blocking
	 * the course-save request.
	 *
	 * @var string
	 */
	const PROCESS_COURSE_HOOK = 'masteriyo/job/process_course_enrollment_expiration';

	/**
	 * Number of enrollments processed per background chunk.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 200;

	/**
	 * Course meta key storing the last-applied expiration configuration.
	 *
	 * Used to skip the (re)scheduling job when a course is saved without an
	 * actual change to its expiration settings, since `masteriyo_update_course`
	 * fires on every save and the model's changes are already cleared by the
	 * time the hook runs.
	 *
	 * @var string
	 */
	const SCHEDULE_SIGNATURE_META = '_enrollment_expiration_schedule_signature';

	/**
	 * Register all action hook handlers for this job.
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle_batch_check' ) );
		add_action( self::SINGLE_ENROLLMENT_HOOK, array( $this, 'handle_single_enrollment_expiration' ) );
		add_action( self::PROCESS_COURSE_HOOK, array( $this, 'handle_process_course_expiration' ), 10, 2 );
	}

	/**
	 * Initialize hooks for scheduling per-enrollment expiration.
	 */
	public function init_enrollment_hooks() {
		add_action( 'masteriyo_new_user_course', array( $this, 'schedule_enrollment_expiration' ), 10, 2 );
		add_action( 'masteriyo_update_user_course', array( $this, 'maybe_reschedule_on_update' ), 10, 2 );
		add_action( 'masteriyo_new_course', array( $this, 'handle_course_expiration_settings_change' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'handle_course_expiration_settings_change' ), 10, 2 );
		add_filter( 'masteriyo_can_start_course', array( $this, 'deny_access_for_expired_enrollment' ), 10, 3 );
	}

	/**
	 * Whether a course expires access on a per-enrollment basis.
	 *
	 * Open / registration-only courses grant access regardless of enrollment,
	 * so they are excluded. Shared by all scheduling and access-gate paths.
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 *
	 * @return bool
	 */
	private function course_expires_per_enrollment( $course ) {
		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return false;
		}

		if ( in_array( $course->get_access_mode(), array( CourseAccessMode::OPEN, CourseAccessMode::NEED_REGISTRATION ), true ) ) {
			return false;
		}

		if ( ! $course->get_enrollment_expiration_enabled() ) {
			return false;
		}

		return absint( $course->get_enrollment_expiration_duration() ) >= 1;
	}

	/**
	 * Deny course access once an enrollment has expired.
	 *
	 * Flipping the enrollment to `inactive` is not enough on its own: for paid
	 * courses `masteriyo_can_start_course()` grants access off the completed
	 * order and never denies based on enrollment status, so an expired student
	 * would keep access until (and unless) the order changed. This gate closes
	 * that hole by denying access when the user's enrollment is inactive or has
	 * passed its expiration window, independent of whether the background job
	 * has already revoked it.
	 *
	 * @param bool                     $can_start_course Whether the user can start the course.
	 * @param \Masteriyo\Models\Course $course           Course object.
	 * @param \Masteriyo\Models\User   $user             User object.
	 *
	 * @return bool
	 */
	public function deny_access_for_expired_enrollment( $can_start_course, $course, $user ) {
		// Already denied, or nothing to evaluate — leave the decision untouched.
		if ( ! $can_start_course || ! $course || is_wp_error( $course ) || ! $user || is_wp_error( $user ) ) {
			return $can_start_course;
		}

		if ( ! $this->course_expires_per_enrollment( $course ) ) {
			return $can_start_course;
		}

		$expiration_days = $course->get_enrollment_expiration_duration();

		$cache_key   = 'masteriyo_enrollment_expiry_' . $course->get_id() . '_' . $user->get_id();
		$user_course = wp_cache_get( $cache_key, 'masteriyo' );

		if ( false === $user_course ) {
			$query = new UserCourseQuery(
				array(
					'course_id' => $course->get_id(),
					'user_id'   => $user->get_id(),
					'per_page'  => 1,
				)
			);

			$user_course = current( $query->get_user_courses() );
			wp_cache_set( $cache_key, $user_course ? $user_course : null, 'masteriyo', MINUTE_IN_SECONDS );
		}

		// No enrollment record — let other access logic decide.
		if ( ! $user_course ) {
			return $can_start_course;
		}

		// The job has already revoked it.
		if ( UserCourseStatus::ACTIVE !== $user_course->get_status() ) {
			$this->log( sprintf( 'Access denied for user #%1$d on course #%2$d — enrollment inactive (revoked).', $user->get_id(), $course->get_id() ), 'debug' );
			return false;
		}

		// Still active but past the window (job hasn't run yet) — deny anyway.
		$date_start = $user_course->get_date_start();

		if ( $date_start && ( $date_start->getTimestamp() + $expiration_days * DAY_IN_SECONDS ) <= time() ) {
			$this->log( sprintf( 'Access denied for user #%1$d on course #%2$d — past window, job not yet run.', $user->get_id(), $course->get_id() ), 'debug' );
			return false;
		}

		return $can_start_course;
	}

	/**
	 * Schedule expiration for a new enrollment.
	 *
	 * @param int                          $user_course_id User course ID.
	 * @param \Masteriyo\Models\UserCourse $user_course    User course object.
	 */
	public function schedule_enrollment_expiration( $user_course_id, $user_course ) {
		if ( ! $user_course ) {
			return;
		}

		$course = masteriyo_get_course( $user_course->get_course_id() );

		if ( ! $this->course_expires_per_enrollment( $course ) ) {
			return;
		}

		$this->schedule_action_for_enrollment( $user_course_id, $user_course, $course->get_enrollment_expiration_duration() );
	}

	/**
	 * Maybe schedule expiration on enrollment update (e.g., reactivation).
	 *
	 * Only schedules if no action is already pending — avoids flooding Action
	 * Scheduler on every progress save since masteriyo_update_user_course fires
	 * on all enrollment updates, not just status changes.
	 *
	 * @param int                          $user_course_id User course ID.
	 * @param \Masteriyo\Models\UserCourse $user_course    User course object.
	 */
	public function maybe_reschedule_on_update( $user_course_id, $user_course ) {
		if ( ! $user_course ) {
			return;
		}

		if ( UserCourseStatus::ACTIVE !== $user_course->get_status() ) {
			$this->log( sprintf( 'Enrollment #%d no longer active — cancelling any scheduled expiry.', $user_course_id ), 'debug' );
			$this->cancel_scheduled_expiration( $user_course_id );
			return;
		}

		// Already scheduled — nothing to do. Avoids cancel+reschedule on every
		// lesson completion or quiz submission that triggers this hook.
		$args = array( 'user_course_id' => $user_course_id );
		if ( as_has_scheduled_action( self::SINGLE_ENROLLMENT_HOOK, $args, 'masteriyo' ) ) {
			return;
		}

		$course = masteriyo_get_course( $user_course->get_course_id() );

		if ( ! $this->course_expires_per_enrollment( $course ) ) {
			return;
		}

		$expiration_days = $course->get_enrollment_expiration_duration();

		// If the enrollment's expiration window has already passed (e.g. an admin
		// manually reactivated an expired enrollment), reset date_start to now so
		// the new expiration window begins from the reactivation date rather than
		// immediately re-revoking the enrollment.
		$date_start = $user_course->get_date_start();
		if ( $date_start && ( $date_start->getTimestamp() + $expiration_days * DAY_IN_SECONDS ) <= time() ) {
			$this->log( sprintf( 'Enrollment #%d reactivated past its window — resetting date_start to now.', $user_course_id ) );
			$user_course->set_date_start( current_time( 'mysql', true ) );
			$user_course->save();
		}

		$this->schedule_action_for_enrollment( $user_course_id, $user_course, $expiration_days );
	}

	/**
	 * Handle course expiration settings change.
	 *
	 * `masteriyo_update_course` fires on every course save, and the model's
	 * changes are already applied (cleared) by the time this hook runs, so we
	 * compare against a stored signature and bail when the expiration settings
	 * did not actually change. When they did, the potentially heavy cancel /
	 * reschedule work is offloaded to a background Action Scheduler job so it
	 * never blocks the course-save request.
	 *
	 * @param int                       $course_id Course ID.
	 * @param \Masteriyo\Models\Course  $course    Course object.
	 */
	public function handle_course_expiration_settings_change( $course_id, $course ) {
		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return;
		}

		$expiration_days = absint( $course->get_enrollment_expiration_duration() );
		// Signature includes access mode, so a mode change also (re)triggers it.
		$signature = $this->course_expires_per_enrollment( $course ) ? (string) $expiration_days : '0';

		$previous_signature = (string) get_post_meta( $course_id, self::SCHEDULE_SIGNATURE_META, true );

		// Settings unchanged since the last save — nothing to (re)schedule.
		if ( $previous_signature === $signature ) {
			return;
		}

		$this->log( sprintf( 'Course #%1$d expiration settings changed (signature %2$s -> %3$s) — dispatching reschedule job.', $course_id, '' === $previous_signature ? 'none' : $previous_signature, $signature ) );

		update_post_meta( $course_id, self::SCHEDULE_SIGNATURE_META, $signature );

		$was_enabled    = ( '' !== $previous_signature && '0' !== $previous_signature );
		$is_now_enabled = ( '0' !== $signature );

		// Expiration was off and is still off (e.g. first save of a course that
		// never used it) — there is nothing to cancel or schedule, so skip the
		// background job entirely. The signature is still persisted above so the
		// next save short-circuits at the equality check.
		if ( ! $was_enabled && ! $is_now_enabled ) {
			return;
		}

		$args = array(
			'course_id' => $course_id,
			'after_id'  => 0,
		);

		// Avoid stacking duplicate jobs if the course is saved repeatedly before
		// the first chunk runs.
		if ( as_has_scheduled_action( self::PROCESS_COURSE_HOOK, $args, 'masteriyo' ) ) {
			return;
		}

		as_enqueue_async_action( self::PROCESS_COURSE_HOOK, $args, 'masteriyo' );
	}

	/**
	 * Process one chunk of a course's enrollments when its expiration settings change.
	 *
	 * Re-reads the course's current settings at run time and either cancels or
	 * reschedules each enrollment's expiration action. Self-chains to the next
	 * chunk while a full batch is returned, keeping memory and per-request work
	 * bounded regardless of how many students are enrolled.
	 *
	 * Action Scheduler fires callbacks with positional args (it passes
	 * `array_values()` of the stored args), so this receives the values, not the
	 * associative array used when scheduling.
	 *
	 * Paginates by last-seen id (keyset) rather than OFFSET: rescheduling an
	 * already-expired enrollment revokes it synchronously, which removes it from
	 * the active set, so an OFFSET-based cursor would skip rows. A keyset cursor
	 * is immune to that shrinkage.
	 *
	 * @param int $course_id Course ID.
	 * @param int $after_id  Process enrollments with an id greater than this.
	 */
	public function handle_process_course_expiration( $course_id = 0, $after_id = 0 ) {
		$course_id = absint( $course_id );
		$after_id  = absint( $after_id );

		if ( ! $course_id ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );

		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return;
		}

		$enrollments = $this->get_active_enrollments_for_course( $course_id, self::BATCH_SIZE, $after_id );

		if ( empty( $enrollments ) ) {
			return;
		}

		$expiration_days = absint( $course->get_enrollment_expiration_duration() );
		// Cancel instead of reschedule when the course no longer expires per enrollment.
		$should_cancel = ! $this->course_expires_per_enrollment( $course );
		$last_id       = $after_id;

		$this->log( sprintf( 'Processing course #%1$d chunk (after_id %2$d): %3$d enrollments, mode=%4$s.', $course_id, $after_id, count( $enrollments ), $should_cancel ? 'cancel' : 'reschedule' ) );

		foreach ( $enrollments as $enrollment ) {
			// Cast to int: $wpdb returns strings, but actions are scheduled with an
			// int user_course_id and AS matches args by type (string would miss).
			$enrollment_id = absint( $enrollment->id );
			$last_id       = max( $last_id, $enrollment_id );

			$this->cancel_scheduled_expiration( $enrollment_id );

			if ( $should_cancel ) {
				continue;
			}

			$user_course = masteriyo_get_user_course( $enrollment_id );

			if ( ! $user_course ) {
				continue;
			}

			$this->schedule_action_for_enrollment( $enrollment_id, $user_course, $expiration_days );
		}

		// A full batch likely means there are more rows — process the next chunk.
		if ( count( $enrollments ) >= self::BATCH_SIZE ) {
			as_enqueue_async_action(
				self::PROCESS_COURSE_HOOK,
				array(
					'course_id' => $course_id,
					'after_id'  => $last_id,
				),
				'masteriyo'
			);
		}
	}

	/**
	 * Schedule a specific action for an enrollment.
	 *
	 * @param int                          $user_course_id  User course ID.
	 * @param \Masteriyo\Models\UserCourse $user_course     User course object.
	 * @param int                          $expiration_days Days until expiration.
	 */
	private function schedule_action_for_enrollment( $user_course_id, $user_course, $expiration_days ) {
		$enrollment_date = $user_course->get_date_start();

		if ( ! $enrollment_date ) {
			return;
		}

		$enrollment_timestamp = $enrollment_date->getTimestamp();
		$expiration_timestamp = $enrollment_timestamp + ( $expiration_days * DAY_IN_SECONDS );

		if ( $expiration_timestamp <= time() ) {
			$this->log( sprintf( 'Enrollment #%d already past window at schedule time — revoking synchronously.', $user_course_id ) );
			$this->handle_single_enrollment_expiration( $user_course_id );
			return;
		}

		$args = array( 'user_course_id' => $user_course_id );

		if ( as_has_scheduled_action( self::SINGLE_ENROLLMENT_HOOK, $args, 'masteriyo' ) ) {
			$this->log( sprintf( 'Enrollment #%d already has a pending expiry action — skipping schedule.', $user_course_id ), 'debug' );
			return;
		}

		as_schedule_single_action( $expiration_timestamp, self::SINGLE_ENROLLMENT_HOOK, $args, 'masteriyo' );
		$this->log( sprintf( 'Scheduled expiry for enrollment #%1$d at %2$s (%3$d days).', $user_course_id, gmdate( 'Y-m-d H:i:s', $expiration_timestamp ), $expiration_days ) );
	}

	/**
	 * Cancel a scheduled expiration for an enrollment.
	 *
	 * @param int $user_course_id User course ID.
	 */
	private function cancel_scheduled_expiration( $user_course_id ) {
		$args = array( 'user_course_id' => $user_course_id );

		if ( as_has_scheduled_action( self::SINGLE_ENROLLMENT_HOOK, $args, 'masteriyo' ) ) {
			as_unschedule_action( self::SINGLE_ENROLLMENT_HOOK, $args, 'masteriyo' );
		}
	}

	/**
	 * Handle a single enrollment expiration.
	 *
	 * Re-verifies the expiration duration at run time so that admin changes to
	 * the course duration after this action was scheduled are respected.
	 *
	 * Action Scheduler fires callbacks with positional args (it passes
	 * `array_values()` of the stored args), so the scheduled
	 * `array( 'user_course_id' => $id )` arrives here as the bare ID, not an
	 * associative array.
	 *
	 * @param int $user_course_id User course ID.
	 */
	public function handle_single_enrollment_expiration( $user_course_id = 0 ) {
		$user_course_id = absint( $user_course_id );

		if ( ! $user_course_id ) {
			return;
		}

		$user_course = masteriyo_get_user_course( $user_course_id );

		if ( ! $user_course ) {
			return;
		}

		if ( UserCourseStatus::ACTIVE !== $user_course->get_status() ) {
			return;
		}

		$course = masteriyo_get_course( $user_course->get_course_id() );

		// Re-verify eligibility at run time (settings may have changed since scheduling).
		if ( ! $this->course_expires_per_enrollment( $course ) ) {
			$this->log( sprintf( 'Enrollment #%d no longer eligible for expiry at run time — not revoking.', $user_course_id ), 'debug' );
			return;
		}

		// Re-verify against current duration — an admin may have extended it
		// after this action was originally scheduled.
		$expiration_days = $course->get_enrollment_expiration_duration();
		$date_start      = $user_course->get_date_start();

		if ( $date_start && ( $date_start->getTimestamp() + $expiration_days * DAY_IN_SECONDS ) > time() ) {
			$this->log( sprintf( 'Enrollment #%d still within window at run time (duration extended?) — not revoking.', $user_course_id ), 'debug' );
			return;
		}

		$this->log( sprintf( 'Expiry action firing for enrollment #%d — revoking.', $user_course_id ) );
		$this->revoke_enrollment( $user_course );
	}

	/**
	 * Handle the daily batch check (safety net).
	 *
	 * Dispatches one async background job per course so that a course with a
	 * large backlog of expired enrollments cannot block the daily callback.
	 */
	public function handle_batch_check() {
		$courses_with_expiration = $this->get_courses_with_enrollment_expiration();

		if ( empty( $courses_with_expiration ) ) {
			$this->log( 'Daily batch check ran — no courses with enrollment expiration enabled.', 'debug' );
			return;
		}

		$this->log( sprintf( 'Daily batch check — dispatching per-course jobs for %d course(s): %s.', count( $courses_with_expiration ), implode( ',', $courses_with_expiration ) ) );

		foreach ( $courses_with_expiration as $course_id ) {
			$args = array(
				'course_id' => $course_id,
				'after_id'  => 0,
			);

			if ( ! as_has_scheduled_action( self::PROCESS_COURSE_HOOK, $args, 'masteriyo' ) ) {
				as_enqueue_async_action( self::PROCESS_COURSE_HOOK, $args, 'masteriyo' );
			}
		}
	}

	/**
	 * Get all published courses that have enrollment expiration enabled.
	 *
	 * @return array Array of course IDs with enrollment expiration enabled.
	 */
	private function get_courses_with_enrollment_expiration() {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		// Exclude open / registration-only courses (expiration does not apply).
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_enabled ON p.ID = pm_enabled.post_id
				INNER JOIN {$wpdb->postmeta} pm_duration ON p.ID = pm_duration.post_id
				WHERE p.post_type = %s
				AND p.post_status = %s
				AND pm_enabled.meta_key = '_enrollment_expiration_enabled'
				AND pm_enabled.meta_value = '1'
				AND pm_duration.meta_key = '_enrollment_expiration_duration'
				AND pm_duration.meta_value > 0
				AND p.ID NOT IN (
					SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_access_mode'
					AND meta_value IN ( %s, %s )
				)",
				PostType::COURSE,
				PostStatus::PUBLISH,
				CourseAccessMode::OPEN,
				CourseAccessMode::NEED_REGISTRATION
			)
		);

		return array_map( 'absint', $course_ids );
	}

	/**
	 * Get a paginated set of active enrollments for a specific course.
	 *
	 * Uses keyset pagination (id greater than $after_id) so callers can process
	 * large courses in bounded chunks without loading every enrollment row into
	 * memory, and without skipping rows when the active set shrinks mid-run.
	 *
	 * @param int $course_id Course ID.
	 * @param int $limit     Maximum number of rows to return.
	 * @param int $after_id  Return only enrollments with an id greater than this.
	 *
	 * @return array Array of enrollment objects.
	 */
	private function get_active_enrollments_for_course( $course_id, $limit = 200, $after_id = 0 ) {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, item_id as course_id, date_start
				FROM {$wpdb->prefix}masteriyo_user_items
				WHERE item_id = %d
				AND item_type = 'user_course'
				AND status = %s
				AND id > %d
				ORDER BY id ASC
				LIMIT %d",
				$course_id,
				UserCourseStatus::ACTIVE,
				$after_id,
				$limit
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Write an enrollment-expiration trace line to the Masteriyo log.
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (info, debug, warning, error).
	 */
	private function log( $message, $level = 'info' ) {
		masteriyo_get_logger()->{$level}( $message, array( 'source' => 'enrollment-expiration' ) );
	}

	/**
	 * Revoke (set to inactive) an enrollment.
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course object.
	 */
	private function revoke_enrollment( $user_course ) {
		if ( ! $user_course || ! $user_course->get_id() ) {
			return;
		}

		wp_cache_delete( 'masteriyo_enrollment_expiry_' . $user_course->get_course_id() . '_' . $user_course->get_user_id(), 'masteriyo' );
		masteriyo_revoke_enrollment_due_to_expiration_by_user_course( $user_course->get_id() );
	}
}
