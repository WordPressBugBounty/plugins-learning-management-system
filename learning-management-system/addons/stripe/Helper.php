<?php
/**
 * Stripe helper functions.
 *
 * @since 2.0.0
 * @package Masteriyo\Stripe
 */

namespace Masteriyo\Addons\Stripe;

use Exception;
use Masteriyo\Addons\Stripe\Client\StripeClient;

defined( 'ABSPATH' ) || exit;


class Helper {
	/**
	 * Return webhook endpoint url.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public static function get_webhook_endpoint_url() {
		return add_query_arg(
			array(
				'action' => 'masteriyo_stripe_webhook',
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Convert cart total to stripe amount which differs according to the currency code.
	 *
	 * @since 2.6.10
	 * @see https://stripe.com/docs/currencies
	 *
	 * @param float|integer|string $total_amount Total cart amount.
	 * @param string $currency_code Currency code.
	 *
	 * @return integer
	 */
	public static function convert_cart_total_to_stripe_amount( $total_amount, $currency_code ) {
		$currency_code = masteriyo_strtoupper( $currency_code );

		// Return as it is for zero decimal currencies.
		if ( in_array( $currency_code, self::get_zero_decimal_currencies(), true ) ) {
			$new_total_amount = absint( $total_amount );
		} elseif ( in_array( $currency_code, self::get_three_decimal_currencies(), true ) ) {
			// Stripe takes these in thousandths and historically rejected
			// amounts whose last digit is not 0; a 0-final amount is valid
			// under every doc revision, so: to hundredths, round, x10.
			$new_total_amount = (int) masteriyo_round( ( (float) $total_amount ) * 100 ) * 10;
		} else {
			// Multiply before rounding: casting 19.99 to int first truncates it to 1900 cents.
			$new_total_amount = (int) masteriyo_round( ( (float) $total_amount ) * 100 );
		}

		return $new_total_amount;
	}

	/**
	 * Convert a Stripe amount (minor units) back to major currency units.
	 *
	 * @param integer $amount Amount in the currency's Stripe minor unit.
	 * @param string $currency_code Currency code.
	 *
	 * @return float
	 */
	public static function convert_stripe_amount_to_cart_total( $amount, $currency_code ) {
		$currency_code = masteriyo_strtoupper( $currency_code );

		if ( in_array( $currency_code, self::get_zero_decimal_currencies(), true ) ) {
			return (float) $amount;
		}

		if ( in_array( $currency_code, self::get_three_decimal_currencies(), true ) ) {
			return ( (float) $amount ) / 1000;
		}

		return ( (float) $amount ) / 100;
	}

	/**
	 * Return three-decimal currencies: Stripe takes their amounts in
	 * thousandths, with the final digit always 0.
	 *
	 * @see https://stripe.com/docs/currencies#three-decimal
	 *
	 * @return array
	 */
	public static function get_three_decimal_currencies() {
		return array(
			'BHD',
			'JOD',
			'KWD',
			'OMR',
			'TND',
		);
	}

