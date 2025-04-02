<?php
/**
 * Email verification class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 *  Email verification class.
 *
 * @since 1.15.0
 *
 * @package Masteriyo\Emails
 */
class EmailVerificationEmail extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var String
	 */
	protected $id = 'email-verification';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/email-verification.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\User $user The user user object.
	 */
	public function trigger( $user ) {
		$user = masteriyo_get_user( $user );

		// Bail early if user doesn't exist.
		if ( is_wp_error( $user ) || is_null( $user ) ) {
			return;
		}

		if ( empty( $user->get_email() ) ) {
			return;
		}

		$user_email = $user->get_email();

		$this->set_recipients( $user_email );
		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'user', $user );

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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.everyone.email_verification.enable' ) );
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

		/** @var \Masteriyo\Models\User $user */
		$user = $this->get( 'user' );

		if ( $user ) {
			$placeholders['{user_email}']              = $user->get_email();
			$placeholders['{username}']                = $user->get_username();
			$placeholders['{first_name}']              = $user->get_first_name();
			$placeholders['{last_name}']               = $user->get_last_name();
			$placeholders['{email_verification_link}'] = wp_kses_post(
				'<a href="' . esc_url( masteriyo_generate_email_verification_link( $user, wp_create_nonce( 'masteriyo_email_verification_nonce' ) ) ) . '" style="text-decoration: none;">Verify Your Email</a>'
			);
		}

		return $placeholders;
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
		 * Filter email verification subject.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['everyone']['email_verification']['subject'] );

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
		 * Filter email verification heading.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.everyone.email_verification.heading' ) );

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
		 * Filter email verification additional content.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.everyone.email_verification.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.everyone.email_verification.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.everyone.email_verification.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['everyone']['email_verification']['content'] );
		$content = $this->format_string( $content );

		$this->set( 'content', trim( $content ) );

		return parent::get_content();
	}
}
