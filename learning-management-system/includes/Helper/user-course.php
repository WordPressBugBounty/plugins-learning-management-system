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
use Masteriyo\Roles;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Enums\CourseProgressStatus;


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
 * @param int|int[]   $course     Course Id or Course IDS
 * @param string|null $start_date Normalized start date ('Y-m-d H:i:s'), or null for no lower bound.
 * @param string|null $end_date   Normalized end date ('Y-m-d H:i:s'), or null for no upper bound.
 *
 * @return integer
 */
function masteriyo_count_enrolled_users( $course, $start_date = null, $end_date = null ) {
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

		// Same exclusion rule as the Enrollments screen, so the course badge and
		// the list it links to agree — staff are excluded unless they also hold
		// the student role.
		$exclude_users = masteriyo_get_enrollment_excluded_user_ids();

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

		if ( $start_date && $end_date ) {
			$sql .= $wpdb->prepare( ' AND date_start >= %s AND date_start <= %s', $start_date, $end_date );
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
 * @since 2.8.0
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

			// Same rule as the Enrollments screen — a dual-role staff member who is
			// a genuine student must receive announcements, meeting invites and
			// scheduled emails like any other enrollee.
			$exclude_users = masteriyo_get_enrollment_excluded_user_ids();

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
	 * @since 2.8.0
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
 * @param int|int[]   $course     Course id or array of course ids.
 * @param string|null $start_date Normalized start date ('Y-m-d H:i:s'), or null for no lower bound.
 * @param string|null $end_date   Normalized end date ('Y-m-d H:i:s'), or null for no upper bound.
 *
 * @return int
 */
function masteriyo_get_user_courses_count_by_course( $course, $start_date = null, $end_date = null ) {
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

		if ( $start_date && $end_date ) {
			$sql .= $wpdb->prepare( ' AND date_start >= %s AND date_start <= %s', $start_date, $end_date );
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


/**
 * Retrieves all course IDs for a given user.
 *
 * @since 2.6.5
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
 * Get the enrollment date for a specific user and course.
 *
 * @since 2.7.0
 *
 * @param int $course_id The course ID.
 * @param int $user_id   The user ID.
 *
 * @return string|null The enrollment date or null if not available.
 */
function masteriyo_get_enrollment_date_by_user_course( $course_id, $user_id ) {
	global $wpdb;
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT date_start FROM {$wpdb->prefix}masteriyo_user_items WHERE item_id = %d AND user_id = %d AND item_type = 'user_course'",
			$course_id,
			$user_id
		)
	);
}

/**
 * Revoke the enrollment for a specific user and course if it has expired.
 *
 * @since 2.7.0
 *
 * @param int $user_item_id The user's active/inactive checking ID.
 *
 * @return bool True if revoked, false otherwise.
 */
function masteriyo_revoke_enrollment_due_to_expiration_by_user_course( $user_item_id ) {
	if ( ! $user_item_id ) {
		return;
	}

	$user_course = masteriyo_get_user_course( $user_item_id );

	if ( ! $user_course ) {
		return;
	}

	$user_course->set_status( UserCourseStatus::INACTIVE );
	$user_course->set_date_modified( current_time( 'mysql', true ) );
	$user_course->save();

	/**
	 * Fire after course enrollment is revoked due to expiration.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course object.
	 */
	do_action( 'masteriyo_revoke_enrollment_due_to_expiration', $user_course );

	masteriyo_get_logger()->info(
		sprintf(
			'Course enrollment revoked: User Course #%d changed to inactive status.',
			$user_course->get_id()
		),
		array(
			'source'    => 'user-course-enrollment',
			'user_id'   => $user_course->get_user_id(),
			'course_id' => $user_course->get_course_id(),
		)
	);
}

/**
 * Get the remaining time in days before a user loses access to a specific course due to enrollment expiration.
 *
 * @since 2.7.0
 *
 * @param int  $user_id   The user ID for whom to find the remaining time.
 * @param int  $course_id The course ID for which to find the remaining time.
 * @param bool $format    If true, return formatted days as string.
 * @param bool $delete    If true, revoke access if remaining days <= 0.
 *
 * @return int|string|null Remaining time in days or formatted string or null if data is not available or the course doesn't expire.
 */
function masteriyo_get_remaining_time_for_single_course( $user_id, $course_id, $format = false, $delete = false ) {
	$course = masteriyo_get_course( $course_id );

	if ( is_null( $course ) || is_wp_error( $course ) || ! masteriyo_can_start_course( $course_id, $user_id ) ) {
		return null;
	}

	if ( ! $course->get_enrollment_expiration_enabled() ) {
		return null;
	}

	$expiration_duration_days = $course->get_enrollment_expiration_duration();

	if ( 1 > $expiration_duration_days ) {
		return null;
	}

	$enrollment_date = masteriyo_get_enrollment_date_by_user_course( $course_id, $user_id );

	if ( ! $enrollment_date ) {
		return null;
	}

	$current_time          = time();
	$enrollment_start_time = absint( strtotime( $enrollment_date ) );

	$elapsed_time_days   = ( $current_time - $enrollment_start_time ) / DAY_IN_SECONDS;
	$remaining_time_days = $expiration_duration_days - $elapsed_time_days;

	// If the remaining time is <= 0 and delete is true, then revoke access and return null.
	if ( $remaining_time_days <= 0 ) {
		global $wpdb;

		// Note: `user_item_id` refers to the ID fetched from the `masteriyo_user_items` table,
		// used specifically for checking a user's active/inactive status.
		// It should not be confused with the `item_type` attribute from the same table.
		$user_item_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}masteriyo_user_items
            WHERE user_id = %d
            AND item_id = %d
            AND item_type = %s
            LIMIT 1",
				$user_id,
				$course_id,
				'user_course'
			)
		);
		$user_item_id = absint( $user_item_id );

		masteriyo_revoke_enrollment_due_to_expiration_by_user_course( $user_item_id );
		return null;
	}

	$rounded_remaining_days = absint( round( $remaining_time_days ) );

	if ( $rounded_remaining_days <= 0 ) {
		return null;
	}

	if ( $format ) {
		/* translators: %d: Number of remaining days */
		return 1 === $rounded_remaining_days ? __( '1 Day', 'learning-management-system' ) : sprintf( __( '%d Days', 'learning-management-system' ), $rounded_remaining_days );
	}

	return $rounded_remaining_days;
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

