<?php
/**
 * PayPal Standard Payment Gateway.
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class       paypal
 * @extends     PaymentGateway
 * @version     2.3.0
 * @package     Masteriyo\Classes\Payment
 */

namespace Masteriyo\Gateways\Paypal;

use Masteriyo\Gateways\Paypal\ApiHandler;
use Masteriyo\Gateways\Paypal\Request;
use Masteriyo\Gateways\Paypal\PdtHandler;
use Masteriyo\Gateways\Paypal\IpnHandler;
use Masteriyo\Abstracts\PaymentGateway;
use Masteriyo\Contracts\PaymentGateway as PaymentGatewayInterface;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Models\Order\Order;

defined( 'ABSPATH' ) || exit;

/**
 * MASTERIYO_Gateway_Paypal Class.
 */
#[\AllowDynamicProperties]
class Paypal extends PaymentGateway implements PaymentGatewayInterface {

	/**
	 * Payment gateway name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name = 'paypal';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 1.0.0
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
	 * @var Logger
	 */
	public static $log = false;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'.
	 *
	 * @since 2.6.10
	 *
	 * @var array
	 */
	protected $supports = array( 'course', 'refund', 'subscription' );

	/**
	 * Constructor for the gateway.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->order_button_text = __( 'Proceed to PayPal', 'learning-management-system' );
		$this->method_title      = __( 'PayPal Standard', 'learning-management-system' );
		/* translators: %s: Link to Masteriyo system status page */
		$this->method_description = __( 'PayPal Standard redirects customers to PayPal to enter their payment information.', 'learning-management-system' );
		$this->supports           = array( 'course', 'refund' );

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->sandbox        = $this->get_option( 'sandbox', false );
		$this->debug          = $this->get_option( 'debug', false );
		$this->email          = $this->get_option( 'email' );
		$this->receiver_email = $this->get_option( 'receiver_email', $this->email );
		$this->identity_token = $this->get_option( 'identity_token' );
		self::$log_enabled    = $this->debug;

		if ( $this->sandbox ) {
			$this->description .= ' ' . sprintf(
				/* translators: %s: Link to PayPal sandbox testing guide page */
				__(
					'SANDBOX ENABLED. You can use sandbox testing accounts only. See the <a href="%s">PayPal Sandbox Testing Guide</a> for more details.',
					'learning-management-system'
				),
				'https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/'
			);
			$this->description = trim( $this->description );
		}

		// Load the settings.
		$this->init_settings();

		add_action( 'masteriyo_order_status_processing', array( $this, 'capture_payment' ) );
		add_action( 'masteriyo_order_status_completed', array( $this, 'capture_payment' ) );

		if ( $this->is_valid_for_use() ) {
			new IpnHandler( $this->sandbox, $this->receiver_email );

			if ( $this->identity_token ) {
				new PdtHandler( $this->sandbox, $this->identity_token );
			}
		} else {
			$this->enabled = false;
		}

		if ( $this->enabled ) {
			add_filter( 'masteriyo_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
		}
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_setup() {
		return ! is_email( $this->email );
	}

	/**
	 * Logging method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = masteriyo_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'paypal' ) );
		}
	}

	/**
	 * Get gateway icon.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_icon() {
		// The local brand badge, the same one the admin's payment settings show, so
		// the header row is one square mark like every other gateway's. The
		// country-dependent acceptance-mark strips that used to stand here are a
		// banner's worth of width in a row sized for a logo, and they now render in
		// the card's body instead — see `payment_fields()`.
		$icon_html = sprintf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( masteriyo_get_plugin_url() . '/includes/Gateways/Paypal/images/paypal.png' ),
			esc_attr__( 'PayPal logo', 'learning-management-system' )
		);

		/**
		 * Filters paypal payment gateway icon.
		 *
		 * @since 1.0.0
		 *
		 * @param string $icon Icon html.
		 * @param string $name Payment gateway name.
		 */
		return apply_filters( 'masteriyo_gateway_icon', $icon_html, $this->name );
	}

