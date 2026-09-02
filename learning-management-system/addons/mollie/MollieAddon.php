<?php
/**
 * Masteriyo Mollie setup.
 *
 * @package Masteriyo\Addons\Mollie
 *
 * @since 1.16.0 [Free]
 */
namespace Masteriyo\Addons\Mollie;

use Mollie\Api\MollieApiClient;

use Exception;
use Masteriyo\Enums\OrderItemType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Pro\Enums\SubscriptionStatus;
use Masteriyo\Pro\Models\Subscription;
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
	 * @since 1.16.0 [Free]
	 *
	 * @var \Masteriyo\Addons\Mollie\MollieAddon
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.16.0 [Free]
	 */
	private function __construct() {
	}

	/**
	 * Return the instance.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.16.0 [Free]
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
	 *  @since 1.16.0 [Free]
	 *
	 * This method processes incoming webhook notifications from Mollie.
	 * It retrieves payment information and updates the order status accordingly.
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
				throw new Exception( 'Missing payment ID in payload.', 400 );
			}

			$secret = masteriyo_mollie_get_api_key();
			if ( empty( $secret ) ) {
				throw new Exception( 'Mollie API key is not configured.', 500 );
			}
			$mollie = new MollieApiClient();

			try {
				$mollie->setApiKey( $secret );
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

			// Serialize concurrent webhook deliveries for the same order: false = another
			// delivery holds the lock (retry), null = lock unavailable (rely on the guard below).
			$lock      = $this->acquire_processing_lock( $order_id );
			$lock_held = ( true === $lock );

			if ( false === $lock ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Mollie webhook busy for order %s; asking Mollie to retry.', $order_id ),
					array( 'source' => 'payment-mollie' )
				);
				throw new Exception( __( 'Another webhook for this order is still processing. Please retry.', 'learning-management-system' ), 503 );
			}

			try {
				// Re-read the order inside the lock so we never act on meta a concurrent delivery just wrote.
				$order = masteriyo_get_order( $order_id );

				if ( ! $order ) {
					throw new Exception( __( 'Order not found.', 'learning-management-system' ) );
				}

				$payment_type = $payment->metadata->payment_type ?? 'one-time';

				// Idempotency guard keyed by paymentId|status|refunded: repeats are no-ops while
				// genuine changes (e.g. a later refund) still process.
				$event_key = $payment->id . '|' . ( $payment->status ?? '' ) . '|' . ( isset( $payment->amountRefunded->value ) ? $payment->amountRefunded->value : '0' );
				$processed = $order->get_meta( '_mollie_processed_events', true );
				$processed = is_array( $processed ) ? $processed : array();

				if ( in_array( $event_key, $processed, true ) ) {
					masteriyo_get_logger()->info(
						sprintf( 'Mollie webhook duplicate ignored | order_id=%1$s | event=%2$s', $order_id, $event_key ),
						array( 'source' => 'payment-mollie' )
					);
				} else {
					masteriyo_get_logger()->info(
						sprintf(
							'Mollie webhook received | payment_id=%1$s | status=%2$s | payment_type=%3$s | order_id=%4$s | mollie_subscriptionId=%5$s | sequenceType=%6$s',
							$payment->id,
							$payment->status ?? 'unknown',
							$payment_type,
							$order_id,
							$payment->subscriptionId ?? 'none',
							$payment->sequenceType ?? 'none'
						),
						array( 'source' => 'payment-mollie' )
					);

					/**
					 * Webhook authenticity verification.
					 *
					 * Mollie does not sign webhook payloads, so the request body is never
					 * trusted. The payment has already been re-fetched from Mollie's API
					 * above using the site's API key; here we additionally confirm the
					 * payment belongs to this order — by transaction ID for one-time
					 * payments and by subscription ID for recurring renewals — before
					 * acting on it. This is Mollie's recommended verification model.
					 */
					if ( 'one-time' === $payment_type ) {
						$stored_transaction_id = $order->get_transaction_id();
						if ( ! empty( $stored_transaction_id ) && $stored_transaction_id !== $payment->id ) {
							throw new Exception( __( 'Payment ID does not match the stored transaction.', 'learning-management-system' ) );
						}
						$this->process_one_time_payment( $order, $payment );
					}

					if ( 'recurring' === $payment_type ) {
						$stored_subscription_id = $order->get_meta( 'mollie_subscription_id', true );
						if ( ! empty( $stored_subscription_id ) ) {
							if ( ! isset( $payment->subscriptionId ) || $payment->subscriptionId !== $stored_subscription_id ) {
								throw new Exception( __( 'Payment subscription ID does not match the stored subscription.', 'learning-management-system' ) );
							}
						}
						$this->process_subscription_payment( $order, $payment, $mollie );
					}

					// Remember this event, capped so the meta cannot grow unbounded.
					$processed[] = $event_key;
					$order->update_meta_data( '_mollie_processed_events', array_slice( $processed, -50 ) );
					$order->save_meta_data();
				}
			} finally {
				if ( $lock_held ) {
					$this->release_processing_lock( $order_id );
				}
			}

			masteriyo_get_logger()->info( 'Mollie webhook processing completed', array( 'source' => 'payment-mollie' ) );
			wp_send_json_success();
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
	 * @since 1.16.0 [Free]
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
	 * Process subscription payment and update the order and subscription status.
	 *
	 * Handles the creation of new subscriptions, updates status on payment events,
	 * and manages cancellation or refund for subscription payments.
	 *
	 * @since 1.16.0
	 *
	 * @param Order   $order   Order object.
	 * @param Payment $payment Mollie payment object.
	 * @param MollieApiClient $mollie Mollie API client.
	 */
	private function process_subscription_payment( $order, $payment, $mollie ) {
		// Subscriptions are pro's — the model, the repository and the statuses. A free
		// site creates no recurring payment, so Mollie never calls back about one.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return;
		}

		$customer_id = $payment->customerId ?? null;
		if ( ! $customer_id ) {
			throw new Exception( 'Customer ID missing in payment data.' );
		}

		$subscription_id = $order->get_meta( 'mollie_subscription_id', true );

		masteriyo_get_logger()->info(
			sprintf(
				'Mollie subscription payment | order_id=%1$s | payment_id=%2$s | status=%3$s | stored_mollie_subscription_id=%4$s | decision=%5$s',
				$order->get_id(),
				$payment->id,
				$payment->status ?? 'unknown',
				$subscription_id ? $subscription_id : 'none',
				( ! $subscription_id && 'paid' === $payment->status ) ? 'CREATE_NEW_SUBSCRIPTION' : 'SKIP_CREATION'
			),
			array( 'source' => 'payment-mollie' )
		);

		if ( ! $subscription_id && 'paid' === $payment->status ) {
			try {
				$customer            = $mollie->customers->get( $customer_id );
				$subscription_data   = $this->get_subscription_data( $order, $payment );
				$mollie_subscription = $customer->createSubscription( $subscription_data );
				$subscription        = $this->create_subscription( $order, $mollie_subscription );
				$order->update_meta_data( 'mollie_subscription_id', $mollie_subscription->id );
				$order->update_meta_data( 'subscription_id', $subscription->get_id() );
				$order->save_meta_data();

				masteriyo_get_logger()->info( 'New subscription created: ' . $mollie_subscription->id, array( 'source' => 'payment-mollie' ) );
			} catch ( ApiException $e ) {
				masteriyo_get_logger()->error(
					'Failed to create subscription for order ' . $order->get_id() . ': ' . $e->getMessage(),
					array( 'source' => 'payment-mollie' )
				);
				throw new Exception( 'Failed to create Mollie subscription: ' . esc_html( $e->getMessage() ), 500 );
			}
		}

		if ( $payment->isPaid() ) {
			if ( $payment->amountRefunded && $payment->amountRefunded->value > 0 ) {
				$order->set_status( OrderStatus::REFUNDED );
				$order_subscription_id = $order->get_meta( 'subscription_id', true );
				if ( $subscription_id ) {
					$this->change_subscription_status( SubscriptionStatus::CANCELLED, $order_subscription_id );
				}
			} else {
				$order->set_status( OrderStatus::COMPLETED );
				masteriyo_get_logger()->info( 'Recurring payment successful for order: ' . $order->get_id(), array( 'source' => 'payment-mollie' ) );
			}
		} elseif ( 'expired' === $payment->status ) {
			$order->set_status( OrderStatus::CANCELLED );
			$order_subscription_id = $order->get_meta( 'subscription_id', true );
			if ( $subscription_id ) {
				$this->change_subscription_status( SubscriptionStatus::EXPIRED, $order_subscription_id );
			}
		} elseif ( 'failed' === $payment->status ) {
			$order->set_status( OrderStatus::FAILED );
			$order_subscription_id = $order->get_meta( 'subscription_id', true );
			if ( $subscription_id ) {
				$this->change_subscription_status( SubscriptionStatus::CANCELLED, $order_subscription_id );
			}
		} elseif ( 'canceled' === $payment->status ) {
			$order->set_status( OrderStatus::CANCELLED );
			$order_subscription_id = $order->get_meta( 'subscription_id', true );
			if ( $subscription_id ) {
				$this->change_subscription_status( SubscriptionStatus::FAILED, $order_subscription_id );
			}
		}
		$order->save();
	}

	/**
	 * Change subscription status of subscription of order.
	 *
	 * @since 2.17.0
	 *
	 * @param string $status
	 * @param string $order_subscription_id
	 * @return void
	 */
	public function change_subscription_status( $status, $order_subscription_id ) {
		// The subscription model and its repository are both pro bindings; without
		// them there is no subscription whose status could change.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return;
		}

		/** @var \Masteriyo\Pro\Models\Subscription */
		$existing_subscription = masteriyo( 'subscription' );
		$existing_subscription->set_id( $order_subscription_id );
		/** @var \Masteriyo\Pro\Repository\SubscriptionRepository */
		$subscription_repo = masteriyo( 'subscription.store' );
		$subscription_repo->read( $existing_subscription );

		$existing_subscription->set_status( $status );
		$existing_subscription->save();
	}

	/**
	 * Acquire a per-order advisory lock so concurrent Mollie webhook deliveries for the
	 * same order are handled one at a time.
	 *
	 * @param int $order_id Order ID.
	 * @param int $timeout  Seconds to wait for the lock.
	 * @return bool|null True if acquired, false on timeout (another delivery holds it),
	 *                   null if the lock subsystem is unavailable on this host.
	 */
	private function acquire_processing_lock( $order_id, $timeout = 10 ) {
		global $wpdb;

		$result = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $this->get_lock_name( $order_id ), $timeout ) );

		if ( null === $result ) {
			masteriyo_get_logger()->warning(
				'Mollie webhook advisory lock unavailable; relying on idempotency guard.',
				array( 'source' => 'payment-mollie' )
			);
			return null;
		}

		return 1 === (int) $result;
	}

	/**
	 * Release the per-order webhook processing lock.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	private function release_processing_lock( $order_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->get_lock_name( $order_id ) ) );
	}

	/**
	 * Build the advisory lock name, namespaced by table prefix and capped at GET_LOCK's 64-char limit.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function get_lock_name( $order_id ) {
		global $wpdb;

		return substr( 'mas_mollie_wh_' . $wpdb->prefix . $order_id, 0, 64 );
	}

	/**
	 * Get the subscription data for creating or updating a subscription.
	 *
	 * Constructs and returns the subscription data array with metadata and billing information.
	 *
	 * @since 1.16.0
	 *
	 * @param Order   $order   Order object.
	 * @param Payment $payment Mollie payment object.
	 * @return array Subscription data array.
	 */
	private function get_subscription_data( $order, $payment ) {
		$order_items = $order->get_items();
		if ( ! empty( $order_items[0] ) && masteriyo_is_bundle_order_item( $order_items[0] ) ) {
			$courses          = $order_items;
			$course_bundle_id = $order_items[0]->get_course_bundle_id();
			$first_course     = masteriyo_get_bundle_product( $course_bundle_id );
		} else {
			$courses      = array_map(
				function( $order_item ) {
					return $order_item->get_course();
				},
				$order_items
			);
			$first_course = current( $courses );
		}

		$receipt_id = get_bloginfo( 'admin_email' );

		$billing_expire_after = $first_course->get_billing_expire_after();
		$billing_interval     = $first_course->get_billing_interval();
		$billing_period       = $first_course->get_billing_period();

		$times = null;
		switch ( $billing_period ) {
			case 'day':
					$times = $billing_expire_after > 0 ? ceil( ( $billing_expire_after * 30 ) / $billing_interval ) : null;
				break;
			case 'week':
					$times = $billing_expire_after > 0 ? ceil( ( $billing_expire_after * 4 ) / $billing_interval ) : null;
				break;
			case 'month':
					$times = $billing_expire_after > 0 ? ceil( $billing_expire_after / $billing_interval ) : null;
				break;
			case 'year':
					$times = $billing_expire_after > 0 ? ceil( $billing_expire_after / ( $billing_interval * 12 ) ) : null;
				break;
		}

		// The `first` payment already covers cycle one, so run the subscription one cycle fewer.
		if ( null !== $times ) {
			$times = (int) max( 1, $times - 1 );
		}

		// Mollie accepts only "days", "weeks", or "months" (plural). Convert year to months.
		switch ( $billing_period ) {
			case 'day':
				$mollie_interval = $billing_interval . ' days';
				break;
			case 'week':
				$mollie_interval = $billing_interval . ' weeks';
				break;
			case 'year':
				$mollie_interval = ( $billing_interval * 12 ) . ' months';
				break;
			case 'month':
			default:
				$mollie_interval = $billing_interval . ' months';
				break;
		}

		// Mollie charges cycle one on the start date (today if omitted). The `first` payment
		// already covered it, so start one interval ahead to avoid a same-day double charge.
		$safe_interval = max( 1, (int) $billing_interval );
		switch ( $billing_period ) {
			case 'day':
				$interval_spec = 'P' . $safe_interval . 'D';
				break;
			case 'week':
				$interval_spec = 'P' . $safe_interval . 'W';
				break;
			case 'year':
				$interval_spec = 'P' . $safe_interval . 'Y';
				break;
			case 'month':
			default:
				$interval_spec = 'P' . $safe_interval . 'M';
				break;
		}

		$start_date = null;
		try {
			$start = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
			$start->add( new \DateInterval( $interval_spec ) );
			$start_date = $start->format( 'Y-m-d' );
		} catch ( \Exception $e ) {
			// Fall back to Mollie's default start date if the interval is unexpectedly invalid.
			$start_date = null;
		}

		$subscription_data = array(
			'amount'      => array(
				'currency' => $order->get_currency(),
				'value'    => number_format( $order->get_total(), 2, '.', '' ),
			),
			'interval'    => $mollie_interval,
			/* translators: %s: order id */
			'description' => sprintf( __( 'Subscription for Order #%s', 'learning-management-system' ), $order->get_id() ),
			'times'       => $times,
			'webhookUrl'  => masteriyo_mollie_get_webhook_url(),
			'metadata'    => array(
				'order_id'     => $order->get_id(),
				'payment_type' => 'recurring',
				'course_id'    => $first_course->get_id(),
				'receipt'      => $receipt_id,
			),
		);

		if ( ! empty( $start_date ) ) {
			$subscription_data['startDate'] = $start_date;
		}

		masteriyo_get_logger()->info(
			sprintf(
				'Mollie subscription payload built for order #%1$s | period=%2$s interval=%3$s expire_after=%4$s | mollie_interval=%5$s | startDate=%6$s | times=%7$s | amount=%8$s %9$s',
				$order->get_id(),
				$billing_period,
				$billing_interval,
				$billing_expire_after,
				$mollie_interval,
				empty( $start_date ) ? 'DEFAULT(today)' : $start_date,
				null === $times ? 'unlimited' : $times,
				$subscription_data['amount']['currency'],
				$subscription_data['amount']['value']
			),
			array( 'source' => 'payment-mollie' )
		);

		return $subscription_data;
	}

	/**
	 * Get the return url (thank you page).
	 *
	 * @since 1.16.0 [Free]
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
		 * @since 1.16.0 [Free]
		 *
		 * @param string $return_url The return URL.
		 * @param Masteriyo\Models\Order\Order|null $order The order object.
		 */
		return apply_filters( 'masteriyo_get_return_url', $return_url, $order );
	}

	/**
	 * Create subscription model.
	 *
	 * @since 1.16.0
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Mollie\Subscription $mollie_subscription
	 */
	public function create_subscription( $order, $mollie_subscription ) {
		// Subscriptions are pro's. Nothing hooks the recurring filters in the free
		// product, so no free order is recurring and this is never reached.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return null;
		}

		masteriyo_get_logger()->info( 'Mollie create_subscription: Start', array( 'source' => 'payment-mollie' ) );
		$order_data = $order->get_data();
		unset( $order_data['id'] );

		$subscription = Subscription::instance();
		$subscription->set_props( $order_data );

		$order_item = current( $order->get_items() );
		if ( $order_item && masteriyo_is_bundle_order_item( $order_item ) ) {
			$course = masteriyo_get_bundle_product( $order_item->get_course_bundle_id() );
		} else {
			$course = masteriyo_get_course( $order_item->get_course_id() );
		}

		$subscription->set_props(
			array(
				'billing_period'          => $course->get_billing_period(),
				'billing_interval'        => $course->get_billing_interval(),
				'billing_expire_after'    => $course->get_billing_expire_after(),
				'requires_manual_renewal' => false,
				'status'                  => SubscriptionStatus::ACTIVE,
				'parent_id'               => $order->get_id(),
				'subscription_id'         => $mollie_subscription->id,
				'recurring_amount'        => $order->get_total(),
			)
		);

		foreach ( $order->get_items( OrderItemType::all() ) as $order_item ) {
			$subscription->add_item( $order_item );
		}

		$subscription->save();

		masteriyo_get_logger()->info( 'Mollie create_subscription: Success', array( 'source' => 'payment-mollie' ) );

		return $subscription;
	}

	/**
	 * Append setting to response.
	 *
	 * @since 1.16.0 [Free]
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

		// Expose the webhook URL so admins can view/copy it for manual
		// configuration in the Mollie dashboard.
		$data['payments']['mollie']['webhook_url'] = masteriyo_mollie_get_webhook_url();

		return $data;
	}

	/**
	 * Save global Mollie settings.
	 *
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
	 * @since 1.16.0 [Free]
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