if ( ! function_exists( 'masteriyo_get_user_enrolled_courses' ) ) {
	/**
	 * Retrieves the enrolled courses of a user.
	 *
	 * Sourced from the enrollment rows, so a course the learner has not opened
	 * yet is listed. Each course carries its progress in `$course->progress`,
	 * which is null until that first visit creates one, and the date it started
	 * in `$course->started_at` - the progress date, or the enrollment date for a
	 * course with no progress row.
	 *
	 * @since 2.6.8
	 *
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 * @param int $limit The maximum number of courses to retrieve. Default is -1 (unlimited).
	 * @param int $offset The number of courses to skip. Default is 0.
	 *
	 * @return array The user's enrolled courses.
	 */
	function masteriyo_get_user_enrolled_courses( $user, $limit = -1, $offset = 0 ) {
		$user = masteriyo_get_user( $user );

		if ( is_null( $user ) || is_wp_error( $user ) ) {
			return array();
		}

		$course_ids = masteriyo_get_user_active_enrolled_course_ids( $user, false, $limit, $offset );

		if ( empty( $course_ids ) ) {
			return array();
		}

		// The progress rows of the whole page in one query, and the enrollment
		// dates of the courses without one in another, so a page costs two
		// lookups rather than two per course.
		$progresses = array();

		$progress_query = new CourseProgressQuery(
			array(
				'user_id'  => $user->get_id(),
				'courses'  => $course_ids,
				'per_page' => -1,
			)
		);

		foreach ( $progress_query->get_course_progress() as $progress ) {
			$progresses[ $progress->get_course_id() ] = $progress;
		}

		$start_dates = array();
		$unopened    = array_values( array_diff( $course_ids, array_keys( $progresses ) ) );

		if ( ! empty( $unopened ) ) {
			$enrollment_query = new UserCourseQuery(
				array(
					'user_id'    => $user->get_id(),
					'course__in' => $unopened,
					'status'     => UserCourseStatus::ACTIVE,
					'per_page'   => -1,
				)
			);

			foreach ( $enrollment_query->get_user_courses() as $enrollment ) {
				$start_dates[ $enrollment->get_course_id() ] = $enrollment->get_date_start();
			}
		}

		$enrolled_courses = array_filter(
			array_map(
				function( $course_id ) use ( $progresses, $start_dates ) {
					$course = masteriyo_get_course( $course_id );

					if ( is_null( $course ) ) {
						return null;
					}

					$progress = isset( $progresses[ $course_id ] ) ? $progresses[ $course_id ] : null;
					$fallback = isset( $start_dates[ $course_id ] ) ? $start_dates[ $course_id ] : null;

					$course->progress   = $progress;
					$course->started_at = $progress ? $progress->get_started_at() : $fallback;

					return $course;
				},
				$course_ids
			)
		);

		return $enrolled_courses;
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
	 * @since 1.14.2 [Free]
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
	 * @since 1.14.2 [Free]
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

if ( ! function_exists( 'masteriyo_query_user_active_enrolled_courses' ) ) {

	/**
	 * Queries the courses a user is actively enrolled in.
	 *
	 * Sourced from enrollment rows (`masteriyo_user_items`), not course-progress
	 * activity, so a course counts even before the learner opens it and creates
	 * its first progress row.
	 *
	 * The total and a page of IDs are answered from one set of criteria, written
	 * here and nowhere else, so the two cannot disagree - a second copy of this
	 * clause is how the enrolled-courses count and list came to contradict each
	 * other.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type Masteriyo\Database\Model|WP_User|int|null $user Optional. User object, WP_User object or ID. Defaults to the current user.
	 *     @type bool $exclude_completed Optional. Exclude the courses the user has completed. Defaults to false.
	 *     @type bool $count Optional. Return the number of courses rather than their IDs. Defaults to false.
	 *     @type int $limit Optional. The maximum number of IDs to return. Default -1 (no limit).
	 *     @type int $offset Optional. The number of IDs to skip. Default 0.
	 * }
	 *
	 * @return int[]|int Distinct published course IDs, or their number when `count` is set.
	 */
	function masteriyo_query_user_active_enrolled_courses( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'user'              => null,
				'exclude_completed' => false,
				'count'             => false,
				'limit'             => -1,
				'offset'            => 0,
			)
		);

		$user = $args['user'];

		if ( is_a( $user, 'Masteriyo\Database\Model' ) ) {
			$user_id = $user->get_id();
		} elseif ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;
		} else {
			$user_id = absint( $user ?? get_current_user_id() );
		}

		if ( ! $user_id ) {
			return $args['count'] ? 0 : array();
		}

		global $wpdb;

		$sql = $args['count'] ? 'SELECT COUNT( DISTINCT b.item_id )' : 'SELECT DISTINCT b.item_id';

		$sql .= " FROM {$wpdb->prefix}masteriyo_user_items b
			WHERE b.user_id = %d
			AND b.item_type = %s
			AND b.status = %s
			AND b.item_id IN (
				SELECT c.ID FROM {$wpdb->prefix}posts c WHERE c.post_type = %s AND c.post_status = %s
			)";

		$params = array(
			absint( $user_id ),
			'user_course',
			UserCourseStatus::ACTIVE,
			PostType::COURSE,
			PostStatus::PUBLISH,
		);

		if ( $args['exclude_completed'] ) {
			$sql     .= " AND b.item_id NOT IN (
				SELECT a.item_id FROM {$wpdb->prefix}masteriyo_user_activities a
				WHERE a.user_id = %d AND a.activity_type = %s AND a.activity_status = %s
			)";
			$params[] = absint( $user_id );
			$params[] = 'course_progress';
			$params[] = CourseProgressStatus::COMPLETED;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $args['count'] ) {
			return absint( $wpdb->get_var( $wpdb->prepare( $sql, $params ) ) );
		}

		$sql .= ' ORDER BY b.item_id DESC';

		$limit  = (int) $args['limit'];
		$offset = absint( $args['offset'] );

		// A page of the enrollment rows, rather than all of them sliced in PHP. A
		// limit of zero asks for none of them; only a negative one is unlimited.
		// MySQL has no OFFSET without a LIMIT, so an offset with no limit asks for
		// every remaining row.
		if ( $limit >= 0 || $offset > 0 ) {
			$sql     .= ' LIMIT %d OFFSET %d';
			$params[] = $limit >= 0 ? $limit : PHP_INT_MAX;
			$params[] = $offset;
		}

		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( $sql, $params ) ) );
		// phpcs:enable
	}
}

