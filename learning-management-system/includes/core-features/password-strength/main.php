<?php
/**
 * Core Feature Name: Password Strength
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define constants for this feature.
 */
define( 'MASTERIYO_PASSWORD_STRENGTH_FILE', __FILE__ );
define( 'MASTERIYO_PASSWORD_STRENGTH_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_PASSWORD_STRENGTH_DIR', __DIR__ );
define( 'MASTERIYO_PASSWORD_STRENGTH_TEMPLATES', __DIR__ . '/templates' );
define( 'MASTERIYO_PASSWORD_STRENGTH_SLUG', 'password-strength' );


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

		$feature = masteriyo( 'core-features.password-strength' );

		if ( is_object( $feature ) && method_exists( $feature, 'init' ) ) {
			$feature->init();

			/**
			 * Fires after the Password Strength core feature has initialized.
			 */
			do_action( 'masteriyo_password_strength_initialized' );
		}
	}
);
