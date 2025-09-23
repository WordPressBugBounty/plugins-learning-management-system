<?php
/**
 * New question reply to student email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.0.0
 */

namespace Masteriyo\Emails\Student;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewQuestionReplyEmailToStudent extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $id = 'new-question-reply/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/student/new-question-reply.php';

	/**
	 * Send this email.
	 *
	 * @since 2.0.0
	 *
	 * @param \Masteriyo\Models\CourseQuestionAnswer $reply Reply object.
	 */
	public function trigger( $reply ) {
		// Bail early if reply doesn't exist or it's not actually a reply.
		if ( ! $reply || ! $reply->is_answer() ) {
			return;
		}

		// Get the original question
		$question = masteriyo_get_course_qa( $reply->get_parent() );
		if ( ! $question ) {
			return;
		}

		$course = masteriyo_get_course( $reply->get_course_id() );

		if ( ! $course ) {
			return;
		}

		$question_author = masteriyo_get_user( $question->get_user_id() );
		$reply_author    = masteriyo_get_user( $reply->get_user_id() );

		// Only send email to the question author (student who asked the question)
		if ( ! $question_author || ! $question_author->get_email() ) {
			return;
		}

		$this->set_recipients( array( $question_author->get_email() ) );
		$this->set( 'reply', $reply );
		$this->set( 'question', $question );
		$this->set( 'course', $course );
		$this->set( 'question_author', $question_author );
		$this->set( 'reply_author', $reply_author );

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
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.new_question_reply.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.student.new_question_reply.subject' ) );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_heading() {
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.new_question_reply.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.new_question_reply.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.student.new_question_reply.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.student.new_question_reply.content' ) );
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $question_author */
		$question_author = $this->get( 'question_author' );

		/** @var \Masteriyo\Models\User|null $reply_author */
		$reply_author = $this->get( 'reply_author' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\CourseQuestionAnswer|null $question */
		$question = $this->get( 'question' );

		/** @var \Masteriyo\Models\CourseQuestionAnswer|null $reply */
		$reply = $this->get( 'reply' );

		if ( $question_author ) {
			$placeholders = $placeholders + array(
				'{student_display_name}' => $question_author->get_display_name(),
				'{student_first_name}'   => $question_author->get_first_name(),
				'{student_last_name}'    => $question_author->get_last_name(),
				'{student_username}'     => $question_author->get_username(),
				'{student_nicename}'     => $question_author->get_nicename(),
				'{student_nickname}'     => $question_author->get_nickname(),
				'{student_name}'         => '' !== trim( sprintf( '%s %s', $question_author->get_first_name(), $question_author->get_last_name() ) ) ? trim( sprintf( '%s %s', $question_author->get_first_name(), $question_author->get_last_name() ) ) : $question_author->get_username(),
				'{student_email}'        => $question_author->get_email(),
			);
		}

		if ( $reply_author ) {
			$placeholders = $placeholders + array(
				'{reply_author_display_name}' => $reply_author->get_display_name(),
				'{reply_author_first_name}'   => $reply_author->get_first_name(),
				'{reply_author_last_name}'    => $reply_author->get_last_name(),
				'{reply_author_username}'     => $reply_author->get_username(),
				'{reply_author_nicename}'     => $reply_author->get_nicename(),
				'{reply_author_nickname}'     => $reply_author->get_nickname(),
				'{reply_author_name}'         => '' !== trim( sprintf( '%s %s', $reply_author->get_first_name(), $reply_author->get_last_name() ) ) ? trim( sprintf( '%s %s', $reply_author->get_first_name(), $reply_author->get_last_name() ) ) : $reply_author->get_username(),
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
			);
		}

		if ( $reply ) {
			$placeholders = $placeholders + array(
				'{reply_content}' => wp_strip_all_tags( $reply->get_content() ),
				'{reply_date}'    => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $reply->get_created_at()->getTimestamp() ),
				'{reply_link}'    => wp_kses_post(
					'<a href="' . $course->start_course_url() . '" style="text-decoration: none;">View Reply</a>'
				),
			);
		}

		return $placeholders;
	}
}
