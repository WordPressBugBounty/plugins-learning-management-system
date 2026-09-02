<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

use Masteriyo\Enums\CourseProgressPostType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\ModelException;
use Masteriyo\PostType\PostType;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Query\CourseProgressItemQuery;
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
	 * @since 1.9.4 [Free]
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
		 * @since 1.9.4 [Free]
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
	 * @since 2.13.0
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
								LIMIT 1
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

if ( ! function_exists( 'masteriyo_is_course_progress_completed_manually' ) ) {
	/**
	 * Determines if a course progress object is marked as manually completed.
	 *
	 * @since 2.18.0
	 *
	 * @param \Masteriyo\Models\CourseProgress $course_progress A course progress object.
	 * @return bool True if manually updated, false otherwise.
	 */
	function masteriyo_is_course_progress_completed_manually( $course_progress ) {
		$manual_update = false;

		if ( is_object( $course_progress ) && is_callable( array( $course_progress, 'get_manual_update' ) ) ) {
			$manual_update = $course_progress->get_manual_update();
		}

		/**
		 * Filters whether a course progress object is marked as manually completed.
		 *
		 * @since 2.18.0
		 *
		 * @param bool $manual_update True if manually updated, false otherwise.
		 * @param \Masteriyo\Models\CourseProgress $course_progress A course progress object.
		 */
		return apply_filters( 'masteriyo_is_course_progress_completed_manually', (bool) $manual_update, $course_progress );
	}
}


