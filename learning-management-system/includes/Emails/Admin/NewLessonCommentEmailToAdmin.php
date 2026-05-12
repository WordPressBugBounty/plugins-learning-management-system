<?php
/**
 * New lesson comment to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since x.x.x
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewLessonCommentEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $id = 'new-lesson-comment/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/new-lesson-comment.php';

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

		$course  = masteriyo_get_course( $lesson->get_course_id() );
		$student = masteriyo_get_user( $comment->get_author_id() );

		$to_addresses_setting = masteriyo_get_setting( 'emails.admin.new_lesson_comment.to_address' );
		$admin_email          = get_option( 'admin_email' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{admin_email}', $admin_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : array( $admin_email ) );
		$this->set( 'comment', $comment );
		$this->set( 'lesson', $lesson );
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
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = masteriyo_get_setting( 'emails.admin.new_lesson_comment.enable' );
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
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.admin.new_lesson_comment.subject' ) );

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
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.admin.new_lesson_comment.heading' ) );

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
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.new_lesson_comment.additional_content' ) );

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
		$content = masteriyo_string_translation( 'emails.admin.new_lesson_comment.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.admin.new_lesson_comment.content' ) );
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

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\Lesson|null $lesson */
		$lesson = $this->get( 'lesson' );

		/** @var \Masteriyo\Models\LessonReview|null $comment */
		$comment = $this->get( 'comment' );

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{comment_author_name}'  => $student->get_display_name(),
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
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/reviews' ) . '" style="text-decoration: none;">View Comment</a>'
				),
			);
		}

		return $placeholders;
	}
}
