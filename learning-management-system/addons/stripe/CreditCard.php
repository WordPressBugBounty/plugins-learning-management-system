<?php
/**
 * Stripe Credit Card Gateway.
 *
 * Provides a credit card payment gateway.
 *
 * @class       CreditCard
 * @extends     PaymentGateway
 * @version     2.0.0
 * @package     Masteriyo\Classes\Payment
 */

namespace Masteriyo\Addons\Stripe;

use Exception;
use Masteriyo\Abstracts\PaymentGateway;
use Masteriyo\Contracts\PaymentGateway as PaymentGatewayInterface;
use Masteriyo\Addons\Stripe\Client\StripeClient;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe credit card Class.
 */
#[\AllowDynamicProperties]
class CreditCard extends PaymentGateway implements PaymentGatewayInterface {

	/**
	 * Payment gateway name.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $name = 'stripe';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 1.14.0
	 *
	 * @var bool
	 */
	protected $has_fields = true;

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	public static $log = false;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'.
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	protected $supports = array( 'course', 'subscription' );


	/**
	 * Constructor for the gateway.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->order_button_text = __( 'Confirm Payment', 'learning-management-system' );
		$this->method_title      = __( 'Stripe (Credit Card)', 'learning-management-system' );
		/* translators: %s: Link to Masteriyo system status page */
		$this->method_description = __( 'CreditCard Standard redirects customers to CreditCard to enter their payment information.', 'learning-management-system' );

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title       = Setting::get_title();
		$this->description = Setting::get_description();
		$this->sandbox     = Setting::is_sandbox_enable();
		$this->debug       = false;
		self::$log_enabled = $this->debug;

		if ( $this->sandbox ) {
			/* translators: %s: Link to CreditCard sandbox testing guide page */
			$this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED.', 'learning-management-system' ) );
			$this->description  = trim( $this->description );
		}

		if ( $this->enabled ) {
			add_filter( 'masteriyo_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
		}
	}

	/**
	 * Logging method.
	 *
	 * @since 1.14.0
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
	}

	/**
	 * Other methods.
	 */

