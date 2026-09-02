<?php
/**
 * Masteriyo Stripe addon setup.
 *
 * @package Masteriyo\StripeAddon
 *
 * @since 2.0.0
 */

namespace Masteriyo\Addons\Stripe;

use Exception;
use Masteriyo\Addons\Stripe\Client\StripeClient;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethodDomain;
use Masteriyo\Constants;
use Stripe\Subscription;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Addons\Stripe\Setting;
use Masteriyo\Pro\Enums\SubscriptionStatus;
use Stripe\Account;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

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
	 * @since 2.0.0
	 *
	 * @var \Masteriyo\Addons\Stripe\StripeAddon
	 */
	protected static $instance = null;

	/**
	 * Why the last deletion of each subscription was refused, by ID.
	 *
	 * Written by `refuse_deletion()`, read back (and cleared) by
	 * `explain_refused_deletion()` later in the same request.
	 *
	 * @var array<int, \WP_Error>
	 */
	private $deletion_refusals = array();

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	protected function __construct() {
	}

	/**
	 * Get class instance.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	public function __clone() {
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 2.0.0
	 */
	public function __wakeup() {
	}

	/**
	 * Initialize the application.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.0.0
	 */
	protected function init_hooks() {
		add_filter( 'masteriyo_payment_gateways', array( $this, 'add_payment_gateway' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'load_localized_scripts' ) );
		// add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
		add_action( 'wp_ajax_masteriyo_stripe_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_stripe_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_masteriyo_stripe_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_stripe_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'masteriyo_checkout_order_received', array( $this, 'verify_payment_on_return' ) );
		// Both completion actions fire inside payment_complete()'s per-order
		// lock: the normal transition, and the already-completed replay (a
		// webhook redelivery after an admin completed the order by hand).
		add_action( 'masteriyo_payment_complete', array( $this, 'maybe_create_subscription_for_paid_order' ) );
		add_action( 'masteriyo_payment_complete_order_status_' . OrderStatus::COMPLETED, array( $this, 'maybe_create_subscription_for_paid_order' ) );
		// Hooked here, not in the gateway constructor: gateways are only constructed
		// where something resolves masteriyo( 'payment-gateways' ), and the
		// order-received render — the one page that applies this filter — never does.
		add_filter( 'masteriyo_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );

		// Setting related hooks.
		add_filter( 'masteriyo_new_setting', array( $this, 'save_setting' ), 10 );
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );

		add_filter( 'masteriyo_subscription_deletable', array( $this, 'cancel_stripe_subscription' ), 10, 3 );
		add_filter( 'masteriyo_rest_subscription_cannot_delete_error', array( $this, 'explain_refused_deletion' ), 10, 2 );
		add_action( 'wp_ajax_masteriyo_stripe_connect', array( $this, 'stripe_connect' ) );
		add_action( 'admin_head', array( $this, 'save_stripe_account' ) );
		add_action( 'masteriyo_admin_notices', array( $this, 'show_webhook_secret_notice' ) );
		add_filter( 'masteriyo_migrations_paths', array( $this, 'append_migrations' ) );
	}

	/**
	 * Append migrations
	 *
	 * @since 2.30.0
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
	 * @return void
	 */
	public function save_stripe_account() {
		$current_screen = get_current_screen();
		if (
		! $current_screen ||
		'toplevel_page_masteriyo' !== $current_screen->base ||
		! current_user_can( 'manage_options' ) ||
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
		delete_transient( 'masteriyo_stripe_account_cache' );
		update_option( '_masteriyo_stripe_integration_method', 'connect' );
		$this->maybe_register_payment_method_domain();
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

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to manage Stripe settings.', 'learning-management-system' ),
				),
				403
			);
		}

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
		delete_transient( 'masteriyo_stripe_account_cache' );
	}

	/**
	 * Localize admin scripts.
	 *
	 * @since 2.6.10
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
	 * @since 2.6.10
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

		delete_transient( 'masteriyo_stripe_account_cache' );

		$this->maybe_register_payment_method_domain();
	}

	/**
	 * Register the site domain with Stripe so wallet payment methods
	 * (Apple Pay, Google Pay, Link) can render in the Payment Element.
	 * Stripe handles Apple's merchant validation behind the scenes; the
	 * domain only has to be registered once per account and mode.
	 *
	 * Runs on settings save. A stored fingerprint skips the API call until
	 * the domain, mode or credentials change; a failed attempt is retried
	 * on the next save and never blocks saving.
	 */
	public function maybe_register_payment_method_domain() {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! $domain || ! Setting::is_enable() ) {
			return;
		}

		$credential = Helper::use_platform() ? Setting::get_stripe_user_id() : Setting::get_secret_key();

		if ( empty( $credential ) ) {
			return;
		}

		$fingerprint = $domain . '|' . ( Setting::is_sandbox_enable() ? 'test' : 'live' ) . '|' . md5( $credential );

		if ( get_option( '_masteriyo_stripe_payment_method_domain' ) === $fingerprint ) {
			return;
		}

		try {
			$this->register_domain_with_stripe( $domain );
			update_option( '_masteriyo_stripe_payment_method_domain', $fingerprint );
			masteriyo_get_logger()->info( 'Payment method domain registered with Stripe: ' . $domain, array( 'source' => 'payment-stripe' ) );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error(
				'Payment method domain registration failed for ' . $domain . ': ' . $e->getMessage()
				. ' Register the domain manually under Stripe Dashboard > Settings > Payment method domains to show Apple Pay and Google Pay.',
				array( 'source' => 'payment-stripe' )
			);
		}
	}

	/**
	 * Send the domain registration request to Stripe.
	 *
	 * @param string $domain Domain host name.
	 *
	 * @throws Exception When the request fails.
	 */
	protected function register_domain_with_stripe( $domain ) {
		if ( Helper::use_platform() ) {
			$response = StripeClient::create()->create_payment_method_domain( array( 'domain_name' => $domain ) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( esc_html( $response->get_error_message() ) );
			}
		} else {
			PaymentMethodDomain::create( array( 'domain_name' => $domain ), Helper::get_stripe_options() );
		}
	}

	/**
	 * Append stripe setting to the global settings.
	 *
	 * @since 2.6.10
	 *
	 * @param array $data Array data.
	 * @param \Masteriyo\Models\Setting            $setting Setting object.
	 * @param string  $context Context.
	 * @return \Masteriyo\RestApi\Controllers\Version1\SettingsController $controller
	 */
	public function append_setting_in_response( $data, $object, $request, $controller ) {
		$stripe_account = null;
		$cache_key      = 'masteriyo_stripe_account_cache';
		$cached         = get_transient( $cache_key );

		if ( false !== $cached ) {
			$stripe_account = $cached ? $cached : null;
		} elseif ( Helper::use_platform() ) {
			$account_response = StripeClient::create()->get_account_details();
			$stripe_account   = ( ! is_wp_error( $account_response ) ) ? ( $account_response['data'] ?? null ) : null;
			set_transient( $cache_key, $stripe_account ? $stripe_account : '', HOUR_IN_SECONDS );
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			if ( ! empty( Setting::get_stripe_user_id() ) && ! empty( Setting::get_secret_key() ) ) {
				try {
					$stripe_account = Account::retrieve( null, Setting::get_secret_key() );
					set_transient( $cache_key, $stripe_account ? $stripe_account : '', HOUR_IN_SECONDS );
				} catch ( \Exception $e ) { // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace, Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					set_transient( $cache_key, '', MINUTE_IN_SECONDS * 5 );
				}
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
	 * Create payment intent.
	 *
	 * @since 2.0.0
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

			$stripe_amount        = Helper::convert_cart_total_to_stripe_amount( $cart_total, $currency_code );
			$has_recurring_course = $cart->contains_recurring_course();

			if ( 0 === $stripe_amount ) {
				masteriyo_get_logger()->error( 'Payment intent not created: cart total is ' . $cart_total . ' and currency is ' . $currency_code . '.', array( 'source' => 'payment-stripe' ) );
				throw new \Exception( __( 'Cart is empty or total is 0. Cannot create a payment intent.', 'learning-management-system' ) );
			}

			// Reuse the session's pending payment intent instead of creating a duplicate on concurrent requests.
			$existing_pi_id = $session->get( 'stripe_payment_intent_id' );
			if ( $existing_pi_id ) {
				try {
					$existing_pi = Helper::retrieve_payment_intent( $existing_pi_id );

					if ( $existing_pi && Helper::is_payment_intent_reusable( $existing_pi, $stripe_amount, $currency_code, $has_recurring_course ) ) {
						masteriyo_get_logger()->info( 'Reusing existing payment intent: ' . $existing_pi_id, array( 'source' => 'payment-stripe' ) );
						wp_send_json_success(
							array(
								'clientSecret'    => $existing_pi->client_secret,
								'paymentIntentId' => $existing_pi->id,
							)
						);
						exit();
					}
				} catch ( \Exception $e ) {
					masteriyo_get_logger()->warning( 'Could not retrieve existing payment intent (' . $existing_pi_id . '): ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
				}
			}

			$payment_intent_params = array_merge(
				array(
					'amount'        => $stripe_amount,
					'currency'      => masteriyo_strtolower( $currency_code ),
					'receipt_email' => $email ? $email : get_bloginfo( 'admin_email' ),
					'metadata'      => array( 'webhookUrl' => Helper::get_webhook_endpoint_url() ),
				),
				Helper::get_payment_intent_method_params( $has_recurring_course )
			);

			// The session-reuse check above is not atomic: two concurrent requests
			// can both read no stored ID and both reach here. The key is built only
			// from state that exists before they race — the session key, the cart's
			// terms, and the last stored intent ID — so racing requests send the
			// same key and Stripe deduplicates the create. A completed purchase
			// leaves its (no longer reusable) intent ID in the session, rotating
			// the key so the next purchase gets a fresh intent.
			$idempotency_key = 'masteriyo-pi-' . md5(
				implode(
					'|',
					array(
						(string) $session->get_key(),
						(string) $stripe_amount,
						masteriyo_strtolower( $currency_code ),
						$has_recurring_course ? '1' : '0',
						(string) $existing_pi_id,
					)
				)
			);

			if ( Helper::use_platform() ) {
				$payment_intent = StripeClient::create()->create_payment_intent( $payment_intent_params, array( 'Idempotency-Key' => $idempotency_key ) );
				if ( is_wp_error( $payment_intent ) ) {
					masteriyo_get_logger()->info( print_r( $payment_intent->get_error_data(), true ), array( 'source' => 'payment-stripe' ) );
					throw new \Exception( $payment_intent->get_error_message() );
				}
				$payment_intent = (object) $payment_intent['data'];
			} else {
				$payment_intent = \Stripe\PaymentIntent::create(
					$payment_intent_params,
					array_merge( Helper::get_stripe_options(), array( 'idempotency_key' => $idempotency_key ) )
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	public function handle_webhook() {
		try {
			masteriyo_get_logger()->info( 'Stripe webhook triggered.', array( 'source' => 'payment-stripe' ) );

			// Validate and parse webhook request.
			$sig_header = $this->get_stripe_signature_header();
			$payload    = $this->get_webhook_payload();

			// Verify webhook signature and construct event.
			$event = $this->construct_and_verify_webhook_event( $payload, $sig_header );

			// Process webhook event.
			$result = $this->process_webhook_event( $event );

			masteriyo_get_logger()->info( 'Stripe webhook completed successfully.', array( 'source' => 'payment-stripe' ) );
			wp_send_json_success( $result );
		} catch ( UnexpectedValueException $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ), 400 );
		} catch ( SignatureVerificationException $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ), 403 );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			$http_code = in_array( $e->getCode(), array( 400, 403, 404, 500 ), true ) ? $e->getCode() : 400;
			wp_send_json_error( array( 'message' => $e->getMessage() ), $http_code );
		}
	}

	/**
	 * Verify a payment intent against the live Stripe API.
	 *
	 * Retrieves the payment intent directly from Stripe to confirm it exists
	 * and its status matches the webhook event, preventing forged payloads
	 * from completing orders even if signature verification is somehow bypassed.
	 *
	 * @param string $payment_intent_id The Stripe payment intent ID.
	 * @throws Exception If the payment intent cannot be verified.
	 */
	protected function verify_payment_intent_with_stripe( $payment_intent_id ) {
		masteriyo_get_logger()->info( 'Verifying payment intent with Stripe API: ' . $payment_intent_id, array( 'source' => 'payment-stripe' ) );

		try {
			$live_intent = Helper::retrieve_payment_intent( $payment_intent_id );
			$pi_status   = isset( $live_intent->status ) ? $live_intent->status : '';

			if ( 'succeeded' !== $pi_status ) {
				masteriyo_get_logger()->error(
					'Stripe webhook: payment intent ' . $payment_intent_id . ' has status "' . $pi_status . '" in Stripe, not "succeeded".',
					array( 'source' => 'payment-stripe' )
				);
				throw new Exception( esc_html__( 'Payment intent verification failed: status mismatch.', 'learning-management-system' ), 400 );
			}

			masteriyo_get_logger()->info( 'Payment intent verified with Stripe API.', array( 'source' => 'payment-stripe' ) );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( 'Stripe webhook: failed to verify payment intent: ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			throw $e;
		}
	}

	/**
	 * Verify the payment with Stripe when the customer returns from checkout.
	 *
	 * The webhook is the primary completion path, but when it is missing,
	 * misconfigured or delayed the customer lands here charged with the order
	 * still pending. Nothing from the redirect is trusted: the intent saved on
	 * the order at process_payment() is retrieved from Stripe server-side and
	 * the order completes only if Stripe says that exact payment succeeded.
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function verify_payment_on_return( $order ) {
		if ( ! in_array( $order->get_payment_method(), array( 'stripe', 'ideal' ), true ) ) {
			return;
		}

		if ( ! $order->has_status( OrderStatus::PENDING ) ) {
			return;
		}

		$payment_intent_id = get_post_meta( $order->get_id(), '_stripe_payment_intent_id', true );

		if ( ! $payment_intent_id ) {
			// Orders created before the intent ID was saved as meta carry it as the transaction ID.
			$payment_intent_id = $order->get_transaction_id();
		}

		if ( ! $payment_intent_id || ! masteriyo_starts_with( (string) $payment_intent_id, 'pi_' ) ) {
			return;
		}

		$current_mode = Setting::is_sandbox_enable() ? 'test' : 'live';
		$order_mode   = get_post_meta( $order->get_id(), '_stripe_mode', true );

		if ( $order_mode && $order_mode !== $current_mode ) {
			// The keys were switched since payment: the intent cannot be retrieved in the
			// current mode, so nothing can be proven either way. Never mark the order failed.
			$this->record_return_verification(
				$order,
				'unverifiable_mode',
				__( 'Stripe payment could not be verified: the payment was made in the other test/live mode than the currently configured keys.', 'learning-management-system' )
			);
			return;
		}

		try {
			$payment_intent = Helper::retrieve_payment_intent( $payment_intent_id );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( 'Stripe return: failed to retrieve payment intent ' . $payment_intent_id . ': ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			$this->record_return_verification(
				$order,
				'unverifiable_api',
				__( 'Stripe payment could not be verified: the payment intent could not be retrieved from Stripe.', 'learning-management-system' )
			);
			return;
		}

		$verdict = Helper::get_payment_intent_verdict( $payment_intent, $order->get_id(), $order->get_total(), $order->get_currency() );

		if ( 'mismatch' !== $verdict && isset( $payment_intent->status ) ) {
			update_post_meta( $order->get_id(), '_stripe_status', $payment_intent->status );
		}

		if ( 'succeeded' === $verdict ) {
			if ( isset( $payment_intent->latest_charge ) ) {
				update_post_meta( $order->get_id(), '_stripe_charge_id', $payment_intent->latest_charge );
			}

			if ( $order->payment_complete( $payment_intent_id ) ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: Stripe payment intent ID */
						__( 'Stripe payment verified on customer return (payment intent %s succeeded).', 'learning-management-system' ),
						$payment_intent_id
					)
				);
				masteriyo_get_logger()->info( 'Stripe return: payment verified, order ' . $order->get_id() . ' completed.', array( 'source' => 'payment-stripe' ) );
			}
		} elseif ( 'processing' === $verdict ) {
			// iDEAL and friends: the money is on its way but not settled. The order stays
			// pending and the webhook (or a later return) completes it.
			$this->record_return_verification(
				$order,
				'processing',
				__( 'Stripe reports the payment as processing. The order stays pending until Stripe confirms it.', 'learning-management-system' )
			);
		} elseif ( 'mismatch' === $verdict ) {
			masteriyo_get_logger()->error( 'Stripe return: payment intent ' . $payment_intent_id . ' does not match order ' . $order->get_id() . ' (order id, amount or currency).', array( 'source' => 'payment-stripe' ) );
			$this->record_return_verification(
				$order,
				'mismatch',
				__( 'Stripe payment could not be verified: the payment intent does not match this order\'s ID, amount or currency.', 'learning-management-system' )
			);
		}
		// 'incomplete': the customer likely abandoned or the payment failed — no state to change here.
	}

	/**
	 * Create the subscription for a recurring order once its payment settles.
	 *
	 * Runs on the payment-completion actions, so it fires exactly once under
	 * payment_complete()'s per-order lock, whichever path completes the order
	 * first — webhook or verified return. The subscription-exists check makes
	 * a replay (webhook redelivery) a no-op.
	 *
	 * Never throws: the customer has already paid, so a failure here must not
	 * fail the completion — it is logged for the merchant instead.
	 *
	 * @param integer $order_id Order ID.
	 */
	public function maybe_create_subscription_for_paid_order( $order_id ) {
		// Subscriptions are pro's; in the free product no order is recurring.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return;
		}

		$order = masteriyo_get_order( $order_id );

		if ( ! $order || ! in_array( $order->get_payment_method(), array( 'stripe', 'ideal' ), true ) ) {
			return;
		}

		if ( ! masteriyo_order_has_recurring_courses( $order ) || masteriyo_get_order_subscription( $order ) ) {
			return;
		}

		try {
			$subscription = ( new CreditCard() )->create_subscription_for_paid_order( $order );

			masteriyo_get_logger()->info(
				'Stripe subscription created for paid order ' . $order->get_id() . ' (subscription ' . $subscription->get_id() . ').',
				array( 'source' => 'payment-stripe' )
			);
		} catch ( Exception $e ) {
			$stripe_subscription_id = get_post_meta( $order->get_id(), '_stripe_subscription_id', true );

			masteriyo_get_logger()->error(
				'Stripe subscription creation failed for paid order ' . $order->get_id() . ': ' . $e->getMessage()
				. ( $stripe_subscription_id
					? ' Stripe subscription ' . $stripe_subscription_id . ' exists and is recorded on the order; the local record is missing and a webhook redelivery resumes it.'
					: ' No Stripe subscription was created; the customer paid the first period, so recreate the subscription or refund the order.' ),
				array( 'source' => 'payment-stripe' )
			);
		}
	}

	/**
	 * The thankyou-page message for a Stripe order, honest about its state.
	 *
	 * @param string $text Default text.
	 * @param \Masteriyo\Models\Order\Order|null $order Order object, null when the order could not be resolved.
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		// 'ideal' is the Stripe iDEAL variant recorded on the order for display purposes; it is still this gateway's order.
		if ( ! $order || ! in_array( $order->get_payment_method(), array( 'stripe', 'ideal' ), true ) ) {
			return $text;
		}

		if ( ! $order->is_paid() ) {
			if ( 'processing' === get_post_meta( $order->get_id(), '_stripe_status', true ) ) {
				return esc_html__( 'Thank you. Your payment is processing — you will get access to your courses as soon as it is confirmed.', 'learning-management-system' );
			}

			// Not verified as paid (yet): don't claim the transaction completed.
			return $text;
		}

		return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your Stripe account to view transaction details.', 'learning-management-system' );
	}

	/**
	 * Record a return-verification outcome on the order, once per outcome.
	 *
	 * The order-received page can be reloaded any number of times while the
	 * order is pending; repeating the same note on every load buries the useful ones.
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 * @param string $outcome Outcome key.
	 * @param string $note Order note for the new outcome.
	 */
	private function record_return_verification( $order, $outcome, $note ) {
		if ( get_post_meta( $order->get_id(), '_stripe_return_verification', true ) === $outcome ) {
			return;
		}

		update_post_meta( $order->get_id(), '_stripe_return_verification', $outcome );
		$order->add_order_note( $note );
	}

	/**
	 * Handle payment intent webhook.
	 *
	 * @since 2.6.10
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

		// For order-completing events, verify the payment intent actually
		// exists in Stripe and has the expected status before trusting the webhook payload.
		if ( 'payment_intent.succeeded' === $event->type ) {
			$this->verify_payment_intent_with_stripe( $payment_intent->id );
		}

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

		// This order object was loaded before the Stripe round-trips above, and a
		// concurrent return or webhook may have settled the order since. Re-read
		// the status straight from the posts table — the object cache was primed
		// at load and cannot see a concurrent write — so the settled-order guard
		// below judges the current row, not the stale copy. Without this, a late
		// payment_failed racing a completion writes failed over completed.
		global $wpdb;
		$fresh_status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM {$wpdb->posts} WHERE ID = %d", $order->get_id() ) );

		if ( $fresh_status && $fresh_status !== $order->get_status() ) {
			$order->set_status( $fresh_status );
		}

		if ( OrderStatus::COMPLETED === $status ) {
			// payment_complete() checks status and date_paid under a per-order lock,
			// so a redelivery or a race with the customer's return completes the
			// order — and sends its emails — exactly once. A completion that failed
			// must fail the delivery: answering 200 tells Stripe never to retry,
			// and the order would stay pending — the stuck state this webhook
			// exists to prevent.
			if ( ! $order->payment_complete( $payment_intent->id ) ) {
				throw new Exception( esc_html__( 'Order completion failed. Retry the delivery.', 'learning-management-system' ), 500 );
			}

			// The completion hooks provision a recurring order's subscription,
			// and a failure in there is caught and logged so the paid customer
			// keeps the completion. But answering 200 would tell Stripe the
			// delivery is done and no retry is coming — so report unfinished
			// provisioning as a failed delivery. The redelivery finds the order
			// paid and retries just the provisioning (see route_webhook_event).
			if ( masteriyo_service_provider_exists( 'subscription' ) ) {
				if ( masteriyo_order_has_recurring_courses( $order ) && ! masteriyo_get_order_subscription( $order ) ) {
					throw new Exception( esc_html__( 'The order is paid but its subscription is not provisioned yet. Retry the delivery.', 'learning-management-system' ), 500 );
				}
			}
		} elseif ( $order->is_paid() || $order->has_status( OrderStatus::REFUNDED ) ) {
			// Stripe does not guarantee delivery order: a payment_failed from a
			// declined first attempt can arrive after the succeeded event for the
			// same intent. No late event may downgrade a paid order — a downgrade
			// revokes the enrollment. A refunded order is a paid order whose money
			// went back; no late event may rewrite that history either.
			masteriyo_get_logger()->info( 'Stripe webhook: ignoring ' . $event->type . ' for settled order ' . $order->get_id() . '.', array( 'source' => 'payment-stripe' ) );
		} elseif ( $status && $status !== $order->get_status() ) {
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
	 * Handle subscription webhook callback.
	 *
	 * @since 2.6.10
	 *
	 * @param \Stripe\Event $event Stripe event.
	 * @param \Masteriyo\Pro\Models\Subscription $subscription
	 */
	protected function handle_subscription_webhook( $event, $subscription ) {
		// Subscription statuses are pro's. A free site has no recurring payments for
		// a webhook to be about, so there is nothing here for it to do.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return array();
		}

		masteriyo_get_logger()->info( 'Subscription webhook triggered.', array( 'source' => 'payment-stripe' ) );
		if ( in_array( $event->type, array( 'customer.subscription.created', 'customer.subscription.updated' ), true ) ) {
			// Both carry the subscription's own status — map from it, never from
			// the event type. Stripe does not guarantee delivery order: a `created`
			// arriving after `updated` must not downgrade an active subscription
			// to a status the event type merely implies.
			$status = $this->map_stripe_subscription_status_to_internal_subscription_status( $event->data->object->status );
		} elseif ( 'payment_intent.succeeded' === $event->type ) {
			$status = SubscriptionStatus::ACTIVE;
		} else {
			$status = $this->map_stripe_events_to_subscription_status( $event->type );
		}

		if ( ! $subscription->get_transaction_id() ) {
			$subscription->set_transaction_id( get_post_meta( $subscription->get_parent_id(), '_transaction_id', true ) );
		}

		$subscription->set_status( $status );
		$subscription->save();

		$this->save_stripe_subscription_data( $event, $subscription );

		return array( 'status' => $status );
	}

	/**
	 * Map stripe subscription status to internal subscription status.
	 *
	 * @since 2.6.10
	 *
	 * @param string $status Stripe subscription status.
	 * @return string
	 */
	protected function map_stripe_subscription_status_to_internal_subscription_status( $status ) {
		// The statuses mapped to are pro's, so without pro there is nothing to map to.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return '';
		}

		masteriyo_get_logger()->info( 'Map stripe subscription status to internal subscription status.', array( 'source' => 'payment-stripe' ) );
		$map = array(
			'active'             => SubscriptionStatus::ACTIVE,
			'past_due'           => SubscriptionStatus::ON_HOLD,
			'unpaid'             => SubscriptionStatus::PENDING,
			'canceled'           => SubscriptionStatus::CANCELLED,
			'incomplete'         => SubscriptionStatus::ON_HOLD,
			'incomplete_expired' => SubscriptionStatus::EXPIRED,
			'trialing'           => SubscriptionStatus::ACTIVE,
			'paused'             => SubscriptionStatus::PENDING,
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : SubscriptionStatus::PENDING;
	}

	/**
	 * Save stripe subscription data to internal subscription model.
	 *
	 * @since 2.6.10
	 *
	 * @param \Stripe\Event $event
	 * @param \Masteriyo\Pro\Models\Subscription $subscription
	 */
	protected function save_stripe_subscription_data( $event, $subscription ) {
		masteriyo_get_logger()->info( 'Save stripe subscription data to internal subscription model.', array( 'source' => 'payment-stripe' ) );
		if ( isset( $event->data->object->customer ) ) {
			update_post_meta( $subscription->get_id(), '_stripe_customer_id', $event->data->object->customer );
		}

		if ( isset( $event->data->object->id ) ) {
			update_post_meta( $subscription->get_id(), '_stripe_subscription_id', $event->data->object->id );
		}

		if ( isset( $event->data->object->default_payment_method ) ) {
			update_post_meta( $subscription->get_id(), '_stripe_subscription_default_payment_method', $event->data->object->default_payment_method );
		}
	}

	/**
	 * Convert stripe events to subscription status.
	 *
	 * @since 2.6.10
	 *
	 * @param string $event_type Stripe event type.
	 *
	 * @return string
	 */
	protected function map_stripe_events_to_subscription_status( $event_type ) {
		// The statuses mapped to are pro's, so without pro there is nothing to map to.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return null;
		}

		masteriyo_get_logger()->info( 'Map stripe events to subscription status.', array( 'source' => 'payment-stripe' ) );
		$map = array(
			'customer.subscription.deleted'                => SubscriptionStatus::CANCELLED,
			'customer.subscription.paused'                 => SubscriptionStatus::ON_HOLD,
			'customer.subscription.pending_update_applied' => SubscriptionStatus::PENDING,
			'customer.subscription.pending_update_expired' => SubscriptionStatus::EXPIRED,
			'customer.subscription.resumed'                => SubscriptionStatus::PENDING,
		);

		$status = isset( $map[ $event_type ] ) ? $map[ $event_type ] : null;

		return $status;
	}

	/**
	 * Store stripe data.
	 *
	 * @since 2.0.2
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	protected function save_stripe_data( $event, $order ) {
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
			// Redelivered events are matched against the stored intent ID, so the
			// charge ID must not replace it — a mismatch makes Stripe retry the
			// delivery until it disables the endpoint.
			update_post_meta( $order->get_id(), '_stripe_charge_id', $event->data->object->latest_charge );
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
				$currency = isset( $event->data->object->currency ) ? $event->data->object->currency : $order->get_currency();
				$amount   = masteriyo_format_decimal( Helper::convert_stripe_amount_to_cart_total( $amount, $currency ) );
			}

			update_post_meta( $order->get_id(), '_stripe_amount', $amount );
		}
	}

	/**
	 * Map stripe payment intent events to order events.
	 *
	 * @since 2.0.0
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
	 * Cancel the Stripe subscription before its local record is deleted.
	 *
	 * Runs on the `masteriyo_subscription_deletable` filter: a failed
	 * cancellation refuses the deletion, because the local record is the
	 * only handle a retry has — deleting it while Stripe keeps billing is
	 * invisible until the customer's next renewal charges anyway.
	 *
	 * @since 2.6.10
	 * @param boolean|\WP_Error $deletable Whether the deletion may proceed.
	 * @param int $id Subscription id.
	 * @param \Masteriyo\Pro\Models\Subscription $subscription Subscription model object.
	 * @return boolean|\WP_Error
	 */
	public function cancel_stripe_subscription( $deletable, $id, $subscription ) {
		// The filter only fires from pro's subscription repository; free
		// never gets here, and this stands down explicitly if it somehow does.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return $deletable;
		}

		if ( true !== $deletable ) {
			return $deletable;
		}

		// Every gateway's cancel handler hears this filter; only this
		// gateway's subscriptions carry a Stripe subscription to cancel.
		if ( ! $subscription || ! in_array( $subscription->get_payment_method(), array( 'stripe', 'ideal' ), true ) ) {
			return $deletable;
		}

		masteriyo_get_logger()->info( 'Cancelling stripe subscription.', array( 'source' => 'payment-stripe' ) );
		masteriyo_get_logger()->info( 'Subscription id: ' . $id, array( 'source' => 'payment-stripe' ) );

		// The model has carried the Stripe subscription ID since creation.
		// The meta reads cover rows from other eras: the subscription post
		// (the webhook backfill wrote it there) and the parent order (where
		// creation records it the moment the remote subscription exists).
		$subscription_id = (string) $subscription->get_subscription_id();

		if ( ! $subscription_id ) {
			$subscription_id = (string) get_post_meta( $id, '_stripe_subscription_id', true );
		}

		if ( ! $subscription_id && $subscription->get_parent_id() ) {
			$subscription_id = (string) get_post_meta( $subscription->get_parent_id(), '_stripe_subscription_id', true );
		}

		if ( ! $subscription_id ) {
			masteriyo_get_logger()->warning(
				'No Stripe subscription is recorded for local subscription ' . $id . '; nothing to cancel on Stripe.',
				array( 'source' => 'payment-stripe' )
			);
			return $deletable;
		}

		// Stripe IDs are test/live-scoped: the other mode's keys cannot see
		// this subscription, so the cancel could only fail. Refuse with the
		// reason instead of a Stripe "no such subscription".
		$order_mode   = $subscription->get_parent_id() ? (string) get_post_meta( $subscription->get_parent_id(), '_stripe_mode', true ) : '';
		$current_mode = Setting::is_sandbox_enable() ? 'test' : 'live';

		if ( $order_mode && $order_mode !== $current_mode ) {
			return $this->refuse_deletion(
				$id,
				new \WP_Error(
					'masteriyo_stripe_mode_mismatch',
					__( 'The Stripe subscription cannot be cancelled: it was created in the other test/live mode than the currently configured keys.', 'learning-management-system' )
				)
			);
		}

		// The local status is not consulted: the dashboard can mark a record
		// cancelled without Stripe hearing about it, so Stripe's own record
		// is the only ground truth on whether anything is still billing.
		try {
			if ( Helper::use_platform() ) {
				$client = StripeClient::create();
				$remote = $client->retrieve_subscription( $subscription_id );

				if ( is_wp_error( $remote ) ) {
					// A subscription Stripe no longer knows needs no cancelling;
					// any other failure keeps the local record for a retry.
					if ( ! $this->stripe_says_gone( $remote ) ) {
						masteriyo_get_logger()->error( 'Stripe subscription cancellation failed: ' . $remote->get_error_message(), array( 'source' => 'payment-stripe' ) );
						return $this->refuse_deletion( $id, $remote );
					}
				} elseif ( ! $this->is_remote_subscription_canceled( $remote ) ) {
					$response = $client->cancel_subscription( $subscription_id );

					if ( is_wp_error( $response ) && ! $this->stripe_says_gone( $response ) ) {
						masteriyo_get_logger()->error( 'Stripe subscription cancellation failed: ' . $response->get_error_message(), array( 'source' => 'payment-stripe' ) );
						return $this->refuse_deletion( $id, $response );
					}
				}
			} else {
				$stripe_subscription = Subscription::retrieve( $subscription_id, Helper::get_stripe_options() );

				// Cancelling an already-canceled subscription is a Stripe 400,
				// which would make this record undeletable; the retrieve just
				// proved there is nothing left to cancel.
				if ( 'canceled' !== $stripe_subscription->status ) {
					$stripe_subscription->cancel();
				}
			}
		} catch ( InvalidRequestException $e ) {
			// 404: already gone on Stripe — deleting locally is the right outcome.
			if ( 404 !== $e->getHttpStatus() ) {
				masteriyo_get_logger()->error( 'Stripe subscription cancellation failed: ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
				return $this->refuse_deletion( $id, new \WP_Error( 'masteriyo_stripe_cancellation_failed', $e->getMessage() ) );
			}
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( 'Stripe subscription cancellation failed: ' . $e->getMessage(), array( 'source' => 'payment-stripe' ) );
			return $this->refuse_deletion( $id, new \WP_Error( 'masteriyo_stripe_cancellation_failed', $e->getMessage() ) );
		}

		unset( $this->deletion_refusals[ $id ] );
		masteriyo_get_logger()->info( 'Subscription cancelled.', array( 'source' => 'payment-stripe' ) );

		return $deletable;
	}

	/**
	 * Whether a platform retrieve response says the subscription is already canceled.
	 *
	 * The platform proxy wraps the Stripe object under a top-level `data` key,
	 * so the status lives at $remote['data']['status']. A payload with no
	 * status reads as not canceled — cancelling anyway is the safe move.
	 *
	 * @param array $remote The platform client's retrieve-subscription response.
	 * @return boolean
	 */
	protected function is_remote_subscription_canceled( $remote ) {
		return 'canceled' === ( $remote['data']['status'] ?? '' );
	}

	/**
	 * Whether a platform-client error means Stripe no longer has the resource.
	 *
	 * @param \WP_Error $error The platform client's error.
	 * @return boolean
	 */
	private function stripe_says_gone( $error ) {
		$data = $error->get_error_data();

		return is_array( $data ) && 404 === ( $data['response_code'] ?? 0 );
	}

	/**
	 * Remember why a subscription's deletion was refused, and refuse it.
	 *
	 * The deletable filter can only answer the repository, which collapses
	 * the answer to "not deleted"; the REST response is built later, in
	 * CrudController. This carries the reason across to
	 * `explain_refused_deletion()`, within the one request both run in.
	 *
	 * @param int $id Subscription id.
	 * @param \WP_Error $error Why the deletion was refused.
	 * @return \WP_Error The same error, for returning from the filter.
	 */
	private function refuse_deletion( $id, $error ) {
		$this->deletion_refusals[ $id ] = $error;

		return $error;
	}

	/**
	 * Replace the REST layer's generic cannot-delete error with the reason.
	 *
	 * Runs on `masteriyo_rest_subscription_cannot_delete_error`. Without it
	 * the admin sees "The subscription cannot be deleted." while the fix —
	 * switch the test/live mode back, or retry once Stripe answers again —
	 * is only in the log.
	 *
	 * @param \WP_Error $error The generic error.
	 * @param \Masteriyo\Database\Model $subscription The subscription that was not deleted.
	 * @return \WP_Error
	 */
	public function explain_refused_deletion( $error, $subscription ) {
		$id = $subscription->get_id();

		if ( isset( $this->deletion_refusals[ $id ] ) ) {
			$refusal = $this->deletion_refusals[ $id ];
			unset( $this->deletion_refusals[ $id ] );

			return new \WP_Error( $refusal->get_error_code(), $refusal->get_error_message(), array( 'status' => 400 ) );
		}

		return $error;
	}

	/**
	 * Get Stripe signature header from request.
	 *
	 *
	 * @throws Exception If signature header is missing.
	 * @return string
	 */
	private function get_stripe_signature_header() {
		// phpcs:disable WordPress.Security.ValidatedInput.InputNotSanitized
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;
		// phpcs:enable WordPress.Security.ValidatedInput.InputNotSanitized

		if ( empty( $sig_header ) ) {
			masteriyo_get_logger()->error( 'Stripe webhook: Stripe-Signature header is missing.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Stripe-Signature header is missing.', 'learning-management-system' ), 400 );
		}

		return $sig_header;
	}

	/**
	 * Get webhook payload from request body.
	 *
	 *
	 * @throws Exception If payload is empty.
	 * @return string
	 */
	private function get_webhook_payload() {
		$payload = file_get_contents( 'php://input' );

		if ( false === $payload ) {
			masteriyo_get_logger()->error( 'Stripe webhook: failed to read payload from input stream.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Failed to read webhook payload.', 'learning-management-system' ), 400 );
		}

		if ( empty( $payload ) ) {
			masteriyo_get_logger()->error( 'Stripe webhook payload is empty.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Payload is empty.', 'learning-management-system' ), 400 );
		}

		return $payload;
	}

	/**
	 * Construct and verify webhook event from payload.
	 *
	 *
	 * @param string $payload Raw webhook payload.
	 * @param string $sig_header Stripe signature header.
	 *
	 * @throws Exception If webhook secret is not configured.
	 * @throws Exception If event cannot be constructed.
	 * @return \Stripe\Event
	 */
	private function construct_and_verify_webhook_event( $payload, $sig_header ) {
		$webhook_secret = Setting::get_webhook_secret();

		if ( empty( $webhook_secret ) ) {
			masteriyo_get_logger()->error( 'Stripe webhook: webhook secret is not configured.', array( 'source' => 'payment-stripe' ) );
			throw new Exception(
				esc_html__( 'Webhook secret is not configured. Please configure the webhook secret in Stripe settings.', 'learning-management-system' ),
				400
			);
		}

		$event = Webhook::constructEvent( $payload, $sig_header, $webhook_secret );

		if ( ! $event ) {
			masteriyo_get_logger()->error( 'Stripe webhook event could not be constructed from the payload.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Stripe webhook event could not be constructed.', 'learning-management-system' ), 400 );
		}

		return $event;
	}

	/**
	 * Process webhook event and dispatch to appropriate handler.
	 *
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 *
	 * @throws Exception If event type is not supported.
	 * @return array
	 */
	private function process_webhook_event( $event ) {
		if ( masteriyo_starts_with( $event->type, 'payment_intent' ) ) {
			return $this->process_payment_intent_event( $event );
		}

		if ( masteriyo_starts_with( $event->type, 'customer.subscription' ) ) {
			return $this->process_subscription_event( $event );
		}

		// Log unhandled event types but don't error
		masteriyo_get_logger()->info(
			'Stripe webhook event type not handled: ' . $event->type,
			array( 'source' => 'payment-stripe' )
		);

		return array( 'status' => 'ignored' );
	}

	/**
	 * Process a subscription lifecycle webhook event.
	 *
	 * Restores the routing that `handle_subscription_webhook()` was written for.
	 * It was lost when the Stripe Connect refactor rewrote this router around
	 * signature and payment-intent verification (07e12a1e, Sep 2025) and did not
	 * carry the subscription branch across — the handler survived with no caller,
	 * so `customer.subscription.*` events have been logged as unhandled and
	 * discarded since. Subscriptions were still being created throughout, by
	 * `CreditCard::process_payment()`, so a customer cancelling at Stripe stayed
	 * active here.
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 *
	 * @throws Exception If the subscription cannot be found.
	 * @return array
	 */
	private function process_subscription_event( $event ) {
		// Subscriptions are pro's — the model, the repository and the statuses.
		// A free site creates none, so there is nothing for the event to be about.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return array(
				'status' => 'ignored',
				'reason' => 'subscriptions_unavailable',
			);
		}

		$object = isset( $event->data->object ) ? $event->data->object : null;

		if ( ! $object || ( ! isset( $object->metadata->subscription_id ) && ! isset( $object->metadata->order_id ) ) ) {
			masteriyo_get_logger()->warning(
				'Stripe webhook: subscription event ' . $event->type . ' has neither subscription_id nor order_id in metadata.',
				array( 'source' => 'payment-stripe' )
			);

			return array(
				'status' => 'skipped',
				'reason' => 'no_subscription_id',
			);
		}

		// subscription_id is patched onto the Stripe subscription after creation;
		// order_id is set at creation itself, so it still routes the event when
		// that patch never landed.
		$subscription = isset( $object->metadata->subscription_id )
			? masteriyo_get_subscription( absint( $object->metadata->subscription_id ) )
			: masteriyo_get_order_subscription( absint( $object->metadata->order_id ) );

		if ( ! $subscription ) {
			masteriyo_get_logger()->error(
				'Stripe webhook: subscription not found for ' . (
					isset( $object->metadata->subscription_id )
						? 'subscription_id ' . $object->metadata->subscription_id
						: 'order_id ' . ( $object->metadata->order_id ?? '' )
				),
				array( 'source' => 'payment-stripe' )
			);
			throw new Exception( esc_html__( 'Subscription not found.', 'learning-management-system' ), 404 );
		}

		return $this->handle_subscription_webhook( $event, $subscription );
	}

	/**
	 * Process payment intent webhook event.
	 *
	 *
	 * @param \Stripe\Event $event Stripe event object.
	 *
	 * @throws Exception If order cannot be found or validated.
	 * @return array
	 */
	private function process_payment_intent_event( $event ) {
		$payment_intent = $event->data->object;

		if ( ! $payment_intent ) {
			masteriyo_get_logger()->error( 'Stripe webhook payment intent is null.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Payment intent is null.', 'learning-management-system' ), 400 );
		}

		// Check if metadata contains order_id
		if ( ! isset( $payment_intent->metadata->order_id ) ) {
			masteriyo_get_logger()->warning(
				'Stripe webhook: payment intent ' . $payment_intent->id . ' has no order_id in metadata.',
				array( 'source' => 'payment-stripe' )
			);
			return array(
				'status' => 'skipped',
				'reason' => 'no_order_id',
			);
		}

		$order_id = absint( $payment_intent->metadata->order_id );
		$order    = masteriyo_get_order( $order_id );

		if ( ! $order ) {
			masteriyo_get_logger()->error(
				'Stripe webhook: order not found for order_id: ' . $order_id,
				array( 'source' => 'payment-stripe' )
			);
			throw new Exception( esc_html__( 'Order not found.', 'learning-management-system' ), 404 );
		}

		// 'ideal' is the Stripe iDEAL variant recorded on the order for display purposes (see Checkout::process_order_payment());
		// the order is still processed by the 'stripe' gateway, so it must be accepted here too.
		if ( ! in_array( $order->get_payment_method(), array( 'stripe', 'ideal' ), true ) ) {
			masteriyo_get_logger()->error( 'Stripe webhook: order payment method is not Stripe.', array( 'source' => 'payment-stripe' ) );
			throw new Exception( esc_html__( 'Invalid payment method for order.', 'learning-management-system' ), 400 );
		}

		$stored_payment_intent_id = get_post_meta( $order_id, '_stripe_payment_intent_id', true );

		if ( empty( $stored_payment_intent_id ) ) {
			$stored_payment_intent_id = $order->get_transaction_id();
		}

		if ( empty( $stored_payment_intent_id ) || $stored_payment_intent_id !== $payment_intent->id ) {
			masteriyo_get_logger()->error(
				'Stripe webhook: payment intent ID does not match stored transaction ID for order ' . $order_id,
				array( 'source' => 'payment-stripe' )
			);
			throw new Exception( esc_html__( 'Payment intent ID mismatch.', 'learning-management-system' ), 400 );
		}

		// A payment intent that names a subscription is a renewal, and the
		// subscription has to hear about it as well as the order. The second half of
		// the routing lost in 07e12a1e; it runs after the order has been verified,
		// so a forged intent cannot reach a subscription either.
		if ( masteriyo_service_provider_exists( 'subscription' ) && isset( $payment_intent->metadata->subscription_id ) ) {
			$subscription = masteriyo_get_subscription( absint( $payment_intent->metadata->subscription_id ) );

			if ( $subscription ) {
				$this->handle_subscription_webhook( $event, $subscription );
			} else {
				masteriyo_get_logger()->warning(
					'Stripe webhook: payment intent names subscription_id ' . $payment_intent->metadata->subscription_id . ', which does not resolve.',
					array( 'source' => 'payment-stripe' )
				);
			}
		}

		// A redelivered success event for an order that is already paid (webhook
		// retry, or the customer's return verified it first) must be acknowledged
		// with a 200, or Stripe keeps retrying and eventually disables the endpoint.
		// The subscription routing above has already run — only the order part is done.
		if ( 'payment_intent.succeeded' === $event->type && $order->is_paid() ) {
			// Paid, but perhaps not provisioned: a crash or Stripe failure inside
			// the completion hooks leaves a recurring order without its
			// subscription, and this redelivery is the retry that heals it — the
			// same for an admin who marked the order paid while the intent was
			// still processing. It stands down when the subscription exists, and
			// it never throws, so the delivery below is acknowledged whatever
			// provisioning did: one redelivery is the retry budget, then the
			// error log is the recovery path.
			$this->maybe_create_subscription_for_paid_order( $order->get_id() );

			masteriyo_get_logger()->info(
				'Stripe webhook: order ' . $order_id . ' is already paid; acknowledging event ' . $event->type . ' as already processed.',
				array( 'source' => 'payment-stripe' )
			);
			return array( 'status' => 'already_processed' );
		}

		return $this->handle_payment_intent_webhook( $event, $order );
	}

	/**
	 * Show admin notice if Stripe is enabled but webhook secret is not configured.
	 */
	public function show_webhook_secret_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! Setting::is_enable() ) {
			return;
		}

		if ( ! empty( Setting::get_webhook_secret() ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=masteriyo#/settings?first=payments&second=payment-methods&expand=stripe' );

		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s" class="masteriyo-notice-link">%s</a>.</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: the product's name */
					__( '%s Stripe:', 'learning-management-system' ),
					masteriyo_get_plugin_name()
				)
			),
			esc_html(
				sprintf(
					/* translators: %s: the product's name */
					__( 'Your webhook signing secret is missing. Copy it from the Stripe Dashboard and save it in %s so payments can be confirmed correctly.', 'learning-management-system' ),
					masteriyo_get_plugin_name()
				)
			),
			esc_url( $settings_url ),
			esc_html(
				sprintf(
					/* translators: %s: the product's name */
					__( 'Open %s’s Stripe settings', 'learning-management-system' ),
					masteriyo_get_plugin_name()
				)
			)
		);
	}
}
