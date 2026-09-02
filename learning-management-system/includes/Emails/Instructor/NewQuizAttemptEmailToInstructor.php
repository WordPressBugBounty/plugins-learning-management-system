<?php
/**
 * Quiz Attempt to instructor email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.12.0
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewQuizAttemptEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $id = 'quiz-attempt/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/quiz-attempt.php';

	/**
	 * Send this email.
	 *
	 * @since 2.12.0
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course object.
	 */
	public function trigger( $course_progress ) {
		$instructor_email = get_bloginfo( 'instructor_email' );

		// Bail early if order doesn't exist.
		if ( empty( $instructor_email ) ) {
			return;
		}

		$new_quiz_attempt = masteriyo_get_quiz_attempt( $course_progress );

		$course = masteriyo_get_course( $new_quiz_attempt->get_course_id() );
		$user   = masteriyo_get_user( $new_quiz_attempt->get_user_id() );
		$quiz   = masteriyo_get_quiz( $new_quiz_attempt->get_quiz_id() );

		$instructors            = array( $course->get_author_id() );
		$additional_instructors = $course->get_meta( '_additional_authors', false );

		if ( $additional_instructors ) {
			$instructors = array_merge( $instructors, $additional_instructors );
		}

		$instructors = array_filter(
			$instructors,
			function( $user_id ) {
				return masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.enable' ) ? ! masteriyo_is_user_admin( $user_id ) : true;
			}
		);

		$instructor_emails = array_map(
			function( $user_id ) {
				return get_the_author_meta( 'user_email', $user_id );
			},
			$instructors
		);

		$to_addresses_setting = masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{instructor_email}', implode( ', ', $instructor_emails ), $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $instructor_emails );
		$this->set( 'course_progress', $course_progress );
		$this->set( 'course', $course );
		$this->set( 'student', $user );
		$this->set( 'quiz', $quiz );
		$this->set( 'quiz_attempt', $new_quiz_attempt );

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
	 * @since 2.12.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter course start email subject to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.subject' ) );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter course start email heading to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter course start email additional content to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.instructor.new_quiz_attempt.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.new_quiz_attempt.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.content' ) );
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.12.0
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\Quiz|null $quiz */
		$quiz = $this->get( 'quiz' );

		/** @var \Masteriyo\Models\QuizAttempt|null $quiz_attempt */
		$quiz_attempt = $this->get( 'quiz_attempt' );

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{student_display_name}' => $student->get_display_name(),
				'{student_first_name}'   => $student->get_first_name(),
				'{student_last_name}'    => $student->get_last_name(),
				'{student_username}'     => $student->get_username(),
				'{student_nicename}'     => $student->get_nicename(),
				'{student_nickname}'     => $student->get_nickname(),
				'{student_name}'         => '' !== trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) ? trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) : $student->get_username(),

			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}' => $course->get_name(),
				'{course_url}'  => $course->get_permalink(),
			);

			$instructor = masteriyo_get_user( absint( $course->get_author_id() ) );

			if ( $instructor ) {
				$placeholders = $placeholders + array(
					'{instructor_display_name}' => $instructor->get_display_name(),
					'{instructor_first_name}'   => $instructor->get_first_name(),
					'{instructor_last_name}'    => $instructor->get_last_name(),
					'{instructor_username}'     => $instructor->get_username(),
					'{instructor_nicename}'     => $instructor->get_nicename(),
					'{instructor_nickname}'     => $instructor->get_nickname(),
					'{instructor_name}'         => '' !== trim( sprintf( '%s %s', $instructor->get_first_name(), $instructor->get_last_name() ) ) ? trim( sprintf( '%s %s', $instructor->get_first_name(), $instructor->get_last_name() ) ) : $instructor->get_username(),
				);
			}
		}

		if ( $quiz ) {
			$placeholders = $placeholders + array(
				'{quiz_name}' => $quiz->get_title(),
			);
		}

		if ( $quiz_attempt ) {
			$placeholders = $placeholders + array(
				'{quiz_attempt_review_link}' => wp_kses_post(
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/quiz-attempts/' ) . $quiz_attempt->get_id() . '" class="email-template--button">Review Quiz Attempt</a>'
				),
			);
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter student registration email reply_to_name to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter student registration email reply_to_address to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter student registration email from_name to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter student registration email from_address to instructor.
		 *
		 * @since 2.12.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.new_quiz_attempt.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
