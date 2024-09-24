<?php
/**
 * Addon Name: Bunny Net
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: A feature that allows teachers to stream videos through bunny net.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Plan: Free
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_BUNNY_NET_FILE', __FILE__ );
define( 'MASTERIYO_BUNNY_NET_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_BUNNY_NET_DIR', __DIR__ );
define( 'MASTERIYO_BUNNY_NET_SLUG', 'bunny-net' );

if ( ! ( new Addons() )->is_active( MASTERIYO_BUNNY_NET_SLUG ) ) {
	return;
}

/**
 * Include service providers for Bunny Net
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Bunny Net Integration.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.bunny-net' )->init();
	}
);
