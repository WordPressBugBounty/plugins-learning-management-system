<?php
/**
 * Course start to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class CourseStartEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'course-start/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/course-start.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course object.
	 */
	public function trigger( $user_course ) {
		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$course = $user_course->get_course();
		$user   = masteriyo_get_user( $user_course->get_user_id() );

		$this->set_recipients( $admin_email );
		$this->set( 'student', $user );
		$this->set( 'course', $course );

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
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.course_start.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter course start email subject to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_default_email_contents()['admin']['course_start']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter course start email heading to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.admin.course_start.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter course start email additional content to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.course_start.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.admin.course_start.additional_content', 'masteriyo-email-message', $additional_content );
		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.course_start.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['admin']['course_start']['content'] );
		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 1.15.0
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
				'{student_first_name}'   => $student->get_first_name(),
				'{student_last_name}'    => $student->get_last_name(),
				'{student_username}'     => $student->get_username(),
				'{student_nicename}'     => $student->get_nicename(),
				'{student_nickname}'     => $student->get_nickname(),
				'{student_name}'         => sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ?? $student->get_username(),
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
}
