<?php

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\ModelException;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Query\CourseProgressQuery;
/**
 * Course progress functions.
 *
 * @since 1.0.0
 * @package Masteriyo\Helper
 */


/**
 * Get course progress.
 *
 * @since 1.0.0
 *
 * @param Masteriyo\Models\CourseProgress|int $course_progress_id Course progress ID.
 *
 * @return Masteriyo\Models\CourseProgress|\WP_Error
 */
function masteriyo_get_course_progress( $course_progress ) {
	if ( is_a( $course_progress, 'Masteriyo\Database\Model' ) ) {
		$id = $course_progress->get_id();
	} else {
		$id = absint( $course_progress );
	}

	try {
		$course_progress_obj = masteriyo( 'course-progress' );
		$course_progress_obj->set_id( $id );
		$course_progress_obj_repo = masteriyo( 'course-progress.store' );
		$course_progress_obj_repo->read( $course_progress_obj );
	} catch ( \Exception $e ) {
		$course_progress_obj = null;
	}

	/**
	 * Filters course progress object.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\CourseProgress $course_progress_obj course progress object.
	 * @param int|Masteriyo\Models\CourseProgress|WP_Post $course_progress course progress id or course progress Model or Post.
	 */
	return apply_filters( 'masteriyo_get_course_progress', $course_progress_obj, $course_progress );
}

/**
 * Get course progress item.
 *
 * @since 1.0.0
 *
 * @param int|Masteriyo\Models\CourseProgressItem $course_progress_item Course progress ID.
 *
 * @return Masteriyo\Models\CourseProgressItem|WP_Error
 */
function masteriyo_get_course_progress_item( $course_progress_item ) {
	if ( is_a( $course_progress_item, 'Masteriyo\Database\Model' ) ) {
		$item_id = $course_progress_item->get_id();
	} else {
		$item_id = (int) $course_progress_item;
	}

	try {
		$item = masteriyo( 'course-progress-item' );
		$item->set_id( $item_id );

		$item_repo = masteriyo( 'course-progress-item.store' );
		$item_repo->read( $item );

		return $item;
	} catch ( ModelException $e ) {
		$item = new \WP_Error( $e->getCode(), $e->getMessage(), $e->getErrorData() );
	}

	/**
	 * Filters course progress item object.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\CourseProgressItem $course_progress_item_obj course progress item object.
	 * @param int|Masteriyo\Models\CourseProgressItem|WP_Post $course_progress_item course progress item id or course progress item Model or Post.
	 */
	return apply_filters( 'masteriyo_get_course_progress_item', $item, $course_progress_item );
}

/**
 * Get course progress.
 *
 * @since 1.0.0
 *
 * @param Masteriyo\Models\Course|WP_Post|int $course Course object.
 * @param Masteriyo\Models\User|WP_Post|int $user User object.
 *
 * @return Masteriyo\Models\CourseProgress|WP_Error
 */
function masteriyo_get_course_progress_by_user_and_course( $user, $course ) {
	if ( is_a( $course, 'Masteriyo\Database\Model' ) ) {
		$id = $course->get_id();
	} elseif ( is_a( $course, '\WP_Post' ) ) {
		$id = $course->ID;
	} else {
		$id = absint( $course );
	}

	if ( is_a( $user, 'Masteriyo\Database\Model' ) ) {
		$id = $user->get_id();
	} elseif ( is_a( $user, '\WP_User' ) ) {
		$id = $user->ID;
	} else {
		$id = absint( $user );
	}

	$query = new CourseProgressQuery(
		array(
			'course_id' => $course,
			'user_id'   => $user,
			'per_page'  => 1,
		)
	);

	$course_progress = current( $query->get_course_progress() );

	/**
	 * Filters course progress object.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\CourseProgress|WP_Error $course_progress Course progress object.
	 * @param Masteriyo\Models\CourseProgress|WP_Error $course_progress Course progress object.
	 */
	return apply_filters( 'masteriyo_get_course_progress', $course_progress, $course_progress );
}

/**
 * Get active courses.
 *
 * @since 1.0.0
 *
 * @param Masteriyo\Models\User|WP_Post|int $user User object.
 * @return Masteriyo\Model\Course[]
 */
