<?php
/**
 * New lesson comment to admin email class.
 *
 * @package Masteriyo\Emails
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewLessonCommentEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @var string
	 */
	protected $id = 'new-lesson-comment/to/admin';

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/new-lesson-comment.php';

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

		$course  = masteriyo_get_course( $lesson->get_course_id() );
		$student = masteriyo_get_user( $comment->get_author_id() );

		$to_addresses_setting = masteriyo_get_setting( 'emails.admin.new_lesson_comment.to_address' );
		$admin_email          = get_option( 'admin_email' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{admin_email}', $admin_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$recipients = ! empty( $to_address ) ? $to_address : array( $admin_email );

		// Prevent duplicate emails when the admin and instructor share the same email address.
		if ( $course ) {
			$instructor = masteriyo_get_user( $course->get_author_id() );
			if ( $instructor ) {
				$instructor_email = $instructor->get_email();
				$recipients       = array_values(
					array_filter(
						$recipients,
						function( $email ) use ( $instructor_email ) {
							return sanitize_email( trim( $email ) ) !== $instructor_email;
						}
					)
				);
			}
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$this->set_recipients( $recipients );
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
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.admin.new_lesson_comment.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';

		if ( empty( $subject ) ) {
			$subject = masteriyo_get_default_email_contents()['admin']['new_lesson_comment']['subject'];
		}

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
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
	 * @return string
	 */
	public function get_additional_content() {
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.new_lesson_comment.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.new_lesson_comment.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.admin.new_lesson_comment.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['admin']['new_lesson_comment']['content'];
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
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.admin.new_lesson_comment.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.admin.new_lesson_comment.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.admin.new_lesson_comment.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.admin.new_lesson_comment.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
