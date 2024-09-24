<?php
/**
 * Addon Name: SureCart Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Addon Type: feature
 * Description: SureCart Integration allows to enroll users using SureCart checkout process and payment methods.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Version: 1.12.0
 * Requires: SureCart
 * Plan: Free
 */

use Masteriyo\Pro\Addons;
use Masteriyo\Addons\SureCartIntegration\SureCartIntegrationAddon;

define( 'MASTERIYO_SURECART_INTEGRATION_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_SURECART_INTEGRATION_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_SURECART_INTEGRATION_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_SURECART_INTEGRATION_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'MASTERIYO_SURECART_INTEGRATION_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_SURECART_INTEGRATION_ADDON_SLUG', 'sure-cart-integration' );

require_once __DIR__ . '/helper/sure-cart.php';



if ( ( new Addons() )->is_active( MASTERIYO_SURECART_INTEGRATION_ADDON_SLUG && ! is_sure_cart_active() ) ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'SureCart Integration addon requires SureCart to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}



// // Bail early if SureCart is not activated.
if ( ! is_sure_cart_active() ) {
	add_filter(
		'masteriyo_pro_addon_sure-cart-integration_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'SureCart is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( 'sure-cart-integration' === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( is_sure_cart_active() );
			}

			return $data;
		},
		10,
		2
	);
}


// // Bail early if the addon is not active.
if ( ! ( ( new Addons() )->is_active( MASTERIYO_SURECART_INTEGRATION_ADDON_SLUG ) && is_sure_cart_active() ) ) {
	return;
}

// Initialize SureCart integration addon.
SureCartIntegrationAddon::instance()->init();
