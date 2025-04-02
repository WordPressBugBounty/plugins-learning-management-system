<?php
/**
 * Student registration email to student class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Student registration email to student class. Used for sending new account email.
 *
 * @since 1.15.0
 *
 * @package Masteriyo\Emails
 */
class AutomaticRegistrationEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'automatic-registration/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/student/student-registration.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\User $student
	 * @param string $password_generated The generated password.
	 */
	public function trigger( $student, $password_generated, $reset_key ) {
		$student = masteriyo_get_user( $student );

		// Bail early if student doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$student_email = $student->get_email();

		$this->set_recipients( $student_email );
		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );
		$this->set( 'password_generated', $password_generated );
		$this->set( 'reset_link', esc_url( masteriyo_get_password_reset_link( $reset_key, $student->get_id() ) ) );

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
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.automatic_registration.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter automatic registration email subject to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['student']['automatic_registration']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter automatic registration email heading to student.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.automatic_registration.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter automatic registration email additional content to student.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.automatic_registration.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.student.automatic_registration.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.6.9
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.student.automatic_registration.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['student']['automatic_registration']['content'] );
		$content = $this->format_string( $content );

		$this->set( 'content', trim( $content ) );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.6.9
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
			$placeholders['{generated_password}']   = $this->get( 'password_generated' );
			$placeholders['{password_reset_link}']  = wp_kses_post(
				'<a href="' . $this->get( 'reset_link' ) . '" style="text-decoration: none;">Reset Your Password</a>'
			);
		}

		return $placeholders;
	}
}
