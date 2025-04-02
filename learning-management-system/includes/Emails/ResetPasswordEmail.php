<?php
/**
 * ResetPasswordEmail class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.0.0
 */

namespace Masteriyo\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * ResetPasswordEmail Class. Used for sending password reset email.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Emails
 */
class ResetPasswordEmail extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $id = 'reset-password';

	/**
	 * Password reset key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $reset_key;

	/**
	 * HTML template path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $html_template = 'emails/reset-password.php';

	/**
	 * Send this email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id User ID.
	 * @param string $reset_key Password reset key.
	 */
	public function trigger( $user_id, $reset_key ) {
		$user = masteriyo_get_user( $user_id );

		// Bail early if user doesn't exist.
		if ( is_wp_error( $user ) || is_null( $user ) ) {
			return;
		}

		$user_email = $user->get_email();

		$this->set_recipients( $user_email );

		// Bail if recipient is empty.
		if ( empty( $this->get_recipients() ) ) {
			return;
		}

		$this->setup_locale();
		$this->set( 'user', $user );
		$this->set( 'reset_link', esc_url( masteriyo_get_password_reset_link( $reset_key, $user->get_id() ) ) );
		$this->set( 'reset_key', $reset_key );

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Get default email subject.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return masteriyo_get_default_email_contents()['everyone']['password_reset']['subject'];
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.everyone.password_reset.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['everyone']['password_reset']['content'] );
		$content = $this->format_string( $content );
		$this->set( 'content', trim( $content ) );
		return parent::get_content();
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_additional_content() {
		/**
		 * Filter password reset email additional content.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.everyone.password_reset.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.everyone.password_reset.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}

	/**
	 * Set the password reset key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 */
	public function set_reset_key( $key ) {
		$this->reset_key = $key;
	}

	/**
	 * Get the password reset key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_reset_key() {
		return $this->reset_key;
	}

	/**
	 * Return subject.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->get_default_subject();
	}

	/**
	 * Return true if the email is enabled.
	 *
	 * @since 1.5.35
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		$is_enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'emails.everyone.password_reset.enable' ) );

		/**
		 * Filters boolean-like value: 'yes' if reset password email should be disabled, otherwise 'no'.
		 *
		 * @since 1.0.0
		 *
		 * @param string $is_disabled 'yes' if reset password email should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_reset_password_email', $is_enabled ? 'no' : 'yes' ) );

		return 'yes' === $is_disabled ? false : $is_enabled;
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
			$placeholders['{user_email}']          = $user->get_email();
			$placeholders['{username}']            = $user->get_username();
			$placeholders['{password_reset_link}'] = wp_kses_post(
				'<a href="' . $this->get( 'reset_link' ) . '" style="text-decoration: none;">Reset Your Password</a>'
			);
		}

		return $placeholders;
	}
}