	/**
	 * Display the gateway's own content inside its payment method card.
	 *
	 * The description, then the acceptance marks for the store's country — which
	 * cards PayPal will take here, shown where a buyer weighing the method is
	 * looking rather than crammed into the header beside the radio.
	 *
	 * The marks are PayPal's own images, served from paypalobjects.com, so they
	 * are loaded lazily: the body they sit in is closed until the method is
	 * chosen, and a checkout should not wait on a third party for a picture no
	 * one has asked to see.
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		parent::payment_fields();

		// The marks are indexed by the store's country, so with no base country
		// there is nothing to look up.
		$base_country = masteriyo( 'countries' )->get_base_country();

		if ( empty( $base_country ) ) {
			return;
		}

		$marks = array_filter( (array) $this->get_icon_image( $base_country ) );

		if ( empty( $marks ) ) {
			return;
		}

		echo '<span class="masteriyo-payment-method__acceptance">';

		foreach ( $marks as $mark ) {
			printf(
				'<img src="%1$s" alt="%2$s" loading="lazy" />',
				esc_url( $mark ),
				esc_attr__( 'PayPal acceptance mark', 'learning-management-system' )
			);
		}

		echo '</span>';
	}

	/**
	 * Get PayPal images for a country.
	 *
	 * @since 1.0.0
	 *
	 * @param string $country Country code.
	 * @return string|string[] Image URL, or URLs where a country has more than one.
	 */
	protected function get_icon_image( $country ) {
		switch ( $country ) {
			case 'US':
			case 'NZ':
			case 'CZ':
			case 'HU':
			case 'MY':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TR':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_odeme_secenekleri.jpg';
				break;
			case 'GB':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png';
				break;
			case 'MX':
				$icon = array(
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_visa_mastercard_amex.png',
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_debit_card_275x60.gif',
				);
				break;
			case 'FR':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_moyens_paiement_fr.jpg';
				break;
			case 'AU':
				$icon = 'https://www.paypalobjects.com/webstatic/en_AU/mktg/logo/Solutions-graphics-1-184x80.jpg';
				break;
			case 'DK':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_PayPal_betalingsmuligheder_dk.jpg';
				break;
			case 'RU':
				$icon = 'https://www.paypalobjects.com/webstatic/ru_RU/mktg/business/pages/logo-center/AM_mc_vs_dc_ae.jpg';
				break;
			case 'NO':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_just_pp_319x110.jpg';
				break;
			case 'CA':
				$icon = 'https://www.paypalobjects.com/webstatic/en_CA/mktg/logo-image/AM_mc_vs_dc_ae.jpg';
				break;
			case 'HK':
				$icon = 'https://www.paypalobjects.com/webstatic/en_HK/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'SG':
				$icon = 'https://www.paypalobjects.com/webstatic/en_SG/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TW':
				$icon = 'https://www.paypalobjects.com/webstatic/en_TW/mktg/logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TH':
				$icon = 'https://www.paypalobjects.com/webstatic/en_TH/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'JP':
				$icon = 'https://www.paypal.com/ja_JP/JP/i/bnr/horizontal_solution_4_jcb.gif';
				break;
			case 'IN':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			default:
				// The local badge, which is also what the header wears. The path this
				// named — `/includes/gateways/paypal/assets/images/paypal.png` — was
				// wrong in both its case and its `assets/` segment, so every country
				// outside the list above got a 404 on any host with a case-sensitive
				// filesystem.
				$icon = masteriyo_get_plugin_url() . '/includes/Gateways/Paypal/images/paypal.png';
				break;
		}

		/**
		 * Filters paypal payment gateway icon URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $icon_url Icon URL.
		 */
		return apply_filters( 'masteriyo_paypal_icon', $icon );
	}

