<?php
/**
 * Group course enrollment email to the user class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Group course enrollment email to the user class.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Emails
 */
class GroupCourseEnrollmentEmailToNewMember extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.9.0
	 *
	 * @var String
	 */
	protected $id = 'group-course-enroll-email';

	/**
	 * HTML template path.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $html_template = 'group-courses/emails/group-course-enroll.php';

	/**
	 * Send this email.
	 *
	 * @since 1.9.0
	 *
	 * @param int $student_id User ID.
	 * @param int $group_id Group ID.
	 * @param int $course_id Course ID.
	 */
	public function trigger( $student_id, $group_id, $course_id ) {
		$student = masteriyo_get_user( $student_id );
		$group   = masteriyo_get_group( $group_id );
		$course  = masteriyo_get_course( $course_id );

		// Bail early if student or group doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) || is_wp_error( $group ) || is_null( $group ) || is_wp_error( $course ) || is_null( $course ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$this->set_recipients( $student->get_email() );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );
		$this->set( 'group', $group );
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
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		/**
		 * Filters boolean-like value: 'yes' if group course enrollment should be disabled, otherwise 'no'.
		 *
		 * @since 1.9.0
		 *
		 * @param string $is_disabled 'yes' if group course enrollment should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_group_course_enrollment_email_to_new_member', 'no' ) );

		return ! $is_disabled;
	}

	/**
	 * Return subject.
	 *
	 * @since 1.9.0s
	 *
	 * @return string
	 */
	public function get_subject() {
		$group  = $this->get( 'group' );
		$course = $this->get( 'course' );

		/* translators: %1$s: Group name, %2$s: Course name */
		$subject = sprintf( esc_html__( 'Welcome to %1$s! Your Journey in "%2$s" Begins', 'learning-management-system' ), esc_html( $group->get_title() ), esc_html( $course->get_name() ) );

		/**
		 * Filter group course enrollment subject to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', $subject );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter group course enrollment heading to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', '' );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter group course enrollment additional content to the user.
		 *
		 * @since 1.9.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', '' );

		return $this->format_string( $additional_content );
	}
}