if ( ! function_exists( 'masteriyo_complete_specific_course_for_user' ) ) {
	/**
 * complete course progress for a specific user.
 *
 * @param int    $course_id      The course ID.
 * @param int    $student_id     The student ID.
 * @param array  $email_settings (Optional) Email settings if provided.
 *
 * @since 2.19.0
 *
 * @return array|\WP_Error Returns an array with course_progress and completed_items on success,
 *                         or a WP_Error on failure.
 */
	function masteriyo_complete_specific_course_for_user( $course_id, $student_id, $email_settings = array() ) {
		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			return new WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$student = masteriyo_get_user( $student_id );
		if ( is_wp_error( $student ) ) {
			return new WP_Error( 'masteriyo_user_not_found', __( 'User not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$user_course = masteriyo_get_active_user_course( $course_id, $student_id );
		if ( is_wp_error( $user_course ) ) {
			return $user_course;
		}

		$course_item_post_types = CourseProgressPostType::all();

		if ( ( new Addons() )->is_active( 'assignment' ) && ! in_array( 'mto-assignment', $course_item_post_types, true ) ) {
			$course_item_post_types = array_merge( $course_item_post_types, array( 'mto-assignment' ) );
		}

		$course_item_args = array(
			'post_type'      => $course_item_post_types,
			'post_status'    => masteriyo_get_course_content_post_statuses(),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_course_id',
			'meta_value'     => $course_id,
		);

		$course_item_ids = get_posts( $course_item_args );

		if ( empty( $course_item_ids ) ) {
			return new WP_Error( 'masteriyo_unable_to_complete_course', __( 'No course items found to complete.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
				update_email_settings( array_map( 'masteriyo_string_to_bool', $email_settings ) );
		}

		$course_progress = update_course_progress( $student_id, $course_id );
		if ( is_wp_error( $course_progress ) ) {
			return $course_progress;
		}

		$completed_items = complete_course_items( $student, $course_item_ids, $course_progress );
		if ( empty( $completed_items ) ) {
				$course_progress->set_status( CourseProgressStatus::STARTED );
				$course_progress->set_manual_update( false );
				$course_progress->save();
				return new WP_Error( 'masteriyo_unable_to_complete_course', __( 'Unable to complete course items.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		return array(
			'course_progress' => $course_progress,
			'completed_items' => $completed_items,
		);
	}
}

if ( ! function_exists( 'masteriyo_get_active_user_course' ) ) {
	/**
	 * Retrieve the ACTIVE user course for a specific course and student.
	 *
	 * Distinct from `masteriyo_get_user_course()`, which reads a user course by its own
	 * id and returns null when it cannot: this one looks a user course up by course and
	 * student, and reports "not enrolled" and "enrolment inactive" as separate errors.
	 *
	 * @since 2.18.0
	 *
	 * @param int $course_id The ID of the course.
	 * @param int $student_id The ID of the student.
	 *
	 * @return \Masteriyo\Models\UserCourse|WP_Error Returns the user course object if found and active,
	 *                                               or a WP_Error object if not found or inactive.
	 */
	function masteriyo_get_active_user_course( $course_id, $student_id ) {
		$user_course_query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $student_id,
			)
		);

		$user_courses = $user_course_query->get_user_courses();
		$user_course  = ! empty( $user_courses ) ? current( $user_courses ) : null;

		if ( ! $user_course || ! $user_course instanceof \Masteriyo\Models\UserCourse ) {
			return new WP_Error(
				'masteriyo_user_course_not_found',
				__( 'User course not found.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		if ( UserCourseStatus::ACTIVE !== $user_course->get_status() ) {
			return new WP_Error(
				'masteriyo_user_course_not_active',
				__( 'User course is not active.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return $user_course;
	}
}

if ( ! function_exists( 'update_email_settings' ) ) {
	/**
	 * Update the email settings.
	 *
	 * @since 2.18.0
	 *
	 * @param array $email_settings The email settings to update.
	 */
	function update_email_settings( $email_settings ) {
		if ( empty( $email_settings ) ) {
			return;
		}

		$settings_map = array(
			'student'    => 'emails.student.manual_course_completion.enable',
			'instructor' => 'emails.instructor.manual_course_completion.enable',
			'admin'      => 'emails.admin.manual_course_completion.enable',
		);

		foreach ( $settings_map as $key => $setting ) {
			if ( isset( $email_settings[ $key ] ) ) {
				masteriyo_set_setting( $setting, $email_settings[ $key ] );
			}
		}
	}
}


if ( ! function_exists( 'update_course_progress' ) ) {
	/**
	 * Update the course progress for a given user and course.
	 *
	 * @since 2.18.0
	 *
	 * @param int $student_id The ID of the student.
	 * @param int $course_id The ID of the course.
	 *
	 * @return \Masteriyo\Models\CourseProgress|WP_Error The updated course progress model on success, WP_Error otherwise.
	 */
	function update_course_progress( $student_id, $course_id ) {
		$course_progress = masteriyo_get_course_progress_by_user_and_course( $student_id, $course_id );

		if ( ! $course_progress instanceof \Masteriyo\Models\CourseProgress ) {
			/** @var \Masteriyo\Models\CourseProgress $course_progress */
			$course_progress = masteriyo( 'course-progress' );
			$course_progress->set_user_id( $student_id );
			$course_progress->set_course_id( $course_id );
		} elseif ( CourseProgressStatus::COMPLETED === $course_progress->get_status() ) {
			return new WP_Error(
				'masteriyo_course_progress_completed',
				__( 'Course is already completed.', 'learning-management-system' ),
				array( 'status' => 409 )
			);
		}

		$current_time = current_time( 'mysql' );

		$course_progress->set_status( CourseProgressStatus::COMPLETED, '', true );
		$course_progress->set_manual_update( true );
		$course_progress->set_completed_at( $current_time );

		return $course_progress->save() ? $course_progress : new WP_Error(
			'masteriyo_course_progress_save_failed',
			__( 'Failed to save course progress.', 'learning-management-system' ),
			array( 'status' => 500 )
		);
	}
}

if ( ! function_exists( 'complete_course_items' ) ) {
	/**
	 * Complete all course items for a given user and course.
	 *
	 * @since 2.18.0
	 *
	 * @param \WP_User $student The user to complete the course items for.
	 * @param array      $course_item_ids The course item IDs to complete.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress model.
	 *
	 * @return array<int> The IDs of the course progress items that were completed.
	 */
	function complete_course_items( $student, $course_item_ids, $course_progress ) {

		$completed_item_ids = array();
		$current_time       = current_time( 'mysql' );

		foreach ( $course_item_ids as $course_item_id ) {
			$progress_item_query = new CourseProgressItemQuery(
				array(
					'user_id'     => $student->get_id(),
					'item_id'     => $course_item_id,
					'progress_id' => $course_progress->get_id(),
					'per_page'    => 1,
				)
			);

			$progress_items = $progress_item_query->get_course_progress_items();

			$course_progress_item = ! empty( $progress_items ) ? current( $progress_items ) : masteriyo( 'course-progress-item' );

			$post_type = get_post_type( $course_item_id );

			/** @var \Masteriyo\Models\CourseProgressItem $course_progress_item */
			$course_progress_item->set_props(
				array(
					'user_id'      => $student->get_id(),
					'item_id'      => $course_item_id,
					'item_type'    => str_replace( 'mto-', '', $post_type ),
					'progress_id'  => $course_progress->get_id(),
					'completed'    => true,
					'modified_at'  => $current_time,
					'completed_at' => $current_time,
				)
			);

			if ( PostType::QUIZ === $post_type ) {
				masteriyo_create_manual_quiz_attempt( $student->get_id(), $course_progress->get_course_id(), $course_item_id );
			}

			if ( $course_progress_item->save() ) {
				$completed_item_ids[] = $course_progress_item->get_id();
			}
		}

		return $completed_item_ids;
	}
}

if ( ! function_exists( 'delete_course_progress_and_related_data' ) ) {
	/**
	 * Delete course progress and related data for a student.
	 *
	 * @since 2.19.0
	 *
	 * @param int $student_id The ID of the student.
	 * @param int $course_id  The ID of the course.
	 * @return void
	 */
	function delete_course_progress_and_related_data( $student_id, $course_id ) {
		global $wpdb;
		$course_progress = masteriyo_get_course_progress_by_user_and_course( $student_id, $course_id );

		if ( $course_progress ) {
			$course_progress_id = $course_progress->get_id();
			if ( $course_progress->delete() ) {

				// Course items.
				$user_activities_table = $wpdb->prefix . 'masteriyo_user_activities';
				if ( $wpdb->get_var( "SHOW TABLES LIKE '$user_activities_table'" ) === $user_activities_table ) { // phpcs:ignore
					$wpdb->delete(
						$user_activities_table,
						array(
							'parent_id' => $course_progress_id,
						)
					);
				}

				// Quiz attempts data.
				$quiz_attempts_table = $wpdb->prefix . 'masteriyo_quiz_attempts';
				if ( $wpdb->get_var( "SHOW TABLES LIKE '$quiz_attempts_table'" ) === $quiz_attempts_table ) { // phpcs:ignore
					$wpdb->delete(
						$quiz_attempts_table,
						array(
							'course_id' => $course_id,
							'user_id'   => $student_id,
						)
					);
				}

				// Gradebook data.
				$gradebook_results_table = $wpdb->prefix . 'masteriyo_gradebook_results';
				if ( $wpdb->get_var( "SHOW TABLES LIKE '$gradebook_results_table'" ) === $gradebook_results_table ) { // phpcs:ignore
					$gradebook_id = absint(
						$wpdb->get_var(
							$wpdb->prepare(
								"SELECT id FROM {$wpdb->prefix}masteriyo_gradebook_results
								WHERE item_id = %d
								AND user_id = %d
								AND item_type = 'course'",
								$course_id,
								$student_id
							)
						)
					);

					if ( $gradebook_id ) {
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

				// Assignment submissions data.
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

				if ( ! empty( $assignment_ids ) ) {
					$ids_str = implode( ', ', array_fill( 0, count( $assignment_ids ), '%d' ) );
					$sql     = "DELETE FROM {$wpdb->posts} WHERE post_author = %d AND post_parent IN ({$ids_str})";

					$wpdb->query( $wpdb->prepare( $sql, array_merge( array( $student_id ), $assignment_ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_course_progress_summary' ) ) {
	/**
	 * Retrieves the progress summary for a given course for the current user.
	 *
	 * This function creates a CourseProgressQuery for the specified course and the current user,
	 * fetches the course progress, and returns a summary of the progress.
	 *
	 * @since 3.0.0
	 *
	 * @param object $course The course object for which to retrieve progress summary.
	 * @return string The progress summary for the course, or an empty string if no progress is found.
	 */
	function masteriyo_course_progress_summary( $course ) {
		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

			$progress = current( $query->get_course_progress() );

			$summary = $progress ? $progress->get_summary( 'all' ) : '';

			return $summary;
	}
}
