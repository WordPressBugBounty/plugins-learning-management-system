<?php
/**
 * Instructor apply email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.6.13
 */

namespace Masteriyo\Emails\Admin;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Instructor apply email class. Used for sending new account email.
 *
 * @since 1.6.13
 *
 * @package Masteriyo\Emails
 */
class InstructorApplyEmailToAdmin extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.6.13
	 *
	 * @var String
	 */
	protected $id = 'instructor-apply/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.6.13
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/instructor-apply.php';

	/**
	 * Send this email.
	 *
	 * @since 1.6.13
	 *
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public function trigger( $user ) {
		$user = masteriyo_get_user( $user );

		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$this->set_recipients( $admin_email );
		$this->set( 'user', $user );

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
	 * @since 1.6.13
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.instructor_apply.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter instructor apply email subject to admin.
		 *
		 * @since 1.6.13
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['admin']['instructor_apply']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.instructor_apply.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['admin']['instructor_apply']['content'] );

		$content = $this->format_string( $content );

		$this->set( 'content', trim( $content ) );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User $student */
		$student = $this->get( 'user' );

		if ( $student ) {
			$placeholders['{student_display_name}']    = $student->get_display_name();
			$placeholders['{student_first_name}']      = $student->get_first_name();
			$placeholders['{student_last_name}']       = $student->get_last_name();
			$placeholders['{student_username}']        = $student->get_username();
			$placeholders['{student_nicename}']        = $student->get_nicename();
			$placeholders['{student_nickname}']        = $student->get_nickname();
			$placeholders['{student_email}']           = $student->get_email();
			$placeholders['{student_name}']            = ! empty( $name ) ? $name : $student->get_display_name();
			$placeholders['{student_registered_date}'] = gmdate( 'd M Y', $student->get_date_created( 'edit' )->getOffsetTimestamp() );
			$placeholders['{review_application_link}'] = wp_kses_post(
				'<a href="' . admin_url( 'admin.php?page=masteriyo#/users/students/' ) . $student->get_id() . '" style="text-decoration: none;">Review Application</a>'
			);
		}

		return $placeholders;
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter student registration email additional content to admin.
		 *
		 * @since 1.6.13
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.instructor_apply.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.admin.instructor_apply.additional_content', 'masteriyo-email-message', $additional_content );
		return $this->format_string( $additional_content );
	}
}
