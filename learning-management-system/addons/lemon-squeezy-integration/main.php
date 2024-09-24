<?php
/**
 * Addon Name: Lemon Squeezy Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Addon Type: feature
 * Description: Lemon Squeezy Integration sales platform with Masteriyo to sell and manage your courses with ease.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Type: Integration
 * Plan: Free
 */

use Masteriyo\Pro\Addons;
use Masteriyo\Addons\LemonSqueezyIntegration\LemonSqueezyIntegrationAddon;

define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_SLUG', 'lemon-squeezy-integration' );
define( 'MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ASSETS_URL', plugins_url( 'assets', MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_FILE ) );


// Bail early if the addon is not active.
if ( ! ( ( new Addons() )->is_active( MASTERIYO_LEMON_SQUEEZY_INTEGRATION_ADDON_SLUG ) ) ) {
	return;
}

// Initialize Lemon Squeezy integration addon.
LemonSqueezyIntegrationAddon::instance()->init();
