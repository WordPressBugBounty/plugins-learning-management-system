<?php

use Masteriyo\Addons\GroupCourses\Models\Setting;
use Masteriyo\Enums\PostStatus;

if ( ! function_exists( 'masteriyo_create_group_object' ) ) {
	/**
	 * Create instance of group model.
	 *
	 * @since 1.9.0
	 *
	 * @return \Masteriyo\Addons\GroupCourses\Models\Group
	 */
	function masteriyo_create_group_object() {
		return masteriyo( 'group-courses' );
	}
}

if ( ! function_exists( 'masteriyo_create_group_store' ) ) {
	/**
	 * Create instance of group repository.
	 *
	 * @since 1.9.0
	 *
	 * @return \Masteriyo\Addons\GroupCourses\Repository\GroupRepository
	 */
	function masteriyo_create_group_store() {
		return masteriyo( 'group-courses.store' );
	}
}

if ( ! function_exists( 'masteriyo_get_group' ) ) {
	/**
	 * Get group.
	 *
	 * @since 1.9.0
	 *
	 * @param int|\Masteriyo\Addons\GroupCourses\Models\Group|\WP_Post $group Group id or Group Model or Post.
	 *
	 * @return \Masteriyo\Addons\GroupCourses\Models\Group|null
	 */
	function masteriyo_get_group( $group ) {
		$group_obj   = masteriyo( 'group-courses' );
		$group_store = masteriyo( 'group-courses.store' );

		if ( is_a( $group, 'Masteriyo\Addons\GroupCourses\Models\Group' ) ) {
			$id = $group->get_id();
		} elseif ( is_a( $group, 'WP_Post' ) ) {
			$id = $group->ID;
		} else {
			$id = absint( $group );
		}

		try {
			$id = absint( $id );
			$group_obj->set_id( $id );
			$group_store->read( $group_obj );
		} catch ( \Exception $e ) {
			return null;
		}

		/**
		 * Filters group object.
		 *
		 * @since 1.9.0
		 *
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group_obj Group object.
		 * @param int|\Masteriyo\Addons\GroupCourses\Models\Group|WP_Post $group Group id or Group Model or Post.
		 */
		return apply_filters( 'masteriyo_get_group', $group_obj, $group );
	}
}

