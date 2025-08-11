<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Google reCAPTCHA
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Allows to add Google reCAPTCHA for Masteriyo forms (login, student registration and instructor registration).
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: enhancement
 * Plan: Free
 * Category: Security
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_RECAPTCHA_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_RECAPTCHA_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_RECAPTCHA_ADDON_DIR', __DIR__ );
define( 'MASTERIYO_RECAPTCHA_ASSETS', __DIR__ . '/assets' );
define( 'MASTERIYO_RECAPTCHA_TEMPLATES', __DIR__ . '/templates' );
define( 'MASTERIYO_RECAPTCHA_ADDON_SLUG', 'recaptcha' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_RECAPTCHA_ADDON_SLUG ) ) {
	return;
}

/**
 * Include service providers for Google Recaptcha.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Google Recaptcha.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.recaptcha' )->init();
	}
);
