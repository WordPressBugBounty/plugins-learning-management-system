<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: H5P Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Integrate interactive H5P content seamlessly into your courses.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Requires: H5P
 * Plan: Free
 * Category: Course Features
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_H5P_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_H5P_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_H5P_ADDON_DIR', __DIR__ );
define( 'MASTERIYO_H5P_ADDON_SLUG', 'h5p' );

$masteriyo_h5p_plugin_active = in_array( 'h5p/h5p.php', get_option( 'active_plugins', array() ), true );

if ( ( new Addons() )->is_active( MASTERIYO_H5P_ADDON_SLUG ) && ! $masteriyo_h5p_plugin_active ) {
	add_action(
		'masteriyo_admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( __( 'H5P Integration addon requires the H5P plugin to be installed and activated.', 'learning-management-system' ) ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

if ( ! $masteriyo_h5p_plugin_active ) {
	add_filter(
		'masteriyo_pro_addon_h5p_activation_requirements',
		function( $result, $request, $controller ) {
			$result = __( 'H5P plugin is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( 'h5p' === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( in_array( 'h5p/h5p.php', get_option( 'active_plugins', array() ), true ) );
			}

			return $data;
		},
		10,
		2
	);
}

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_H5P_ADDON_SLUG ) ) {
	return;
}

/**
 * Include service providers for the H5P addon.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize the H5P addon.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.h5p' )->init();
	}
);
