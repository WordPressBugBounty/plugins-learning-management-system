<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Mollie Payment Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: The "Mollie Payment Integration" addon by Masteriyo enables secure and seamless transactions on your site using mollie payment gateway.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: Feature
 * Plan: Free
 * Category: Payments
 */

use Masteriyo\Pro\Addons;
use Masteriyo\Addons\Mollie\MollieAddon;

define( 'MASTERIYO_MOLLIE_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_MOLLIE_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_MOLLIE_ADDON_DIR', __DIR__ );
define( 'MASTERIYO_MOLLIE_ADDON_SLUG', 'mollie' );
define( 'MASTERIYO_MOLLIE_ADDON_TEMPLATES', __DIR__ . '/templates' );
define( 'MASTERIYO_MOLLIE_ADDON_ASSETS_URL', plugins_url( 'assets', MASTERIYO_MOLLIE_ADDON_FILE ) );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_MOLLIE_ADDON_SLUG ) ) {
	return;
}

// Include theMmollie helper file that possibly contains necessary configurations, functions, and setups for the mollie integration.
require_once __DIR__ . '/helper/mollie.php';

// Initialize the Mollie addon.
MollieAddon::instance()->init();
