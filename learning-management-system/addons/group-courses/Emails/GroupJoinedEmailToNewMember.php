<?php
/**
 * Group joining email to the user class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Group joining email to the user class. Used for sending new account email.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Emails
 */
class GroupJoinedEmailToNewMember extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.9.0
	 *
	 * @var String
	 */
	protected $id = 'group-joining-email';

	/**
	 * HTML template path.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $html_template = 'group-courses/emails/group-joining.php';

	/**
	 * Send this email.
	 *
	 * @since 1.9.0
	 *
	 * @param int $student_id User ID.
	 * @param int $group_id Group ID.
	 */
	public function trigger( $student_id, $group_id ) {
		$student = masteriyo_get_user( $student_id );
		$group   = masteriyo_get_group( $group_id );

		// Bail early if student or group doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) || is_wp_error( $group ) || is_null( $group ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$this->set_recipients( $student->get_email() );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );
		$this->set( 'group', $group );

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		/**
		 * Filters boolean-like value: 'yes' if group joining email should be disabled, otherwise 'no'.
		 *
		 * @since 1.9.0
		 *
		 * @param string $is_disabled 'yes' if group joining email should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_group_joining_email_to_new_member', 'no' ) );

		return ! $is_disabled;
	}

	/**
	 * Return subject.
	 *
	 * @since 1.9.0s
	 *
	 * @return string
	 */
	public function get_subject() {
		$group   = $this->get( 'group' );
		$subject = "Congratulations! You're Now Part of the '" . $group->get_title() . "'!";

		/**
		 * Filter group joining email subject to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', $subject );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter group joining email heading to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', '' );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter group joining email additional content to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', '' );

		return $this->format_string( $additional_content );
	}
}