if ( ! function_exists( 'masteriyo_get_group_price' ) ) {
	/**
	 * Retrieves the group price for a specified course.
	 *
	 * This function fetches the group price set for a course using its course ID. If a group price
	 * exists and is greater than 0, it formats the price using the `masteriyo_price` function
	 * and returns it. Otherwise, it returns false.
	 *
	 * @since 1.9.0
	 *
	 * @param int $course_id The ID of the course for which to retrieve the group price.
	 *
	 * @return mixed The formatted group price if it exists and is greater than 0, otherwise false.
	 */
	function masteriyo_get_group_price( $course_id ) {
		$group_price = floatval( get_post_meta( $course_id, '_group_courses_group_price', true ) );

		if ( $group_price > 0 ) {
			return masteriyo_price( $group_price );
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_get_groups' ) ) {
	/**
	 * Retrieves the group objects for the given group IDs.
	 *
	 * This function iterates through an array of group IDs, retrieves each group object
	 * using the `masteriyo_get_group` function, and collects them into an array. If a group
	 * is successfully retrieved, it is added to the array; otherwise, it is skipped.
	 *
	 * @since 1.9.0
	 *
	 * @param array $group_ids An array of group IDs to retrieve.
	 *
	 * @return array An array of group objects.
	 */
	function masteriyo_get_groups( $group_ids ) {
		$groups = array();

		foreach ( $group_ids as $group_id ) {
			$group = masteriyo_get_group( $group_id );

			if ( $group ) {
				$groups[] = $group;
			}
		}

		return $groups;
	}
}

if ( ! function_exists( 'masteriyo_is_course_valid_for_group' ) ) {
	/**
	 * Checks if a course is valid for group pricing.
	 *
	 * Determines whether a given course qualifies for group pricing based on the existence
	 * of a group price for the course. If no course is explicitly provided, the function attempts
	 * to use a global `$course` variable.
	 *
	 * @since 1.9.0
	 *
	 * @param mixed $course Optional. The course object or ID. If not provided, the function tries to use a global variable.
	 *
	 * @return bool True if the course has a valid group price, false otherwise.
	 */
	function masteriyo_is_course_valid_for_group( $course = null ) {
		if ( ! $course ) {
			$course = $GLOBALS['course'];
		}

		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return false;
		}
		$group_price = masteriyo_get_group_price( $course->get_id() );

		if ( $group_price ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_get_groups_limit' ) ) {
	/**
	 * Retrieves the maximum number of group members allowed.
	 *
	 * This function fetches the maximum number of members allowed in a group from the settings.
	 *
	 * @since 1.9.0
	 *
	 * @return int The maximum number of members allowed in a group.
	 */
	function masteriyo_get_groups_limit() {
		$group_limit = Setting::get( 'max_members' );

		return absint( $group_limit );
	}
}

if ( ! function_exists( 'masteriyo_validate_groups_for_course' ) ) {
	/**
	 * Validates and filters group IDs for a given course based on certain criteria.
	 *
	 * This function checks each group associated with the provided course ID to ensure
	 * it is not in the Trash status and meets the maximum group size criteria set in the
	 * course's metadata. It filters out any groups that do not meet these criteria.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object for which groups are being validated.
	 *                        Expected to at least have a valid ID.
	 * @param array $group_ids An array of group IDs to validate for the course.
	 *                         Each ID is checked to ensure the corresponding group is valid
	 *                         and does not exceed the maximum group size associated with the course.
	 *
	 * @return array An array of validated group IDs that are not trashed and do not exceed
	 *               the maximum group size set for the course. If no groups meet the criteria,
	 *               or if invalid parameters are provided, an empty array is returned.
	 */
	function masteriyo_validate_groups_for_course( $course, $group_ids ) {
		if ( ! $course || empty( $group_ids ) ) {
			return array();
		}

		if ( ! $course->is_purchasable() || ! $course->get_price() ) {
			return array();
		}

		$max_group_size = absint( get_post_meta( $course->get_id(), '_group_courses_max_group_size', true ) );

		$valid_group_ids = array();

		foreach ( $group_ids as $group_id ) {
			$group = masteriyo_get_group( $group_id );

			if ( ! $group || PostStatus::PUBLISH !== $group->get_status() ) {
				continue;
			}

			$user_id   = get_current_user_id();
			$author_id = absint( $group->get_author_id() );

			if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
				if ( $user_id !== $author_id ) {
					continue;
				}
			}

			$emails     = $group->get_emails();
			$group_size = count( $emails );

			if ( 0 === $max_group_size || $group_size <= $max_group_size ) {
				$valid_group_ids[] = $group_id;
			}
		}

		return $valid_group_ids;
	}
}

if ( ! function_exists( 'masteriyo_get_members_emails_from_group' ) ) {
	/**
	 * Retrieves members' emails from group.
	 *
	 * @since 1.9.0
	 *
	 * @param array $group_id Group ID.
	 * @return array An array of members' emails.
	 */
	function masteriyo_get_members_emails_from_group( $group_id ) {
		$group = masteriyo_get_group( $group_id );

		$emails = array();
		if ( $group ) {
			$emails = $group->get_emails();
		}

		return $emails;
	}
}


if ( ! function_exists( 'masteriyo_fetch_group_member_emails' ) ) {
		/**
	 * Fetches the email addresses of all members in the given groups.
	 *
	 * @since 1.9.0
	 *
	 * @param array $group_ids Array of group IDs.
	 * @return array Array of email addresses.
	 */
	function masteriyo_fetch_group_member_emails( $group_ids ) {
		$emails = array();

		foreach ( $group_ids as $group_id ) {
			$group_emails = masteriyo_get_members_emails_from_group( $group_id );
			if ( ! empty( $group_emails ) ) {
				$emails = array_merge( $emails, $group_emails );
			}
		}

		return array_unique( $emails );
	}
}

if ( ! function_exists( 'masteriyo_update_user_enrollments_status' ) ) {
	/**
	 * Updates the enrollment status for users based on their email addresses.
	 *
	 * @since 1.9.0
	 *
	 * @param int $group_id Group ID. $name
	 * @param array $emails User email addresses.
	 * @param string $status New status to apply.
	 */
	function masteriyo_update_user_enrollments_status( $group_id, $emails, $status ) {
		global $wpdb;

		if ( ! $wpdb || empty( $emails ) || empty( $status ) ) {
			return;
		}

		$course_ids = masteriyo_get_active_course_ids_for_group( $group_id );

		if ( ! $course_ids ) {
			return;
		}

		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );

			if ( ! $user ) {
				continue;
			}

			foreach ( $course_ids as $course_id ) {
				if ( ! masteriyo_is_user_already_enrolled( $user->ID, $course_id ) ) {
					continue;
				}

				$status_db = masteriyo_get_user_enrollment_status( $user, $course_id );

				if ( $status === $status_db ) {
					continue;
				}

				$wpdb->update(
					"{$wpdb->prefix}masteriyo_user_items",
					array( 'status' => $status ),
					array(
						'user_id'   => $user->ID,
						'item_id'   => $course_id,
						'item_type' => 'user_course',
					),
					array( '%s' ),
					array( '%d', '%d', '%s' )
				);
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_get_user_enrollment_status' ) ) {
	/**
	 * Retrieves the current enrollment status for a given user and course item type.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_User $user The user object whose enrollment status is to be retrieved.
	 * @param int $course_id The ID of the course item for which to retrieve the enrollment status.
	 *
	 * @return string|null The current enrollment status of the user, or null if not found.
	 */
	function masteriyo_get_user_enrollment_status( $user, $course_id ) {
		global $wpdb;

		$enrollment_status_db = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user->ID,
				absint( $course_id )
			)
		);

		return $enrollment_status_db ? $enrollment_status_db : null;
	}
}


if ( ! function_exists( 'masteriyo_is_user_already_enrolled' ) ) {
	/**
	 * Checks if a user is already enrolled in a course.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id   The user ID.
	 * @param int $course_id The course ID.
	 * @return bool True if the user is already enrolled, false otherwise.
	 */
	function masteriyo_is_user_already_enrolled( $user_id, $course_id ) {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user_id,
				$course_id
			)
		);

		return $count > 0;
	}
}