	/**
	 * Return zero-decimal currencies meaning currencies which don't have decimal values.
	 *
	 * @since 2.6.10
	 *
	 * @return array
	 */
	public static function get_zero_decimal_currencies() {
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

	/**
	 * Decide what a payment intent retrieved from Stripe proves about an order.
	 *
	 * The redirect back from Stripe carries query parameters anyone can type, so
	 * the decision uses only the retrieved intent: it must name this order in its
	 * metadata and carry exactly the order's total in the order's currency.
	 *
	 * @param object $payment_intent Payment intent with nested object access (Stripe SDK object, or API array decoded via json_decode( wp_json_encode( ... ) )).
	 * @param integer $order_id Order ID the intent must name in metadata.order_id.
	 * @param float|integer|string $order_total Order total, in major currency units.
	 * @param string $currency_code Order currency code.
	 *
	 * @return string 'succeeded', 'processing', 'mismatch' (intent is not this order's payment) or 'incomplete' (matches but not paid).
	 */
	public static function get_payment_intent_verdict( $payment_intent, $order_id, $order_total, $currency_code ) {
		if ( absint( $payment_intent->metadata->order_id ?? 0 ) !== absint( $order_id ) ) {
			return 'mismatch';
		}

		if ( (int) ( $payment_intent->amount ?? -1 ) !== self::convert_cart_total_to_stripe_amount( $order_total, $currency_code ) ) {
			return 'mismatch';
		}

		if ( masteriyo_strtolower( $currency_code ) !== masteriyo_strtolower( (string) ( $payment_intent->currency ?? '' ) ) ) {
			return 'mismatch';
		}

		$status = (string) ( $payment_intent->status ?? '' );

		if ( in_array( $status, array( 'succeeded', 'processing' ), true ) ) {
			return $status;
		}

		return 'incomplete';
	}

	/**
	 * Payment-method portion of the PaymentIntent parameters.
	 *
	 * The method list is delegated to the merchant's Stripe dashboard
	 * (`automatic_payment_methods`), so Klarna, iDEAL, SEPA and the rest appear
	 * as soon as the merchant enables them there — filtered by currency and
	 * buyer country on Stripe's side. A cart holding a recurring course asks
	 * Stripe to save the payment method for off-session renewals, which also
	 * makes the Payment Element hide every method that cannot be reused
	 * (Klarna, Affirm) while keeping card, SEPA, and the methods that settle
	 * into a SEPA mandate (iDEAL, Bancontact).
	 *
	 * @param boolean $has_recurring_course Whether the cart contains a recurring course.
	 *
	 * @return array
	 */
	public static function get_payment_intent_method_params( $has_recurring_course ) {
		$params = array( 'automatic_payment_methods' => array( 'enabled' => true ) );

		if ( $has_recurring_course ) {
			$params['setup_future_usage'] = 'off_session';
		}

		return $params;
	}

	/**
	 * Whether an existing payment intent can be reused for the current cart.
	 *
	 * Reusable only while the intent is still awaiting payment, its amount and
	 * currency match the current cart, and its off-session setup matches the
	 * cart's recurring flag in both directions: a recurring cart needs the
	 * payment method saved, and a one-time cart must not save it — an
	 * `off_session` intent also hides the payment methods that are valid only
	 * for one-time purchases.
	 *
	 * @param object $payment_intent Payment intent with nested object access.
	 * @param integer $expected_amount Current cart amount, in the currency's minor unit.
	 * @param string $expected_currency Current cart currency code.
	 * @param boolean $has_recurring_course Whether the cart contains a recurring course.
	 *
	 * @return boolean
	 */
	public static function is_payment_intent_reusable( $payment_intent, $expected_amount, $expected_currency, $has_recurring_course = false ) {
		$reusable_statuses = array( 'requires_payment_method', 'requires_confirmation', 'requires_action' );

		if ( ! in_array( (string) ( $payment_intent->status ?? '' ), $reusable_statuses, true ) ) {
			return false;
		}

		if ( (int) ( $payment_intent->amount ?? -1 ) !== $expected_amount ) {
			return false;
		}

		if ( masteriyo_strtolower( (string) ( $payment_intent->currency ?? '' ) ) !== masteriyo_strtolower( $expected_currency ) ) {
			return false;
		}

		if ( (bool) $has_recurring_course !== ( 'off_session' === ( $payment_intent->setup_future_usage ?? '' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * When the first period of a subscription is due on Stripe's side.
	 *
	 * The checkout PaymentIntent already collected the first period, so the
	 * Stripe subscription is created active with its billing cycle anchored
	 * exactly one billing interval later — the first renewal charge.
	 *
	 * @param integer $billing_interval Number of periods per billing cycle.
	 * @param string $billing_period One of CourseBillingPeriod: 'day', 'week', 'month', 'year'.
	 * @param integer $from Unix timestamp the paid period starts at.
	 *
	 * @return integer Unix timestamp of the first renewal.
	 */
	public static function get_first_renewal_timestamp( $billing_interval, $billing_period, $from ) {
		$interval = max( 1, absint( $billing_interval ) );
		$period   = in_array( $billing_period, array( 'day', 'week', 'month', 'year' ), true ) ? $billing_period : 'month';

		$start = new \DateTimeImmutable( '@' . $from );
		$end   = $start->modify( sprintf( '+%d %s', $interval, $period ) );

		// PHP rolls a missing day forward (Jan 31 + 1 month = Mar 3), which would
		// stretch the paid period and shift the billing anchor. Clamp to the last
		// day of the intended month instead — Stripe's own intervals bill that way.
		if ( in_array( $period, array( 'month', 'year' ), true ) && $end->format( 'j' ) !== $start->format( 'j' ) ) {
			$end = $end->modify( 'last day of previous month' );
		}

		return $end->getTimestamp();
	}

	/**
	 * The payment method a subscription can charge for renewals.
	 *
	 * A card or SEPA payment method is reusable as-is. iDEAL and Bancontact
	 * are single-use: their charge settles into a generated SEPA mandate,
	 * and only that generated payment method can be charged again. The charge
	 * records it under payment_method_details.<type>.generated_sepa_debit.
	 *
	 * @param object|null $charge Charge with nested object access, or null when unavailable.
	 * @param string $fallback_payment_method The intent's own payment method ID.
	 *
	 * @return string Payment method ID to store as the subscription default.
	 */
	public static function get_reusable_payment_method_from_charge( $charge, $fallback_payment_method ) {
		$details = $charge->payment_method_details ?? null;
		$type    = (string) ( $details->type ?? '' );

		if ( $type && isset( $details->{$type}->generated_sepa_debit ) && is_string( $details->{$type}->generated_sepa_debit ) ) {
			return $details->{$type}->generated_sepa_debit;
		}

		// A single-use method with no generated mandate cannot charge renewals.
		// Answering the fallback would create a subscription whose every renewal
		// fails; answering nothing makes the caller stop and log instead.
		if ( in_array( $type, array( 'ideal', 'bancontact', 'sofort', 'giropay' ), true ) ) {
			return '';
		}

		return $fallback_payment_method;
	}

	/**
	 * Retrieve one Stripe object, platform or direct.
	 *
	 * @param string $id Stripe object ID.
	 * @param string $platform_method StripeClient method that retrieves it.
	 * @param string $sdk_class SDK class with a static retrieve().
	 *
	 * @throws Exception When the platform request fails.
	 *
	 * @return object The Stripe object with nested object access.
	 */
	private static function retrieve_stripe_object( $id, $platform_method, $sdk_class ) {
		if ( self::use_platform() ) {
			$response = StripeClient::create()->{$platform_method}( $id );

			if ( is_wp_error( $response ) ) {
				throw new Exception( esc_html( $response->get_error_message() ) );
			}

			// The platform client returns nested arrays; the SDK returns objects.
			// Re-encode so both read the same way ($intent->metadata->order_id).
			return json_decode( wp_json_encode( isset( $response['data'] ) ? $response['data'] : array() ) );
		}

		return $sdk_class::retrieve( $id, self::get_stripe_options() );
	}

	/**
	 * Retrieve a payment intent from Stripe, platform or direct.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 *
	 * @throws Exception When the platform request fails.
	 *
	 * @return object Payment intent with nested object access.
	 */
	public static function retrieve_payment_intent( $payment_intent_id ) {
		return self::retrieve_stripe_object( $payment_intent_id, 'retrieve_payment_intent', \Stripe\PaymentIntent::class );
	}

	/**
	 * Retrieve a charge from Stripe, platform or direct.
	 *
	 * @param string $charge_id Charge ID.
	 *
	 * @throws Exception When the platform request fails.
	 *
	 * @return object Charge with nested object access.
	 */
	public static function retrieve_charge( $charge_id ) {
		return self::retrieve_stripe_object( $charge_id, 'retrieve_charge', \Stripe\Charge::class );
	}

	/**
	 * Retrieve a subscription from Stripe, platform or direct.
	 *
	 * @param string $subscription_id Stripe subscription ID.
	 *
	 * @throws Exception When the platform request fails.
	 *
	 * @return object Subscription with nested object access.
	 */
	public static function retrieve_subscription( $subscription_id ) {
		return self::retrieve_stripe_object( $subscription_id, 'retrieve_subscription', \Stripe\Subscription::class );
	}

	/**
	 * Get stripe options.
	 *
	 * @return array
	 */
	public static function get_stripe_options() {
		return array(
			'api_key' => Setting::get_secret_key(),
		);
	}

	/**
	 * Use platform.
	 *
	 * @return boolean
	 */
	public static function use_platform() {
		return Setting::get_stripe_user_id() && Setting::get( 'use_platform' );
	}
}
