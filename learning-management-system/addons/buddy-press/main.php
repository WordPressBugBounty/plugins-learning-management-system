<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: BuddyPress Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Integrate Masteriyo LMS with BuddyPress to sync course progress, activities, and updates, creating an engaging and collaborative learning community.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Requires: BuddyPress
 * Plan: Free
 * Category: Social Engagement
 */

use Masteriyo\Addons\BuddyPress\Helper;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_BUDDY_PRESS_FILE', __FILE__ );
define( 'MASTERIYO_BUDDY_PRESS_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_BUDDY_PRESS_DIR', __DIR__ );
define( 'MASTERIYO_BUDDY_PRESS_SLUG', 'buddy-press' );


if ( ( new Addons() )->is_active( MASTERIYO_BUDDY_PRESS_SLUG ) && ! Helper::is_bp_active() ) {
	add_action(
		'masteriyo_admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'BuddyPress Integration addon requires BuddyPress plugin to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

// Bail early if Elementor is not activated.
if ( ! Helper::is_bp_active() ) {
	add_filter(
		'masteriyo_pro_addon_' . MASTERIYO_BUDDY_PRESS_SLUG . '_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'BuddyPress plugin needs to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( MASTERIYO_BUDDY_PRESS_SLUG === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( Helper::is_bp_active() );
			}

			return $data;
		},
		10,
		2
	);

	return;
}

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_BUDDY_PRESS_SLUG ) ) {
	return;
}

/**
 * Include service providers for BuddyPress Integration
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo BuddyPress Integration.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.buddy-press' )->init();
	}
);
