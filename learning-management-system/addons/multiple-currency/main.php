<?php
/**
 * Addon Name: Multiple Currency
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Multiple Currency allows you to sell the same product in multiple currencies based on the country of the customer.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: Feature
 * Plan: Free
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_MULTIPLE_CURRENCY_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_MULTIPLE_CURRENCY_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_MULTIPLE_CURRENCY_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_MULTIPLE_CURRENCY_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_MULTIPLE_CURRENCY_ADDON_SLUG', 'multiple-currency' );
define( 'MASTERIYO_MULTIPLE_CURRENCY_ADDON_ASSETS_URL', plugins_url( 'assets', MASTERIYO_MULTIPLE_CURRENCY_ADDON_FILE ) );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_MULTIPLE_CURRENCY_ADDON_SLUG ) ) {
	return;
}

require_once __DIR__ . '/helper/multiple-currency.php';

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_MULTIPLE_CURRENCY_ADDON_SLUG ) ) {
	return;
}

add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once dirname( __FILE__ ) . '/config/providers.php' );
	}
);

add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.multiple-currency' )->init();
	}
);
