<?php
namespace Masteriyo\Addons\Mollie;

use DateInterval;
use DateTime;
use Exception;
use Masteriyo\Abstracts\PaymentGateway;
use Masteriyo\Constants;
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
	 * @since 1.16.0 [Free]
	 *
	 * @var string
	 */
	protected $name = 'mollie';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
	 *
	 * @var Logger
	 */
	public static $log = false;

	/**
	 * Indicate if the sandbox mode is enabled.
	 *
	 * @since 1.16.0 [Free]
	 *
	 * @var bool
	 */
	protected $sandbox = false;

	/**
	 * Indicate if the debug mode is enabled.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * The addon's own badge — the white wordmark on black, rounded — so the backing
	 * and the corners ride on the image and the checkout needs only to cap its
	 * height. It lives at the addon root rather than under `assets/`, because that
	 * is where the addon browser looks for a thumbnail.
	 *
	 * @return string
	 */
	public function get_icon() {
		$image_url = plugins_url( 'thumbnail.png', Constants::get( 'MASTERIYO_MOLLIE_ADDON_FILE' ) );

		$icon_html = sprintf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( $image_url ),
			esc_attr__( 'Mollie logo', 'learning-management-system' )
		);

		/**
		 * Filters mollie icon.
		 *
		 * @param string $icon Icon html.
		 * @param string $name Payment gateway name.
		 */
		return apply_filters( 'masteriyo_mollie_icon', $icon_html, $this->name );
	}

	/**
	 * Init settings for gateways.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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

			$payment_type = masteriyo_order_has_recurring_courses( $order ) ? 'recurring' : 'one-time';

			masteriyo_get_logger()->info(
				sprintf( 'Mollie process_payment | order_id=%1$s | payment_type=%2$s | total=%3$s', $order_id, $payment_type, $order->get_total() ),
				array( 'source' => 'payment-mollie' )
			);

			$order_items = $order->get_items();
			if ( ! empty( $order_items[0] ) && masteriyo_is_bundle_order_item( $order_items[0] ) ) {
				$courses = $order_items;
			} else {
				$courses = array_map(
					function( $order_item ) {
						return $order_item->get_course();
					},
					$order_items
				);
			}

			if ( empty( $courses ) ) {
				masteriyo_get_logger()->info( 'No courses found in the order.', array( 'source' => 'payment-mollie' ) );
				throw new Exception( __( 'No courses found in the order.', 'learning-management-system' ) );
			}

			$first_course = current( $courses );
			if ( ! $first_course || ! $first_course->get_id() ) {
				masteriyo_get_logger()->info( 'Invalid course data in the order.', array( 'source' => 'payment-mollie' ) );
				throw new Exception( __( 'Invalid course data in the order.', 'learning-management-system' ) );
			}

			$receipt_id = $order->get_billing_email();

			$street_and_number = trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() );
			$billing_country   = $order->get_billing_country();
			$billing_postcode  = $order->get_billing_postcode();
			$billing_city      = $order->get_billing_city();

			$has_full_billing_address = ! empty( $billing_country )
				&& ! empty( $street_and_number )
				&& ! empty( $billing_postcode )
				&& ! empty( $billing_city );

			$payment_data = array(
				'amount'      => array(
					'currency' => $order->get_currency() ?? 'EUR',
					'value'    => number_format( $order->get_total(), 2, '.', '' ),
				),
				'description' => sprintf(
				/* translators: %s: order number */
					_x( 'Order #%s', 'Payment description (order number)', 'learning-management-system' ),
					$order_id
				),
				'redirectUrl' => $this->get_return_url( $order ),
				'webhookUrl'  => masteriyo_mollie_get_webhook_url(),
				'metadata'    => array(
					'order_id'     => $order_id,
					'payment_type' => $payment_type,
					'course_id'    => $first_course->get_id(),
					'receipt'      => $receipt_id,
				),
			);

			/**
			 * Mollie treats `lines` + `billingAddress` as address-based methods (Klarna,
			 * Riverty, Afterpay), which require a complete billing address. Sending them
			 * with empty country/street causes "422 No suitable payment methods found",
			 * blocking simpler methods (card, iDEAL) that don't need an address.
			 */
			if ( $has_full_billing_address ) {
				$payment_data['billingAddress'] = array(
					'givenName'       => $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
					'familyName'      => $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
					'streetAndNumber' => $street_and_number,
					'postalCode'      => $billing_postcode,
					'city'            => $billing_city,
					'country'         => $billing_country,
					'email'           => $order->get_billing_email() ? $order->get_billing_email() : '',
				);
				$payment_data['lines']          = $this->get_order_lines( $order );
			}

			if ( 'recurring' === $payment_type ) {
				$billing_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
				$billing_email = $order->get_billing_email();

				if ( empty( $billing_name ) || empty( $billing_email ) ) {
					throw new Exception( __( 'Billing information is incomplete for recurring payment.', 'learning-management-system' ) );
				}

				$mollie_customer = $mollie->customers->create(
					array(
						'name'  => $billing_name ?? '',
						'email' => $billing_email ?? '',
					)
				);

				$payment_data['customerId']   = $mollie_customer->id;
				$payment_data['sequenceType'] = 'first';

				$order->update_meta_data( 'mollie_customer_id', $mollie_customer->id );

				masteriyo_get_logger()->info(
					sprintf( 'Mollie first payment prepared | order_id=%1$s | customer_id=%2$s | sequenceType=first', $order_id, $mollie_customer->id ),
					array( 'source' => 'payment-mollie' )
				);
			}

			$payment = $mollie->payments->create( $payment_data );

			if ( empty( $payment ) || empty( $payment->id ) ) {
				throw new Exception( __( 'Failed to create payment with Mollie.', 'learning-management-system' ) );
			}

			masteriyo_get_logger()->info(
				sprintf( 'Mollie payment created | order_id=%1$s | payment_id=%2$s | status=%3$s | sequenceType=%4$s', $order_id, $payment->id, $payment->status, $payment_type === 'recurring' ? 'first' : 'none' ),
				array( 'source' => 'payment-mollie' )
			);

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
			throw new Exception( esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Build the Mollie payment lines for an order.
	 *
	 * Mollie's v2 payments API rejects the request with a 422 unless `amount`
	 * equals the sum of `lines[].totalAmount`. Item totals are tax-exclusive
	 * while the order total includes tax (and reflects order-level discounts),
	 * so any remainder is reconciled with a surcharge or discount line.
	 *
	 * @since 3.3.4
	 *
	 * @param \Masteriyo\Models\Order\Order $order The order.
	 *
	 * @return array
	 */
	protected function get_order_lines( $order ) {
		$currency    = $order->get_currency() ? $order->get_currency() : 'EUR';
		$order_lines = array();
		$lines_total = 0;

		foreach ( $order->get_items() as $order_item ) {
			$item_name     = '';
			$item_quantity = 1;
			$item_price    = 0;

			if ( masteriyo_is_bundle_order_item( $order_item ) ) {
				$item_name  = $order_item->get_name();
				$item_price = $order_item->get_total();
			} else {
				$course = $order_item->get_course();
				if ( $course ) {
					$item_name  = $course->get_name();
					$item_price = $order_item->get_total();
				}
			}

			$line_total   = number_format( (float) $item_price * $item_quantity, 2, '.', '' );
			$lines_total += (float) $line_total;

			$order_lines[] = array(
				'type'        => 'digital',
				'description' => $item_name ? $item_name : __( 'Course', 'learning-management-system' ),
				'quantity'    => $item_quantity,
				'unitPrice'   => array(
					'currency' => $currency,
					'value'    => number_format( (float) $item_price, 2, '.', '' ),
				),
				'totalAmount' => array(
					'currency' => $currency,
					'value'    => $line_total,
				),
				'vatRate'     => '0.00',
				'vatAmount'   => array(
					'currency' => $currency,
					'value'    => '0.00',
				),
			);
		}

		$difference = round( (float) $order->get_total() - $lines_total, 2 );

		if ( abs( $difference ) >= 0.01 ) {
			$order_lines[] = array(
				'type'        => $difference > 0 ? 'surcharge' : 'discount',
				'description' => $difference > 0 ? __( 'Taxes and fees', 'learning-management-system' ) : __( 'Discount', 'learning-management-system' ),
				'quantity'    => 1,
				'unitPrice'   => array(
					'currency' => $currency,
					'value'    => number_format( $difference, 2, '.', '' ),
				),
				'totalAmount' => array(
					'currency' => $currency,
					'value'    => number_format( $difference, 2, '.', '' ),
				),
				'vatRate'     => '0.00',
				'vatAmount'   => array(
					'currency' => $currency,
					'value'    => '0.00',
				),
			);
		}

		return $order_lines;
	}

	/**
	 * Handle different payment statuses and update order accordingly.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * Create subscription or handle regular payment.
	 *
	 * @since 1.16.0 [Free]
	 * @param object $mollie_customer
	 * @param array $mollie_objects
	 * @param int $expire_after Expire/Cancel subscription after months.
	 */
	protected function create_mollie_subscription( $mollie_customer, $price_objects, $expire_after ) {
		masteriyo_get_logger()->info( 'Mollie create_subscription: Start', array( 'source' => 'payment-mollie' ) );

		if ( empty( $price_objects ) ) {
			masteriyo_get_logger()->info( 'Mollie create_subscription: No price objects', array( 'source' => 'payment-mollie' ) );
			return;
		}

		$items = array_map(
			function( $price_object ) {
				return array( 'price' => $price_object->id );
			},
			$price_objects
		);

		try {
			$subscription = $mollie->subscriptions->create(
				array(
					'customer' => $mollie_customer->id,
					'lines'    => $items,
					'metadata' => array(
						'expire_after' => $expire_after,
					),
					'status'   => 'active', // Set to active or pending based on your logic
				)
			);

				masteriyo_get_logger()->info( 'Mollie create_subscription: Success', array( 'source' => 'payment-mollie' ) );
				return $subscription;
		} catch ( \Mollie\Api\Exceptions\ApiException $e ) {
			masteriyo_get_logger()->error( 'Mollie API error: ' . $e->getMessage(), array( 'source' => 'payment-mollie' ) );
			throw new Exception( 'Unable to create subscription: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Create or return Mollie customer id.
	 *
	 * @since 2.6.10
	 * @return object
	 */
	protected function create_mollie_customer() {
		masteriyo_get_logger()->info( 'Mollie create_mollie_customer: Start', array( 'source' => 'payment-mollie' ) );

		$user               = masteriyo_get_current_user();
		$mollie_customer_id = get_user_meta( $user->get_id(), '_mollie_customer', true );

		if ( ! empty( $mollie_customer_id ) ) {
			// Retrieve existing customer
			return \Mollie\Api\Resources\Customer::retrieve( $mollie_customer_id );
		}

		// Create new customer if not exists
		try {
			$mollie_customer = \Mollie\Api\Resources\Customer::create(
				array(
					'email' => $user->get_billing_email(),
					'name'  => $user->get_billing_first_name() . ' ' . $user->get_billing_last_name(),
				// Add more fields as necessary
				)
			);

			// Save customer ID in user meta
			update_user_meta( $user->get_id(), '_mollie_customer', $mollie_customer->id );

			masteriyo_get_logger()->info( 'Mollie create_mollie_customer: Customer ID: ' . $mollie_customer->id, array( 'source' => 'payment-mollie' ) );
			return $mollie_customer;
		} catch ( \Mollie\Api\Exceptions\ApiException $e ) {
			masteriyo_get_logger()->error( 'Mollie API error: ' . $e->getMessage(), array( 'source' => 'payment-mollie' ) );
			throw new Exception( 'Unable to create customer: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refund' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
