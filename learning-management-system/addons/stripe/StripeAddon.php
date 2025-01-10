<?php

/**
 * Masteriyo Stripe addon setup.
 *
 * @package Masteriyo\StripeAddon
 *
 * @since 1.14.0
 */

namespace Masteriyo\Addons\Stripe;

use Exception;
use Stripe\Stripe;
use Masteriyo\Constants;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Addons\Stripe\Setting;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Stripe class.
 *
 * @class Masteriyo\Stripe
 */

class StripeAddon {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.14.0
	 *
	 * @var \Masteriyo\Addons\Stripe\StripeAddon
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
	protected function __construct() {
	}

	/**
	 * Get class instance.
	 *
	 * @since 1.14.0
	 *
	 * @return \Masteriyo\Addons\Stripe\StripeAddon
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.14.0
	 */
	public function __clone() {
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.14.0
	 */
	public function __wakeup() {
	}

	/**
	 * Initialize the application.
	 *
	 * @since 1.14.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.14.0
	 */
	protected function init_hooks() {
		add_filter( 'masteriyo_payment_gateways', array( $this, 'add_payment_gateway' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'load_localized_scripts' ) );
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
		add_action( 'wp_ajax_masteriyo_stripe_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_stripe_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_masteriyo_stripe_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_stripe_webhook', array( $this, 'handle_webhook' ) );

		// Setting related hooks.
		add_filter( 'masteriyo_new_setting', array( $this, 'save_setting' ), 10 );
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );
	}

	/**
	 * Localize admin scripts.
	 *
	 * @since 1.14.0
	 * @param array $scripts Admin scripts.
	 * @return array
	 */
	public function localize_admin_scripts( $scripts ) {
		$scripts['backend']['data']['is_stripe_test_mode'] = masteriyo_bool_to_string( Setting::get( 'sandbox' ) );
		return $scripts;
	}

	/**
	 * Save setting.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\Setting $setting
	 */
	public function save_setting() {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		if ( ! isset( $request['payments']['stripe'] ) ) {
			return;
		}

		Setting::read();

		// Sanitization.
		if ( isset( $request['payments']['stripe']['enable'] ) ) {
			Setting::set( 'enable', masteriyo_string_to_bool( $request['payments']['stripe']['enable'] ) );
		}

		if ( isset( $request['payments']['stripe']['enable_ideal'] ) ) {
			Setting::set( 'enable_ideal', masteriyo_string_to_bool( $request['payments']['stripe']['enable_ideal'] ) );
		}

		if ( isset( $request['payments']['stripe']['title'] ) ) {
			Setting::set( 'title', $request['payments']['stripe']['title'] );
		}

		if ( isset( $request['payments']['stripe']['sandbox'] ) ) {
			Setting::set( 'sandbox', masteriyo_string_to_bool( $request['payments']['stripe']['sandbox'] ) );
		}

		if ( isset( $request['payments']['stripe']['description'] ) ) {
			Setting::set( 'description', sanitize_textarea_field( $request['payments']['stripe']['description'] ) );
		}

		if ( isset( $request['payments']['stripe']['test_publishable_key'] ) ) {
			Setting::set( 'test_publishable_key', sanitize_textarea_field( $request['payments']['stripe']['test_publishable_key'] ) );
		}

		if ( isset( $request['payments']['stripe']['test_secret_key'] ) ) {
			Setting::set( 'test_secret_key', sanitize_textarea_field( $request['payments']['stripe']['test_secret_key'] ) );
		}

		if ( isset( $request['payments']['stripe']['live_publishable_key'] ) ) {
			Setting::set( 'live_publishable_key', sanitize_textarea_field( $request['payments']['stripe']['live_publishable_key'] ) );
		}

		if ( isset( $request['payments']['stripe']['live_secret_key'] ) ) {
			Setting::set( 'live_secret_key', sanitize_textarea_field( $request['payments']['stripe']['live_secret_key'] ) );
		}

		if ( isset( $request['payments']['stripe']['webhook_secret'] ) ) {
			Setting::set( 'webhook_secret', sanitize_textarea_field( $request['payments']['stripe']['webhook_secret'] ) );
		}
	}