function masteriyo_get_active_courses( $user ) {
	if ( is_a( $user, 'Masteriyo\Database\User' ) ) {
		$id = $user->get_id();
	} elseif ( is_a( $user, '\WP_User' ) ) {
		$id = $user->ID;
	} else {
		$id = absint( $user );
	}

	$query = new CourseProgressQuery(
		array(
			'user_id' => get_current_user_id(),
			'status'  => array( 'started', 'progress' ),
		)
	);

	$progresses = $query->get_course_progress();

	$active_courses = array_filter(
		array_map(
			function( $progress ) {
				$course = masteriyo_get_course( $progress->get_course_id() );

				if ( is_null( $course ) ) {
					return null;
				}

				$course->progress = $progress;
				return $course;
			},
			$progresses
		)
	);

	return $active_courses;
}

if ( ! function_exists( 'masteriyo_get_learn_page_welcome_message_status' ) ) {
	/**
	 * Retrieves the welcome message status for a user on a course's learn page.
	 *
	 * @since 1.9.4
	 *
	 * @param int $course_id The ID of the course.
	 * @param int $user_id   The ID of the user.
	 * @param string $status The course progress status.
	 *
	 * @return bool|array Returns false if welcome message is already shown to the currently logged in user otherwise default welcome message data.
	 */
	function masteriyo_get_learn_page_welcome_message_status( $course_id, $user_id, $status ) {

		if ( ! $course_id || ! $user_id || CourseProgressStatus::STARTED !== $status ) {
			return false;
		}

		$course = masteriyo_get_course( $course_id );

		if ( ! $course || ! $course instanceof \Masteriyo\Models\Course ) {
			return false;
		}

		$welcome_message_data = $course->get_welcome_message_to_first_time_user();
		$is_welcome_msg_shown = get_user_meta( $user_id, "is_masteriyo_course_{$course_id}_wc_msg_shown", true );

		if ( 'yes' === $is_welcome_msg_shown || ( isset( $welcome_message_data['enable'] ) && masteriyo_string_to_bool( $welcome_message_data['enable'] ) ) ) {
			return false;
		}

		$is_shown = isset( $_COOKIE[ 'MasteriyoLearnPageWelcomeMessage-' . $user_id . '-' . $course_id ] ) ? sanitize_text_field( $_COOKIE[ 'MasteriyoLearnPageWelcomeMessage-' . $user_id . '-' . $course_id ] ) : 'not_shown';

		if ( 'shown' === $is_shown ) {
			setcookie( 'MasteriyoLearnPageWelcomeMessage-' . $user_id . '-' . $course_id, '', time() - 3600, '/' );
			update_user_meta( $user_id, "is_masteriyo_course_{$course_id}_wc_msg_shown", 'yes' );
			return false;
		}

		/**
		 * Filters the welcome message status for the learn page.
		 *
		 * @since 1.9.4
		 *
		 * @param array $welcome_message_data The welcome message data.
		 * @param int   $course_id            The course ID.
		 * @param int   $user_id              The user ID.
		 *
		 * @return array The filtered welcome message data.
		 */
		return apply_filters( 'masteriyo_learn_page_welcome_message_status', $welcome_message_data, $course_id, $user_id );
	}
}

if ( ! function_exists( 'masteriyo_get_user_activity_meta' ) ) {
	/**
	 * Retrieves meta value for a given user, item, and meta key.
	 *
	 * @since 1.12.0
	 *
	 * @param int    $user_id The user ID.
	 * @param int    $item_id The item ID (lesson or course_progress).
	 * @param string $item_type The item type ('lesson' or 'course_progress').
	 * @param string $meta_key The meta key.
	 *
	 * @return mixed|null The meta value on success, null on failure.
	 */
	function masteriyo_get_user_activity_meta( $user_id, $item_id, $meta_key, $item_type = 'lesson' ) {
		global $wpdb;

		$meta_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}masteriyo_user_activitymeta
						WHERE user_activity_id = (
								SELECT id FROM {$wpdb->prefix}masteriyo_user_activities
								WHERE item_id = %d
								AND user_id = %d
								AND activity_type = %s
						)
						AND meta_key = %s",
				$item_id,
				$user_id,
				$item_type,
				$meta_key
			)
		);

		if ( is_null( $meta_value ) ) {
			return null;
		}

		return maybe_unserialize( $meta_value );
	}
}