if ( ! function_exists( 'masteriyo_get_user_active_enrolled_course_ids' ) ) {

	/**
	 * Retrieves the course IDs a user is actively enrolled in.
	 *
	 * Used by the account dashboard for the enrolled-courses statistic and the
	 * Continue Studying list, and by the enrolled-courses list for one page of a
	 * learner's enrollments.
	 *
	 * @param Masteriyo\Database\Model|WP_User|int|null $user Optional. User object, WP_User object or ID. Defaults to the current user.
	 * @param bool $exclude_completed Optional. Exclude courses the user has completed. Defaults to false.
	 * @param int $limit Optional. The maximum number of IDs to return. Default -1 (no limit).
	 * @param int $offset Optional. The number of IDs to skip. Default 0.
	 *
	 * @return int[] Distinct published course IDs the user is actively enrolled in.
	 */
	function masteriyo_get_user_active_enrolled_course_ids( $user = null, $exclude_completed = false, $limit = -1, $offset = 0 ) {
		return masteriyo_query_user_active_enrolled_courses(
			array(
				'user'              => $user,
				'exclude_completed' => $exclude_completed,
				'limit'             => $limit,
				'offset'            => $offset,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_get_user_courses_count_by_course_status' ) ) {

	/**
	 * Get the count of user courses by course status.
	 *
	 * Retrieves the number of courses for a given user based on the specified course status.
	 *
	 * @since 1.14.2 [Free]
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
	 * Counted from the enrollment rows, so a course counts from the moment the
	 * learner is enrolled, not from the moment they first open it.
	 *
	 * @since 1.14.2 [Free]
	 *
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 *
	 * @return int The number of enrolled courses for the user.
	 */
	function masteriyo_get_user_enrolled_courses_count( $user = null ) {
		return masteriyo_query_user_active_enrolled_courses(
			array(
				'user'  => $user,
				'count' => true,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_delete_user_course_assignment_submissions' ) ) {
	/**
	 * Remove assignment submissions of a course for the user.
	 *
	 * @since 2.16.0
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id   The user ID.
	 */
	function masteriyo_delete_user_course_assignment_submissions( $course_id, $user_id ) {
		$course_id = absint( $course_id );
		$user_id   = absint( $user_id );

		if ( ! $course_id || ! $user_id ) {
			return;
		}

		global $wpdb;

		$args           = array(
			'post_type'      => PostType::ASSIGNMENT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_course_id',
					'value'   => $course_id,
					'compare' => '=',
				),
			),
		);
		$query          = new \WP_Query( $args );
		$assignment_ids = $query->posts;

		if ( empty( $assignment_ids ) ) {
			return;
		}

		$ids_str = implode( ', ', array_fill( 0, count( $assignment_ids ), '%d' ) );
		$sql     = "DELETE FROM {$wpdb->posts} WHERE post_author = %d AND post_parent IN ({$ids_str})";

		$wpdb->query( $wpdb->prepare( $sql, array_merge( array( $user_id ), $assignment_ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

if ( ! function_exists( 'masteriyo_delete_user_course_gradebooks' ) ) {
	/**
	 * Remove assignment submissions of a course for the current user.
	 *
	 * @since 2.7.0
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id  The user ID.
	 */
	function masteriyo_delete_user_course_gradebooks( $course_id, $user_id ) {
		$course_id = absint( $course_id );
		$user_id   = absint( $user_id );

		if ( ! $course_id || ! $user_id ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_gradebook_results';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {  // phpcs:ignore
			$gradebook_id = absint(
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}masteriyo_gradebook_results
					WHERE item_id = %d
					AND user_id = %d
					AND item_type = 'course'",
						$course_id,
						$user_id
					)
				)
			);

			if ( ! $gradebook_id ) {
				return;
			}

			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_gradebook_results",
				array(
					'parent_id' => $gradebook_id,
				)
			);

			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_gradebook_results",
				array(
					'id' => $gradebook_id,
				)
			);
		}
	}
}

/**
 * Get all user emails for enrolled users in a course.
 *
 * @since 2.21.0
 *
 * @param int $course_id The ID of the course.
 * @return array List of user emails.
 */
function masteriyo_get_enrolled_user_emails( $course_id ) {
	$user_ids    = masteriyo_get_enrolled_users( $course_id );
	$user_emails = array();

	if ( ! empty( $user_ids ) && is_array( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user && ! empty( $user->user_email ) ) {
				$user_emails[] = $user->user_email;
			}
		}
	}

	return $user_emails;
}

if ( ! function_exists( 'masteriyo_get_enrollment_excluded_user_ids' ) ) {
	/**
	 * Admin/instructor/manager user IDs excluded from the Enrollments list (they aren't
	 * students, even if they hold a user_items row). A user who also holds the student
	 * role stays listed: promoting a student to instructor must not hide their real
	 * enrollments.
	 *
	 * `get_users( role__in => [...] )` runs an unindexed `wp_capabilities` LIKE scan, so this
	 * is transient-cached and invalidated below on every hook that can change a user's role
	 * set. Kept as an ID list rather than a per-row NOT EXISTS subquery because the exclusion
	 * applies to an unbounded COUNT(*) in EnrollmentsController::get_items() — a correlated
	 * subquery there would run once per row, where this cache runs once per TTL window.
	 *
	 * @since 2.31.0
	 *
	 * @return int[]
	 */
	function masteriyo_get_enrollment_excluded_user_ids() {
		$cached = get_transient( 'masteriyo_enrollment_excluded_user_ids' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		// Full user objects so WP primes all usermeta in one query — roles come
		// free, where a fields=>'ID' pass would pay one get_userdata() per user.
		// The staff list is small; the student list is the whole school.
		$staff = (array) get_users(
			array(
				'role__in' => array( Roles::ADMIN, Roles::INSTRUCTOR, Roles::MANAGER ),
			)
		);

		$excluded_user_ids = array();

		foreach ( $staff as $user ) {
			if ( ! in_array( Roles::STUDENT, (array) $user->roles, true ) ) {
				$excluded_user_ids[] = absint( $user->ID );
			}
		}

		set_transient( 'masteriyo_enrollment_excluded_user_ids', $excluded_user_ids, 5 * MINUTE_IN_SECONDS );

		return $excluded_user_ids;
	}
}

if ( ! function_exists( 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' ) ) {
	/**
	 * Delete the cached excluded-user-ids transient.
	 *
	 * @since 2.31.0
	 */
	function masteriyo_invalidate_enrollment_excluded_user_ids_cache() {
		delete_transient( 'masteriyo_enrollment_excluded_user_ids' );
	}
}

// Invalidate whenever a user's role set, or a user's existence, could have changed.
add_action( 'set_user_role', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
add_action( 'add_user_role', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
add_action( 'remove_user_role', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
add_action( 'profile_update', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
add_action( 'user_register', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
add_action( 'deleted_user', 'masteriyo_invalidate_enrollment_excluded_user_ids_cache' );
