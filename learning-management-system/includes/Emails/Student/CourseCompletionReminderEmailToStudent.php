<?php
/**
 * CourseCompletionReminderEmailToStudent class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.6.10
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * CourseCompletionReminderEmailToStudent Class. Used for sending password reset email.
 *
 * @since 2.6.10
 *
 * @package Masteriyo\Emails
 */
class CourseCompletionReminderEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	protected $id = 'course-completion-reminder/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	public $html_template = 'emails/student/course-completion-reminder.php';

	/**
	 * Send this email.
	 *
	 * @since 2.6.10
	 *
	 * @param int $user_id User id.
	 * @param int $course_id Course Id.
	 */
	public function trigger( $user_id, $course_id ) {
		$student = masteriyo_get_user( $user_id );
		$course  = masteriyo_get_course( $course_id );

		if ( ! $student || ! $course ) {
			return;
		}

		$remaining_days = masteriyo_get_remaining_time_for_single_course( $user_id, $course_id, false, true );

		// The helper returns null both when access expired and when the course
		// has no enrollment expiration at all. Only the first case blocks the
		// reminder — otherwise unlimited-access courses never get one.
		$enrollment_expired = $course->get_enrollment_expiration_enabled() && is_null( $remaining_days );

		if ( ! masteriyo_is_user_already_enrolled( $user_id, $course_id, 'active' ) || masteriyo_user_has_completed_course( $course_id, $user_id ) || $enrollment_expired ) {
			return;
		}

		$this->set( 'course', $course );
		$this->set( 'student', $student );

		$student_email        = $student->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.student.course_completion_reminder.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{student_email}', $student_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
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
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_subject() {

		/**
		 * Filter course completion email subject to student.
		 *
		 * @since 2.6.10
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.student.course_completion_reminder.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['student']['course_completion_reminder']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @since 2.6.10
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.course_completion_reminder.enable' ) );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter course completion email heading to instructor.
		 *
		 * @since 2.6.10
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.course_completion_reminder.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter course completion email additional content to instructor.
		 *
		 * @since 2.6.10
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.course_completion_reminder.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.student.course_completion_reminder.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.student.course_completion_reminder.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['student']['course_completion_reminder']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.6.10
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
				'{student_last_name}'    => empty( $student->get_last_name() ) ? $student->get_display_name() : $student->get_last_name(),
				'{student_name}'         => '' !== trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) ? trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) : $student->get_display_name(),
				'{student_username}'     => $student->get_username(),
				'{student_nicename}'     => $student->get_nicename(),
				'{student_nickname}'     => $student->get_nickname(),
				'{student_email}'        => $student->get_email(),
				'{account_login_link}'   => wp_kses_post(
					'<a href="' . $this->get_account_url() . '" class="email-template--button">' . __( 'Login to Your Account', 'learning-management-system' ) . '</a>'
				),
				'{my_courses_url}'       => masteriyo_get_learner_home_url( $course, $student->get_id() ),
			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}' => $course->get_name(),
				'{course_url}'  => $course->get_permalink(),
			);
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
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.student.course_completion_reminder.reply_to_name' ) );
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
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.student.course_completion_reminder.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

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
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.course_completion_reminder.from_name' ) );
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
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.course_completion_reminder.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
