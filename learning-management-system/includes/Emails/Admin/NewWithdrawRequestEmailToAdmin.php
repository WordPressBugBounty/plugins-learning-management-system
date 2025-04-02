<?php
/**
 * New withdraw request email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.6.14
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

/**
 * New withdraw request email class. Used for sending new withdraw request email.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\Addons\RevenueSharing\Emails
 */
class NewWithdrawRequestEmailToAdmin extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $id = 'new-withdraw-request/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/new-withdraw-request.php';

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

		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$this->set_recipients( $admin_email );
		$this->set( 'withdraw', $withdraw );
		$this->set( 'withdrawer', $withdraw->get_withdrawer() );

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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.new_withdraw_request.enable' ) );
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
		 * Filter student registration email subject to admin.
		 *
		 * @since 1.6.14
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['admin']['new_withdraw_request']['subject'] );

		return $this->format_string( $subject );
	}


	/**
	 * Get email content.
	 *
	 * @since 1.15.0 [Free]
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.new_withdraw_request.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['admin']['new_withdraw_request']['content'] );
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

	/**
	 * Return additional content.
	 *
	 * @since 1.6.14
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter student registration email additional content to admin.
		 *
		 * @since 1.6.14
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.new_withdraw_request.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.admin.new_withdraw_request.additional_content', 'masteriyo-email-message', $additional_content );
		return $this->format_string( $additional_content );
	}
}
