<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Oxygen Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Equip your Oxygen builder with Masteriyo elements. Add components like course lists and categories to any page/post.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Requires: Oxygen
 * Plan: Free
 */

use Masteriyo\Addons\OxygenIntegration\OxygenIntegrationAddon;
use Masteriyo\Addons\OxygenIntegration\Helper;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_OXYGEN_INTEGRATION_FILE', __FILE__ );
define( 'MASTERIYO_OXYGEN_INTEGRATION_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_OXYGEN_INTEGRATION_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_OXYGEN_INTEGRATION_SLUG', 'oxygen-integration' );

if ( ( new Addons() )->is_active( MASTERIYO_OXYGEN_INTEGRATION_SLUG ) && ! Helper::is_oxygen_active() ) {
	add_action(
		'masteriyo_admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Oxygen Integration addon requires Oxygen to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

// Bail early if Oxygen is not activated.
if ( ! Helper::is_oxygen_active() ) {
	add_filter(
		'masteriyo_pro_addon_' . MASTERIYO_OXYGEN_INTEGRATION_SLUG . '_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'Oxygen is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( MASTERIYO_OXYGEN_INTEGRATION_SLUG === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( Helper::is_oxygen_active() );
			}

			return $data;
		},
		10,
		2
	);

	return;
}

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_OXYGEN_INTEGRATION_SLUG ) ) {
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
		( new OxygenIntegrationAddon() )->init();
	}
);
