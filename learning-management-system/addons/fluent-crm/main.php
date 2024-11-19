<?php
/**
 * Addon Name: Fluent CRM Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Addon Type: integration
 * Description: The Fluent CRM Integration Addon for Masteriyo LMS syncs student data, automates email campaigns, and enhances CRM functionality for better engagement and streamlined communication with learners.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Version: 1.14.0
 * Requires: Fluent CRM
 * Plan: Free
 */

use Masteriyo\Pro\Addons;
use Masteriyo\Addons\FluentCRM\Helper;
use Masteriyo\Addons\FluentCRM\FluentCrmAddon;


define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_DIR', dirname( __FILE__ ) );
// define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_ASSETS', dirname( __FILE__ ) . '/assets' );
// define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_SLUG', 'fluent-crm' );

if ( ( new Addons() )->is_active( MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_SLUG ) && ! Helper::is_fluent_crm_active() ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Fluent CRM Integration addon requires FluentCRM to be installed and activated.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);
}

// Bail early if fluent CRM is not activated.
if ( ! Helper::is_fluent_crm_active() ) {
	add_filter(
		'masteriyo_pro_addon_fluent-crm_integration_activation_requirements',
		function ( $result, $request, $controller ) {
			$result = __( 'FluentCRM is to be installed and activated for this addon to work properly', 'learning-management-system' );
			return $result;
		},
		10,
		3
	);

	add_filter(
		'masteriyo_pro_addon_data',
		function( $data, $slug ) {
			if ( 'fluent-crm' === $slug ) {
				$data['requirement_fulfilled'] = masteriyo_bool_to_string( Helper::is_fluent_crm_active() );
			}

			return $data;
		},
		10,
		2
	);
}


// Bail early if the addon is not active.
if ( ! ( ( new Addons() )->is_active( MASTERIYO_FLUENT_CRM_INTEGRATION_ADDON_SLUG ) && Helper::is_fluent_crm_active() ) ) {
	return;
}

// Initialize fluent CRM addon.
FluentCrmAddon::instance()->init();
