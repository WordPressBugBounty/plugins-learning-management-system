<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Stripe Payment Gateway
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Easily sell online courses and accept credit card payments via Stripe. It supports major cards like Visa, MasterCard, American Express, Discover, debit cards, etc.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: payment
 * Plan: Free
 * Category: Payments
 */

use Masteriyo\Addons\Stripe\StripeAddon;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_STRIPE_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_STRIPE_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_STRIPE_ADDON_DIR', __DIR__ );
define( 'MASTERIYO_STRIPE_ASSETS', __DIR__ . '/assets' );
define( 'MASTERIYO_STRIPE_TEMPLATES', __DIR__ . '/templates' );
define( 'MASTERIYO_STRIPE_ADDON_SLUG', 'stripe' );
define( 'MASTERIYO_STRIPE_PLATFORM_LIVE_PUBLIC_KEY', 'pk_live_51Rg5LZGwlzUj8nZsJJjZ2LHFSZKngZ9WP12VyO99A93MKDgpegKG5m1qwVdNw3iaU3F2Yc8ssqsmYDFNAQXALb7K00rI9a3h2d' );
define( 'MASTERIYO_STRIPE_PLATFORM_TEST_PUBLIC_KEY', 'pk_test_51Rg5LiGfv9Ycu87rI2vHG3C47yTkJ9E1hv0Yx3NEVIZ5ijtFWsWvfUmeKZN1wNThvtG6FMy7iDQLTwUiV19kk9kS00L2tZ9WmH' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_STRIPE_ADDON_SLUG ) ) {
	return;
}

/**
 * Include service providers for stripe.
 */
add_filter(
	'masteriyo_service_providers',
	function ( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

StripeAddon::instance()->init();
