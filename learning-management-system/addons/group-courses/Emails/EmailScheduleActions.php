<?php
/**
 * Group email schedule actions class.
 *
 * @package Masteriyo\Addons\GroupCourses\Emails
 *
 * @since 2.30.0
 */

namespace Masteriyo\Addons\GroupCourses\Emails;

defined( 'ABSPATH' ) || exit;

class EmailScheduleActions {

	/**
	 * Initialize.
	 *
	 * @since 2.30.0
	 */
	public static function init() {
		add_action( 'masteriyo/schedule/email/group-joining-email', array( __CLASS__, 'send_group_joined_email' ), 10, 1 );
		add_action( 'masteriyo/schedule/email/group-course-enroll-email', array( __CLASS__, 'send_group_course_enrollment_email' ), 10, 1 );
		add_action( 'masteriyo/schedule/email/group-published-email', array( __CLASS__, 'send_group_published_email' ), 10, 1 );
		add_action( 'masteriyo/schedule/email/group-member-removed-email', array( __CLASS__, 'send_group_member_removed_email' ), 10, 1 );
	}

	/**
	 * Send group joined email to new member.
	 *
	 * @since 2.30.0
	 *
	 * @param array $args Arguments containing user_id and group_id.
	 */
	public static function send_group_joined_email( $args ) {
		if ( empty( $args['id'] ) || empty( $args['group_id'] ) ) {
			return;
		}

		$email = new GroupJoinedEmailToNewMember();
		$email->trigger( $args['id'], $args['group_id'] );
	}

	/**
	 * Send group course enrollment email to new member.
	 *
	 * @since 2.30.0
	 *
	 * @param array $args Arguments containing user_id, group_id and course_id.
	 */
	public static function send_group_course_enrollment_email( $args ) {
		if ( empty( $args['id'] ) || empty( $args['group_id'] ) || empty( $args['course_id'] ) ) {
			return;
		}

		$email = new GroupCourseEnrollmentEmailToNewMember();
		$email->trigger( $args['id'], $args['group_id'], $args['course_id'] );
	}

	/**
	 * Send group member removed email to the removed member.
	 *
	 * @param array $args Arguments containing user id and group_id.
	 */
	public static function send_group_member_removed_email( $args ) {
		if ( empty( $args['id'] ) || empty( $args['group_id'] ) ) {
			return;
		}

		$email = new GroupMemberRemovedEmailToMember();
		$email->trigger( $args['id'], $args['group_id'] );
	}

	/**
	 * Send group published email to author.
	 *
	 * @since 2.30.0
	 *
	 * @param array $args Arguments containing author_id and group_id.
	 */
	public static function send_group_published_email( $args ) {
		if ( empty( $args['author_id'] ) || empty( $args['group_id'] ) ) {
			return;
		}

		$email = new GroupPublishedEmailToAuthor();
		$email->trigger( $args['author_id'], $args['group_id'] );
	}
}
