<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addon Name: Migration Tool
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Effortlessly migrate data from various LMS platforms to Masteriyo with the Masteriyo Migration Tool.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Plan: Free
 * Category: Tools
 */

use Masteriyo\Addons\MigrationTool\Controllers\MigrationNoticeController;
use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\AddonsFramework\Addons;

define( 'MASTERIYO_MIGRATION_TOOL_FILE', __FILE__ );
define( 'MASTERIYO_MIGRATION_TOOL_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_MIGRATION_TOOL_DIR', __DIR__ );
define( 'MASTERIYO_MIGRATION_TOOL_SLUG', 'migration-tool' );


/**
 * Switch the addon on when the site still runs a competing LMS.
 *
 * Registered above the guard below, because an inactive addon is exactly the one
 * that has to detect. It runs on `admin_init`, where an authenticated user
 * exists to authorise the change; the addon therefore registers its providers on
 * the next admin page load, and the notice appears with it.
 * See docs/adr/0001-auto-activate-migration-tool.md.
 */
add_action( 'admin_init', array( Helper::class, 'maybe_auto_activate' ) );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_MIGRATION_TOOL_SLUG ) ) {
	return;
}

add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.migration-tool' )->init();
	}
);

/**
 * Tell the user we enabled the Migration Tool for them.
 *
 * On `masteriyo_admin_notices`, not `admin_notices`: Masteriyo screens clear
 * every `admin_notices` callback and fire this action instead.
 */
add_action(
	'masteriyo_admin_notices',
	function() {
		/**
		 * Inside Masteriyo the backend app renders its own MigrationNotice.tsx, so a
		 * plain WordPress notice would sit above the app chrome looking foreign. This
		 * is the split core already uses for the review notice — see
		 * Masteriyo::add_review_notice().
		 */
		if ( masteriyo_is_admin_page() || ! Helper::should_display_activation_notice() ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible masteriyo-migration-notice"><p><strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a></p></div>',
			esc_html( masteriyo_get_plugin_name() . ':' ),
			esc_html( Helper::notice_message() ),
			esc_url( admin_url( Helper::NOTICE_CTA_PATH ) ),
			esc_html__( 'Start migration', 'learning-management-system' )
		);

		// WordPress injects the dismiss button itself for `is-dismissible`, so the
		// listener is delegated rather than bound to an element rendered above.
		printf(
			'<script>document.addEventListener("click",function(e){if(!e.target.closest(".masteriyo-migration-notice .notice-dismiss")){return}fetch(%s,{method:"POST",credentials:"same-origin",headers:{"X-WP-Nonce":%s}}).catch(function(){})});</script>',
			wp_json_encode( rest_url( MigrationNoticeController::dismiss_path() ) ),
			wp_json_encode( wp_create_nonce( 'wp_rest' ) )
		);
	}
);

/**
 * Feed the in-app notice.
 *
 * Contributed through the filter rather than added to ScriptStyle directly, so
 * core carries no reference to this addon.
 */
add_filter(
	'masteriyo_localized_admin_scripts',
	function( $scripts ) {
		if ( ! isset( $scripts['backend']['data'] ) ) {
			return $scripts;
		}

		$show      = Helper::should_display_activation_notice();
		$migrators = $show ? Helper::notice_source_lms() : array();

		$scripts['backend']['data']['migrationNotice'] = array(
			'show'  => masteriyo_bool_to_string( $show ),
			'lms'   => Helper::notice_source_labels( $migrators ),
			// The React string has to make its verb agree the same way the PHP one does.
			'count' => count( $migrators ),
			'url'   => admin_url( Helper::NOTICE_CTA_PATH ),
		);

		return $scripts;
	}
);
