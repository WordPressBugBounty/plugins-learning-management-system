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
use Masteriyo\Addons\Stripe\Client\StripeClient;
use Masteriyo\Constants;
use Masteriyo\Contracts\PaymentGateway as PaymentGatewayInterface;
use Masteriyo\Enums\OrderItemType;
use Masteriyo\Pro\Enums\SubscriptionStatus;
use Masteriyo\Pro\Models\Subscription;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Subscription as StripeSubscription;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe credit card Class.
 */
#[\AllowDynamicProperties]
class CreditCard extends PaymentGateway implements PaymentGatewayInterface {

	/**
	 * Payment gateway name.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $name = 'stripe';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @since 2.0.0
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
	 * @since 2.6.10
	 *
	 * @var array
	 */
	protected $supports = array( 'course', 'subscription' );


	/**
	 * Constructor for the gateway.
	 *
	 * @since 2.0.0
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
			$this->description .= ' ' . __( 'SANDBOX ENABLED.', 'learning-management-system' );
			$this->description  = trim( $this->description );
		}

	}

	/**
	 * Logging method.
	 *
	 * @since 2.0.0
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
	 * The addon's own badge — white mark on the brand's purple, rounded — so the
	 * backing and the corners ride on the image and the checkout needs only to cap
	 * its height. It lives at the addon root rather than under `assets/`, because
	 * that is where the addon browser looks for a thumbnail.
	 *
	 * @return string
	 */
	public function get_icon() {
		$image_url = plugins_url( 'thumbnail.png', Constants::get( 'MASTERIYO_STRIPE_ADDON_FILE' ) );

		$icon_html = sprintf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( $image_url ),
			esc_attr__( 'Stripe logo', 'learning-management-system' )
		);