	/**
	 * Init settings for gateways.
	 *
	 * @since 1.14.0
	 */
	public function init_settings() {
		$this->enabled = Setting::is_enable();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.14.0
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		masteriyo_get_logger()->info( 'Stripe process_payment: ' . $order_id, array( 'source' => 'payment-stripe' ) );
		try {
			$order = masteriyo_get_order( $order_id );

			/** @var \Masteriyo\Session\Session */
			$session = masteriyo( 'session' );

			if ( ! $order ) {
				masteriyo_get_logger()->error( 'Stripe process_payment: Order not found', array( 'source' => 'payment-stripe' ) );
				throw new Exception( __( 'Invalid order ID or order does not exist', 'learning-management-system' ) );
			}

			if ( ! $session ) {
				masteriyo_get_logger()->error( 'Stripe process_payment: Session not found', array( 'source' => 'payment-stripe' ) );
				throw new Exception( __( 'Session not found.', 'learning-management-system' ) );
			}

			$payment_intent_id = $session->get( 'stripe_payment_intent_id' );
			$order->set_transaction_id( $payment_intent_id );
			$order->save();

			$payment_intent_args = array(
				'receipt_email' => $order->get_billing_email(),
				'metadata'      => array( 'order_id' => $order->get_id() ),
				'amount'        => Helper::convert_cart_total_to_stripe_amount( $order->get_total(), $order->get_currency() ),
			);

			if ( Helper::use_platform() ) {
				StripeClient::create()->update_payment_intent( $payment_intent_id, $payment_intent_args );
			} else {
				PaymentIntent::update(
					$payment_intent_id,
					$payment_intent_args,
					Helper::get_stripe_options()
				);
			}

			masteriyo_get_logger()->info( 'Payment intent updated.', array( 'source' => 'payment-stripe' ) );

			masteriyo_get_logger()->info( 'Stripe process_payment: Success', array( 'source' => 'payment-stripe' ) );
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refund' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @since 1.14.0
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Custom CreditCard order received text.
	 *
	 * @since 1.14.0
	 * @param string   $text Default text.
	 * @param \Masteriyo\Models\Order\Order $order Order data.
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		masteriyo_get_logger()->info( 'Stripe order_received_text : Start', array( 'source' => 'payment-stripe' ) );
		if ( $order && $this->name === $order->get_payment_method() ) {
			masteriyo_get_logger()->info( 'Stripe order_received_text: Success', array( 'source' => 'payment-stripe' ) );
			return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your Stripe account to view transaction details.', 'learning-management-system' );
		}

		return $text;
	}

	/**
	 * Display fields.
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( $description ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}

		echo '<div id="masteriyo-stripe-method" style="width: 100%; height: 228px;" ><div id="masteriyo-stripe-payment-element"></div></div>';
	}

	/**
	 * Create a stripe price objects for subscription.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\Course[] $courses
	 * @param string $currency_code
	 * @param float|string $total
	 *
	 * @return array
	 */
	protected function create_price_objects( $courses, $currency_code, $total ) {
		masteriyo_get_logger()->info( 'Stripe create_price_objects: Start', array( 'source' => 'payment-stripe' ) );
		$price_objects = array_map(
			function ( $course ) use ( $currency_code, $total ) {
				$price_data = array(
					'currency'     => $currency_code,
					'unit_amount'  => masteriyo_round( $total * 100, 2 ),
					'recurring'    => array(
						'interval_count' => $course->get_billing_interval(),
						'interval'       => $course->get_billing_period(),
					),
					'product_data' => array(
						'name'     => $course->get_name(),
						'metadata' => array(
							'course_id'            => $course->get_id(),
							'billing_interval'     => $course->get_billing_interval(),
							'billing_period'       => $course->get_billing_period(),
							'billing_expire_after' => $course->get_billing_expire_after(),
						),
					),
				);
				if ( Helper::use_platform() ) {
					$price = StripeClient::create()->create_price( $price_data );
					return $price['data'];
				}
				return Price::create( $price_data, Helper::get_stripe_options() );
			},
			$courses
		);

		masteriyo_get_logger()->info( 'Stripe create_price_objects: Success', array( 'source' => 'payment-stripe' ) );

		return array_values( $price_objects );
	}

	/**
	 * Create or return stripe customer id.
	 *
	 * @since 1.14.0
	 *
	 * @return object|array
	 */
	protected function create_stripe_customer() {
		masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Start', array( 'source' => 'payment-stripe' ) );

		$user                 = masteriyo_get_current_user();
		$existing_customer_id = get_user_meta( $user->get_id(), '_stripe_customer', true );
		$stripe_customer      = null;

		try {
			if ( ! empty( $existing_customer_id ) ) {
				$stripe_customer = Helper::use_platform()
				? StripeClient::create()->retrieve_customer( $existing_customer_id )
				: Customer::retrieve( $existing_customer_id, Helper::get_stripe_options() );
			}

			if ( ! $stripe_customer ) {
				$customer_data = array(
					'phone'    => $user->get_billing_phone(),
					'email'    => $user->get_billing_email(),
					'name'     => trim( $user->get_billing_first_name() . ' ' . $user->get_billing_last_name() ),
					'address'  => $user->get_billing_address(),
					'metadata' => array(
						'customer_id'    => $user->get_id(),
						'customer_email' => $user->get_email(),
					),
				);

				$stripe_customer = Helper::use_platform()
				? StripeClient::create()->create_customer( $customer_data )['data'] ?? null
				: Customer::create( $customer_data, Helper::get_stripe_options() );
			}

			if ( is_wp_error( $stripe_customer ) ) {
				throw new Exception( $stripe_customer->get_error_message() );
			}
			$stripe_customer = is_array( $stripe_customer ) ? (object) $stripe_customer['data'] : $stripe_customer;
			$customer_id     = $stripe_customer->id;
			update_post_meta( $user->get_id(), '_stripe_customer', $customer_id );
			masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Success', array( 'source' => 'payment-stripe' ) );
			masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Customer ID: ' . $customer_id, array( 'source' => 'payment-stripe' ) );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
		}

		return $stripe_customer;
	}
}
