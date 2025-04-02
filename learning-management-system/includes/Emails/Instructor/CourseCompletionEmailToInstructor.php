<?php
/**
 * Course completion to instructor email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class CourseCompletionEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'course-completion/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/course-completion.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\CourseProgress $course_progress User course object.
	 */
	public function trigger( $course_progress ) {
		$course = masteriyo_get_course( $course_progress->get_course_id() );

		$instructors            = array( $course->get_author_id() );
		$additional_instructors = $course->get_meta( '_additional_authors', false );

		if ( $additional_instructors ) {
			$instructors = array_merge( $instructors, $additional_instructors );
		}

		$instructors = array_filter(
			$instructors,
			function( $user_id ) {
				return masteriyo_get_setting( 'emails.admin.course_completion.enable' ) ? ! masteriyo_is_user_admin( $user_id ) : true;
			}
		);

		$instructor_emails = array_map(
			function( $user_id ) {
				return get_the_author_meta( 'user_email', $user_id );
			},
			$instructors
		);

		$this->set_recipients( $instructor_emails );

		$student = masteriyo_get_user( $course_progress->get_user_id() );

		$this->set( 'course_progress', $course_progress );
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
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.course_completion.enable' ) );
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
		 * Filter course completion email subject to instructor.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_default_email_contents()['instructor']['course_completion']['subject'] );

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
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.course_completion.heading' ) );

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
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.course_completion.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.instructor.course_completion.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.instructor.course_completion.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['instructor']['course_completion']['content'] );
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

			$instructor = masteriyo_get_user( absint( $course->get_author_id() ) );

			if ( $instructor ) {
				$placeholders = $placeholders + array(
					'{instructor_display_name}' => $instructor->get_display_name(),
					'{instructor_first_name}'   => $instructor->get_first_name(),
					'{instructor_last_name}'    => $instructor->get_last_name(),
					'{instructor_username}'     => $instructor->get_username(),
					'{instructor_nicename}'     => $instructor->get_nicename(),
					'{instructor_nickname}'     => $instructor->get_nickname(),
					'{instructor_name}'         => sprintf( '%s %s', $instructor->get_first_name(), $instructor->get_last_name() ) ?? $instructor->get_username(),
				);
			}
		}

		return $placeholders;
	}
}
