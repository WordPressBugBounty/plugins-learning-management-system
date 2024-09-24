<?php

/**
 * Masteriyo WooCommerce Integration setup.
 *
 * @package Masteriyo\LemonSqueezyIntegration
 *
 * @since 1.9.3
 */

namespace Masteriyo\Addons\LemonSqueezyIntegration;

use Exception;
use Masteriyo\Enums\OrderStatus;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo LemonSqueezyIntegration class.
 *
 * @class Masteriyo\Addons\LemonSqueezyIntegration
 * @since 1.9.3
 */

class LemonSqueezyIntegrationAddon {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.9.3
	 *
	 * @var \Masteriyo\Addons\LemonSqueezyIntegration\LemonSqueezyIntegration|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * Initializes the LemonSqueezyIntegrationAddon instance.
	 *
	 * @since 1.9.3
	 */
	protected function __construct() {  }

	/**
	 * Get class instance.
	 *
	 * Returns a singleton instance of the LemonSqueezyIntegrationAddon class.
	 * Ensures only one instance of the class is created
	 *
	 * @since 1.9.3
	 *
	 * @return \Masteriyo\Addons\LemonSqueezyIntegration\LemonSqueezyIntegration Instance.
	 */
	final public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevents the instance from being cloned.
	 *
	 * @since 1.9.3
	 */
	public function __clone() {     }

	/**
	 * Prevents the instance from being unserialize.
	 *
	 * @since 1.9.3
	 */
	public function __wakeup() {    }

	/**
	 * Initialize module.
	 *
	 * Initializes the LemonSqueezyIntegration module by setting up hooks and filters.
	 *
	 * @since 1.9.3
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * Sets up WordPress hooks and filters for integration with LemonSqueezy.
	 *
	 * @since 1.9.3
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_payment_gateways', array( $this, 'add_payment_gateway' ), 11, 1 );

		add_filter( 'masteriyo_rest_course_schema', array( $this, 'add_lemon_squeezy_schema_to_course' ) );
		add_action( 'masteriyo_new_course', array( $this, 'save_lemon_squeezy_data' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'save_lemon_squeezy_data' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_lemon_squeezy_data_in_response' ), 10, 4 );

		add_filter( 'masteriyo_new_setting', array( $this, 'save_setting' ), 10 );
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );

		add_action( 'wp_ajax_masteriyo_lemon_squeezy_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_lemon_squeezy_webhook', array( $this, 'handle_webhook' ) );
	}

	/**
	 * Add Lemon Squeezy payment gateway to available payment gateways.
	 *
	 * @since 1.9.3
	 *
	 * @param Masteriyo\Abstracts\PaymentGateway[]
	 *
	 * @return Masteriyo\Abstracts\PaymentGateway[]
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = LemonSqueezy::class;

		return $gateways;
	}

	/**
	 * Adds Lemon Squeezy integration data to the course data response.
	 *
	 * @since 1.9.3
	 *
	 * @param array $data Course data.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.
	 *
	 * @return array The modified course data with Lemon Squeezy integration data appended.
	 */
	public function append_lemon_squeezy_data_in_response( $data, $course, $context, $controller ) {

		if ( $course instanceof \Masteriyo\Models\Course ) {
			$data['lemon_squeezy_integration'] = array(
				'product_id' => get_post_meta( $course->get_id(), '_lemon_squeezy_product_id', true ),
			);
		}

		return $data;
	}

	/**
	 * Save Lemon Squeezy data.
	 *
	 * @since 1.9.3
	 *
	 * @param integer $id The course ID.
	 * @param \Masteriyo\Models\Course $object The course object.
	 */
	public function save_lemon_squeezy_data( $id, $course ) {
		$request = masteriyo_current_http_request();

		if ( null === $request ) {
			return;
		}

		if ( ! isset( $request['lemon_squeezy_integration'] ) ) {
			return;
		}

		if ( isset( $request['lemon_squeezy_integration']['product_id'] ) ) {
			update_post_meta( $id, '_lemon_squeezy_product_id', sanitize_text_field( $request['lemon_squeezy_integration']['product_id'] ) );
		}
	}

