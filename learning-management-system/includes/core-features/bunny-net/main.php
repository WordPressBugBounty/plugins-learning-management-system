<?php
/**
 * Core Feature Name: Bunny Net
 */

defined( 'ABSPATH' ) || exit;

// Constants (safe against double-includes).
defined( 'MASTERIYO_BUNNY_NET_FILE' ) || define( 'MASTERIYO_BUNNY_NET_FILE', __FILE__ );
defined( 'MASTERIYO_BUNNY_NET_BASENAME' ) || define( 'MASTERIYO_BUNNY_NET_BASENAME', plugin_basename( __FILE__ ) );
defined( 'MASTERIYO_BUNNY_NET_DIR' ) || define( 'MASTERIYO_BUNNY_NET_DIR', __DIR__ );
defined( 'MASTERIYO_BUNNY_NET_SLUG' ) || define( 'MASTERIYO_BUNNY_NET_SLUG', 'bunny-net' );

/**
 * Register this feature's service providers into Masteriyo's provider list.
 * This must run BEFORE the container boots/collects providers.
 */
add_filter(
	'masteriyo_service_providers',
	static function( $providers ) {
		$providers_file = __DIR__ . '/config/providers.php';

		if ( file_exists( $providers_file ) ) {
			$feature_providers = require $providers_file;

			if ( is_array( $feature_providers ) ) {
				$providers = array_merge( $providers, $feature_providers );
			}
		}

		return $providers;
	}
);
add_action(
	'masteriyo_before_init',
	static function() {

		if ( ! function_exists( 'masteriyo' ) ) {
			return;
		}

		$feature = masteriyo( 'core-features.bunny-net' );

		if ( is_object( $feature ) && method_exists( $feature, 'init' ) ) {
			$feature->init();

			/**
			 * Fires after the Password Strength core feature has initialized.
			 */
			do_action( 'masteriyo_bunny_net_initialized' );
		}
	}
);
