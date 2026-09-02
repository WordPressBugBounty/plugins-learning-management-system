<?php
/**
 * Cancelled order email class to student.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.5.35
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Order cancelled email class student.
 *
 * @since 1.5.35
 *
 * @package Masteriyo\Emails
 */
class CancelledOrderEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.5.35
	 *
	 * @var String
	 */
	protected $id = 'cancelled-order/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 1.5.35
	 *
	 * @var string
	 */
	protected $html_template = 'emails/student/cancelled-order.php';

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

		$student = masteriyo_get_user( $order->get_customer_id() );

		if ( is_wp_error( $student ) || is_null( $student ) ) {
			return;
		}

		// Bail early if order doesn't exist.
		if ( empty( $student->get_email() ) ) {
			return;
		}

		$student_email        = $student->get_email();
		$to_addresses_setting = masteriyo_get_setting( 'emails.student.cancelled_order.to_address' );
		$to_address           = array();

		if ( ! empty( $to_addresses_setting ) ) {
			$to_addresses_setting = str_replace( '{student_email}', $student_email, $to_addresses_setting );
			$to_address           = explode( ',', $to_addresses_setting );
		}

		$this->set_recipients( ! empty( $to_address ) ? $to_address : $student_email );
		$this->set( 'order', $order );
		$this->set( 'customer', $order->get_customer() );
		$this->set( 'order_item', ! empty( $order->get_items( 'course-bundle' ) ) ? current( $order->get_items( 'course-bundle' ) ) : current( $order->get_items( 'course' ) ) );

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
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.cancelled_order.enable' ) );
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
		 * Filter order cancelled email subject to student.
		 *
		 * @since 1.5.35
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.student.cancelled_order.subject' ) );
		$subject = empty( trim( $subject ) ) ? masteriyo_get_default_email_contents()['student']['cancelled_order']['subject'] : $subject;

		return $this->format_string( $subject );
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
		 * Filter order cancelled email additional content to student.
		 *
		 * @since 1.5.35
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.cancelled_order.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.student.cancelled_order.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.student.cancelled_order.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.student.cancelled_order.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['student']['cancelled_order']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

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

		/** @var \Masteriyo\Models\User $customer */
		$customer = $this->get( 'customer' );

		/** @var \Masteriyo\Models\Order\Order $order */
		$order = $this->get( 'order' );

		/** @var \Masteriyo\Models\OrderItem $order_item */
		$order_item = $this->get( 'order_item' );

		if ( $customer ) {
			$placeholders['{billing_first_name}'] = ! empty( $customer->get_billing_first_name() ) ? $customer->get_billing_first_name() : $customer->get_first_name();
			$placeholders['{billing_last_name}']  = ! empty( $customer->get_billing_last_name() ) ? $customer->get_billing_last_name() : $customer->get_last_name();

			$billing_first_name = $placeholders['{billing_first_name}'];
			$billing_last_name  = $placeholders['{billing_last_name}'];
			$billing_name       = trim( sprintf( '%s %s', $billing_first_name, $billing_last_name ) );

			$placeholders['{billing_name}']       = ! empty( $billing_name ) ? $billing_name : $customer->get_display_name();
			$placeholders['{billing_email}']      = ! empty( $customer->get_billing_email() ) ? $customer->get_billing_email() : $customer->get_email();
			$placeholders['{account_login_link}'] = wp_kses_post(
				'<a href="' . $this->get_account_url() . '" class="email-template--button">' . __( 'Login to Your Account', 'learning-management-system' ) . '</a>'
			);
		}

		if ( $order_item ) {
			$placeholders['{course_name}'] = $order_item->get_name();
		}

		if ( $order ) {
			$placeholders['{total_price}'] = masteriyo_price( $order->get_total() );
			$placeholders['{order_id}']    = $order->get_order_number();
			$placeholders['{order_date}']  = gmdate( 'd M Y', $order->get_date_created()->getTimestamp() );
			$placeholders['{order_table}'] = $this->get_order_table( $order );
		}

		return $placeholders;
	}

	/**
	 * Gets the order table HTML.
	 *
	 * @since 2.16.0
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
	 * Get the reply_to_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter student registration email reply_to_name to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.student.cancelled_order.reply_to_name' ) );
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
		 * Filter student registration email reply_to_address to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.student.cancelled_order.reply_to_address' ) );

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
		 * Filter student registration email from_name to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.cancelled_order.from_name' ) );
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
		 * Filter student registration email from_address to student.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.cancelled_order.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
