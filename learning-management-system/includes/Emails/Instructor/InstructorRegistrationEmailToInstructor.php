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

		$instructor_email     = $instructor->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.instructor.instructor_registration.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{instructor_email}', $instructor_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $instructor_email );
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
		$subject = strval( masteriyo_get_setting( 'emails.instructor.instructor_registration.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['instructor']['instructor_registration']['subject'] : $subject;

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

	/**
	 * Get email content.
	 *
	 * @since 2.6.9
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.instructor_registration.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.instructor_registration.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['instructor']['instructor_registration']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', trim( $content ) );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.6.9
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
					'<a href="' . $this->get_account_url() . '" class="email-template--button">Login to Your Account</a>'
				),
			);
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
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.instructor.instructor_registration.reply_to_name' ) );
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
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.instructor.instructor_registration.reply_to_address' ) );

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
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.instructor_registration.from_name' ) );
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
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.instructor_registration.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
