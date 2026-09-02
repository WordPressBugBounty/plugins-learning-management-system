<?php
/**
 * Email verification class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.16.0
 */

namespace Masteriyo\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 *  Email verification class.
 *
 * @since 2.16.0
 *
 * @package Masteriyo\Emails
 */
class EmailVerificationEmail extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 2.16.0
	 *
	 * @var String
	 */
	protected $id = 'email-verification';

	/**
	 * HTML template path.
	 *
	 * @since 2.16.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/email-verification.php';

	/**
	 * Send this email.
	 *
	 * @since 2.16.0
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

		$user_email           = $user->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.everyone.email_verification.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{user_email}', $user_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $user_email );
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
	 * @since 2.16.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.everyone.email_verification.enable' ) );
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.16.0
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
				'<a href="' . esc_url( masteriyo_generate_email_verification_link( $user, wp_create_nonce( 'masteriyo_email_verification_nonce' ) ) ) . '" class="email-template--button">' . __( 'Verify Your Email', 'learning-management-system' ) . '</a>'
			);
		}

		return $placeholders;
	}

	/**
	 * Return subject.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter email verification subject.
		 *
		 * @since 2.16.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.everyone.email_verification.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['everyone']['email_verification']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter email verification heading.
		 *
		 * @since 2.16.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.everyone.email_verification.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter email verification additional content.
		 *
		 * @since 2.16.0
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
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.everyone.email_verification.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.everyone.email_verification.content' ) );
		$content = $this->format_string( $content );

		$this->set( 'content', trim( $content ) );

		return parent::get_content();
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter email verification reply_to_name.
		 *
		 * @since 2.16.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.everyone.email_verification.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter email verification reply_to_address.
		 *
		 * @since 2.16.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.everyone.email_verification.reply_to_address' ) );
		$reply_to_address = is_string( $reply_to_address ) ? trim( $reply_to_address ) : '';

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter email verification from_name.
		 *
		 * @since 2.16.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.everyone.email_verification.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.16.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter email verification from_address.
		 *
		 * @since 2.16.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.everyone.email_verification.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
