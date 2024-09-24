<?php

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\CourseProgressQuery;

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

if ( ! function_exists( 'masteriyo_get_user_enrollment_statuses' ) ) {
	/**
	 * Retrieves the current enrollment status for a given user and course item type.
	 *
	 * @since 1.12.0
	 *
	 * @param WP_User $user The user object whose enrollment status is to be retrieved.
	 * @param int $course_id The ID of the course item for which to retrieve the enrollment status.
	 *
	 * @return string|null The current enrollment status of the user, or null if not found.
	 */
	function masteriyo_get_user_enrollment_statuses( $user, $course_id ) {
		global $wpdb;

		$enrollment_status_db = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				absint( $user ),
				absint( $course_id )
			)
		);

		return $enrollment_status_db ? $enrollment_status_db : null;
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
	function masteriyo_enroll_surecart_user( $user_id, $course_id, $status = '' ) {
		global $wpdb;

		if ( ! $wpdb || empty( $course_id ) || empty( $user_id ) || empty( $status ) ) {
			return;
		}

		$user = masteriyo_get_user( $user_id );

		if ( ! $user ) {

			return;
		}

		if ( masteriyo_is_user_enrolled_in_course( $course_id, $user_id ) ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return;
		}

		$status_db = masteriyo_get_user_enrollment_statuses( $user_id, $course_id );

		if ( $status === $status_db ) {
			return;
		}

		$table_name = $wpdb->prefix . 'masteriyo_user_items';

		$user_items_data = array(
			'user_id'       => $user_id,
			'item_id'       => $course_id,
			'item_type'     => 'user_course',
			'status'        => UserCourseStatus::ACTIVE,
			'date_start'    => current_time( 'Y-m-d H:i:s' ),
			'date_modified' => current_time( 'Y-m-d H:i:s' ),
		);

		$wpdb->insert(
			$table_name,
			$user_items_data,
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		$masteriyo_user_activities_table_name = $wpdb->prefix . 'masteriyo_user_activities';
		$masteriyo_user_activities_data       = array(
			'user_id'         => $user_id,
			'item_id'         => $course_id,
			'activity_status' => CourseProgressStatus::STARTED,
			'activity_type'   => 'course_progress',
			'created_at'      => current_time( 'Y-m-d H:i:s' ),
			'modified_at'     => current_time( 'Y-m-d H:i:s' ),
		);

		$format = array( '%d', '%d', '%s', '%s', '%s', '%s' );
		$wpdb->insert( $masteriyo_user_activities_table_name, $masteriyo_user_activities_data, $format );

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

			$user = masteriyo_get_user( $user_id );

			if ( ! $user ) {
				return;
			}

			if ( ! masteriyo_is_user_enrolled_in_course( $course_id, $user_id ) ) {
				return;
			}

			$table_name = $wpdb->prefix . 'masteriyo_user_items';
			$wpdb->delete(
				$table_name,
				array(
					'user_id'   => $user_id,
					'item_id'   => $course_id,
					'item_type' => 'user_course',
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);

			$masteriyo_user_activities_table_name = $wpdb->prefix . 'masteriyo_user_activities';
			$wpdb->delete(
				$masteriyo_user_activities_table_name,
				array(
					'user_id'       => $user_id,
					'item_id'       => $course_id,
					'activity_type' => 'course_progress',
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);
		}
	}
}