if ( ! function_exists( 'masteriyo_get_enrolled_group_user_emails' ) ) {
	/**
	 * Get the emails of users enrolled in a course for a specific group.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
	 * @param int $course_id Course ID.
	 *
	 * @return array An array of user emails not enrolled in the specified course.
	 */
	function masteriyo_get_enrolled_group_user_emails( $group, $course_id ) {
		if ( ! $group ) {
			return array();
		}

		$emails = $group->get_emails();

		if ( empty( $emails ) ) {
			return array();
		}

		$enrolled_emails = array_filter(
			array_map(
				function( $email ) use ( $course_id ) {
						$user = get_user_by( 'email', $email );

						return ( $user && masteriyo_is_user_already_enrolled( $user->ID, $course_id ) ) ? $email : null;
				},
				$emails
			)
		);

		return array_values( $enrolled_emails );
	}
}

if ( ! function_exists( 'masteriyo_get_active_course_ids_for_group' ) ) {
	/**
	 * Retrieves course IDs for a specified group, optionally filtered by enrollment status.
	 * If no status is provided, all course IDs are returned.
	 *
	 * @since 1.9.0
	 *
	 * @param int $group_id The group ID to retrieve course IDs for.
	 * @param string $status Optional. The enrollment status to filter by. If empty, all course IDs are returned.
	 *
	 * @return array An array of course IDs for the group, optionally filtered by the specified status.
	 */
	function masteriyo_get_active_course_ids_for_group( $group_id, $status = '' ) {
		$course_data = get_post_meta( $group_id, 'masteriyo_course_data', true );

		$course_ids = array();

		if ( is_array( $course_data ) ) {
			foreach ( $course_data as $data ) {
				if ( empty( $status ) || ( isset( $data['enrolled_status'] ) && $data['enrolled_status'] === $status ) ) {
					$course_ids[] = $data['course_id'];
				}
			}
		}

		return $course_ids;
	}
}
