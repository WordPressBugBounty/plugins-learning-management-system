<?php

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\UserCourseQuery;

if ( ! function_exists( 'is_sure_cart_active' ) ) {
	/**
	 * Return if SureCart is active.
	 *
	 * @since 1.12.0 [free]
	 *
	 * @return boolean
	 */
	function is_sure_cart_active() {
		return in_array( 'surecart/surecart.php', get_option( 'active_plugins', array() ), true );
	}
}

if ( ! function_exists( 'masteriyo_check_user_course_activity' ) ) {
	/**
	 * Return if user course is active.
	 *
	 * @since 1.13.2 [free]
	 *
	 * @param int $course_id
	 *
	 * @return object $activity user course activity.
	 */
	function masteriyo_check_user_course_activity( $course_id, $user_id = '' ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$activity = current( $query->get_user_courses() );
		$status   = $activity ? $activity->get_status() : '';
		return (string) $status;
	}
}

if ( ! function_exists( 'masteriyo_enroll_surecart_user' ) ) {
	/**
	 * Updates the enrollment status for users based on their id.
	 *
	 * @since 1.12.0 [free]
	 *
	 * @param int $course_id Group ID. $name
	 * @param array $emails User email addresses.
	 */
	function masteriyo_enroll_surecart_user( $user_id, $course_id ) {
		global $wpdb;

		if ( ! $wpdb || empty( $course_id ) || empty( $user_id ) ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );

		if ( is_wp_error( $course ) ) {
			return;
		}

		$user = masteriyo_get_user( $user_id );

		if ( ! $user ) {
			return;
		}

		// A re-enrollment has to MUTATE the existing row, not build a blank model: `masteriyo(
		// 'user-course' )` returns a model with no ID, so `save()` dispatches to
		// `UserCourseRepository::create()`, which returns early via
		// `masteriyo_is_user_already_enrolled()` whenever any row exists for the pair —
		// status-insensitive. Creating here therefore silently does nothing at all.
		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$activity = current( $query->get_user_courses() );

		if ( empty( $activity ) ) {
			$user_course = masteriyo( 'user-course' );

			$user_course->set_course_id( $course_id );
			$user_course->set_user_id( $user_id );
			$user_course->set_status( UserCourseStatus::ACTIVE );
			$user_course->set_date_start( current_time( 'mysql', true ) );

			$user_course->save();

			// These rows carry no Masteriyo order, so without a stamp the Enrollments
			// screen's source filter would class this purchase as 'manual'.
			if ( $user_course instanceof \Masteriyo\Models\UserCourse ) {
				$user_course->update_meta_data( '_source', 'automatic' );
				$user_course->save_meta_data();
			}
		} elseif ( UserCourseStatus::ACTIVE === $activity->get_status() ) {
			return;
		} else {
			$activity->set_status( UserCourseStatus::ACTIVE );
			$activity->set_date_modified( current_time( 'mysql' ) );
			$activity->save();

			// Also fixes up pre-stamp rows the first time a renewal touches them.
			$activity->update_meta_data( '_source', 'automatic' );
			$activity->save_meta_data();
		}
	}
}


if ( ! function_exists( 'masteriyo_unenroll_surecart_user' ) ) {
	/**
	 * Deletes the enrollment status for users based on their id.
	 *
	 * @since 2.13.0
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 */
	function masteriyo_unenroll_surecart_user( $user_id, $course_id ) {
		global $wpdb;

		if ( ! $wpdb || empty( $course_id ) || empty( $user_id ) ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );

		if ( is_wp_error( $course ) ) {
			return;
		}

		$user = masteriyo_get_user( $user_id );

		if ( ! $user ) {
			return;
		}

		// Two defects lived here. `! 'active' === $activity` parses as `( ! 'active' ) ===
		// $activity`, i.e. `false === $activity` — `!` binds tighter than `===` — and the
		// helper casts its return to string, so the guard could never fire and every call
		// fell through. And revoking has to MUTATE the existing row for the same reason
		// `masteriyo_enroll_surecart_user()` does: a blank model saves through
		// `UserCourseRepository::create()`, which returns early when a row already exists.
		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$activity = current( $query->get_user_courses() );

		if ( empty( $activity ) ) {
			return;
		}

		if ( UserCourseStatus::ACTIVE !== $activity->get_status() ) {
			return;
		}

		$activity->set_status( UserCourseStatus::INACTIVE );
		$activity->set_date_modified( current_time( 'mysql' ) );
		$activity->save();
	}
}
