<?php
namespace Masteriyo\Addons\Mollie;

use DateInterval;
use DateTime;
use Exception;
use Masteriyo\Abstracts\PaymentGateway;
use Masteriyo\Contracts\PaymentGateway as PaymentGatewayInterface;
use Masteriyo\Enums\OrderItemType;
use Masteriyo\Enums\OrderStatus;
use Mollie\Api\MollieApiClient;
use Stripe\Price;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Mollie extends PaymentGateway implements PaymentGatewayInterface {
	/**
	 * Payment gateway identifier.
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	protected $name = 'mollie';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 1.16.0
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
	 * @since 1.16.0
	 *
	 * @var Logger
	 */
	public static $log = false;

	/**
	 * Indicate if the sandbox mode is enabled.
	 *
	 * @since 1.16.0
	 *
	 * @var bool
	 */
	protected $sandbox = false;

	/**
	 * Indicate if the debug mode is enabled.
	 *
	 * @since 1.16.0
	 *
	 * @var bool
	 */
	protected $debug = false;

	public function __construct() {
		$this->order_button_text  = __( 'Proceed to Mollie', 'learning-management-system' );
		$this->method_title       = __( 'Mollie', 'learning-management-system' );
		$this->method_description = __( 'Mollie redirects customers to enter their payment information.', 'learning-management-system' );

		// Load settings
		$this->init_settings();

		self::$log_enabled = $this->debug;

		if ( $this->sandbox ) {
			$this->description .= ' ' . __( 'SANDBOX ENABLED.', 'learning-management-system' );
			$this->description  = trim( $this->description );
		}

		if ( $this->enabled ) {
			add_filter( 'masteriyo_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
		}
	}

	/**
	 * Logging method.
	 *
	 * @since 1.16.0
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
	}

	/**
	 * Init settings for gateways.
	 *
	 * @since 1.16.0
	 */
	public function init_settings() {
		$this->enabled     = Setting::get( 'enable' );
		$this->title       = Setting::get( 'title' );
		$this->description = Setting::get( 'description' );
		$this->sandbox     = masteriyo_mollie_test_mode_enabled(); // Corrected to use Mollie's test mode
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.16.0
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		try {
			masteriyo_get_logger()->info( 'Mollie payment processing started', array( 'source' => 'payment-mollie' ) );

			$order = masteriyo_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order ID or order does not exist.', 'learning-management-system' ) );
			}

			$secret = masteriyo_mollie_get_api_key();
			if ( empty( $secret ) ) {
				masteriyo_get_logger()->error( 'Mollie API key is missing or invalid', array( 'source' => 'payment-mollie' ) );
				throw new Exception( __( 'Mollie API key is missing or invalid.', 'learning-management-system' ) );
			}

			$mollie = new MollieApiClient();
			$mollie->setApiKey( $secret );

			$payment_type = 'one-time';

			$courses = array_map(
				function ( $order_item ) {
					return $order_item->get_course();
				},
				$order->get_items()
			);

			if ( empty( $courses ) ) {
				masteriyo_get_logger()->info( 'No courses found in the order.', array( 'source' => 'payment-mollie' ) );
				throw new Exception( __( 'No courses found in the order.', 'learning-management-system' ) );
			}

			$first_course = current( $courses );
			if ( ! $first_course || ! $first_course->get_id() ) {
				masteriyo_get_logger()->info( 'Invalid course data in the order.', array( 'source' => 'payment-mollie' ) );
				throw new Exception( __( 'Invalid course data in the order.', 'learning-management-system' ) );
			}

			$receipt_id   = $order->get_billing_email();
			$payment_data = array(
				'amount'      => array(
					'currency' => $order->get_currency() ?? 'EUR',
					'value'    => number_format( $order->get_total(), 2, '.', '' ),
				),
				/* translators: %s: order id */
				'description' => sprintf( __( 'Order #%s', 'learning-management-system' ), $order_id ),
				'redirectUrl' => $this->get_return_url( $order ),
				'webhookUrl'  => home_url( 'wp-admin/admin-ajax.php?action=masteriyo_mollie_webhook' ),
				'metadata'    => array(
					'order_id'     => $order_id,
					'payment_type' => $payment_type,
					'course_id'    => $first_course->get_id(),
					'receipt'      => $receipt_id,
				),
			);

			$payment = $mollie->payments->create( $payment_data );

			if ( empty( $payment ) || empty( $payment->id ) ) {
				throw new Exception( __( 'Failed to create payment with Mollie.', 'learning-management-system' ) );
			}

			$order->set_transaction_id( $payment->id );
			$order->save_meta_data();
			$order->save();
			$this->handle_payment_status( $order_id, $payment->status );
			return array(
				'result'         => 'success',
				'redirect'       => $payment->getCheckoutUrl(),
				'payment_method' => 'mollie',
				'order_id'       => $order_id,
			);
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-mollie' ) );
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Handle different payment statuses and update order accordingly.
	 *
	 * @since 1.16.0
	 * @param int $order_id The order ID
	 * @param string $status The payment status
	 * @return void
	 */
	public function handle_payment_status( $order_id, $status ) {
		try {
			$order = masteriyo_get_order( $order_id );

			if ( ! $order ) {
					throw new Exception( __( 'Order not found.', 'learning-management-system' ) );
			}

			switch ( $status ) {
				case 'open':
						$order->set_status( OrderStatus::PENDING );
					break;

				case 'failed':
						$order->set_status( OrderStatus::FAILED );
					break;

				case 'expired':
						$order->set_status( OrderStatus::CANCELLED );
					break;

				case 'canceled':
					$order->set_status( OrderStatus::CANCELLED );

			}

			$order->save();

			masteriyo_get_logger()->info(
				sprintf( 'Order %s status updated to %s', $order_id, $order->get_status() ),
				array( 'source' => 'payment-mollie' )
			);

		} catch ( Exception $e ) {
			masteriyo_get_logger()->error(
				'Error updating order status: ' . $e->getMessage(),
				array( 'source' => 'payment-mollie' )
			);
		}
	}

	/**
	 * Handle open payment status.
	 *
	 * @since 1.16.0
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Mollie\Api\Resources\Payment $payment
	 */
	protected function handle_open_payment( $order, $payment ) {
		// Set order status to pending
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$order->set_customer_note(
			sprintf(
				/* translators: %s: payment id */
				__( 'Mollie payment is pending. Payment ID: %s', 'learning-management-system' ),
				$payment->id
			)
		);

		masteriyo_get_logger()->info(
			sprintf( 'Order %s marked as pending for open payment', $order->get_id() ),
			array( 'source' => 'payment-mollie' )
		);
	}

	/**
	 * Handle failed payment status.
	 *
	 * @since 1.16.0
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Mollie\Api\Resources\Payment $payment
	 */
	protected function handle_failed_payment( $order, $payment ) {
		$order->set_status( OrderStatus::FAILED );
		$order->save();

		$order->set_customer_note(
			sprintf(
				/* translators: %s: payment id */
				__( 'Mollie payment failed. Payment ID: %s', 'learning-management-system' ),
				$payment->id
			)
		);

		masteriyo_get_logger()->info(
			sprintf( 'Order %s marked as failed', $order->get_id() ),
			array( 'source' => 'payment-mollie' )
		);
	}

	/**
	 * Handle expired payment status.
	 *
	 * @since 1.16.0
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Mollie\Api\Resources\Payment $payment
	 */
	protected function handle_expired_payment( $order, $payment ) {
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();
		$order->set_customer_note(
			sprintf(
				/* Translators: %s: Payment ID */
				__( 'Mollie payment expired. Payment ID: %s', 'learning-management-system' ),
				$payment->id
			)
		);

		masteriyo_get_logger()->info(
			sprintf( 'Order %s marked as cancelled due to payment expiration', $order->get_id() ),
			array( 'source' => 'payment-mollie' )
		);
	}


	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refund' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @since 1.16.0
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 *
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
	}

	/**
	 * Custom Mollie order received text.
	 *
	 * @since 1.16.0
	 *
	 * @param string   $text Default text.
	 * @param Order $order Order data.
	 *
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		masteriyo_get_logger()->info( 'Mollie order received text processing started', array( 'source' => 'payment-mollie' ) );
		if ( $order && $this->name === $order->get_payment_method() ) {
			masteriyo_get_logger()->info( 'Mollie order received text processing completed.', array( 'source' => 'payment-mollie' ) );
			return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your Mollie account to view transaction details.', 'learning-management-system' );
		}

		return $text;
	}

	/**
	 * Get the transaction URL.
	 *
	 * @since 1.16.0
	 *
	 * @param  Order $order Order object.
	 *
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		$payment_id = get_post_meta( $order->get_id(), '_mollie_payment_id', true );

		if ( $payment_id ) {
			return sprintf( 'https://www.mollie.com/payers/%s', esc_html( $payment_id ) );
		}

		return parent::get_transaction_url( $order );
	}
}
