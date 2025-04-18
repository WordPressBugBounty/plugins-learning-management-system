<?php
/**
 * Instructor registration email to Instructor class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.5.35
 */

namespace Masteriyo\Emails\Instructor;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Instructor registration email class. Used for sending new account email.
 *
 * @since 1.5.35
 *
 * @package Masteriyo\Emails
 */
class InstructorRegistrationEmailToInstructor extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.5.35
	 *
	 * @var String
	 */
	protected $id = 'instructor-registration/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 1.5.35
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/instructor-registration.php';

	/**
	 * Send this email.
	 *
	 * @since 1.5.35
	 *
	 * @param int $id Instructor ID.
	 */
	public function trigger( $id ) {
		$instructor = masteriyo_get_user( $id );

		if ( is_wp_error( $instructor ) || is_null( $instructor ) ) {
			return;
		}

		// Bail early if the instructor doesn't have email.
		if ( empty( $instructor->get_email() ) ) {
			return;
		}

		$this->set_recipients( $instructor->get_email() );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'instructor', $instructor );

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
	 * @since 1.5.35
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.instructor_registration.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = masteriyo_get_default_email_contents()['instructor']['instructor_registration']['subject'];

		/**
		 * Filter instructor registration email subject to instructor.
		 *
		 * @since 1.5.35
		 *
		 * @param string $subject Subject.
		 * @param \Masteriyo\Emails\Email Current email object.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', $subject, $this );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter instructor registration email heading to instructor.
		 *
		 * @since 1.5.35
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.instructor.instructor_registration.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.instructor_registration.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['instructor']['instructor_registration']['content'] );
		$content = $this->format_string( $content );
		$this->set( 'content', trim( $content ) );

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

		/** @var \Masteriyo\Models\User $instructor */
		$instructor = $this->get( 'instructor' );

		if ( $instructor ) {
			$placeholders = $placeholders + array(
				'{instructor_display_name}' => $instructor->get_display_name(),
				'{instructor_first_name}'   => $instructor->get_first_name(),
				'{instructor_last_name}'    => $instructor->get_last_name(),
				'{instructor_username}'     => $instructor->get_username(),
				'{instructor_nicename}'     => $instructor->get_nicename(),
				'{instructor_nickname}'     => $instructor->get_nickname(),
				'{account_login_link}'      => wp_kses_post(
					'<a href="' . $this->get_account_url() . '" style="text-decoration: none;">Login to Your Account</a>'
				),
			);
		}

		return $placeholders;
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter instructor registration email additional content to instructor.
		 *
		 * @since 1.5.35
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.instructor_registration.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.instructor.instructor_registration.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}
}
