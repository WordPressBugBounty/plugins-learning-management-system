<?php
/**
 * New lesson comment to instructor email class.
 *
 * @package Masteriyo\Emails
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewLessonCommentEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @var string
	 */
	protected $id = 'new-lesson-comment/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/new-lesson-comment.php';

	/**
	 * Send this email.
	 *
	 * @param \Masteriyo\Models\LessonReview $comment Comment object.
	 */
	public function trigger( $comment ) {
		// Bail early if comment doesn't exist.
		if ( ! $comment || $comment->is_reply() ) {
			return;
		}

		$lesson = masteriyo_get_lesson( $comment->get_lesson_id() );
		if ( ! $lesson ) {
			return;
		}

		$course = masteriyo_get_course( $lesson->get_course_id() );
		if ( ! $course ) {
			return;
		}

		// Don't send email if the commenter is the course author
		if ( $course->get_author_id() === $comment->get_author_id() ) {
			return;
		}

		$student = masteriyo_get_user( $comment->get_author_id() );

		$recipients = array();
		$author     = masteriyo_get_user( $course->get_author_id() );
		if ( $author ) {
			$recipients[] = $author->get_email();
		}

		// Courses can have more than one author.
		$additional_authors = $course->get_meta( '_additional_authors', false );
		if ( ! empty( $additional_authors ) ) {
			foreach ( $additional_authors as $additional_author_id ) {
				// Don't send to additional author if they are the commenter.
				if ( (int) $additional_author_id === (int) $comment->get_author_id() ) {
					continue;
				}
				$additional_author = masteriyo_get_user( $additional_author_id );
				if ( $additional_author ) {
					$recipients[] = $additional_author->get_email();
				}
			}
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$this->set_recipients( array_unique( $recipients ) );
		$this->set( 'comment', $comment );
		$this->set( 'lesson', $lesson );
		$this->set( 'course', $course );
		$this->set( 'student', $student );
		$this->set( 'instructor', $author ); // For placeholder format_string

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
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = masteriyo_get_setting( 'emails.instructor.new_lesson_comment.enable' );
		if ( is_null( $enabled ) ) {
			return true;
		}
		return masteriyo_string_to_bool( $enabled );
	}

	/**
	 * Return subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';

		if ( empty( $subject ) ) {
			$subject = masteriyo_get_default_email_contents()['instructor']['new_lesson_comment']['subject'];
		}

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.new_lesson_comment.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['instructor']['new_lesson_comment']['content'];
		}

		$content = $this->format_string( $content );
		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User|null $instructor */
		$instructor = $this->get( 'instructor' );

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\Lesson|null $lesson */
		$lesson = $this->get( 'lesson' );

		/** @var \Masteriyo\Models\LessonReview|null $comment */
		$comment = $this->get( 'comment' );

		if ( $instructor ) {
			$placeholders = $placeholders + array(
				'{instructor_first_name}' => $instructor->get_first_name(),
				'{instructor_last_name}'  => $instructor->get_last_name(),
				'{instructor_name}'       => $instructor->get_display_name(),
				'{instructor_email}'      => $instructor->get_email(),
			);
		}

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{comment_author_name}'  => $student->get_display_name(),
				'{student_display_name}' => $student->get_display_name(),
				'{student_first_name}'   => $student->get_first_name(),
				'{student_last_name}'    => $student->get_last_name(),
				'{student_username}'     => $student->get_username(),
				'{student_name}'         => '' !== trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) ? trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ) : $student->get_username(),
			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}' => $course->get_name(),
				'{course_url}'  => $course->get_permalink(),
			);
		}

		if ( $lesson ) {
			$placeholders = $placeholders + array(
				'{lesson_name}' => $lesson->get_name(),
				'{lesson_url}'  => $lesson->get_learn_url(),
			);
		}

		if ( $comment ) {
			$placeholders = $placeholders + array(
				'{comment_content}' => wp_strip_all_tags( $comment->get_content() ),
				'{comment_date}'    => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $comment->get_date_created()->getTimestamp() ),
				'{comment_link}'    => wp_kses_post(
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/reviews/' . $comment->get_id() . '/edit?reviewType=lesson' ) . '" style="text-decoration: none;">View Comment</a>'
				),
			);
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
