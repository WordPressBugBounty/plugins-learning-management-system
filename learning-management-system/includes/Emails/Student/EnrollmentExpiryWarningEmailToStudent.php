<?php
/**
 * EnrollmentExpiryWarningEmailToStudent class.
 *
 * @package Masteriyo\Emails
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Warns a student that their course access ends soon.
 *
 * @package Masteriyo\Emails
 */
class EnrollmentExpiryWarningEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @var string
	 */
	protected $id = 'enrollment-expiry-warning/to/student';

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	public $html_template = 'emails/email-template.php';

	/**
	 * Send this email.
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course object.
	 * @param int                          $expires_at  Unix timestamp the access ends at.
	 *
	 * @return bool Whether the email was sent.
	 */
	public function trigger( $user_course, $expires_at ) {
		$student = masteriyo_get_user( $user_course->get_user_id() );
		$course  = masteriyo_get_course( $user_course->get_course_id() );

		if ( ! $student || is_wp_error( $student ) || ! $course ) {
			return false;
		}

		$this->set( 'course', $course );
		$this->set( 'student', $student );
		$this->set( 'expires_at', $expires_at );

		$to_address = str_replace( '{student_email}', $student->get_email(), (string) $this->setting( 'to_address' ) );

		$this->set_recipients( '' !== trim( $to_address ) ? explode( ',', $to_address ) : $student->get_email() );
		$this->setup_locale();

		return (bool) $this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Read one of this email's settings.
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed
	 */
	private function setting( $key ) {
		return masteriyo_get_setting( 'emails.student.enrollment_expiry_warning.' . $key );
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( $this->setting( 'enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = trim( (string) apply_filters( $this->get_full_id(), $this->setting( 'subject' ) ) );
		$subject = '' === $subject ? masteriyo_get_default_email_contents()['student']['enrollment_expiry_warning']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return $this->format_string( apply_filters( $this->get_full_id() . '_heading', $this->setting( 'heading' ) ) );
	}

	/**
	 * Return additional content.
	 *
	 * @return string
	 */
	public function get_additional_content() {
		return $this->format_string( apply_filters( $this->get_full_id() . '_additional_content', $this->setting( 'additional_content' ) ) );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.student.enrollment_expiry_warning.content', 'masteriyo-email-message', $this->setting( 'content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( '' === $content ) {
			$content = masteriyo_get_default_email_contents()['student']['enrollment_expiry_warning']['content'];
		}

		$this->set( 'content', $this->format_string( $content ) );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{student_display_name}' => $student->get_display_name(),
				'{student_first_name}'   => empty( $student->get_first_name() ) ? $student->get_display_name() : $student->get_first_name(),
				'{student_email}'        => $student->get_email(),
				'{account_login_link}'   => wp_kses_post(
					'<a href="' . $this->get_account_url() . '" class="email-template--button">' . __( 'Login to Your Account', 'learning-management-system' ) . '</a>'
				),
			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}' => $course->get_name(),
				'{course_url}'  => $course->get_permalink(),
			);
		}

		if ( $this->get( 'expires_at' ) ) {
			$placeholders['{expiry_date}'] = wp_date( get_option( 'date_format' ), $this->get( 'expires_at' ) );
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		$reply_to_name = trim( (string) $this->setting( 'reply_to_name' ) );

		return '' !== $reply_to_name ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @return string
	 */
	public function get_reply_to_address() {
		$reply_to_address = trim( (string) $this->setting( 'reply_to_address' ) );

		return '' !== $reply_to_address ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = trim( (string) $this->setting( 'from_name' ) );

		return '' !== $from_name ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @param string $from_address Unused.
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		$from_address = trim( (string) $this->setting( 'from_address' ) );

		return '' !== $from_address ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