	/**
	 * Append stripe setting to the global settings.
	 *
	 * @since 1.14.0
	 *
	 * @param array $data Array data.
	 * @param \Masteriyo\Models\Setting            $setting Setting object.
	 * @param string  $context Context.
	 * @return \Masteriyo\RestApi\Controllers\Version1\SettingsController $controller
	 */
	public function append_setting_in_response( $data, $object, $request, $controller ) {
		// Add webhook endpoint.
		$data['payments']['stripe'] = wp_parse_args( Setting::all(), array( 'webhook_endpoint' => Helper::get_webhook_endpoint_url() ) );

		return $data;
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.14.0
	 */
	public function enqueue_scripts() {
		wp_add_inline_style( 'masteriyo-checkout', '.payment-method-stripe .payment-method__detail { width: 100%; }' );
	}

	/**
	 * Create payment intent.
	 *
	 * @since 1.14.0
	 */
	public function create_payment_intent() {
		try {
			masteriyo_get_logger()->info( 'Create payment intent.', array( 'source' => 'payment-stripe' ) );

			\Stripe\Stripe::setApiKey( Setting::get_secret_key() );

			// Throw error is cart is null.
			if ( ! masteriyo( 'cart' ) ) {
				throw new \Exception( 'Cart not found.' );
			}

			$session = masteriyo( 'session' );
			$cart    = masteriyo( 'cart' );
			$cart->get_cart_from_session();

			$cart_total    = $cart->get_total();
			$currency_code = masteriyo_get_setting( 'payments.currency.currency' );

			$payment_methods = array(
				'card',
			);

			if ( masteriyo_string_to_bool( Setting::get( 'enable_ideal' ) ) ) {
				$payment_methods[] = 'ideal';
			}

			// Create a PaymentIntent with amount and currency
			$payment_intent = \Stripe\PaymentIntent::create(
				array(
					'amount'               => $this->convert_cart_total_to_stripe_amount( $cart_total, $currency_code ),
					'currency'             => masteriyo_strtolower( $currency_code ),
					'receipt_email'        => get_bloginfo( 'admin_email' ),
					'payment_method_types' => $payment_methods,
				)
			);

			$session->put( 'stripe_payment_intent_id', $payment_intent->id );

			$output = array(
				'clientSecret'    => $payment_intent->client_secret,
				'paymentIntentId' => $payment_intent->id,
			);

			masteriyo_get_logger()->info( 'Payment intent created.', array( 'source' => 'payment-stripe' ) );
			wp_send_json_success( $output );
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( 'Error while creating payment intent. Error: ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			wp_send_json_error( array( 'error' => $e->getMessage() ), 500 );
		}

		exit();
	}

	/**
	 * Load scripts.
	 *
	 * @since 1.14.0
	 *
	 * @param array $scripts Scripts which are to be loaded.
	 *
	 * @return array
	 */
	public function load_scripts( $scripts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return array_merge(
			$scripts,
			array(
				'stripe-official' => array(
					'src'      => 'https://js.stripe.com/v3/',
					'context'  => 'public',
					'version'  => Constants::get( 'MASTERIYO_STRIPE_VERSION' ),
					'callback' => function () {
						return masteriyo_is_checkout_page();
					},
				),
				'stripe'          => array(
					'src'      => plugin_dir_url( MASTERIYO_STRIPE_ADDON_FILE ) . 'assets/js/frontend/stripe' . $suffix . '.js',
					'context'  => 'public',
					'version'  => Constants::get( 'MASTERIYO_STRIPE_VERSION' ),
					'callback' => function () {
						return masteriyo_is_checkout_page();
					},
				),
			)
		);
	}

	/**
	 * Load localized scripts.
	 *
	 * @since 1.14.0
	 *
	 * @param array $localized_scripts
	 * @return array
	 */
	public function load_localized_scripts( $localized_scripts ) {
		$user = masteriyo_get_current_user();

		return array_merge(
			$localized_scripts,
			array(
				'stripe' => array(
					'name' => '_MASTERIYO_STRIPE_',
					'data' => array(
						'publishableKey'   => Setting::get_publishable_key(),
						'ajaxURL'          => admin_url( 'admin-ajax.php' ),
						'thankYouPage'     => masteriyo_get_checkout_endpoint_url( 'order-received' ),
						'blogName'         => get_bloginfo( 'name' ),
						'billingFirstName' => $user ? $user->get_billing_first_name() : '',
						'billingLastName'  => $user ? $user->get_billing_last_name() : '',
						'billingAddress1'  => $user ? $user->get_billing_address_1() : '',
						'billingAddress2'  => $user ? $user->get_billing_address_2() : '',
						'billingState'     => $user ? $user->get_billing_state() : '',
						'billingCity'      => $user ? $user->get_billing_city() : '',
						'billingPostcode'  => $user ? $user->get_billing_postcode() : '',
						'billingCountry'   => $user ? $user->get_billing_country() : '',
					),
				),
			)
		);
	}

	/**
	 * Add stripe payment gateway to available payment gateways.
	 *
	 * @since 1.14.0
	 *
	 * @param Masteriyo\Abstracts\PaymentGateway[]
	 *
	 * @return Masteriyo\Abstracts\PaymentGateway[]
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = CreditCard::class;
		return $gateways;
	}

	/**
	 * Handle webhook.
	 *
	 * @since 1.14.0
	 */
	public function handle_webhook() {
		try {
			masteriyo_get_logger()->info( 'Stripe webhook triggered.', array( 'source' => 'payment-stripe' ) );

			$sig_header     = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;
			$payload        = @file_get_contents('php://input');  // phpcs:disable
			$event          = null;
			$webhook_secret = Setting::get_webhook_secret();

			if (empty($payload)) {
				masteriyo_get_logger()->error('Stripe webhook payload is empty.', array('source' => 'payment-stripe'));
				throw new Exception(esc_html__('Payload is empty.', 'learning-management-system'), 400);
			}


			if (empty($sig_header) || empty($webhook_secret)) {
				$event = \Stripe\Event::constructFrom(
					json_decode($payload, true)
				);
			} else {

				/**
				 * Filters whether to validate the webhook secret or not.
				 *
				 * @since 1.14.0
				 */
				if (apply_filters('masteriyo_stripe_validate_webhook', true)) {
					$event = \Stripe\Event::constructFrom(
						json_decode($payload, true),
						$sig_header,
						$webhook_secret
					);
				}
			}

			if (! $event) {
				masteriyo_get_logger()->error('Stripe webhook event is null.', array('source' => 'payment-stripe'));
				throw new Exception(esc_html__('Event is null.', 'learning-management-system'), 400);
			}

			$result = array();
			if (masteriyo_starts_with($event->type, 'payment_intent')) {
				$payment_intent = $event->data->object;

				if (! $payment_intent) {
					masteriyo_get_logger()->error('Stripe webhook payment intent is null.', array('source' => 'payment-stripe'));
					throw new Exception(esc_html__('Payment intent is null.', 'learning-management-system'), 400);
				}

				if (isset($payment_intent->metadata->order_id)) {
					$order_id = $payment_intent->metadata->order_id;
					$order    = masteriyo_get_order($order_id);
					$result = $this->handle_payment_intent_webhook($event, $order);
				}
			}
			masteriyo_get_logger()->info('Stripe webhook completed.', array('source' => 'payment-stripe'));
			wp_send_json_success($result);
		} catch (UnexpectedValueException $e) {
			masteriyo_get_logger()->error($e->getMessage(), array('source' => 'payment-stripe'));
			$order->add_order_note(
				esc_html__('Stripe invalid event type.', 'learning-management-system')
			);

			wp_send_json_error(array('message' => $e->getMessage()), $e->getCode());
		} catch (SignatureVerificationException $e) {
			masteriyo_get_logger()->error($e->getMessage(), array('source' => 'payment-stripe'));
			$order->add_order_note(
				esc_html__('Stripe webhook signature verification failed.', 'learning-management-system')
			);

			wp_send_json_error(array('message' => $e->getMessage()), $e->getCode());
		} catch (Exception $e) {
			masteriyo_get_logger()->error($e->getMessage(), array('source' => 'payment-stripe'));
			wp_send_json_error(array('message' => $e->getMessage()), $e->getCode());
		}

		exit();
	}

	/**
	 * Handle payment intent webhook.
	 *
	 * @since 1.14.0
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	protected function handle_payment_intent_webhook($event, $order) {
		masteriyo_get_logger()->info('Payment intent webhook triggered.', array('source' => 'payment-stripe'));
		$status = $this->map_stripe_events_to_order_status($event->type);

		if (! $status) {
			masteriyo_get_logger()->error('Invalid event type.', array('source' => 'payment-stripe'));
			throw new Exception(esc_html__('Invalid event type.', 'learning-management-system'), 400);
		}

		masteriyo_get_logger()->info('Before saving the stripe data', array('source' => 'payment-stripe'));
		$this->save_stripe_data($event, $order);
		masteriyo_get_logger()->info('After saving the stripe data', array('source' => 'payment-stripe'));

		if ($status && $status !== $order->get_status()) {
			$order->set_status($status);
			$order->save();
		}

		masteriyo_get_logger()->info('Payment intent webhook completed.', array('source' => 'payment-stripe'));
		// Add order notes.
		$order->add_order_note(
			sprintf(
				esc_html__('Payment of %1$s: Event Type = %2$s, Payment Intent ID = %3$s', 'learning-management-system'),
				$order->get_id(),
				$event->type,
				$event->data->object->id
			)
		);

		return array('status' => $status);
	}

	/**
	 * Store stripe data.
	 *
	 * @since 1.14.0
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	protected function save_stripe_data($event, $order) {
		masteriyo_get_logger()->info('Save stripe data method triggered: ' . print_r($event, true));
		if (isset($event->type)) {
			update_post_meta($order->get_id(), '_stripe_event_type', $event->type);
		}

		if (isset($event->data->object->status)) {
			update_post_meta($order->get_id(), '_stripe_status', $event->data->object->status);
		}

		if (isset($event->data->object->id)) {
			update_post_meta($order->get_id(), '_stripe_payment_intent_id', $event->data->object->id);
		}

		if (isset($event->data->object->latest_charge)) {
			$order->set_transaction_id($event->data->object->latest_charge);
		}

		if (isset($event->data->object->currency)) {
			update_post_meta($order->get_id(), '_stripe_currency', $event->data->object->currency);
		}

		if (isset($event->data->object->payment_method)) {
			update_post_meta($order->get_id(), '_stripe_payment_method', $event->data->object->payment_method);
		}

		if (isset($event->data->object->amount)) {
			$amount = $event->data->object->amount;

			if (0 !== $amount) {
				$amount = masteriyo_format_decimal($event->data->object->amount / 100);
			}

			update_post_meta($order->get_id(), '_stripe_amount', $amount);
		}
	}

	/**
	 * Map stripe payment intent events to order events.
	 *
	 * @since 1.14.0
	 *
	 * @param string $event_type Stripe event type.
	 *
	 * @return string|null
	 */
	protected function map_stripe_events_to_order_status($event_type) {
		masteriyo_get_logger()->info('Map stripe events to order status.', array('source' => 'payment-stripe'));
		$map = array(
			'payment_intent.amount_capturable_updated' => OrderStatus::PENDING,
			'payment_intent.created'                   => OrderStatus::PENDING,
			'payment_intent.processing'                => OrderStatus::PENDING,
			'payment_intent.requires_action'           => OrderStatus::PENDING,
			'payment_intent.succeeded'                 => OrderStatus::COMPLETED,
			'payment_intent.canceled'                  => OrderStatus::CANCELLED,
			'payment_intent.payment_failed'            => OrderStatus::FAILED,
		);

		$status = isset($map[$event_type]) ? $map[$event_type] : null;

		return $status;
	}

	/**
	 * Convert cart total to stripe amount which differs according to the currency code.
	 *
	 * @since 1.14.0
	 * @see https://stripe.com/docs/currencies
	 *
	 * @param float|integer|string $total_amount Total cart amount.
	 * @param string $currency_code Currency code.
	 *
	 * @return integer
	 */
	protected function convert_cart_total_to_stripe_amount($total_amount, $currency_code) {
		masteriyo_get_logger()->info("Converting stripe amount.", array('source' => 'payment-stripe'));
		$currency_code = masteriyo_strtoupper($currency_code);

		// Return as it is for zero decimal currencies.
		if (in_array($currency_code, $this->get_zero_decimal_currencies(), true)) {
			$new_total_amount = absint($total_amount);
		} else {
			$new_total_amount = masteriyo_round( $total_amount, 2 ) * 100;
		}

		return $new_total_amount;
	}

	/**
	 * Return zero-decimal currencies meaning currencies which don't have decimal values.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	protected function get_zero_decimal_currencies() {
		return array(
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'JPY',
			'KMF',
			'KRW',
			'MGA',
			'PYG',
			'RWF',
			'UGX',
			'VND',
			'VUV',
			'XAF',
			'XOF',
			'XPF'
		);
	}
}
