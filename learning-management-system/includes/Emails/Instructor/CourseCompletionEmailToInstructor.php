<?php
/**
 * Course start to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.6.10
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class CourseCompletionEmailToInstructor extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	protected $id = 'course-completion/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/course-completion.php';

	/**
	 * Send this email.
	 *
	 * @since 2.6.10
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

		$to_addresses_setting = masteriyo_get_setting( 'emails.instructor.course_completion.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{instructor_email}', implode( ', ', $instructor_emails ), $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $instructor_emails );

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
	 * @since 2.6.10
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.course_completion.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter course start email subject to admin.
		 *
		 * @since 2.6.10
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.instructor.course_completion.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['instructor']['course_completion']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter course start email heading to admin.
		 *
		 * @since 2.6.10
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.course_completion.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter course start email additional content to admin.
		 *
		 * @since 2.6.10
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
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.course_completion.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.course_completion.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['instructor']['course_completion']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.6.10
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
				);
			}
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter student registration email reply_to_name to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.instructor.course_completion.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter student registration email reply_to_address to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.instructor.course_completion.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter student registration email from_name to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.course_completion.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter student registration email from_address to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.course_completion.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
