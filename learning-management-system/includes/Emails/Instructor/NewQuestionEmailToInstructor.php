<?php
/**
 * New question to instructor email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.0.0 [Free]
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewQuestionEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @var string
	 */
	protected $id = 'new-question/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/new-question.php';

	/**
	 * Send this email.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param \Masteriyo\Models\CourseQuestionAnswer $question Question object.
	 */
	public function trigger( $question ) {
		// Bail early if question doesn't exist.
		if ( ! $question || $question->is_answer() ) {
			return;
		}

		$course = masteriyo_get_course( $question->get_course_id() );

		if ( ! $course ) {
			return;
		}

		$student = masteriyo_get_user( $question->get_user_id() );

		// Get instructors for this course
		$instructors            = array( $course->get_author_id() );
		$additional_instructors = $course->get_meta( '_additional_authors', false );

		if ( $additional_instructors ) {
			$instructors = array_merge( $instructors, $additional_instructors );
		}

		// No admin email for questions, so send to all instructors including admins

		$instructor_emails = array_map(
			function( $user_id ) {
				return get_the_author_meta( 'user_email', $user_id );
			},
			$instructors
		);

		$to_addresses_setting = masteriyo_get_setting( 'emails.instructor.new_question.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{instructor_email}', implode( ', ', $instructor_emails ), $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $instructor_emails );
		$this->set( 'question', $question );
		$this->set( 'course', $course );
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
	 * @since 2.0.0 [Free]
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.new_question.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.instructor.new_question.subject' ) );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return string
	 */
	public function get_heading() {
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.new_question.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.new_question.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.new_question.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.new_question.content' ) );
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\CourseQuestionAnswer|null $question */
		$question = $this->get( 'question' );

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
					'{instructor_email}'        => $instructor->get_email(),
				);
			}
		}

		if ( $question ) {
			$placeholders = $placeholders + array(
				'{question_content}' => wp_strip_all_tags( $question->get_content() ),
				'{question_date}'    => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $question->get_created_at()->getTimestamp() ),
				'{question_link}'    => wp_kses_post(
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/question-answers' ) . '" class="email-template--button">View Question</a>'
				),
			);
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		$reply_to_name = apply_filters( $this->get_full_id() . '_reply_to_name', masteriyo_get_setting( 'emails.instructor.new_question.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		$reply_to_address = apply_filters( $this->get_full_id() . '_reply_to_address', masteriyo_get_setting( 'emails.instructor.new_question.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.new_question.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.new_question.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
