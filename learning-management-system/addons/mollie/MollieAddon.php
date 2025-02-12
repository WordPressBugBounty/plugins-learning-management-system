<?php
/**
 * Masteriyo Mollie setup.
 *
 * @package Masteriyo\Addons\Mollie
 *
 * @since 1.16.0
 */
namespace Masteriyo\Addons\Mollie;

use Mollie\Api\MollieApiClient;

use Exception;
use Masteriyo\Enums\OrderStatus;
use Mollie\Api\Exceptions\ApiException;

defined( 'ABSPATH' ) || exit;
/**
 * Main Masteriyo Mollie class.
 *
 * @class Masteriyo\Addons\Mollie
 */
class MollieAddon {
	/**
	 * Instance
	 *
	 * @since 1.16.0
	 *
	 * @var \Masteriyo\Addons\Mollie\MollieAddon
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.16.0
	 */
	private function __construct() {
	}

	/**
	 * Return the instance.
	 *
	 * @since 1.16.0
	 *
	 * @return \Masteriyo\Addons\Mollie\MollieAddon
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize module.
	 *
	 * @since 1.16.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.16.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );
		add_action( 'masteriyo_new_setting', array( $this, 'save_mollie_settings' ), 10, 1 );

		add_filter( 'masteriyo_payment_gateways', array( $this, 'add_payment_gateway' ), 11, 1 );
		add_action( 'wp_ajax_masteriyo_mollie_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_mollie_webhook', array( $this, 'handle_webhook' ) );
	}

	/**
	 * Handle the webhook request from Mollie.
	 *
	 * This method processes incoming webhook notifications from Mollie.
	 * It retrieves payment information and updates the order status accordingly.
	 *
	 * @since 1.16.0
	 */
	public function handle_webhook() {
		try {
			masteriyo_get_logger()->info( 'Mollie webhook processing started', array( 'source' => 'payment-mollie' ) );
			$payload = @file_get_contents( 'php://input' );

			if ( empty( $payload ) ) {
				masteriyo_get_logger()->error( 'Empty payload received', array( 'source' => 'payment-mollie' ) );
				throw new Exception( 'Payload is empty.', 400 );
			}

			parse_str( $payload, $data );

			if ( empty( $data ) || ! is_array( $data ) ) {
				throw new Exception( 'Invalid payload format.', 400 );
			}

			if ( ! isset( $data['id'] ) ) {
				masteriyo_get_logger()->error( 'Missing payment ID in payload', array( 'source' => 'payment-mollie' ) );
				http_response_code( 400 );
				exit;
			}

			$api_key = masteriyo_mollie_get_api_key();
			if ( empty( $api_key ) ) {
				throw new Exception( 'Mollie API key is not configured.', 500 );
			}
			$mollie = new MollieApiClient();

			try {
				$mollie->setApiKey( $api_key );
				$payment = $mollie->payments->get( $data['id'] );
			} catch ( ApiException $e ) {
					masteriyo_get_logger()->error( 'Mollie API error: ' . $e->getMessage(), array( 'source' => 'payment-mollie' ) );
					throw new Exception( 'Failed to retrieve payment information.', 502 );
			}

			if ( ! $payment || ! isset( $payment->status ) ) {
				throw new Exception( 'Invalid payment data received from Mollie.', 400 );
			}

			if ( ! isset( $payment->metadata ) ) {
				throw new Exception( __( 'Metadata is missing.', 'learning-management-system' ) );
			}

			$order_id = isset( $payment->metadata->order_id ) ? $payment->metadata->order_id : null;

			if ( ! $order_id || $order_id <= 0 ) {
				throw new Exception( __( 'Invalid order ID in payment metadata.', 'learning-management-system' ) );
			}

			$order = masteriyo_get_order( $order_id );

			if ( ! $order ) {
				throw new Exception( __( 'Order not found.', 'learning-management-system' ) );
			}

			$payment_type = $payment->metadata->payment_type ?? 'one-time';

			if ( 'one-time' === $payment_type ) {
				$this->process_one_time_payment( $order, $payment );
			}

			masteriyo_get_logger()->info( 'Mollie webhook processing completed', array( 'source' => 'payment-mollie' ) );
			http_response_code( 200 );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-mollie' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
		}
	}

