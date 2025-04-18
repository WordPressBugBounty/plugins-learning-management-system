<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * User course functions.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package Masteriyo\Helper
 */

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Roles;

/**
 * Get user course.
 *
 * @since 1.0.0
 *
 * @param int $user_course_id User course ID.
 * @return Masteriyo\Models\UserCourse|null
 */
function masteriyo_get_user_course( $user_course_id ) {
	try {
		$user_course = masteriyo( 'user-course' );
		$user_course->set_id( $user_course_id );

		$user_course_repo = masteriyo( 'user-course.store' );
		$user_course_repo->read( $user_course );

		return $user_course;
	} catch ( \Exception $e ) {
		return null;
	}
}

/**
 * Retrieves all course IDs for a given user.
 *
 * @since 1.11.0
 *
 * @param int $user_id Optional. User ID. Defaults to 0.
 *
 * @return array Array of course IDs.
 */
function masteriyo_get_all_user_course_ids( $user_id ) {
	global $wpdb;

	$course_ids = array();

	if ( $wpdb ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT item_id FROM {$wpdb->prefix}masteriyo_user_items WHERE (status = %s OR status = %s) AND user_id = %d",
				array(
					UserCourseStatus::ACTIVE,
					UserCourseStatus::ENROLLED,
					intval( $user_id ),
				)
			)
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				$course_ids[] = $result->item_id;
			}
		}
	}

	return $course_ids;
}

/**
 * Get list of status for user course.
 *
 * @since 1.0.0
 * @deprecated 1.5.3
 *
 * @return array
 */
function masteriyo_get_user_course_statuses() {
	$statuses = array(
		'active' => array(
			'label' => _x( 'Active', 'User Course status', 'learning-management-system' ),
		),
	);

	/**
	 * Filters statuses for user course.
	 *
	 * @since 1.0.0
	 *
	 * @param array $statuses The statuses for user course.
	 */
	return apply_filters( 'masteriyo_user_course_statuses', $statuses );
}

/**
 * Count enrolled users by course or multiple courses.
 *
 * @since 1.0.0
 * @since 1.6.7 Argument $course supports array.
 *
 * @param int|int[] $course Course Id or Course IDS
 *
 * @return integer
 */
function masteriyo_count_enrolled_users( $course ) {
	global $wpdb;

	$count = 0;

	if ( is_array( $course ) ) {
		$course = array_filter( array_map( 'absint', $course ) );
	}

	if ( $wpdb && $course ) {
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE ( status = %s OR status = %s )",
			UserCourseStatus::ACTIVE,
			UserCourseStatus::ENROLLED
		);

		$exclude_users = array_map(
			'absint',
			(array) get_users(
				array(
					'role__in' => array( Roles::ADMIN, Roles::INSTRUCTOR, Roles::MANAGER ),
					'fields'   => 'ID',
				)
			)
		);

		if ( ! empty( $exclude_users ) ) {
			$placeholders = array_fill( 0, count( $exclude_users ), '%d' );
			$sql         .= $wpdb->prepare( ' AND user_id NOT IN (' . implode( ',', $placeholders ) . ')', $exclude_users ); //phpcs:ignore
		}

		if ( is_array( $course ) ) {
			$placeholders = array_fill( 0, count( $course ), '%d' );
			$sql         .= $wpdb->prepare( ' AND item_id IN (' . implode( ',', $placeholders ) . ')', $course ); //phpcs:ignore
		} else {
			$sql .= $wpdb->prepare( ' AND item_id = %d', $course );
		}

		$count = $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Filters enrolled users count for a course.
	 *
	 * @since 1.0.0
	 * @since 1.5.17 Removed third $query parameter.
	 *
	 * @param integer $count The enrolled users count for the given course.
	 * @param int|int[] $course Course ID or Course object.
	 */
	return apply_filters( 'masteriyo_count_enrolled_users', absint( $count ), $course );
}

/**
 * Get enrolled users IDs by course or multiple courses.
 *
 * @since 1.11.0
 *
 * @param int $course Course Id
 *
 * @return array
 */
function masteriyo_get_enrolled_users( $course ) {
	global $wpdb;

	$user_ids = array();

	if ( is_array( $course ) ) {
			$course = array_filter( array_map( 'absint', $course ) );
	}

	if ( $wpdb && $course ) {
			$sql = $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}masteriyo_user_items WHERE ( status = %s OR status = %s )",
				UserCourseStatus::ACTIVE,
				UserCourseStatus::ENROLLED
			);

			$exclude_users = array_map(
				'absint',
				(array) get_users(
					array(
						'role__in' => array( Roles::ADMIN, Roles::INSTRUCTOR, Roles::MANAGER ),
						'fields'   => 'ID',
					)
				)
			);

		if ( ! empty( $exclude_users ) ) {
				$placeholders = array_fill( 0, count( $exclude_users ), '%d' );
				$sql         .= $wpdb->prepare( ' AND user_id NOT IN (' . implode( ',', $placeholders ) . ')', $exclude_users ); //phpcs:ignore
		}

		if ( is_array( $course ) ) {
				$placeholders = array_fill( 0, count( $course ), '%d' );
				$sql         .= $wpdb->prepare( ' AND item_id IN (' . implode( ',', $placeholders ) . ')', $course ); //phpcs:ignore
		} else {
				$sql .= $wpdb->prepare( ' AND item_id = %d', $course );
		}

			$user_ids = $wpdb->get_col( $sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Filters enrolled user IDs for a course.
	 *
	 * @since 1.11.0
	 *
	 * @param array   $user_ids Array of user IDs enrolled in the given course.
	 * @param int|int[] $course   Course ID or Course object.
	 */
	return apply_filters( 'masteriyo_get_enrolled_users', array_map( 'absint', $user_ids ), $course );
}

