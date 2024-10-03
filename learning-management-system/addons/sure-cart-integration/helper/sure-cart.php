<?php

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\UserCourseQuery;

if ( ! function_exists( 'is_sure_cart_active' ) ) {
	/**
	 * Return if SureCart is active.
	 *
	 * @since 1.12.0
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
	 * @since 1.13.2
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
	 * @since 1.12.0
	 *
	 * @param int $course_id Group ID. $name
	 * @param array $emails User email addresses.
	 * @param string $status New status to apply.
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

			$result = $user_course->save();
		} elseif ( 'active' === $activity->get_status() ) {
			return;
		} elseif ( 'inactive' === $activity->get_status() ) {
			$activity->set_status( UserCourseStatus::ACTIVE );
			$activity->save();
		}
	}
}

if ( ! function_exists( 'masteriyo_unenroll_surecart_user' ) ) {
	/**
	 * Deletes the enrollment status for users based on their id.
	 *
	 * @since 1.12.0
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

		if ( 'inactive' === $activity->get_status() ) {
			return;
		}

		$activity->set_status( UserCourseStatus::INACTIVE );
		$activity->save();
	}
}