		/**
		 * Filters stripe icon.
		 *
		 * @param string $icon Icon html.
		 * @param string $name Payment gateway name.
		 */
		return apply_filters( 'masteriyo_stripe_icon', $icon_html, $this->name );
	}

	/**
	 * Other methods.
	 */

	/**
	 * Init settings for gateways.
	 *
	 * @since 2.0.0
	 */
	public function init_settings() {
		$this->enabled = Setting::is_enable();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 2.0.0
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		masteriyo_get_logger()->info( 'Stripe process_payment: ' . $order_id, array( 'source' => 'payment-stripe' ) );
		try {

			$order   = masteriyo_get_order( $order_id );
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

			if ( ! $payment_intent_id ) {
				masteriyo_get_logger()->error( 'Stripe process_payment: Payment intent ID not found in session', array( 'source' => 'payment-stripe' ) );
				throw new Exception( __( 'Payment intent ID not found in session.', 'learning-management-system' ) );
			}

			$order->set_transaction_id( $payment_intent_id );
			$order->save();

			// When no webhook ever arrives, these are the only record of which
			// intent, in which key mode, this order was paid with.
			update_post_meta( $order->get_id(), '_stripe_payment_intent_id', $payment_intent_id );
			update_post_meta( $order->get_id(), '_stripe_mode', Setting::is_sandbox_enable() ? 'test' : 'live' );

			$order_courses     = $order->get_order_item_course( $order->get_items(), 'view' );
			$first_course_name = ( is_array( $order_courses ) && ! empty( $order_courses[0]['name'] ) )
			? $order_courses[0]['name']
			: '';

			$payment_intent_args = array(
				'receipt_email' => $order->get_billing_email(),
				'metadata'      => array( 'order_id' => $order->get_id() ),
				'description'   => sprintf( 'Item: %s', $first_course_name ),
				'amount'        => Helper::convert_cart_total_to_stripe_amount( $order->get_total(), $order->get_currency() ),
			);

			if ( masteriyo_order_has_recurring_courses( $order ) ) {
				// The Stripe subscription itself is created once the first payment
				// settles (StripeAddon::maybe_create_subscription_for_paid_order) —
				// a SEPA first payment can take days, and the reusable payment
				// method only exists after the intent succeeds. Here the intent
				// only learns which Stripe customer that payment method is saved on.
				$stripe_customer = $this->create_stripe_customer( $order );

				if ( ! $stripe_customer || empty( $stripe_customer->id ) ) {
					masteriyo_get_logger()->error( 'Stripe process_payment: Unable to resolve a Stripe customer for the subscription.', array( 'source' => 'payment-stripe' ) );
					throw new Exception( esc_html__( 'Unable to create Stripe customer for the subscription.', 'learning-management-system' ) );
				}

				$payment_intent_args['customer']           = $stripe_customer->id;
				$payment_intent_args['setup_future_usage'] = 'off_session';
			}

			if ( Helper::use_platform() ) {
				$update_response = StripeClient::create()->update_payment_intent( $payment_intent_id, $payment_intent_args );

				// An intent that never learned the customer collects a payment no
				// subscription can reuse; better to stop before the customer pays.
				if ( is_wp_error( $update_response ) ) {
					throw new Exception( esc_html( $update_response->get_error_message() ) );
				}
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
	 * @since 2.0.0
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
	 * Display fields.
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( $description ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}

		// No fixed height: the Payment Element sizes itself to whichever methods
		// Stripe offers, and a hardcoded 228px either clipped it or left a gap.
		//
		// It ships in its loading state, because the payment intent is fetched the
		// moment the script runs: the skeleton holds the slot open from the first
		// paint until the element is ready, so the section never starts at nothing
		// and grows under a buyer who is reading it. `stripe.js` takes the class off
		// on the element's own `ready` event.
		echo '<div id="masteriyo-stripe-method" class="masteriyo-stripe-method masteriyo-stripe-method--loading" aria-busy="true">';
		echo '<div class="masteriyo-stripe-skeleton" aria-hidden="true">';
		echo '<span class="masteriyo-stripe-skeleton__line masteriyo-stripe-skeleton__line--label"></span>';
		echo '<span class="masteriyo-stripe-skeleton__line masteriyo-stripe-skeleton__line--field"></span>';
		echo '<span class="masteriyo-stripe-skeleton__line masteriyo-stripe-skeleton__line--label"></span>';
		echo '<span class="masteriyo-stripe-skeleton__line masteriyo-stripe-skeleton__line--field"></span>';
		echo '</div>';
		echo '<div id="masteriyo-stripe-payment-element"></div></div>';
	}

	/**
	 * Create subscription model.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Stripe\Subscription $stripe_subscription
	 */
	public function create_subscription( $order, $stripe_subscription ) {
		// Subscriptions are pro's — the model and its statuses both live there. The
		// free product never reaches this: nothing hooks the recurring filters, so no
		// free order is recurring and no Stripe subscription is ever created for one.
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return false;
		}

		masteriyo_get_logger()->info( 'Stripe create_subscription: Start', array( 'source' => 'payment-stripe' ) );
		$order_data = $order->get_data();
		unset( $order_data['id'] );

		$subscription = Subscription::instance();
		$subscription->set_props( $order_data );

		$order_item = current( $order->get_items() );
		$course     = masteriyo_get_order_item_subscription_product( $order_item );

		if ( ! $course ) {
			masteriyo_get_logger()->error( 'Stripe create_subscription: No subscription-capable product resolved from order item.', array( 'source' => 'payment-stripe' ) );
			return false;
		}

		$subscription->set_props(
			array(
				'billing_period'          => $course->get_billing_period(),
				'billing_interval'        => $course->get_billing_interval(),
				'billing_expire_after'    => $course->get_billing_expire_after(),
				'requires_manual_renewal' => false,
				'status'                  => SubscriptionStatus::ACTIVE,
				'parent_id'               => $order->get_id(),
				'subscription_id'         => $stripe_subscription->id,
				'recurring_amount'        => $order->get_total(),
			)
		);

		foreach ( $order->get_items( OrderItemType::all() ) as $order_item ) {
			$subscription->add_item( $order_item );
		}

		$subscription->save();

		masteriyo_get_logger()->info( 'Stripe create_subscription: Success', array( 'source' => 'payment-stripe' ) );

		return $subscription;
	}

	/**
	 * Create a stripe price objects for subscription.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Models\Course[] $courses
	 * @param string $currency_code
	 * @param float|string $total
	 * @param integer $order_id Order the prices belong to, for idempotency.
	 *
	 * @throws Exception When any course's price cannot be created — a partial
	 *                   result would create a subscription that renews without
	 *                   some of the paid courses.
	 *
	 * @return array
	 */
	protected function create_price_objects( $courses, $currency_code, $total, $order_id ) {
		masteriyo_get_logger()->info( 'Stripe create_price_objects: Start', array( 'source' => 'payment-stripe' ) );
		$price_objects = array_map(
			function ( $course ) use ( $currency_code, $total, $order_id ) {
					$price_data = $this->build_price_data( $course, $currency_code, $total );

					// One deterministic key per order and course, so a replay
					// after a crash receives the price the first attempt
					// created — keeping the subscription request identical for
					// its own idempotency key to resume.
					$idempotency_key = sprintf( 'masteriyo-order-%d-course-%d-price', $order_id, $course->get_id() );

				if ( Helper::use_platform() ) {
					$price = StripeClient::create()->create_price( $price_data, array( 'Idempotency-Key' => $idempotency_key ) );
					if ( is_wp_error( $price ) ) {
						throw new Exception( esc_html( $price->get_error_message() ) );
					}
					$price = (object) ( $price['data'] ?? array() );
					if ( empty( $price->id ) ) {
						throw new Exception( 'Stripe price response is missing an ID.' );
					}
					return $price;
				}
					return Price::create( $price_data, array_merge( Helper::get_stripe_options(), array( 'idempotency_key' => $idempotency_key ) ) );
			},
			$courses
		);

		masteriyo_get_logger()->info( 'Stripe create_price_objects: Success', array( 'source' => 'payment-stripe' ) );

		return array_values( $price_objects );
	}

	/**
	 * Build the Stripe price payload for one course of a recurring order.
	 *
	 * The unit_amount goes through the same currency-aware conversion as the
	 * checkout PaymentIntent: zero-decimal currencies (JPY, KRW, …) are already
	 * in Stripe's smallest unit, so multiplying by 100 would bill every renewal
	 * 100x the checkout charge.
	 *
	 * @param \Masteriyo\Models\Course $course Course being billed.
	 * @param string $currency_code Currency code.
	 * @param float|string $total Order total in major units.
	 *
	 * @return array
	 */
	protected function build_price_data( $course, $currency_code, $total ) {
		return array(
			'currency'     => $currency_code,
			'unit_amount'  => Helper::convert_cart_total_to_stripe_amount( $total, $currency_code ),
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
	}

	/**
	 * Create or return stripe customer id.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Models\Order\Order|null $order Order object to get customer ID from.
	 *
	 * @return object
	 */
	protected function create_stripe_customer( $order = null ) {
		masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Start', array( 'source' => 'payment-stripe' ) );

		$user = null;

		if ( $order && $order->get_customer_id() ) {
			$user = masteriyo_get_user( $order->get_customer_id() );
		}

		if ( ! $user || is_wp_error( $user ) ) {
			$user = masteriyo_get_current_user();
		}

		if ( ! $user || is_wp_error( $user ) ) {
			masteriyo_get_logger()->error( 'Stripe create_stripe_customer: User not found', array( 'source' => 'payment-stripe' ) );
			return null;
		}

		$existing_customer_id = get_user_meta( $user->get_id(), '_stripe_customer', true );
		$stripe_customer      = null;

		try {
			if ( ! empty( $existing_customer_id ) ) {
				if ( Helper::use_platform() ) {
					$response = StripeClient::create()->retrieve_customer( $existing_customer_id );
					if ( is_wp_error( $response ) ) {
						masteriyo_get_logger()->warning( "Failed to retrieve customer {$existing_customer_id}: " . $response->get_error_message(), array( 'source' => 'payment-stripe' ) );
					} else {
						$stripe_customer = $response['data'] ?? null;
						if ( $stripe_customer && ! empty( $stripe_customer['id'] ) ) {
							$stripe_customer = (object) $stripe_customer;
							return $stripe_customer;
						}
					}
				} else {
					$stripe_customer = Customer::retrieve( $existing_customer_id, Helper::get_stripe_options() );

					if ( $stripe_customer && ! empty( $stripe_customer->id ) ) {
						return $stripe_customer;
					}
				}
				$stripe_customer = null;
			}

			if ( ! $stripe_customer ) {
				$customer_data = array(
					'email'    => $user->get_billing_email() ? $user->get_billing_email() : $user->get_email(),
					'name'     => trim( $user->get_billing_first_name() . ' ' . $user->get_billing_last_name() ),
					'metadata' => array(
						'customer_id'    => $user->get_id(),
						'customer_email' => $user->get_email(),
					),
				);

				$billing_phone = $user->get_billing_phone();
				if ( ! empty( $billing_phone ) ) {
					$customer_data['phone'] = $billing_phone;
				}

				$stripe_address = array(
					'line1'       => (string) $user->get_billing_address_1(),
					'line2'       => (string) $user->get_billing_address_2(),
					'city'        => (string) $user->get_billing_city(),
					'state'       => (string) $user->get_billing_state(),
					'postal_code' => (string) $user->get_billing_postcode(),
					'country'     => (string) $user->get_billing_country(),
				);
				$stripe_address = array_filter(
					$stripe_address,
					function ( $v ) {
						return '' !== $v;
					}
				);
				if ( ! empty( $stripe_address ) ) {
					$customer_data['address'] = $stripe_address;
				}

				if ( Helper::use_platform() ) {
					$response = StripeClient::create()->create_customer( $customer_data );
					if ( is_wp_error( $response ) ) {
						throw new Exception( 'StripeClient error: ' . $response->get_error_message() );
					}
					$stripe_customer_data = (array) ( $response['data'] ?? array() );
					if ( empty( $stripe_customer_data['id'] ) ) {
						throw new Exception( 'StripeClient response missing customer ID' );
					}
					$stripe_customer = (object) $stripe_customer_data;
				} else {
					$stripe_customer = Customer::create( $customer_data, Helper::get_stripe_options() );
				}
			}
			$customer_id = $stripe_customer->id;
			update_user_meta( $user->get_id(), '_stripe_customer', $customer_id );
			masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Success', array( 'source' => 'payment-stripe' ) );
			masteriyo_get_logger()->info( 'Stripe create_stripe_customer: Customer ID: ' . $customer_id, array( 'source' => 'payment-stripe' ) );
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'payment-stripe' ) );
		}

		return $stripe_customer;
	}


	/**
	 * Create subscription.
	 *
	 * @since 2.6.10
	 *
	 * @param string $customer_id Stripe customer the payment method is saved on.
	 * @param array $price_objects Stripe price objects for the order's courses.
	 * @param object $course Subscription-capable product that sets the billing cycle.
	 * @param \Masteriyo\Models\Order\Order $order Paid order.
	 * @param string $default_payment_method Reusable payment method for renewals.
	 *
	 * @throws Exception When Stripe refuses the subscription or answers without an ID.
	 *
	 * @return object Stripe subscription.
	 */
	protected function create_stripe_subscription( $customer_id, $price_objects, $course, $order, $default_payment_method ) {
		masteriyo_get_logger()->info( 'Stripe create_stripe_subscription: Start', array( 'source' => 'payment-stripe' ) );

		if ( empty( $price_objects ) ) {
			throw new Exception( 'No Stripe prices were created for this order.' );
		}

		$items = array_map(
			function ( $price_object ) {
				return array(
					'price' => $price_object->id,
				);
			},
			$price_objects
		);

		// Everything below derives from the order, never from the clock, so a
		// replay after a crash sends the exact request the first attempt sent.
		// With the idempotency key, Stripe then answers with the subscription
		// that attempt created instead of creating (and billing) a second one.
		$paid_at = absint( $order->get_date_paid( 'edit' ) );
		$paid_at = $paid_at ? $paid_at : time();

		$subscription_params = array(
			'customer'               => $customer_id,
			'items'                  => $items,
			'default_payment_method' => $default_payment_method,
			// The checkout intent collected the first period, so billing starts
			// at the first renewal: a future anchor with proration off raises no
			// invoice until then. Not `trial_end` — Stripe floors that at 48
			// hours, which a daily billing cycle renews sooner than.
			'billing_cycle_anchor'   => Helper::get_first_renewal_timestamp( $course->get_billing_interval(), $course->get_billing_period(), $paid_at ),
			'proration_behavior'     => 'none',
			'metadata'               => array( 'order_id' => $order->get_id() ),
		);

		$expire_after = $course->get_billing_expire_after();

		if ( $expire_after ) {
			$subscription_params['cancel_at'] = $paid_at + intval( $expire_after ) * MONTH_IN_SECONDS;
		}

		$idempotency_key = 'masteriyo-order-' . $order->get_id() . '-subscription';

		if ( Helper::use_platform() ) {
			$subscription = StripeClient::create()->create_subscription( $subscription_params, array( 'Idempotency-Key' => $idempotency_key ) );

			if ( is_wp_error( $subscription ) ) {
				throw new Exception( esc_html( $subscription->get_error_message() ) );
			}
			$subscription = (object) ( $subscription['data'] ?? array() );
		} else {
			$subscription = StripeSubscription::create(
				$subscription_params,
				array_merge( Helper::get_stripe_options(), array( 'idempotency_key' => $idempotency_key ) )
			);
		}

		if ( empty( $subscription->id ) ) {
			throw new Exception( 'Stripe subscription response is missing an ID.' );
		}

		masteriyo_get_logger()->info( 'Stripe create_stripe_subscription: Success', array( 'source' => 'payment-stripe' ) );

		return $subscription;
	}

	/**
	 * Create the Stripe and Masteriyo subscriptions for a paid recurring order.
	 *
	 * Runs from the payment-completion hook, whichever path completes the
	 * order first (webhook or verified return). By then the checkout intent
	 * has succeeded with `setup_future_usage`, so the customer and a reusable
	 * payment method exist on Stripe's side.
	 *
	 * @param \Masteriyo\Models\Order\Order $order Paid order.
	 *
	 * @throws Exception When no reusable payment method exists or a Stripe call fails.
	 *
	 * @return object The Masteriyo subscription.
	 */
	public function create_subscription_for_paid_order( $order ) {
		$courses = array_filter( array_map( 'masteriyo_get_order_item_subscription_product', $order->get_items() ) );

		if ( empty( $courses ) ) {
			throw new Exception( esc_html__( 'No subscription product found for this order.', 'learning-management-system' ) );
		}

		$payment_intent_id = get_post_meta( $order->get_id(), '_stripe_payment_intent_id', true );

		if ( ! $payment_intent_id ) {
			$payment_intent_id = $order->get_transaction_id();
		}

		if ( ! $payment_intent_id ) {
			throw new Exception( esc_html__( 'No payment intent recorded on the order.', 'learning-management-system' ) );
		}

		$payment_intent = Helper::retrieve_payment_intent( $payment_intent_id );

		// The completion hooks also fire when an admin marks the order paid,
		// where a SEPA-family intent can still be `processing`: it already has
		// a customer, method and charge, but the funds have not settled. Only
		// a succeeded intent may start a billing schedule — if this payment
		// later fails, a subscription would bill for an unpaid first period.
		$intent_status = (string) ( $payment_intent->status ?? '' );

		if ( 'succeeded' !== $intent_status ) {
			throw new Exception(
				sprintf(
					/* translators: %s: Stripe payment intent status. */
					esc_html__( 'The payment intent has not succeeded (status: %s); the subscription is created only after the payment settles.', 'learning-management-system' ),
					esc_html( $intent_status ? $intent_status : 'unknown' )
				)
			);
		}

		$customer_id    = (string) ( $payment_intent->customer ?? '' );
		$payment_method = (string) ( $payment_intent->payment_method ?? '' );
		$latest_charge  = (string) ( $payment_intent->latest_charge ?? '' );

		if ( $latest_charge ) {
			// iDEAL and Bancontact settle into a generated SEPA mandate; the
			// charge is the only place that names it.
			$payment_method = Helper::get_reusable_payment_method_from_charge( Helper::retrieve_charge( $latest_charge ), $payment_method );
		}

		if ( ! $customer_id || ! $payment_method ) {
			throw new Exception( esc_html__( 'The payment intent carries no customer or reusable payment method.', 'learning-management-system' ) );
		}

		$course = current( $courses );

		// A billable Stripe subscription must never outlive our knowledge of it.
		// Its ID lands on the order the moment it exists, so a failure anywhere
		// after this point leaves a findable record — and a replay resumes this
		// subscription instead of creating (and billing) a second one.
		$existing_stripe_subscription_id = get_post_meta( $order->get_id(), '_stripe_subscription_id', true );

		if ( $existing_stripe_subscription_id ) {
			$stripe_subscription = Helper::retrieve_subscription( $existing_stripe_subscription_id );

			masteriyo_get_logger()->info(
				'Resuming Stripe subscription ' . $existing_stripe_subscription_id . ' for order ' . $order->get_id() . ' after an earlier incomplete creation.',
				array( 'source' => 'payment-stripe' )
			);
		} else {
			$price_objects       = $this->create_price_objects( $courses, $order->get_currency(), $order->get_total(), $order->get_id() );
			$stripe_subscription = $this->create_stripe_subscription( $customer_id, $price_objects, $course, $order, $payment_method );

			update_post_meta( $order->get_id(), '_stripe_subscription_id', $stripe_subscription->id );
		}

		$subscription = $this->create_subscription( $order, $stripe_subscription );

		if ( ! $subscription ) {
			throw new Exception( esc_html__( 'Unable to create subscription', 'learning-management-system' ) );
		}

		// Patch the Masteriyo subscription ID onto the Stripe subscription for
		// webhook routing. Failure here is tolerable, never fatal: the local
		// subscription is already real, and the router falls back to the
		// order_id metadata the subscription carried from creation.
		$subscription_args = array(
			'metadata' => array(
				'subscription_id' => $subscription->get_id(),
				'order_id'        => $order->get_id(),
			),
		);

		try {
			if ( Helper::use_platform() ) {
				$patched = StripeClient::create()->update_subscription( $stripe_subscription->id, $subscription_args );

				if ( is_wp_error( $patched ) ) {
					throw new Exception( esc_html( $patched->get_error_message() ) );
				}
			} else {
				StripeSubscription::update(
					$stripe_subscription->id,
					$subscription_args,
					Helper::get_stripe_options()
				);
			}
		} catch ( Exception $e ) {
			masteriyo_get_logger()->warning(
				'Stripe subscription ' . $stripe_subscription->id . ' kept only its order_id metadata (subscription_id patch failed: ' . $e->getMessage() . '); webhook routing falls back to the order.',
				array( 'source' => 'payment-stripe' )
			);
		}

		return $subscription;
	}
}
