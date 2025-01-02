<?php
/**
 * Student registration email to admin class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Masteriyo\Abstracts\Email;

/**
 * Student registration email to admin class. Used for sending new account email.
 *
 * @since 1.15.0
 *
 * @package Masteriyo\Emails
 */
class StudentRegistrationEmailToAdmin extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'student-registration/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/student-registration.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\User|int $user_id
	 */
	public function trigger( $user_id ) {
		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$student = masteriyo_get_user( $user_id );

		// Bail early if student doesn't exist.
		if ( is_wp_error( $student ) ) {
			return;
		}

		$this->set_recipients( $admin_email );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );

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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.student_registration.enable' ) );
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
		 * Filter student registration email subject to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters(
			$this->get_full_id(),
			masteriyo_get_default_email_contents()['admin']['student_registration']['subject']
		);

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
		 * Filter student registration email heading to student.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.admin.student_registration.heading' ) );

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
		 * Filter student registration email additional content to student.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.admin.student_registration.additional_content' ) );

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
		$content = masteriyo_get_default_email_contents()['admin']['student_registration']['content'];
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
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
		$student = $this->get( 'student' );

		if ( $student ) {
			$name = trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) );

			$placeholders = $placeholders + array(
				'{student_display_name}'    => $student->get_display_name(),
				'{student_first_name}'      => $student->get_first_name(),
				'{student_last_name}'       => $student->get_last_name(),
				'{student_username}'        => $student->get_username(),
				'{student_nicename}'        => $student->get_nicename(),
				'{student_nickname}'        => $student->get_nickname(),
				'{student_name}'            => ! empty( $name ) ? $name : $student->get_display_name(),
				'{student_registered_date}' => gmdate( 'd M Y', $student->get_date_created( 'edit' )->getOffsetTimestamp() ),
			);
		}

		return $placeholders;
	}
}
