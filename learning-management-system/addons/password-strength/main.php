<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Password Strength
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Use this feature to make your users use strong password with combination of numbers, capital letters and unique symbols while signing up.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: enhancement
 * Plan: Starter,Pro,Elite,Growth,Scale
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_PASSWORD_STRENGTH_FILE', __FILE__ );
define( 'MASTERIYO_PASSWORD_STRENGTH_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_PASSWORD_STRENGTH_DIR', __DIR__ );
define( 'MASTERIYO_PASSWORD_STRENGTH_TEMPLATES', __DIR__ . '/templates' );
define( 'MASTERIYO_PASSWORD_STRENGTH_SLUG', 'password-strength' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_PASSWORD_STRENGTH_SLUG ) ) {
	return;
}

/**
 * Include service providers for Password Strength.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Password Strength.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.password-strength' )->init();
	}
);
