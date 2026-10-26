<?php
/**
 * Payment tracking information.
 *
 * @package Masteriyo\Tracking
 */

namespace Masteriyo\Tracking;

use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Payment configuration and order aggregates. Booleans, slugs and counts only.
 *
 * @phpstan-type PaymentsData array{currency: mixed, enabled: bool, paypal_enabled: bool, offline_enabled: bool, invoice_enabled: bool, revenue_sharing_enabled: bool, gateways: array<string, bool>, orders_by_gateway: array<array-key, positive-int>, orders_by_status: array<array-key, positive-int>}
 */
class PaymentsTrackingInfo {

	/**
	 * Gateway names known even when their addon is currently switched off, so
	 * past orders stay under their own name instead of collapsing into `other`.
	 *
	 * @var string[]
	 */
	const KNOWN_GATEWAYS = array( 'paypal', 'offline', 'stripe', 'mollie', 'razorpay', 'lemon_squeezy' );

	/**
	 * Build the payments group.
	 *
	 * @return PaymentsData
	 */
	public static function all(): array {
		$gateways = self::get_gateways();
		$known    = array_unique( array_merge( array_keys( $gateways ), self::KNOWN_GATEWAYS ) );

		return array(
			'currency'                => masteriyo_get_setting( 'payments.currency.currency' ),
			'enabled'                 => (bool) masteriyo_commerce_enabled(),
			'paypal_enabled'          => masteriyo_string_to_bool( masteriyo_get_setting( 'payments.paypal.enable' ) ),
			'offline_enabled'         => masteriyo_string_to_bool( masteriyo_get_setting( 'payments.offline.enable' ) ),
			'invoice_enabled'         => masteriyo_string_to_bool( masteriyo_get_setting( 'payments.invoice.enable' ) ),
			'revenue_sharing_enabled' => self::is_revenue_sharing_enabled(),
			'gateways'                => $gateways,
			'orders_by_gateway'       => self::get_orders_by_gateway( $known ),
			'orders_by_status'        => TrackingCounts::by_post_status( PostType::ORDER ),
		);
	}

	/**
	 * Whether the revenue-sharing addon is active and switched on. Its setting
	 * lives in its own option, not `masteriyo_settings`.
	 *
	 * @return bool
	 */
	public static function is_revenue_sharing_enabled(): bool {
		if ( ! ( new Addons() )->is_active( 'revenue-sharing' ) ) {
			return false;
		}

		$settings = get_option( 'masteriyo_revenue_sharing_settings', array() );

		return masteriyo_string_to_bool( masteriyo_array_get( $settings, 'enable', false ) );
	}

	/**
	 * Enabled flag for every registered gateway, keyed by gateway name.
	 *
	 * @return array<string, bool>
	 */
	public static function get_gateways(): array {
		$flags = array();

		try {
			$registry = masteriyo( 'payment-gateways' );

			if ( ! is_object( $registry ) || ! method_exists( $registry, 'payment_gateways' ) ) {
				return array();
			}

			foreach ( $registry->payment_gateways() as $gateway ) {
				$name = TrackingCounts::slugify( $gateway->get_name() );

				if ( '' !== $name ) {
					$flags[ $name ] = masteriyo_string_to_bool( $gateway->is_enabled() );
				}
			}
		} catch ( \Throwable $e ) {
			return array();
		}

		ksort( $flags );

		return $flags;
	}

	/**
	 * Completed order counts per payment method; unknown methods are summed into
	 * `other`, orders with no method (free checkouts, manual orders) into `none`.
	 *
	 * @param string[] $known Known gateway names.
	 *
	 * @return array<array-key, positive-int>
	 */
	public static function get_orders_by_gateway( array $known ): array {
		return TrackingCounts::by_meta_value_or_unset( PostType::ORDER, '_payment_method', $known, OrderStatus::COMPLETED, 'none' );
	}
}
