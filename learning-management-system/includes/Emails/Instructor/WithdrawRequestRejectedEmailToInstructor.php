<?php
/**
 * Withdraw request rejected email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.6.14
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

/**
 * Withdraw Request rejected email class. Used for sending withdraw request rejected email.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\Addons\RevenueSharing\Emails
 */
class WithdrawRequestRejectedEmailToInstructor extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $id = 'withdraw-request-rejected/to/instructor';

	/**
	 * HTML template path.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/withdraw-request-rejected.php';

	/**
	 * Send this email.
	 *
	 * @since 1.6.14
	 *
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw
	 */
	public function trigger( $withdraw ) {
		$withdraw = masteriyo_get_withdraw( $withdraw );

		if ( ! $withdraw ) {
			return;
		}

		$withdrawer = $withdraw->get_withdrawer();

		if ( ! $withdrawer ) {
			return;
		}

		$email                = $withdrawer->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.instructor.withdraw_request_pending.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{instructor_email}', $email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $email );
		$this->set( 'withdraw', $withdraw );
		$this->set( 'withdrawer', $withdrawer );

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
	 * @since 1.6.14
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.6.14
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter student registration email subject to instructor.
		 *
		 * @since 1.6.14
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.subject' ) );

		return $this->format_string( $subject );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.6.14
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter student registration email additional content to instructor.
		 *
		 * @since 1.6.14
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.instructor.withdraw_request_rejected.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0 [Free]
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.instructor.withdraw_request_rejected.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.content' ) );
		$content = $this->format_string( $content );
		$this->set( 'content', $content );
		return parent::get_content();
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
		/** @var \Masteriyo\Models\User $withdrawer */
		$withdrawer = $this->get( 'withdrawer' );

		/** @var \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw */
		$withdraw = $this->get( 'withdraw' );

		if ( $withdrawer ) {
			$first_name = empty( $withdrawer->get_billing_first_name() ) ? $withdrawer->get_first_name() : $withdrawer->get_billing_first_name();
			$last_name  = empty( $withdrawer->get_billing_last_name() ) ? $withdrawer->get_last_name() : $withdrawer->get_billing_last_name();

			$placeholders['{withdrawer_display_name}'] = $withdrawer->get_display_name();
			$placeholders['{withdrawer_first_name}']   = $first_name;
			$placeholders['{withdrawer_last_name}']    = $last_name;
			$placeholders['{withdrawer_username}']     = $withdrawer->get_username();
			$placeholders['{withdrawer_nicename}']     = $withdrawer->get_nicename();
			$placeholders['{withdrawer_nickname}']     = $withdrawer->get_nickname();
			$placeholders['{withdrawer_name}']         = '' !== trim( sprintf( '%s %s', $first_name, $last_name ) ) ? trim( sprintf( '%s %s', $first_name, $last_name ) ) : $withdrawer->get_display_name();
			$placeholders['{withdrawer_email}']        = $withdrawer->get_email();
		}

		if ( $withdraw ) {
			$placeholders['{withdraw_amount}'] = masteriyo_price( $withdraw->get_withdraw_amount() );
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
		 * Filter student registration email reply_to_name to instructor.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.reply_to_name' ) );
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
		 * Filter student registration email reply_to_address to instructor.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.reply_to_address' ) );
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
		 * Filter student registration email from_name to instructor.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.from_name' ) );
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
		 * Filter student registration email from_address to instructor.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.instructor.withdraw_request_rejected.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
