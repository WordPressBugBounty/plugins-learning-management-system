<?php

/**
 * BuddyPress Integration helper functions.
 *
 * @since 1.15.0
 * @package Masteriyo\Addons\BuddyPress
 */

namespace Masteriyo\Addons\BuddyPress;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;

class Helper {

	/**
	 * Return if BuddyPress is active.
	 *
	 * @since 1.15.0
	 *
	 * @return boolean
	 */
	public static function is_bp_active() {
		return in_array( 'buddypress/bp-loader.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Return if BuddyPress Groups is active.
	 *
	 * @since 1.15.0
	 *
	 * @return boolean
	 */
	public static function bp_masteriyo_group_activity_is_on( $key, $group_id = false, $default_true = true ) {
		if ( ! function_exists( 'buddypress' ) || ! bp_is_active( 'groups' ) ) {
			return false;
		}
		if ( ! $group_id ) {
			$group_id = bp_get_group_id();
		}

		$retval                    = $default_true;
		$bp_sensei_course_activity = groups_get_groupmeta( $group_id, '_masteriyo_bp_group_activities' );

		if ( is_array( $bp_sensei_course_activity ) ) {
			$retval = isset( $bp_sensei_course_activity[ $key ] );
		}

		return $retval;
	}

	/**
	 * Add or remove member from the buddypress group on a course access update
	 * @param $user_id
	 * @param $course_id
	 * @param $remove
	 *
	 * @since 1.15.0
	 */
	public static function bp_masteriyo_user_course_access_update( $user_id, $course_id, $remove ) {
		$group_attached = (int) get_post_meta( $course_id, 'bp_course_group', true );

		if ( empty( $group_attached ) ) {
			return;
		}

		// Bail out, if Group component not active.
		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		if ( false === $remove ) {

			// Add a student to the group
			if ( ! groups_is_user_member( $user_id, $group_attached ) && ! groups_is_user_banned( $user_id, $group_attached ) ) {
				groups_join_group( $group_attached, $user_id );
			}

			// Record course started activity
			if ( self::bp_masteriyo_group_activity_is_on( 'user_course_start', $group_attached ) ) {
				global $bp;

				$now   = $bp->groups->id;
				$now_1 = $group_attached;

				$user_link         = bp_core_get_userlink( $user_id );
				$course_title      = get_the_title( $course_id );
				$course_link       = get_permalink( $course_id );
				$course_link_html  = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
				$args              = array(
					'type'              => 'started_course',
					'user_id'           => $user_id,
					'action'            => apply_filters(
						'bp_masteriyo_user_course_start_activity',
						sprintf(
							__( '%1$s started taking the course %2$s', 'learning-management-system' ),
							$user_link,
							$course_link_html
						),
						$user_id,
						$course_id
					),
					'item_id'           => $group_attached,
					'secondary_item_id' => $course_id,
					'component'         => $bp->groups->id,
				);
				$activity_recorded = self::bp_masteriyo_record_activity( $args );
				if ( $activity_recorded ) {
					bp_activity_add_meta( $activity_recorded, 'bp_masteriyo_group_activity_markup_courseid', $course_id );
				}
			}
		} elseif ( true === $remove ) {
			// Remove student from the group
			$group = bp_get_group( $group_attached );

			if ( ! groups_is_user_member( $user_id, $group_attached ) ) {
				return;
			}

			if ( ! empty( $group ) ) {
				groups_remove_member( $user_id, $group_attached, $group->creator_id );
			}
		}
	}

	/**
	 * Record an activity item
	 *
	 * @param array $args
	 *
	 * @since 1.15.0
	 *
	 * @return int|bool
	 *
	 */
	public static function bp_masteriyo_record_activity( $args = '' ) {
		global $bp;

		if ( ! function_exists( 'bp_activity_add' ) ) {
			return false;
		}

		$defaults = array(
			'id'                => false,
			'user_id'           => $bp->loggedin_user->id,
			'action'            => '',
			'content'           => '',
			'primary_link'      => '',
			'component'         => $bp->profile->id,
			'type'              => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'recorded_time'     => gmdate( 'Y-m-d H:i:s' ),
			'hide_sitewide'     => false,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$activity_id = groups_record_activity(
			array(
				'id'                => $id,
				'user_id'           => $user_id,
				'action'            => $action,
				'content'           => $content,
				'primary_link'      => $primary_link,
				'component'         => $component,
				'type'              => $type,
				'item_id'           => $item_id,
				'secondary_item_id' => $secondary_item_id,
				'recorded_time'     => $recorded_time,
				'hide_sitewide'     => $hide_sitewide,
			)
		);

		bp_activity_add_meta( $activity_id, 'bp_masteriyo_group_activity_markup', 'true' );

		return $activity_id;
	}


	/**
	 * Removes members from group
	 * @param type $course_id
	 * @param type $group_id
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public static function bp_masteriyo_remove_members_group( $course_id, $group_id ) {

		$course_students = masteriyo_get_enrolled_users( $course_id );

		if ( empty( $course_students ) ) {
			return;
		}
		if ( is_array( $course_students ) ) {
			foreach ( $course_students as $course_students_id ) {
				groups_remove_member( $course_students_id, $group_id );
			}
		} else {
			groups_remove_member( $course_students, $group_id );
		}
	}

	/**
	 * Add members to groups
	 * @param type $course_id
	 * @param type $group_id
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public static function bp_masteriyo_add_members_group( $course_id, $group_id ) {

		$course_students = masteriyo_get_enrolled_users( $course_id );

		if ( empty( $course_students ) ) {
			return;
		}

		if ( ! is_array( $course_students ) ) {
			$course_students = array( $course_students );
		}

		if ( is_array( $course_students ) ) {
			foreach ( $course_students as $course_students_id ) {
				$query = new CourseProgressQuery(
					array(
						'course_id' => $course_id,
						'user_id'   => $course_students_id,
						'status'    => array( CourseProgressStatus::STARTED, CourseProgressStatus::PROGRESS ),
					)
				);

				$activity = $query->get_course_progress();

				if ( ! empty( $activity ) ) {
					if ( ! groups_is_user_banned( $course_students_id, $group_id ) ) {
						groups_join_group( $group_id, $course_students_id );
					}
				}
			}
		}

	}

	/**
	 * Add course teacher as group admin
	 * @param type $course_id
	 * @param type $group_id
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public static function bp_masteriyo_course_teacher_group_admin( $course_id, $group_id ) {

		$teacher = get_post_field( 'post_author', $course_id );
		groups_join_group( $group_id, $teacher );
		$member = new \BP_Groups_Member( $teacher, $group_id );
		$member->promote( 'admin' );
	}
}
