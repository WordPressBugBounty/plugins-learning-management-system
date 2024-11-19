<?php
/**
 * Addon Name: Stripe Payment Gateway
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Easily sell online courses and accept credit card payments via Stripe. It supports major cards like Visa, MasterCard, American Express, Discover, debit cards, etc.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: payment
 * Plan: Free
 */

use Masteriyo\Addons\Stripe\StripeAddon;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_STRIPE_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_STRIPE_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_STRIPE_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_STRIPE_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'MASTERIYO_STRIPE_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_STRIPE_ADDON_SLUG', 'stripe' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_STRIPE_ADDON_SLUG ) ) {
	return;
}

/**
 * Include service providers for stripe.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once dirname( __FILE__ ) . '/config/providers.php' );
	}
);

StripeAddon::instance()->init();
