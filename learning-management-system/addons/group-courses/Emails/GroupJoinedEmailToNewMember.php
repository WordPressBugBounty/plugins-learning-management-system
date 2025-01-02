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
		$enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.group_joining.enable' ) );

		/**
		 * Filters boolean-like value: 'yes' if group joining email should be disabled, otherwise 'no'.
		 *
		 * @since 1.9.0
		 *
		 * @param string $is_disabled 'yes' if group joining email should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_group_joining_email_to_new_member', $enabled ? 'no' : 'yes' ) );

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
		/**
		 * Filter group joining email subject to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['student']['group_joining']['subject'] );
		return $this->format_string( $subject );
	}

	/**
	 * Get placeholders.
	 *
	 * @since 1.15.0 [Free]
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User $student */
		$student = $this->get( 'student' );

		if ( $student ) {
			$placeholders['{student_display_name}'] = $student->get_display_name();
			$placeholders['{student_first_name}']   = empty( $student->get_first_name() ) ? $student->get_display_name() : $student->get_first_name();
			$placeholders['{student_last_name}']    = empty( $student->get_last_name() ) ? $student->get_display_name() : $student->get_last_name();
			$placeholders['{student_name}']         = sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ?? $student->get_display_name();
			$placeholders['{student_username}']     = $student->get_username();
			$placeholders['{student_nicename}']     = $student->get_nicename();
			$placeholders['{student_nickname}']     = $student->get_nickname();
			$placeholders['{student_email}']        = $student->get_email();
			$placeholders['{account_login_link}']   = wp_kses_post(
				'<a href="' . $this->get_account_url() . '" style="text-decoration: none;">Login to Your Account</a>'
			);
		}

		/** @var \Masteriyo\Addons\GroupCourses\Models\Group $group */
		$group = $this->get( 'group' );

		if ( $group ) {
			$placeholders['{group_name}'] = $group->get_title();
		}

		return $placeholders;
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

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_get_default_email_contents()['student']['group_joining']['content'];

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

}
