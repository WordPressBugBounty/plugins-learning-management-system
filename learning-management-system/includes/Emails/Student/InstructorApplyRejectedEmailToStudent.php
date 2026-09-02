<?php
/**
 * Instructor apply rejected email to student class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.6.13
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Instructor apply rejected email to student class. Used for sending new account email.
 *
 * @since 1.6.13
 *
 * @package Masteriyo\Emails
 */
class InstructorApplyRejectedEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.6.13
	 *
	 * @var String
	 */
	protected $id = 'instructor-apply-rejected/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 1.6.13
	 *
	 * @var string
	 */
	protected $html_template = 'emails/student/instructor-apply-rejected.php';

	/**
	 * Send this email.
	 *
	 * @since 1.6.13
	 *
	 * @param \Masteriyo\Models\User $student
	 */
	public function trigger( $student ) {
		$student = masteriyo_get_user( $student );

		// Bail early if student doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$student_email        = $student->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.student.instructor_apply_rejected.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{student_email}', $student_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $student_email );
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
	 * @since 1.6.13
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.instructor_apply_rejected.enable' ) );
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
		 * Filter Instructor apply rejected email subject to student.
		 *
		 * @since 1.6.13
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['student']['instructor_apply_rejected']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter Instructor apply rejected email heading to student.
		 *
		 * @since 1.6.13
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.heading' ) );

		return $this->format_string( $heading );
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
		 * Filter Instructor apply rejected email additional content to student.
		 *
		 * @since 1.6.13
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.student.instructor_apply_rejected.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.student.instructor_apply_rejected.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['student']['instructor_apply_rejected']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

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
			$placeholders['{student_name}']         = '' !== trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) ? trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) : $student->get_display_name();
			$placeholders['{student_username}']     = $student->get_username();
			$placeholders['{student_nicename}']     = $student->get_nicename();
			$placeholders['{student_nickname}']     = $student->get_nickname();
			$placeholders['{student_email}']        = $student->get_email();
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter student registration email reply_to_name to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter student registration email reply_to_address to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.reply_to_address' ) );

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter student registration email from_name to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter student registration email from_address to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.instructor_apply_rejected.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
