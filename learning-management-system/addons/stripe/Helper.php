<?php
/**
 * Stripe helper functions.
 *
 * @since 1.14.0
 * @package Masteriyo\Stripe
 */

namespace Masteriyo\Addons\Stripe;

defined( 'ABSPATH' ) || exit;


class Helper {
	/**
	 * Return webhook endpoint url.
	 *
	 * @since 1.14.0
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
	 * @since 1.14.0
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
		} else {
			$new_total_amount = (int) masteriyo_round( $total_amount, 2 ) * 100;
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