	/**
	 * Add Lemon Squeezy fields to course schema.
	 *
	 * Adds Lemon Squeezy integration settings to the course schema for REST API.
	 *
	 * @since 1.9.3
	 *
	 * @param array $schema The existing course schema.
	 *
	 * @return array The modified schema with Lemon Squeezy integration fields added.
	 */
	public function add_lemon_squeezy_schema_to_course( $schema ) {
		$schema = wp_parse_args(
			$schema,
			array(
				'lemon_squeezy_integration' => array(
					'description' => __( 'Lemon Squeezy Integration setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'product_id' => array(
								'description' => __( 'Product ID.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
			)
		);

		return $schema;
	}

	/**
	 * Save setting.
	 *
	 * Saves Lemon Squeezy integration settings based on the request data.
	 *
	 * @since 1.9.3
	 */
	public function save_setting() {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		if ( ! isset( $request['payments']['lemon_squeezy_integration'] ) ) {
			return;
		}

		$settings = masteriyo_array_only( $request['payments']['lemon_squeezy_integration'], array_keys( Setting::all() ) );
		$settings = masteriyo_parse_args( $settings, Setting::all() );

		// Sanitization.
		$settings['enable']         = masteriyo_string_to_bool( $settings['enable'] );
		$settings['title']          = sanitize_text_field( $settings['title'] );
		$settings['description']    = sanitize_textarea_field( $settings['description'] );
		$settings['api_key']        = sanitize_text_field( $settings['api_key'] );
		$settings['store_id']       = sanitize_text_field( $settings['store_id'] );
		$settings['webhook_secret'] = sanitize_text_field( $settings['webhook_secret'] );

		$unenrollment_statuses = $settings['unenrollment_status'];

		$settings['unenrollment_status'] = is_array( $unenrollment_statuses ) ? array_filter(
			array_map(
				function( $unenrollment_status ) {
					if ( empty( $unenrollment_status ) ) {
						return null;
					}
					return sanitize_text_field( $unenrollment_status );
				},
				$unenrollment_statuses
			)
		) : array();

		Setting::set_props( $settings );

		Setting::save();
	}

	/**
	 * Adds Lemon Squeezy integration settings to the global settings response.
	 *
	 * @since 1.9.3
	 *
	 * @param array $data The current settings data.
	 * @param \Masteriyo\Models\Setting $setting The settings object.
	 * @param string $context The context in which the settings are being retrieved.
	 * @param \Masteriyo\RestApi\Controllers\Version1\SettingsController $controller The REST settings controller object.
	 *
	 * @return array The modified settings data with Lemon Squeezy integration settings appended.
	 */
	public function append_setting_in_response( $data, $object, $request, $controller ) {
		$data['payments']['lemon_squeezy_integration'] = wp_parse_args( array( 'webhook_url' => Helper::get_webhook_endpoint_url() ), Setting::all() );

		return $data;
	}

	/**
	 * Handles the incoming webhook requests from Lemon Squeezy.
	 *
	 * Processes incoming webhook requests from Lemon Squeezy, verifying the request integrity, and routing the request to the appropriate handler.
	 *
	 * @since 1.9.3
	 *
	 * @throws Exception If the payload is empty, the event type is unsupported, or the signature verification fails.
	 */
	public function handle_webhook() {
		try {
			masteriyo_get_logger()->info( 'Lemon Squeezy webhook processing started', array( 'source' => 'payment-lemon-squeezy' ) );
			$signature = isset( $_SERVER['HTTP_X_SIGNATURE'] ) ? $_SERVER['HTTP_X_SIGNATURE'] : null;
			$payload   = @file_get_contents( 'php://input' ); // phpcs:ignore
			$event     = isset( $_SERVER['HTTP_X_EVENT_NAME'] ) ? $_SERVER['HTTP_X_EVENT_NAME'] : null;

			if ( empty( $payload ) ) {
				masteriyo_get_logger()->error( 'Payload is empty', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Payload is empty.', 400 );
			}

			if ( empty( $event ) ) {
				masteriyo_get_logger()->error( 'Event is null', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Event is null.', 400 );
			}

			// Verify signature.
			$secret = Setting::get_webhook_secret();
			$hash   = hash_hmac( 'sha256', $payload, $secret );
			if ( ! hash_equals( $hash, $signature ) ) {
				masteriyo_get_logger()->error( 'Invalid signature', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Invalid signature.', 403 );
			}

			$data = json_decode( $payload, true );

			if ( ! $data ) {
				masteriyo_get_logger()->error( 'Invalid payload', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Invalid payload.', 400 );
			}

			$meta_data   = $data['meta'];
			$custom_data = $meta_data['custom_data'];
			$course_id   = absint( $custom_data['course_id'] );
			$order_id    = absint( $custom_data['order_id'] );
			$user_id     = absint( $custom_data['user_id'] );

			if ( ! $order_id ) {
				masteriyo_get_logger()->error( 'Invalid order id', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Invalid order id.', 400 );
			}

			if ( ! $course_id ) {
				masteriyo_get_logger()->error( 'Invalid course id', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Invalid course id.', 400 );
			}

			if ( ! $user_id ) {
				masteriyo_get_logger()->error( 'Invalid user id', array( 'source' => 'payment-lemon-squeezy' ) );
				throw new Exception( 'Invalid user id.', 400 );
			}

			$result = $this->process_event( $event, $data, $order_id, $custom_data );

			if ( is_wp_error( $result ) ) {
				masteriyo_get_logger()->error( $result->get_error_message(), array( 'source' => 'payment-lemon-squeezy' ) );
				wp_send_json_error( array( 'message' => $result->get_error_message() ), $result->get_error_code() );
			}

			masteriyo_get_logger()->info( 'Lemon Squeezy webhook processing completed', array( 'source' => 'payment-lemon-squeezy' ) );

			wp_send_json_success( $result );

		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-lemon-squeezy' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ), $e->getCode() );
		}
	}

	/**
	 * Process event.
	 *
	 * Routes the webhook event to the appropriate handler based on the event type.
	 *
	 * @since 1.9.3
	 *
	 * @param string $event The event type from the webhook.
	 * @param array $data The decoded webhook payload.
	 * @param int $order_id The ID of the order related to the event.
	 * @param array $custom_data The custom data associated with the event.
	 *
	 * @return mixed The result of the event handler.
	 *
	 * @throws Exception If the event type is unsupported.
	 */
	private function process_event( $event, $data, $order_id, $custom_data ) {

		$data = array(
			'order_id'        => $data['data']['id'],
			'type'            => $data['data']['type'],
			'total'           => $data['data']['attributes']['total'],
			'status'          => $data['data']['attributes']['status'],
			'currency'        => $data['data']['attributes']['currency'],
			'subtotal'        => $data['data']['attributes']['subtotal'],
			'test_mode'       => $data['data']['attributes']['test_mode'],
			'total_usd'       => $data['data']['attributes']['total_usd'],
			'user_name'       => $data['data']['attributes']['user_name'],
			'create_at'       => $data['data']['attributes']['created_at'],
			'update_at'       => $data['data']['attributes']['updated_at'],
			'user_email'      => $data['data']['attributes']['user_email'],
			'refunded'        => $data['data']['attributes']['refunded'],
			'refunded_at'     => $data['data']['attributes']['refunded_at'],
			'currency_rate'   => $data['data']['attributes']['currency_rate'],
			'subtotal_usd'    => $data['data']['attributes']['subtotal_usd'],
			'total_formatted' => $data['data']['attributes']['total_formatted'],
			'product_id'      => $data['data']['attributes']['first_order_item']['product_id'],
			'quantity'        => $data['data']['attributes']['first_order_item']['quantity'],
			'product_name'    => $data['data']['attributes']['first_order_item']['product_name'],
			'variant_id'      => $data['data']['attributes']['first_order_item']['variant_id'],
			'variant_name'    => $data['data']['attributes']['first_order_item']['variant_name'],
		);

		$data['total']    = floatval( $data['total'] ) / 100;
		$data['subtotal'] = floatval( $data['subtotal'] ) / 100;

		switch ( $event ) {
			case 'order_created':
				return $this->handle_order_created( $data, $order_id, $custom_data );
			case 'order_refunded':
				$unenrollment_statuses = Setting::get( 'unenrollment_status' );
				$is_refundable         = false;
				if ( ! empty( $unenrollment_statuses ) && is_array( $unenrollment_statuses ) ) {
					foreach ( $unenrollment_statuses as $unenrollment_status ) {
						if ( in_array( OrderStatus::REFUNDED, $unenrollment_status, true ) ) {
							$is_refundable = true;
							break;
						}
					}
				}
				if ( $is_refundable ) {
					return $this->handle_order_refunded( $order_id );
				} else {
					return new WP_Error( 'unsupported_event_type', __( 'Unsupported event type.', 'learning-management-system' ) );
				}
				break;

			default:
				return new WP_Error( 'unsupported_event_type', __( 'Unsupported event type.', 'learning-management-system' ) );
		}
	}

	/**
	 * Handle order created event.
	 *
	 * Processes the order created event from Lemon Squeezy, creating an order and enrolling the user in the course.
	 *
	 * @since 1.9.3
	 *
	 * @param array $data Webhook payload data.
	 * @param int $order_id Order ID.
	 * @param array $custom_data The custom data.
	 *
	 * @return array Response data including message, order ID.
	 */
	private function handle_order_created( $data, $order_id, $custom_data ) {
		$order_status = OrderStatus::PENDING;

		if ( 'paid' === $data['status'] ) {
			$order_status = OrderStatus::COMPLETED;
		} elseif ( 'refunded' === $data['status'] ) {
			$order_status = OrderStatus::REFUNDED;
		} elseif ( 'failed' === $data['status'] ) {
			$order_status = OrderStatus::FAILED;
		}

		$order = masteriyo_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$order->set_status( $order_status );
		$order->set_currency( $data['currency'] );
		$order->set_transaction_id( $data['order_id'] );
		$order->save();

		// update order meta.
		$this->update_order_meta( $order_id, $data );

		// Update user itemmeta.
		$this->update_user_itemmeta( $order_id, $data );

		// Update order itemmeta.
		$this->update_order_itemmeta( $order_id, $data );

		return array(
			'message'  => __( 'Order created successfully.', 'learning-management-system' ),
			'order_id' => $order_id,
		);
	}

	/**
	 * Update Masteriyo order meta.
	 *
	 * Updates the order metadata in Masteriyo based on the data from the Lemon Squeezy webhook.
	 *
	 * @since 1.9.3
	 *
	 * @param int $order_id Order ID.
	 * @param array $data Order data from the webhook.
	 */
	private function update_order_meta( $order_id, $data ) {
		update_post_meta( $order_id, '_total', $data['total'] );
		update_post_meta( $order_id, '_currency', $data['currency'] );
		update_post_meta( $order_id, '_was_lemon_squeezy_order', true );
	}

	/**
	 * Update user itemmeta.
	 *
	 * @since 1.9.3
	 *
	 * @param int $order_id Order ID.
	 * @param array $data Webhook payload data.
	 *
	 * @return void
	 */
	private function update_user_itemmeta( $order_id, $data ) {
		global $wpdb;

		if ( ! $wpdb ) {
			return;
		}

		$user_item_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_item_id FROM {$wpdb->prefix}masteriyo_user_itemmeta WHERE meta_key = %s AND meta_value = %s",
				'_order_id',
				$order_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_user_itemmeta SET meta_value = %s WHERE user_item_id = %d AND meta_key = %s",
				$data['total'],
				$user_item_id,
				'_price'
			)
		);
	}

	/**
	 * Update order itemmeta.
	 *
	 * @since 1.9.3
	 *
	 * @param int $order Order ID.
	 * @param array $data Webhook payload data.
	 *
	 * @return void
	 */
	private function update_order_itemmeta( $order_id, $data ) {
		global $wpdb;

		if ( ! $wpdb ) {
			return;
		}

		$order = masteriyo_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$order_item = current( $order->get_items() );

		if ( ! $order_item instanceof \Masteriyo\Models\Order\OrderItemCourse ) {
			return;
		}

		$order_item_id = $order_item->get_id();

		if ( ! $order_item_id ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_order_itemmeta SET meta_value = %s WHERE order_item_id = %d AND meta_key = %s",
				$data['total'],
				$order_item_id,
				'total'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_order_itemmeta SET meta_value = %s WHERE order_item_id = %d AND meta_key = %s",
				$data['subtotal'],
				$order_item_id,
				'subtotal'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_order_itemmeta SET meta_value = %s WHERE order_item_id = %d AND meta_key = %s",
				$data['quantity'],
				$order_item_id,
				'quantity'
			)
		);
	}

	/**
	 * Handle order refunded event.
	 *
	 * Processes the order refunded event from Lemon Squeezy, updating the enrollment status to inactive.
	 *
	 * @since 1.9.3
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Response data including message, user ID, and course ID.
	 */
	private function handle_order_refunded( $order_id ) {
		$order = masteriyo_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$order->set_status( OrderStatus::REFUNDED );
		$order->save();

		return array(
			'message'  => __( 'Order refunded successfully.', 'learning-management-system' ),
			'order_id' => $order_id,
		);
	}
}
