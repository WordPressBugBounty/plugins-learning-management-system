<?php
/**
 * Lemon Squeezy Gateway.
 *
 * Provides a LemonSqueezy Gateway.
 *
 * @class       LemonSqueezy
 * @extends     LemonSqueezyGateway
 * @version     1.9.3
 * @package     \Masteriyo\Addons\LemonSqueezyIntegration
 */

namespace Masteriyo\Addons\LemonSqueezyIntegration;

use Exception;
use Masteriyo\Abstracts\PaymentGateway;
use Masteriyo\Constants;
use Masteriyo\Contracts\PaymentGateway as PaymentGatewayInterface;
use Masteriyo\Models\Order\Order;

defined( 'ABSPATH' ) || exit;

/**
 * LemonSqueezy Payment Gateway Class
 *
 * Provides a LemonSqueezy Payment Gateway.
 *
 * @package Masteriyo\Classes\Payment
 *
 * @version 1.9.3
 */
class LemonSqueezy extends PaymentGateway implements PaymentGatewayInterface {
	/**
	 * Payment gateway identifier.
	 *
	 * @since 1.9.3
	 *
	 * @var string
	 */
	protected $name = 'lemon_squeezy';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 1.9.3
	 *
	 * @var bool
	 */
	protected $has_fields = false;

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @since 1.9.3
	 *
	 * @var Logger
	 */
	public static $log = false;

	/**
	 * Indicate if the debug mode is enabled.
	 *
	 * @since 1.9.3
	 *
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	protected $supports = array( 'course' );

	/**
	 * Constructor for the gateway.
	 *
	 * @since 1.9.3
	 */
	public function __construct() {

		$this->order_button_text = __( 'Proceed to Lemon Squeezy', 'learning-management-system' );
		$this->method_title      = __( 'Lemon Squeezy', 'learning-management-system' );
		/* translators: %s: Link to Masteriyo system status page */
		$this->method_description = __( 'Lemon Squeezy redirects customers to Lemon Squeezy to enter their payment information.', 'learning-management-system' );

		// Load the settings.
		$this->init_settings();

		$this->debug       = false;
		self::$log_enabled = $this->debug;

		if ( $this->enabled ) {
			add_filter( 'masteriyo_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
		}
	}

	/**
	 * Logging method.
	 *
	 * @since 1.9.3
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
	}

	/**
	 * Get gateway icon.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	public function get_icon() {
		$image_url = Constants::get( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ASSETS_URL' ) . '/images/logo.png';

		$icon_html  = '<div style="text-align: center; padding: 8px 16px; background-color: #5423e7; border-radius: 4px; margin-top: 4px; margin-bottom: 4px;">';
		$icon_html .= '<img src="' . esc_attr( $image_url ) . '" alt="' . esc_attr__( 'Lemon Squeezy Logo', 'learning-management-system' ) . '" style="width: 180px; display: block; margin: 0 auto;" />';
		$icon_html .= '</div>';

		/**
		 * Filters lemon squeezy icon.
		 *
		 * @since 1.9.3
		 *
		 * @param string $icon Icon html.
		 * @param string $name Payment gateway name.
		 */
		return apply_filters( 'masteriyo_lemon_squeezy_icon', $icon_html, $this->name );
	}

	/**
	 * Init settings for gateways.
	 *
	 * @since 1.9.3
	 */
	public function init_settings() {
		$this->enabled     = Setting::is_enable();
		$this->title       = Setting::get( 'title' );
		$this->description = Setting::get( 'description' );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.9.3
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		try {
			masteriyo_get_logger()->info( 'Lemon Squeezy payment processing started', array( 'source' => 'payment-lemon-squeezy' ) );
			$order   = masteriyo_get_order( $order_id );
			$session = masteriyo( 'session' );

			if ( ! $order ) {
				masteriyo_get_logger()->error( 'Order not found', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( __( 'Invalid order ID or order does not exist', 'learning-management-system' ) );
			}

			if ( ! $session ) {
				masteriyo_get_logger()->error( 'Session not found', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( __( 'Session not found.', 'learning-management-system' ) );
			}

			$order_item = current( $order->get_items() );
			$course_id  = $order_item->get_course_id();

			$request = new Request();

			$checkout_data = array(
				'email' => $order->get_billing_email(),
				'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			);

			if ( ! empty( $order->get_billing_country() ) ) {
				$checkout_data['billing_address']['country'] = $order->get_billing_country();
			}

			if ( ! empty( $order->get_billing_state() ) ) {
				$checkout_data['billing_address']['state'] = $order->get_billing_city();
			}

			if ( ! empty( $order->get_billing_postcode() ) ) {
				$checkout_data['billing_address']['zip'] = $order->get_billing_postcode();
			}

			$checkout_data['custom'] = array(
				'order_id'      => (string) $order_id,
				'user_id'       => (string) $order->get_user_id(),
				'billing_email' => (string) $order->get_billing_email(),
				'course_id'     => (string) $course_id,
			);

			$options      = array( 'redirect_url' => $this->get_return_url( $order ) );
			$checkout_url = $request->create_checkout_url( $course_id, $checkout_data, $options );

			if ( is_wp_error( $checkout_url ) || ! $checkout_url ) {
				masteriyo_get_logger()->error( 'Failed to create a checkout URL. Error: ' . $checkout_url->get_error_message(), array( 'source' => 'payment-lemon-squeezy' ) );
				$error_message = is_wp_error( $checkout_url ) ? $checkout_url->get_error_message() : __( 'Failed to create a checkout URL.', 'learning-management-system' );

				masteriyo_add_notice( $error_message, 'error' );

				return array(
					'result'         => 'failure',
					'messages'       => $error_message,
					'payment_method' => $this->name,
				);
			}

			masteriyo_get_logger()->info( 'Lemon Squeezy payment processing completed', array( 'source' => 'payment-lemon-squeezy' ) );

			return array(
				'result'         => 'success',
				'redirect'       => $checkout_url,
				'payment_method' => $order->get_payment_method(),
			);

		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-lemon-squeezy' ) );
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refund' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @since 1.9.3
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 *
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Custom LemonSqueezy order received text.
	 *
	 * @since 1.9.3
	 *
	 * @param string   $text Default text.
	 * @param Order $order Order data.
	 *
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		masteriyo_get_logger()->info( 'LemonSqueezy order received text processing started', array( 'source' => 'payment-lemon-squeezy' ) );
		if ( $order && $this->name === $order->get_payment_method() ) {
			masteriyo_get_logger()->info( 'LemonSqueezy order received text processing completed.', array( 'source' => 'payment-lemon-squeezy' ) );
			return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your Lemon Squeezy account to view transaction details.', 'learning-management-system' );
		}

		return $text;
	}
}
