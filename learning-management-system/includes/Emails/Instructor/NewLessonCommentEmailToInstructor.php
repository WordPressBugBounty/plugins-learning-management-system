<?php
/**
 * New lesson comment to instructor email class.
 *
 * @package Masteriyo\Emails
 *
 * @since x.x.x
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewLessonCommentEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $id = 'new-lesson-comment/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/new-lesson-comment.php';

	/**
	 * Send this email.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.instructor.new_lesson_comment.subject' ) );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.new_lesson_comment.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.new_lesson_comment.content' ) );
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since x.x.x
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
					'<a href="' . $lesson->get_learn_url() . '" style="text-decoration: none;">View Comment</a>'
				),
			);
		}

		return $placeholders;
	}
}