	/**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			masteriyo_get_currency(),
			/**
			 * Filters supported currencies of paypal.
			 *
			 * @since 1.0.0
			 *
			 * @param string[] $currencies Paypal supported currencies.
			 */
			apply_filters(
				'masteriyo_paypal_supported_currencies',
				array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR' )
			),
			true
		);
	}

	/**
	 * Get the transaction URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  Order $order Order object.
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( $this->sandbox ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order          = masteriyo_get_order( $order_id );
		$paypal_request = new Request( $this );

		$this->create_subscription( $order );

		return array(
			'result'   => 'success',
			'redirect' => $paypal_request->get_request_url( $order, $this->sandbox ),
		);
	}

	/**
	 * Can the order be refunded via PayPal?
	 *
	 * @since 1.0.0
	 *
	 * @param  Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$has_api_creds = false;

		if ( $this->sandbox ) {
			$has_api_creds = $this->get_option( 'sandbox_api_username' ) && $this->get_option( 'sandbox_api_password' ) && $this->get_option( 'sandbox_api_signature' );
		} else {
			$has_api_creds = $this->get_option( 'api_username' ) && $this->get_option( 'api_password' ) && $this->get_option( 'api_signature' );
		}

		return $order && $order->get_transaction_id() && $has_api_creds;
	}

	/**
	 * Init the API class and set the username/password etc.
	 *
	 * @since 1.0.0
	 */
	protected function init_api() {
		if ( $this->sandbox ) {
			ApiHandler::$api_username  = $this->get_option( 'sandbox_api_username' );
			ApiHandler::$api_password  = $this->get_option( 'sandbox_api_password' );
			ApiHandler::$api_signature = $this->get_option( 'sandbox_api_signature' );
		} else {
			ApiHandler::$api_username  = $this->get_option( 'live_api_username' );
			ApiHandler::$api_password  = $this->get_option( 'live_api_password' );
			ApiHandler::$api_signature = $this->get_option( 'live_api_signature' );
		}

		ApiHandler::$sandbox = $this->sandbox;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = masteriyo_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			return new \WP_Error( 'error', __( 'Refund failed.', 'learning-management-system' ) );
		}

		$this->init_api();

		$result = ApiHandler::refund_transaction( $order, $amount, $reason );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
			return new \WP_Error( 'error', $result->get_error_message() );
		}

		$this->log( 'Refund Result: ' . masteriyo_print_r( $result, true ) );

		switch ( strtolower( $result->ACK ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			case 'success':
			case 'successwithwarning':
				$order->add_order_note(
					/* translators: 1: Refund amount, 2: Refund ID */
					sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'learning-management-system' ), $result->GROSSREFUNDAMT, $result->REFUNDTRANSACTIONID ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				);
				return true;
		}

		return isset( $result->L_LONGMESSAGE0 ) ? new \WP_Error( 'error', $result->L_LONGMESSAGE0 ) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @since 1.0.0
	 *
	 * @param  int $order_id Order ID.
	 */
	public function capture_payment( $order_id ) {
		$order = masteriyo_get_order( $order_id );

		if ( 'paypal' === $order->get_payment_method() && OrderStatus::PENDING === $order->get_meta( '_paypal_status', true ) && $order->get_transaction_id() ) {
			$this->init_api();
			$result = ApiHandler::do_capture( $order );

			if ( is_wp_error( $result ) ) {
				$this->log( 'Capture Failed: ' . $result->get_error_message(), 'error' );
				/* translators: %s: Paypal gateway error message */
				$order->add_order_note( sprintf( __( 'Payment could not be captured: %s', 'learning-management-system' ), $result->get_error_message() ) );
				return;
			}

			$this->log( 'Capture Result: ' . masteriyo_print_r( $result, true ) );

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( ! empty( $result->PAYMENTSTATUS ) ) {
				switch ( $result->PAYMENTSTATUS ) {
					case 'Completed':
						/* translators: 1: Amount, 2: Authorization ID, 3: Transaction ID */
						$order->add_order_note( sprintf( __( 'Payment of %1$s was captured - Auth ID: %2$s, Transaction ID: %3$s', 'learning-management-system' ), $result->AMT, $result->AUTHORIZATIONID, $result->TRANSACTIONID ) );
						update_post_meta( $order->get_id(), '_paypal_status', $result->PAYMENTSTATUS );
						update_post_meta( $order->get_id(), '_transaction_id', $result->TRANSACTIONID );
						break;
					default:
						/* translators: 1: Authorization ID, 2: Payment status */
						$order->add_order_note( sprintf( __( 'Payment could not be captured - Auth ID: %1$s, Status: %2$s', 'learning-management-system' ), $result->AUTHORIZATIONID, $result->PAYMENTSTATUS ) );
						break;
				}
			}
			// phpcs:enable
		}
	}

	/**
	 * Custom PayPal order received text.
	 *
	 * @since 1.0.0
	 * @param string   $text Default text.
	 * @param Order $order Order data.
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		if ( $order && $this->name === $order->get_payment_method() ) {
			return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your PayPal account to view transaction details.', 'learning-management-system' );
		}

		return $text;
	}

	/**
	 * Create the subscription backing a recurring order.
	 *
	 * Subscriptions are pro's — the model, the statuses and the licence check all
	 * live there — so this asks rather than builds. Core establishes only that the
	 * order is one a subscription would be created for, which it does through the
	 * `masteriyo_order_has_recurring_courses()` seam; with pro absent that seam
	 * answers false and the action never fires.
	 *
	 * The guard has to come first. This ran before it, resolving `license` from a
	 * container that has no such binding in the free product, and so threw on every
	 * PayPal checkout there — caught by `Checkout`, which turned it into a failed
	 * checkout with no way to complete the purchase.
	 *
	 * @since 2.6.10
	 *
	 * @param Order $order Order data.
	 */
	public function create_subscription( $order ) {
		if ( ! masteriyo_order_has_recurring_courses( $order ) ) {
			return;
		}

		/**
		 * Fires when a gateway order needs its subscription created.
		 *
		 * Pro answers this from `SubscriptionServiceProvider`. Nothing answers it in
		 * the free product, where no order can be recurring in the first place.
		 *
		 * @param \Masteriyo\Models\Order\Order $order   The order.
		 * @param string                        $gateway The gateway that took it.
		 */
		do_action( 'masteriyo_gateway_create_order_subscription', $order, $this->name );
	}
}
