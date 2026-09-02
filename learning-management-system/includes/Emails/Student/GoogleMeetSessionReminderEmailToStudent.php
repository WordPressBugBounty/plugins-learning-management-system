<?php
/**
 * GoogleMeetSessionReminderEmailToStudent class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.30.0
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * GoogleMeetSessionReminderEmailToStudent Class. Used for sending Google Meet session reminder email.
 *
 * @since 2.30.0
 *
 * @package Masteriyo\Emails
 */
class GoogleMeetSessionReminderEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 2.30.0
	 *
	 * @var string
	 */
	protected $id = 'google-meet-session-reminder/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 2.30.0
	 *
	 * @var string
	 */
	public $html_template = 'emails/student/google-meet-session-reminder.php';

	/**
	 * Send this email.
	 *
	 * Validates input parameters, checks student enrollment status, and sends the reminder
	 * email if all conditions are met. The email is only sent to enrolled, active students.
	 *
	 * @since 2.30.0
	 *
	 * @param int $student_id Student id.
	 * @param int $session_id Google Meet session id.
	 *
	 * @return void
	 */
	public function trigger( $student_id, $session_id ) {
		// Validate input parameters.
		if ( ! is_numeric( $student_id ) || $student_id <= 0 ) {
			return;
		}

		if ( ! is_numeric( $session_id ) || $session_id <= 0 ) {
			return;
		}

		$student = masteriyo_get_user( absint( $student_id ) );
		$session = masteriyo_get_google_meet( absint( $session_id ) );

		if ( ! $student || ! $session ) {
			return;
		}

		// Check if student is enrolled in the course.
		$course_id = $session->get_course_id();
		if ( ! masteriyo_is_user_already_enrolled( $student_id, $course_id, 'active' ) ) {
			return;
		}

		$this->set( 'session', $session );
		$this->set( 'student', $student );
		$this->set( 'course', masteriyo_get_course( $course_id ) );

		$student_email        = $student->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.student.google_meet_session_reminder.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{student_email}', $student_email, $to_addresses_setting );
			$to_address_raw       = explode( ',', $to_addresses_setting );

			// Validate and sanitize each email address.
			$to_address = array_filter(
				array_map(
					function( $email ) {
						$email = trim( $email );
						$email = sanitize_email( $email );
						return is_email( $email ) ? $email : false;
					},
					$to_address_raw
				)
			);
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $student_email );
		$this->setup_locale();

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Return subject.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_subject() {

		/**
		 * Filter google meet session reminder email subject to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.student.google_meet_session_reminder.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? __( 'Reminder: Your live session "{session_title}" starts soon', 'learning-management-system' ) : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @since 2.30.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.google_meet_session_reminder.enable' ) );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter google meet session reminder email heading to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter google meet session reminder email additional content to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.student.google_meet_session_reminder.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['student']['google_meet_session_reminder']['content'] ?? '';
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * Returns an array of placeholders that can be used in the email subject and content.
	 * Placeholders are automatically replaced with their corresponding values when the
	 * email is sent.
	 *
	 * Available placeholders:
	 * - Student information: {student_display_name}, {student_first_name}, {student_last_name}, etc.
	 * - Course information: {course_name}, {course_url}, {course_learn_url}
	 * - Session information: {session_title}, {session_start_time}
	 * - Meeting information: {meeting_link}, {meeting_url}
	 * - Session navigation: {session_learn_url}, {session_learn_link}
	 *
	 * @since 2.30.0
	 *
	 * @return array Associative array of placeholders and their values.
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Addons\GoogleMeetIntegration\Models\GoogleMeet|null $session */
		$session = $this->get( 'session' );

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{student_display_name}' => $student->get_display_name(),
				'{student_first_name}'   => empty( $student->get_first_name() ) ? $student->get_display_name() : $student->get_first_name(),
				'{student_last_name}'    => empty( $student->get_last_name() ) ? $student->get_display_name() : $student->get_last_name(),
				'{student_name}'         => '' !== trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) ? trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) : $student->get_display_name(),
				'{student_username}'     => $student->get_username(),
				'{student_nicename}'     => $student->get_nicename(),
				'{student_nickname}'     => $student->get_nickname(),
				'{student_email}'        => $student->get_email(),
				'{account_login_link}'   => wp_kses_post(
					'<a href="' . $this->get_account_url() . '" class="email-template--button">Login to Your Account</a>'
				),
			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}'      => esc_html( $course->get_name() ),
				'{course_url}'       => esc_url( $course->get_permalink() ),
				'{course_learn_url}' => esc_url( $course->start_course_url( false ) ),
			);
		}

		// Add live session specific URL if both course and session are available.
		if ( $course && $session ) {
			// Generate direct link to the Google Meet session in the learn page.
			$learn_page_url = masteriyo_get_page_permalink( 'learn' );
			$session_url    = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();
			$session_url   .= '#/course/' . $course->get_id() . '/google-meet/' . $session->get_id();

			$placeholders['{session_learn_url}']  = esc_url( $session_url );
			$placeholders['{session_learn_link}'] = wp_kses_post( '<a href="' . esc_url( $session_url ) . '" class="email-template--button">Open in Course</a>' );
		}

		if ( $session ) {
			// Format session start time with timezone.
			$session_start_time = '';
			if ( $session->get_starts_at() ) {
				$timestamp = strtotime( $session->get_starts_at() );
				if ( $timestamp ) {
					$session_start_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' (T)', $timestamp );
				}
			}

			$placeholders = $placeholders + array(
				'{session_title}'      => esc_html( $session->get_name() ),
				'{session_start_time}' => esc_html( $session_start_time ),
				'{meeting_link}'       => wp_kses_post( '<a href="' . esc_url( $session->get_meet_url() ) . '" class="email-template--button">Join Meeting</a>' ),
				'{meeting_url}'        => esc_url( $session->get_meet_url() ),
			);
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter google meet session reminder email reply_to_name to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . '_reply_to_name', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter google meet session reminder email reply_to_address to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . '_reply_to_address', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter google meet session reminder email from_name to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter google meet session reminder email from_address to student.
		 *
		 * @since 2.30.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.google_meet_session_reminder.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