	/**
	 * Process one-time payment and update the order status.
	 *
	 * Checks the payment status and updates the order to completed, refunded, or other statuses.
	 *
	 * @since 1.16.0
	 *
	 * @param Order   $order   Order object.
	 * @param Payment $payment Mollie payment object.
	 */
	private function process_one_time_payment( $order, $payment ) {
		if ( $payment->isPaid() ) {
			if ( $payment->amountRefunded && $payment->amount->value === $payment->amountRefunded->value ) {
					$order->set_status( OrderStatus::REFUNDED );
			} else {
					$order->set_status( OrderStatus::COMPLETED );
			}
		} elseif ( 'expired' === $payment->status ) {
			$order->set_status( OrderStatus::FAILED );
		} elseif ( 'failed' === $payment->status ) {
			$order->set_status( OrderStatus::FAILED );
		} elseif ( 'canceled' === $payment->status ) {
			$order->set_status( OrderStatus::CANCELLED );
		}
		$order->save();
	}

	/**
	 * Get the return url (thank you page).
	 *
	 * @since 1.16.0
	 *
	 * @param Order|null $order Order object.
	 * @return string
	 */
	public function get_return_url( $order = null ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = masteriyo_get_endpoint_url( 'order-received', '', masteriyo_get_checkout_url() );
		}

		/**
		 * Filters return URL for a payment gateway.
		 *
		 * @since 1.16.0
		 *
		 * @param string $return_url The return URL.
		 * @param Masteriyo\Models\Order\Order|null $order The order object.
		 */
		return apply_filters( 'masteriyo_get_return_url', $return_url, $order );
	}


	/**
	 * Append setting to response.
	 *
	 * @since 1.16.0
	 *
	 * @param array $data Setting data.
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\SettingsController $controller REST settings controller object.
	 *
	 * @return array
	 */
	public function append_setting_in_response( $data, $setting, $context, $controller ) {
		$data['payments']['mollie'] = Setting::all();

		return $data;
	}

	/**
	 * Save global Mollie settings.
	 *
	 * @since 1.16.0
	 *
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 */
	public function save_mollie_settings( $setting ) {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() || ! isset( $request['payments']['mollie'] ) ) {
			return;
		}

		$current_settings = Setting::all();
		$new_settings     = masteriyo_array_only( $request['payments']['mollie'], array_keys( $current_settings ) );
		$new_settings     = masteriyo_parse_args( $new_settings, $current_settings );

		$new_settings['enable']       = masteriyo_string_to_bool( $new_settings['enable'] );
		$new_settings['title']        = sanitize_text_field( $new_settings['title'] );
		$new_settings['sandbox']      = masteriyo_string_to_bool( $new_settings['sandbox'] );
		$new_settings['description']  = sanitize_textarea_field( $new_settings['description'] );
		$new_settings['test_api_key'] = sanitize_text_field( $new_settings['test_api_key'] );
		$new_settings['live_api_key'] = sanitize_text_field( $new_settings['live_api_key'] );

		if ( $this->api_key_changed( $current_settings, $new_settings ) ) {
			$new_settings['error_message'] = $this->validate_mollie_keys( $new_settings );
		} else {
			$new_settings['error_message'] = $current_settings['error_message'] ?? '';
		}

		Setting::set_props( $new_settings );

		Setting::save();
	}

	/**
	 * Add Mollie payment gateway to available payment gateways.
	 *
	 * @since 1.16.0
	 *
	 * @param Masteriyo\Abstracts\PaymentGateway[]
	 *
	 * @return Masteriyo\Abstracts\PaymentGateway[]
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = Mollie::class;

		return $gateways;
	}

	/**
	 * Validate Mollie API keys based on settings.
	 *
	 * @since 1.16.0
	 *
	 * @param array $settings Mollie settings array.
	 * @return string Error message if validation fails, or an empty string if successful.
	 */
	private function validate_mollie_keys( $settings ) {
		$mollie = new MollieApiClient();

		try {
			if ( $settings['enable'] ) {
					$api_key = $settings['sandbox'] ? $settings['test_api_key'] : $settings['live_api_key'];
					$mollie->setApiKey( $api_key );
					$mollie->profiles->getCurrent();

					return '';
			}
		} catch ( \Mollie\Api\Exceptions\ApiException $e ) {
			return $settings['sandbox']
					? 'Invalid Test API key. Please verify that your Mollie API credentials are correct and save the settings again.'
					: 'Invalid Live API key. Please verify that your Mollie API credentials are correct and save the settings again.';
		}

		return '';
	}

	/**
	 * Determine if relevant Mollie API key settings have changed.
	 *
	 * @since 1.16.0
	 *
	 * @param array $current_settings The current stored settings.
	 * @param array $new_settings The new settings from the request.
	 * @return bool True if any API key or sandbox mode has changed, false otherwise.
	 */
	private function api_key_changed( $current_settings, $new_settings ) {
		return $current_settings['sandbox'] !== $new_settings['sandbox'] ||
		$current_settings['test_api_key'] !== $new_settings['test_api_key'] ||
		$current_settings['live_api_key'] !== $new_settings['live_api_key'];
	}
}
