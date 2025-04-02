<?php
/**
 * Withdraw request approved email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.6.14
 */

namespace Masteriyo\Emails\Instructor;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

/**
 * Withdraw request approved email class. Used for sending withdraw request approved email.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\Addons\RevenueSharing\Emails
 */
class WithdrawRequestApprovedEmailToInstructor extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $id = 'withdraw-request-approved/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $html_template = 'emails/instructor/withdraw-request-approved.php';

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

		$email = $withdrawer->get_email();

		$this->set_recipients( $email );
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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.instructor.withdraw_request_approved.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.6.14
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = masteriyo_get_default_email_contents()['instructor']['withdraw_request_approved']['subject'];

		/**
		 * Filter withdraw request approved email subject to instructor.
		 *
		 * @since 1.6.14
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', $subject );

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
		 * Filter withdraw request approved email additional content to instructor.
		 *
		 * @since 1.6.14
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.instructor.withdraw_request_approved.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.instructor.withdraw_request_approved.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.instructor.withdraw_request_approved.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['instructor']['withdraw_request_approved']['content'] );
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
			$placeholders['{withdrawer_name}']         = sprintf( '%s %s', $first_name, $last_name ) ?? $withdrawer->get_display_name();
			$placeholders['{withdrawer_email}']        = $withdrawer->get_email();
		}

		if ( $withdraw ) {
			$placeholders['{withdraw_amount}'] = masteriyo_price( $withdraw->get_withdraw_amount() );
		}

		return $placeholders;
	}
}
