<?php
/**
 * New lesson comment reply to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since x.x.x
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class NewLessonCommentReplyEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $id = 'new-lesson-comment-reply/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/new-lesson-comment-reply.php';

	/**
	 * Send this email.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\LessonReview $reply Reply object.
	 */
	public function trigger( $reply ) {
		// Bail early if reply doesn't exist.
		if ( ! $reply || ! $reply->is_reply() ) {
			return;
		}

		$parent_comment = masteriyo_get_lesson_review( $reply->get_parent() );
		if ( ! $parent_comment ) {
			return;
		}

		$lesson = masteriyo_get_lesson( $reply->get_lesson_id() );
		if ( ! $lesson ) {
			return;
		}

		$course       = masteriyo_get_course( $lesson->get_course_id() );
		$reply_author = masteriyo_get_user( $reply->get_author_id() );

		$to_addresses_setting = masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.to_address' );
		$admin_email          = get_option( 'admin_email' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{admin_email}', $admin_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : array( $admin_email ) );
		$this->set( 'reply', $reply );
		$this->set( 'comment', $parent_comment );
		$this->set( 'lesson', $lesson );
		$this->set( 'course', $course );
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
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.enable' );
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
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.subject' ) );

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
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.heading' ) );

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
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.additional_content' ) );

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
		$content = masteriyo_string_translation( 'emails.admin.new_lesson_comment_reply.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.admin.new_lesson_comment_reply.content' ) );
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

		/** @var \Masteriyo\Models\User|null $reply_author */
		$reply_author = $this->get( 'reply_author' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		/** @var \Masteriyo\Models\Lesson|null $lesson */
		$lesson = $this->get( 'lesson' );

		/** @var \Masteriyo\Models\LessonReview|null $comment */
		$comment = $this->get( 'comment' );

		/** @var \Masteriyo\Models\LessonReview|null $reply */
		$reply = $this->get( 'reply' );

		if ( $reply_author ) {
			$placeholders = $placeholders + array(
				'{reply_author_name}' => $reply_author->get_display_name(),
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
			);
		}

		if ( $reply ) {
			$placeholders = $placeholders + array(
				'{reply_content}' => wp_strip_all_tags( $reply->get_content() ),
				'{reply_date}'    => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $reply->get_date_created()->getTimestamp() ),
				'{reply_link}'    => wp_kses_post(
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/reviews' ) . '" style="text-decoration: none;">View Reply</a>'
				),
			);
		}

		return $placeholders;
	}
}
