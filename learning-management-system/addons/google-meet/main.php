<?php
/**
 * Addon Name: Google Meet Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: A feature that allows teachers to share virtual lessons with their class directly within Google Meet.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Plan: Free
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_GOOGLE_MEET_INTEGRATION_FILE', __FILE__ );
define( 'MASTERIYO_GOOGLE_MEET_INTEGRATION_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_GOOGLE_MEET_INTEGRATION_DIR', __DIR__ );
define( 'MASTERIYO_GOOGLE_MEET_INTEGRATION_SLUG', 'google-meet' );

if ( ! ( new Addons() )->is_active( MASTERIYO_GOOGLE_MEET_INTEGRATION_SLUG ) ) {
	return;
}

require_once __DIR__ . '/helper/google-meet.php';

/**
 * Include service providers for Google Meet Integration.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Google Meet Integration.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.google-meet' )->init();
	}
);
