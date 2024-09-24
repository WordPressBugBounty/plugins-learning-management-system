<?php
/**
 * Addon Name: Bricks Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Equip your Bricks editor with Masteriyo elements. Add components like course lists and categories to any page/post.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Requires: Brick
 * Plan: Free
 */

use Masteriyo\Addons\BricksIntegration\BricksIntegrationAddon;
use Masteriyo\Addons\BricksIntegration\Helper;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_BRICKS_INTEGRATION_FILE', __FILE__ );
define( 'MASTERIYO_BRICKS_INTEGRATION_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_BRICKS_INTEGRATION_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_BRICKS_INTEGRATION_SLUG', 'bricks-integration' );

// to check if the bricks build is activated or not
if ( ( new Addons() )->is_active( MASTERIYO_BRICKS_INTEGRATION_SLUG ) && ! Helper::is_bricks_active() ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Bricks Integration addon requires Bricks Builder Theme to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

// Bail early if Bricks is not activated.
if ( ! Helper::is_bricks_active() ) {
	add_filter(
		'masteriyo_pro_addon_' . MASTERIYO_BRICKS_INTEGRATION_SLUG . '_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'Bricks is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( MASTERIYO_BRICKS_INTEGRATION_SLUG === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( Helper::is_bricks_active() );
			}

			return $data;
		},
		10,
		2
	);

	return;
}

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_BRICKS_INTEGRATION_SLUG ) ) {
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
		( new BricksIntegrationAddon() )->init();
	}
);
