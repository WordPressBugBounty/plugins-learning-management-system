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

use Masteriyo\AddonsFramework\Addons;

define( 'MASTERIYO_H5P_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_H5P_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_H5P_ADDON_DIR', __DIR__ );
define( 'MASTERIYO_H5P_ADDON_SLUG', 'h5p' );

$masteriyo_h5p_plugin_active = in_array( 'h5p/h5p.php', get_option( 'active_plugins', array() ), true ) || masteriyo_check_plugin_active_in_network( 'h5p/h5p.php' );

if ( ( new Addons() )->is_active( MASTERIYO_H5P_ADDON_SLUG ) && ! $masteriyo_h5p_plugin_active ) {
	add_action(
		'masteriyo_admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( masteriyo_get_plugin_name() . ':' ),
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
			$result = __( 'The H5P plugin must be installed and activated before this addon can be enabled.', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) use ( $masteriyo_h5p_plugin_active ) {
			if ( 'h5p' === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( $masteriyo_h5p_plugin_active );

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data['required_plugin'] = array(
					'name'   => 'H5P',
					'file'   => 'h5p/h5p.php',
					'slug'   => 'h5p',
					'status' => isset( get_plugins()['h5p/h5p.php'] ) ? 'inactive' : 'not-installed',
				);
			}

			return $data;
		},
		10,
		2
	);
}

// Bail early if the addon is not active or H5P plugin is not active.
if ( ! ( ( new Addons() )->is_active( MASTERIYO_H5P_ADDON_SLUG ) && $masteriyo_h5p_plugin_active ) ) {
	return;
}

/**
 * Fires when the H5P addon loads, past its activation checks.
 *
 * Anything that ships only with pro joins the addon here rather than being
 * required from this file, which is present in both products. Firing it from
 * beyond the bail above means the pro half inherits both of the addon's
 * conditions — the addon is active, and the H5P plugin is active — instead of
 * having to restate them. It fires before the container reads
 * `masteriyo_service_providers`, so a provider added from here is still in time.
 */
do_action( 'masteriyo_h5p_addon_loaded' );

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
