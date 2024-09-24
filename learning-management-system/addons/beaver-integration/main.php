<?php
/**
 * Addon Name: Beaver Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Equip your Beaver builder with Masteriyo elements. Add components like course lists and categories to any page/post.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Requires: Beaver
 * Plan: Free
 */

use Masteriyo\Addons\BeaverIntegration\BeaverIntegrationAddon;
use Masteriyo\Addons\BeaverIntegration\Helper;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_BEAVER_INTEGRATION_FILE', __FILE__ );
define( 'MASTERIYO_BEAVER_INTEGRATION_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_BEAVER_INTEGRATION_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_BEAVER_INTEGRATION_SLUG', 'beaver-integration' );


if ( ( new Addons() )->is_active( MASTERIYO_BEAVER_INTEGRATION_SLUG ) && ! Helper::is_beaver_active() ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Beaver Integration addon requires Beaver Plugin to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

// Bail early if Beaver is not activated.
if ( ! Helper::is_beaver_active() ) {
	add_filter(
		'masteriyo_pro_addon_' . MASTERIYO_BEAVER_INTEGRATION_SLUG . '_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'Beaver is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( MASTERIYO_BEAVER_INTEGRATION_SLUG === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( Helper::is_beaver_active() );
			}

			return $data;
		},
		10,
		2
	);

	return;
}


/**
 * Initialize Masteriyo Beaver Builder Integration.
 *
 * @since 1.10.0
 */
	add_action(
		'masteriyo_before_init',
		function() {
			( new BeaverIntegrationAddon() )->init();
		}
	);
