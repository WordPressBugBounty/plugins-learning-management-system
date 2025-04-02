<?php
/**
 * New order email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.5.35
 */

namespace Masteriyo\Emails\Admin;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * New order email class. Used for sending new account email.
 *
 * @since 1.5.35
 *
 * @package Masteriyo\Emails
 */
class NewOrderEmailToAdmin extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.5.35
	 *
	 * @var String
	 */
	protected $id = 'new-order/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.5.35
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/new-order.php';

	/**
	 * Send this email.
	 *
	 * @since 1.5.35
	 *
	 * @param \Masteriyo\Models\Order $order
	 */
	public function trigger( $order ) {
		$order = masteriyo_get_order( $order );

		if ( ! $order ) {
			return;
		}

		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$this->set_recipients( $admin_email );

		$a = $order->get_items( 'course' );

		$this->set( 'order', $order );
		$this->set( 'customer', $order->get_customer() );
		$this->set( 'order_item_course', current( $order->get_items( 'course' ) ) );

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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.new_order.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter student registration email subject to admin.
		 *
		 * @since 1.5.35
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_default_email_contents()['admin']['new_order']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.new_order.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['admin']['new_order']['content'] );
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
		/** @var \Masteriyo\Models\User $customer */
		$customer = $this->get( 'customer' );

		/** @var \Masteriyo\Models\Order\Order $order */
		$order = $this->get( 'order' );

		/** @var \Masteriyo\Models\Order\OrderItem $order_item_course */
		$order_item_course = $this->get( 'order_item_course' );

		if ( $customer ) {
			$placeholders['{billing_first_name}'] = ! empty( $customer->get_billing_first_name() ) ? $customer->get_billing_first_name() : $customer->get_first_name();
			$placeholders['{billing_last_name}']  = ! empty( $customer->get_billing_last_name() ) ? $customer->get_billing_last_name() : $customer->get_last_name();

			$billing_first_name = $placeholders['{billing_first_name}'];
			$billing_last_name  = $placeholders['{billing_last_name}'];
			$billing_name       = trim( sprintf( '%s %s', $billing_first_name, $billing_last_name ) );

			$placeholders['{billing_name}']  = ! empty( $billing_name ) ? $billing_name : $customer->get_display_name();
			$placeholders['{billing_email}'] = ! empty( $customer->get_billing_email() ) ? $customer->get_billing_email() : $customer->get_email();
		}

		if ( $order_item_course ) {
			$placeholders['{course_name}'] = $order_item_course->get_name();
		}

		if ( $order ) {
			$placeholders['{total_price}']                 = masteriyo_price( $order->get_total() );
			$placeholders['{order_id}']                    = $order->get_order_number();
			$placeholders['{order_date}']                  = gmdate( 'd M Y', $order->get_date_created()->getTimestamp() );
			$placeholders['{order_table}']                 = $this->get_order_table( $order );
			$placeholders['{new_order_celebration_image}'] = $this->get_celebration_image();
		}

		return $placeholders;
	}

	/**
	 * Gets the order table HTML.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 *
	 * @return string The order table HTML.
	 */
	private function get_order_table( $order ) {
		return masteriyo_get_template_html(
			'emails/order-details.php',
			array(
				'order' => $order,
				'email' => $this,
			)
		);
	}

	/**
	 * Retrieves the HTML or URL for the celebration image for new order.
	 *
	 * @since 1.15.0
	 *
	 * @return string The celebration image HTML or URL.
	 */
	private function get_celebration_image() {
		/**
		 * Retrieves the HTML for the new order celebration image.
		 *
		 * @since 1.15.0
		 *
		 * @return string The HTML for the celebration image.
		 */
		return apply_filters(
			'masteriyo_admin_new_order_email_celebration_image',
			sprintf(
				'<img src="%s" alt="celebration image">',
				esc_url( masteriyo_get_plugin_url() . '/assets/img/new-order-celebration.png' )
			)
		);
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
		 * Filter student registration email additional content to admin.
		 *
		 * @since 1.5.35
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.new_order.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.admin.new_order.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}
}
