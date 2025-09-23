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
use Masteriyo\Addons\Stripe\Client\StripeClient;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Masteriyo\Constants;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Addons\Stripe\Setting;
use Stripe\Account;
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

		add_action( 'wp_ajax_masteriyo_stripe_connect', array( $this, 'stripe_connect' ) );
		add_action( 'admin_head', array( $this, 'save_stripe_account' ) );
		add_filter( 'masteriyo_migrations_paths', array( $this, 'append_migrations' ) );
	}

	/**
	 * Append migrations
	 *
	 * @since 1.20.0
	 *
	 * @param array $migrations
	 * @return array
	 */
	public function append_migrations( $migrations ) {
		$migrations[] = plugin_dir_path( MASTERIYO_STRIPE_ADDON_FILE ) . 'migrations';
		return $migrations;
	}

	/**
	 * Save stripe account details after redirect from Stripe.
	 *
	 * @since 1.20.0
	 * @return void
	 */
	public function save_stripe_account() {
		$current_screen = get_current_screen();
		if (
		! $current_screen ||
		'toplevel_page_masteriyo' !== $current_screen->base ||
		! isset( $_GET['nonce'], $_GET['accountId'], $_GET['mode'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'masteriyo_stripe_nonce' )
		) {
			return;
		}

		$account_id = sanitize_text_field( wp_unslash( $_GET['accountId'] ) );
		$mode       = sanitize_text_field( wp_unslash( $_GET['mode'] ) );

		Setting::read();
		Setting::set_props(
			array(
				'enable'         => true,
				'stripe_user_id' => $account_id,
				'sandbox'        => 'test' === $mode,
				'use_platform'   => true,
			)
		);
		Setting::save();
		update_option( '_masteriyo_stripe_integration_method', 'connect' );
		$url = admin_url( 'admin.php?page=masteriyo#/settings?first=payments&second=payment-methods' );
		echo '<script>window.location.href = "' . esc_url_raw( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) ) . '";</script>';
		exit;
	}

	/**
	 * Handle Stripe connect request.
	 *
	 * @since 1.20.0
	 * @return void
	 */
	public function stripe_connect() {
		check_ajax_referer( 'masteriyo_stripe_nonce', 'nonce' );
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		if ( 'disconnect' === $type ) {
			$this->reset_stripe_connect();
			wp_send_json_success(
				array(
					'type' => 'disconnect',
				)
			);
			exit;
		}

		if ( isset( $_POST['state'] ) ) {
			$data = json_decode( wp_unslash( $_POST['state'] ), true );
			$this->update_settings_before_stripe_connect( $data );
		}

		$is_sandbox = masteriyo_bool_to_string( Setting::get( 'sandbox' ) );
		$client     = StripeClient::create();
		$response   = $client->get_account_link(
			array(
				'mode'       => 'yes' === $is_sandbox ? 'test' : 'live',
				'return_url' => sanitize_url( wp_unslash( $_POST['current_page_uri'] ?? '' ) ),
				'nonce'      => sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ),
			)
		);
		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => $response->get_error_message(),
				)
			);
			exit;
		}
		wp_send_json_success(
			array_merge(
				$response,
				array(
					'type' => 'connect',
				)
			)
		);
		exit;
	}

	/**
	 * Update settings before stripe connect.
	 *
	 * @param array $data
	 * @return void
	 */
	private function update_settings_before_stripe_connect( $data ) {
		$sanitize_callbacks = array(
			'enable'         => 'masteriyo_string_to_bool',
			'enable_ideal'   => 'masteriyo_string_to_bool',
			'title'          => 'sanitize_text_field',
			'sandbox'        => 'masteriyo_string_to_bool',
			'description'    => 'sanitize_textarea_field',
			'webhook_secret' => 'sanitize_textarea_field',
		);
		$next_props         = array();
		foreach ( (array) $data as $key => $value ) {
			if ( ! isset( $sanitize_callbacks[ $key ] ) ) {
				continue;
			}
			$next_props[ $key ] = call_user_func( $sanitize_callbacks[ $key ], $value );
		}
		if ( empty( $next_props ) ) {
			return;
		}
		Setting::read();
		Setting::set_props( $next_props );
		Setting::save();
	}

	/**
	 * Reset stripe credentials set from stripe connect.
	 *
	 * @return void
	 */
	private function reset_stripe_connect() {
		Setting::read();
		Setting::set_props(
			array(
				'test_publishable_key' => '',
				'test_secret_key'      => '',
				'live_publishable_key' => '',
				'live_secret_key'      => '',
				'stripe_user_id'       => '',
			)
		);
		Setting::save();
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
		$scripts['backend']['data']['stripe_nonce']        = wp_create_nonce( 'masteriyo_stripe_nonce' );
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
		$stripe_account = null;

		if ( Helper::use_platform() ) {
			$account_response = StripeClient::create()->get_account_details();
			$stripe_account   = ( ! is_wp_error( $account_response ) ) ? ( $account_response['data'] ?? null ) : null;
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			if ( ! empty( Setting::get_stripe_user_id() ) && ! empty( Setting::get_secret_key() ) ) {
				try {
					$stripe_account = Account::retrieve( null, Setting::get_secret_key() );
				} catch ( \Exception $e ) {} // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace, Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		$data['payments']['stripe'] = wp_parse_args(
			Setting::all(),
			array(
				'webhook_endpoint' => Helper::get_webhook_endpoint_url(),
				'account'          => $stripe_account,
				'method'           => get_option( '_masteriyo_stripe_integration_method', 'connect' ),
			)
		);

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

			// Throw error is cart is null.
			if ( ! masteriyo( 'cart' ) ) {
				throw new \Exception( 'Cart not found.' );
			}

			/** @var \Masteriyo\Session\Session */
			$session = masteriyo( 'session' );

			/** @var \Masteriyo\Cart\Cart */
			$cart = masteriyo( 'cart' );
			$cart->get_cart_from_session();

			$email = '';
			if ( $session->get_user_id() ) {
				$user = masteriyo_get_user( $session->get_user_id() );
				if ( ! is_wp_error( $user ) ) {
					$email = $user->get_email();
				}
			}

			$cart_total    = $cart->get_total();
			$currency_code = masteriyo_get_setting( 'payments.currency.currency' );

			// For the local currency usage.
			$item = masteriyo_get_item_from_cart( $cart );
			if ( $item && is_a( $item, 'Masteriyo\Models\Course' ) ) {
				$item_currency = $item->get_currency();

				if ( ! empty( $item_currency ) ) {
					$currency_code = $item_currency;
				}
			}

			$payment_methods = array(
				'card',
			);

			if ( masteriyo_string_to_bool( Setting::get( 'enable_ideal' ) ) ) {
				$payment_methods[] = 'ideal';
			}

			$payment_intent_params = array(
				'amount'               => $this->convert_cart_total_to_stripe_amount( $cart_total, $currency_code ),
				'currency'             => masteriyo_strtolower( $currency_code ),
				'receipt_email'        => $email ? $email : get_bloginfo( 'admin_email' ),
				'payment_method_types' => $payment_methods,
				'metadata'             => array( 'webhookUrl' => Helper::get_webhook_endpoint_url() ),
			);

			if ( Helper::use_platform() ) {
				$payment_intent = StripeClient::create()->create_payment_intent( $payment_intent_params );
				if ( is_wp_error( $payment_intent ) ) {
					throw new \Exception( $payment_intent->get_error_message() );
				}
				$payment_intent = (object) $payment_intent['data'];
			} else {
				$payment_intent = \Stripe\PaymentIntent::create(
					$payment_intent_params,
					Helper::get_stripe_options()
				);
			}

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
						'publishableKey'   => Helper::use_platform() ? ( Setting::is_sandbox_enable() ? MASTERIYO_STRIPE_PLATFORM_TEST_PUBLIC_KEY : MASTERIYO_STRIPE_PLATFORM_LIVE_PUBLIC_KEY ) : Setting::get_publishable_key(),
						'accountId'        => Helper::use_platform() ? Setting::get_stripe_user_id() : null,
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
			$payload        = @file_get_contents( 'php://input' ); // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
			$event          = null;
			$webhook_secret = Setting::get_webhook_secret();

			if ( empty( $payload ) ) {
				masteriyo_get_logger()->error( 'Stripe webhook payload is empty.', array( 'source' => 'payment-stripe' ) );
				throw new Exception( esc_html__( 'Payload is empty.', 'learning-management-system' ), 400 );
			}

			if ( empty( $sig_header ) || empty( $webhook_secret ) ) {
				$event = \Stripe\Event::constructFrom(
					json_decode( $payload, true )
				);
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found

				/**
				 * Filters whether to validate the webhook secret or not.
				 *
				 * @since 1.14.0
				 */
				if ( apply_filters( 'masteriyo_stripe_validate_webhook', true ) ) {
					$event = \Stripe\Event::constructFrom(
						json_decode( $payload, true ),
						$sig_header,
						$webhook_secret
					);
				}
			}

			if ( ! $event ) {
				masteriyo_get_logger()->error( 'Stripe webhook event is null.', array( 'source' => 'payment-stripe' ) );
				throw new Exception( esc_html__( 'Event is null.', 'learning-management-system' ), 400 );
			}

			$result = array();
			if ( masteriyo_starts_with( $event->type, 'payment_intent' ) ) {
				$payment_intent = $event->data->object;

				if ( ! $payment_intent ) {
					masteriyo_get_logger()->error( 'Stripe webhook payment intent is null.', array( 'source' => 'payment-stripe' ) );
					throw new Exception( esc_html__( 'Payment intent is null.', 'learning-management-system' ), 400 );
				}

				if ( isset( $payment_intent->metadata->order_id ) ) {
					$order_id = $payment_intent->metadata->order_id;
					$order    = masteriyo_get_order( $order_id );
					$result   = $this->handle_payment_intent_webhook( $event, $order );
				}
			}
			masteriyo_get_logger()->info( 'Stripe webhook completed.', array( 'source' => 'payment-stripe' ) );
			wp_send_json_success( $result );
		} catch ( UnexpectedValueException $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			$order->add_order_note(
				esc_html__( 'Stripe invalid event type.', 'learning-management-system' )
			);

			wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
		} catch ( SignatureVerificationException $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			$order->add_order_note(
				esc_html__( 'Stripe webhook signature verification failed.', 'learning-management-system' )
			);

			wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
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
	protected function handle_payment_intent_webhook( $event, $order ) {
		masteriyo_get_logger()->info( 'Payment intent webhook triggered.', array( 'source' => 'payment-stripe' ) );
		$status = $this->map_stripe_events_to_order_status( $event->type );

		if ( ! $status ) {
			masteriyo_get_logger()->error( 'Invalid event type.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Invalid event type.', 'learning-management-system' ), 400 );
		}

		$payment_intent = $event->data->object;

		if ( 'payment_intent.succeeded' === $event->type && ! empty( $order->get_billing_email() ) ) {
			try {
				if ( Helper::use_platform() ) {
					$response = StripeClient::create()->update_payment_intent(
						$payment_intent->id,
						array( 'receipt_email' => $order->get_billing_email() )
					);
					if ( is_wp_error( $response ) ) {
						throw new Exception( $response->get_error_message() );
					}
				} else {
					\Stripe\PaymentIntent::update(
						$payment_intent->id,
						array( 'receipt_email' => $order->get_billing_email() )
					);
				}
				masteriyo_get_logger()->info( 'Receipt email updated for Payment Intent.', array( 'source' => 'payment-stripe' ) );
			} catch ( Exception $e ) {
				masteriyo_get_logger()->error( 'Failed to update receipt email: ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			}
		}

		masteriyo_get_logger()->info( 'Before saving the stripe data', array( 'source' => 'payment-stripe' ) );
		$this->save_stripe_data( $event, $order );
		masteriyo_get_logger()->info( 'After saving the stripe data', array( 'source' => 'payment-stripe' ) );

		if ( $status && $status !== $order->get_status() ) {
			$order->set_status( $status );
			$order->save();
		}

		masteriyo_get_logger()->info( 'Payment intent webhook completed.', array( 'source' => 'payment-stripe' ) );
		// Add order notes.
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: Order id, %2$s: Event type, %3$s: Event id */
				esc_html__( 'Payment of %1$s: Event Type = %2$s, Payment Intent ID = %3$s', 'learning-management-system' ),
				$order->get_id(),
				$event->type,
				$event->data->object->id
			)
		);

		return array( 'status' => $status );
	}

	/**
	 * Store stripe data.
	 *
	 * @since 1.14.0
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	protected function save_stripe_data( $event, $order ) {
		masteriyo_get_logger()->info( 'Save stripe data method triggered: ' . print_r( $event, true ) );
		if ( isset( $event->type ) ) {
			update_post_meta( $order->get_id(), '_stripe_event_type', $event->type );
		}

		if ( isset( $event->data->object->status ) ) {
			update_post_meta( $order->get_id(), '_stripe_status', $event->data->object->status );
		}

		if ( isset( $event->data->object->id ) ) {
			update_post_meta( $order->get_id(), '_stripe_payment_intent_id', $event->data->object->id );
		}

		if ( isset( $event->data->object->latest_charge ) ) {
			$order->set_transaction_id( $event->data->object->latest_charge );
		}

		if ( isset( $event->data->object->currency ) ) {
			update_post_meta( $order->get_id(), '_stripe_currency', $event->data->object->currency );
		}

		if ( isset( $event->data->object->payment_method ) ) {
			update_post_meta( $order->get_id(), '_stripe_payment_method', $event->data->object->payment_method );
		}

		if ( isset( $event->data->object->amount ) ) {
			$amount = $event->data->object->amount;

			if ( 0 !== $amount ) {
				$amount = masteriyo_format_decimal( $event->data->object->amount / 100 );
			}

			update_post_meta( $order->get_id(), '_stripe_amount', $amount );
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
	protected function map_stripe_events_to_order_status( $event_type ) {
		masteriyo_get_logger()->info( 'Map stripe events to order status.', array( 'source' => 'payment-stripe' ) );
		$map = array(
			'payment_intent.amount_capturable_updated' => OrderStatus::PENDING,
			'payment_intent.created'                   => OrderStatus::PENDING,
			'payment_intent.processing'                => OrderStatus::PENDING,
			'payment_intent.requires_action'           => OrderStatus::PENDING,
			'payment_intent.succeeded'                 => OrderStatus::COMPLETED,
			'payment_intent.canceled'                  => OrderStatus::CANCELLED,
			'payment_intent.payment_failed'            => OrderStatus::FAILED,
		);

		$status = isset( $map[ $event_type ] ) ? $map[ $event_type ] : null;

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
	protected function convert_cart_total_to_stripe_amount( $total_amount, $currency_code ) {
		masteriyo_get_logger()->info( 'Converting stripe amount.', array( 'source' => 'payment-stripe' ) );
		$currency_code = masteriyo_strtoupper( $currency_code );

		// Return as it is for zero decimal currencies.
		if ( in_array( $currency_code, $this->get_zero_decimal_currencies(), true ) ) {
			$new_total_amount = absint( $total_amount );
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
			'XPF',
		);
	}
}