/**
 * Get the number of active courses.
 *
 * @since 1.0.0
 *
 * @param Masteriyo\Models\User|int $user User.
 *
 * @return int
 */
function masteriyo_get_active_courses_count( $user ) {
	global $wpdb;

	$user_id = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : $user;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_activities
			WHERE user_id = %d AND activity_type = 'course_progress'
			AND ( activity_status = 'started' OR activity_status = 'progress' )  AND parent_id = 0",
			$user_id
		)
	);

	return $count;
}

/**
 * Get the number of user courses.
 *
 * @since 1.0.0
 * @since 1.6.7 Argument $course supports array.
 * @param int|int[] $course Course id or array of course ids.
 *
 * @return int
 */
function masteriyo_get_user_courses_count_by_course( $course ) {
	global $wpdb;

	$count = 0;

	if ( is_array( $course ) ) {
		$course = array_filter( array_map( 'absint', $course ) );
	}

	if ( $wpdb && $course ) {
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE item_type = 'user_course'";

		if ( is_array( $course ) ) {
			$placeholders = array_fill( 0, count( $course ), '%d' );
			$sql         .= $wpdb->prepare( 'AND item_id IN (' . implode( ',', $placeholders ) . ')', $course ); // phpcs:ignore
		} else {
			$sql .= $wpdb->prepare( 'AND item_id = %d', $course );
		}

		$count = $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Filters user courses count by course.
	 *
	 * @since 1.6.7
	 *
	 * @param integer $count The enrolled users count for the given course.
	 * @param int|int[] $course Course ID or Course object.
	 */
	return apply_filters( 'masteriyo_get_user_courses_count_by_course', absint( $count ), $course );
}

/**
 * Get user/enrolled course by user ID and course ID.
 *
 * @since 1.5.4
 *
 * @param int $user_id User ID.
 * @param int $course_id Course ID.
 * @return Masteriyo\Models\UserCourse
 */
function masteriyo_get_user_course_by_user_and_course( $user_id, $course_id ) {
	$query = new UserCourseQuery(
		array(
			'course_id' => $course_id,
			'user_id'   => $user_id,
		)
	);

	return current( $query->get_user_courses() );
}

if ( ! function_exists( 'masteriyo_count_all_enrolled_users' ) ) {
	/**
	 * Count total enrolled users from all courses.
	 *
	 * @since 1.6.16
	 *
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 *
	 * @return integer
	 */
	function masteriyo_count_all_enrolled_users( $user ) {
		$total_count = 0;

		$user = masteriyo_get_user( $user );

		if ( is_null( $user ) || is_wp_error( $user ) ) {
			return $total_count;
		}

		// Get all courses.
		$all_courses = get_posts(
			array(
				'post_type'      => PostType::COURSE,
				'post_status'    => PostStatus::PUBLISH,
				'author'         => $user->get_id(),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		// Iterate through each course and count enrolled users.
		foreach ( $all_courses as $course_id ) {
				$total_count += masteriyo_count_enrolled_users( $course_id );
		}

		return $total_count;
	}
}

if ( ! function_exists( 'masteriyo_count_user_courses' ) ) {
	/**
	 * Get the count of courses created by a user.
	 *
	 * @since 1.6.16
	 *
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 *
	 * @return int The count of courses created by the user.
	 */
	function masteriyo_count_user_courses( $user ) {
		$user = masteriyo_get_user( $user );

		if ( is_null( $user ) || is_wp_error( $user ) ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'      => PostType::COURSE,
				'post_status'    => PostStatus::PUBLISH,
				'author'         => $user->get_id(),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		return $query->found_posts;
	}
}

if ( ! function_exists( 'masteriyo_is_user_already_enrolled' ) ) {
	/**
	 * Checks if a user is enrolled in a specific course, optionally filtering by enrollment status.
	 *
	 * @since 1.8.3
	 *
	 * @param int         $user_id   The ID of the user.
	 * @param int         $course_id The ID of the course.
	 * @param string|null $status    Optional. The enrollment status to check ('active', 'inactive'.). Default null.
	 *
	 * @return bool True if the user is enrolled with the specified status (if provided), false otherwise.
	 */
	function masteriyo_is_user_already_enrolled( $user_id, $course_id, $status = null ) {
		global $wpdb;

		if ( ! $wpdb || ! $user_id || ! $course_id ) {
			return false;
		}

		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'";
		$args  = array( $user_id, $course_id );

		if ( ! is_null( $status ) ) {
			$query .= ' AND status = %s';
			$args[] = $status;
		}

		$query .= ' LIMIT 1';

		$count = $wpdb->get_var( $wpdb->prepare( $query, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $count > 0;
	}
}

if ( ! function_exists( 'masteriyo_is_request_from_account_dashboard' ) ) {
	/**
	 * Determines if the request is from the account dashboard.
	 *
	 * @since 1.14.2
	 *
	 * @param WP_REST_Request|null $request Optional. The request object. Defaults to current HTTP request.
	 *
	 * @return bool True if the request is from the account dashboard, false otherwise.
	 */
	function masteriyo_is_request_from_account_dashboard( $request = null ) {
		$request = $request ?? masteriyo_current_http_request();

		if ( ! $request instanceof \WP_REST_Request ) {
			return false;
		}

		return masteriyo_string_to_bool( $request['from_account_dashboard'] ) ?? false;
	}
}

if ( ! function_exists( 'masteriyo_get_user_progress_course_ids' ) ) {

	/**
	 * Retrieves an array of course IDs for a given user filtered by the specified course status.
	 *
	 * @since 1.14.2
	 *
	 * @param Masteriyo\Models\User|int|null $user Optional. User object or ID. Defaults to the current user.
	 * @param string $course_status Optional. The status of the course. Defaults to 'progress'.
	 *
	 * @return array The array of course IDs matching the specified status for the user.
	 */
	function masteriyo_get_user_course_ids_by_course_status( $user = null, $course_status = CourseProgressStatus::PROGRESS ) {

		$user_id = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : absint( $user ) ?? get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		global $wpdb;

		$courses_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT a.item_id
					FROM {$wpdb->prefix}masteriyo_user_activities a
					WHERE a.user_id = %d
					AND a.activity_type = %s
					AND a.activity_status = %s
					AND a.item_id IN (
						SELECT b.item_id FROM {$wpdb->prefix}masteriyo_user_items b WHERE b.status = %s
					)
					AND a.item_id IN (
						SELECT c.ID FROM {$wpdb->prefix}posts c WHERE c.post_type = %s AND c.post_status = %s
					) ORDER BY a.item_id DESC",
				array(
					absint( $user_id ),
					'course_progress',
					$course_status,
					UserCourseStatus::ACTIVE,
					PostType::COURSE,
					PostStatus::PUBLISH,
				)
			)
		);

		return $courses_ids;
	}
}

if ( ! function_exists( 'masteriyo_get_user_courses_count_by_course_status' ) ) {

	/**
	 * Get the count of user courses by course status.
	 *
	 * Retrieves the number of courses for a given user based on the specified course status.
	 *
	 * @since 1.14.2
	 *
	 * @param Masteriyo\Models\User|int|null $user Optional. User object or ID. Defaults to the current user.
	 * @param string $course_status Optional. The status of the course. Defaults to 'progress'.
	 *
	 * @return int The count of courses matching the specified status for the user.
	 */
	function masteriyo_get_user_courses_count_by_course_status( $user = null, $course_status = CourseProgressStatus::PROGRESS ) {
		$user_id = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : absint( $user ) ?? get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;

		$courses_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
					FROM {$wpdb->prefix}masteriyo_user_activities a
					WHERE a.user_id = %d
					AND a.activity_type = %s
					AND a.activity_status = %s
					AND a.item_id IN (
						SELECT b.item_id FROM {$wpdb->prefix}masteriyo_user_items b WHERE b.status = %s
					)
					AND a.item_id IN (
						SELECT c.ID FROM {$wpdb->prefix}posts c WHERE c.post_type = %s AND c.post_status = %s
					) ORDER BY a.item_id DESC",
				array(
					absint( $user_id ),
					'course_progress',
					$course_status,
					UserCourseStatus::ACTIVE,
					PostType::COURSE,
					PostStatus::PUBLISH,
				)
			)
		);

		return $courses_count ? absint( $courses_count ) : 0;
	}
}

if ( ! function_exists( 'masteriyo_get_user_enrolled_courses_count' ) ) {

	/**
	 * Retrieves the number of courses in which a user is enrolled.
	 *
	 * @since 1.14.2
	 *
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 *
	 * @return int The number of enrolled courses for the user.
	 */
	function masteriyo_get_user_enrolled_courses_count( $user = null ) {
		$user_id = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : absint( $user ) ?? get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;

		$courses_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
					FROM {$wpdb->prefix}masteriyo_user_activities a
					WHERE a.user_id = %d
					AND a.activity_type = %s
					AND a.item_id IN (
						SELECT b.item_id FROM {$wpdb->prefix}masteriyo_user_items b WHERE b.status = %s
					)
					AND a.item_id IN (
						SELECT c.ID FROM {$wpdb->prefix}posts c WHERE c.post_type = %s AND c.post_status = %s
					) ORDER BY a.item_id DESC",
				array(
					absint( $user_id ),
					'course_progress',
					UserCourseStatus::ACTIVE,
					PostType::COURSE,
					PostStatus::PUBLISH,
				)
			)
		);

		return $courses_count ? absint( $courses_count ) : 0;
	}
}
