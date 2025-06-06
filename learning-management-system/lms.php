<?php
/**
 * Plugin Name: Masteriyo LMS
 * Plugin URI: https://masteriyo.com/wordpress-lms/
 * Description: A Complete WordPress LMS plugin to create and sell online courses in no time.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Version: 1.18.1
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Text Domain: learning-management-system
 * Domain Path: /i18n/languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WordPress Available:  yes
 * Requires License:    no
 */

use Masteriyo\Masteriyo;
use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * @since 1.4.4 Auto deactivation of free plugin.
 */
if ( in_array( 'learning-management-system-pro/lms.php', get_option( 'active_plugins', array() ), true ) ) {
	add_action(
		'admin_init',
		function() {
			deactivate_plugins( 'learning-management-system/lms.php', true );

			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		},
		0
	);

	return;
}

if ( ! defined( 'MASTERIYO_SLUG' ) ) {
	define( 'MASTERIYO_SLUG', 'learning-management-system' );
}

if ( ! defined( 'MASTERIYO_VERSION' ) ) {
	define( 'MASTERIYO_VERSION', '1.18.1' );
}

if ( ! defined( 'MASTERIYO_PLUGIN_FILE' ) ) {
	define( 'MASTERIYO_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MASTERIYO_PLUGIN_BASENAME' ) ) {
	define( 'MASTERIYO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'MASTERIYO_PLUGIN_DIR' ) ) {
	define( 'MASTERIYO_PLUGIN_DIR', __DIR__ );
}

if ( ! defined( 'MASTERIYO_ASSETS' ) ) {
	define( 'MASTERIYO_ASSETS', __DIR__ . '/assets' );
}

if ( ! defined( 'MASTERIYO_TEMPLATES' ) ) {
	define( 'MASTERIYO_TEMPLATES', __DIR__ . '/templates' );
}

if ( ! defined( 'MASTERIYO_LANGUAGES' ) ) {
	define( 'MASTERIYO_LANGUAGES', __DIR__ . '/i18n/languages' );
}

if ( ! defined( 'MASTERIYO_PRO_ADDONS_DIR' ) ) {
	define( 'MASTERIYO_PRO_ADDONS_DIR', __DIR__ . '/addons' );
}

if ( ! defined( 'MASTERIYO_LOG_DIR' ) ) {
	define( 'MASTERIYO_LOG_DIR', wp_upload_dir()['basedir'] . '/masteriyo/masteriyo-logs/' );
}

if ( ! defined( 'MASTERIYO_LOG_URL' ) ) {
	define( 'MASTERIYO_LOG_URL', wp_upload_dir()['baseurl'] . '/masteriyo/masteriyo-logs/' );
}

if ( ! defined( 'MASTERIYO_UPLOAD_DIR' ) ) {
	define( 'MASTERIYO_UPLOAD_DIR', 'masteriyo' );
}



/**
 * Include the autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Include action scheduler.
 *
 * @since 1.5.35
 */
require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

// Check whether assets are built or not.
if ( masteriyo_is_production() && ! file_exists( __DIR__ . '/assets/js/build/masteriyo-backend.js' ) ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-error is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Assets are need to be built. Run <code>yarn && yarn build</code> from the wp-content/plugins/learning-management-system directory.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);

	add_action(
		'admin_init',
		function() {
			deactivate_plugins( plugin_basename( MASTERIYO_PLUGIN_FILE ) );

			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		},
		0
	);

	return;
}


// Check for the existence of autoloader file.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="notice notice-error is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
				esc_html( 'Masteriyo:' ),
				wp_kses_post( 'Requires autoloader files to work properly. Run <code>composer update</code> from the wp-content/plugins/learning-management-system directory.', 'learning-management-system' ),
				esc_html__( 'Dismiss this notice.', 'learning-management-system' )
			);
		}
	);

	add_action(
		'admin_init',
		function() {
			deactivate_plugins( plugin_basename( MASTERIYO_PLUGIN_FILE ) );

			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		}
	);

	return;
}

if ( ! function_exists( 'masteriyo' ) ) {

	// Load all addons.
	( new Addons() )->load_all();

	/**
	 * Bootstrap the application.
	 */
	$GLOBALS['masteriyo'] = require_once __DIR__ . '/bootstrap/app.php';

	/**
	 * Return the service container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Class name or alias.
	 * @return Masteriyo\Masteriyo
	 */
	function masteriyo( $class = 'app' ) {
		global $masteriyo;

		return empty( $class ) ? $masteriyo : $masteriyo->get( $class );
	}

	// Initialize pro module.
	$GLOBALS['masteriyo']->get( 'pro' )->init();

	// Initialize the application.
	$GLOBALS['masteriyo']->get( 'app' );

	/**
	 * ThemeIsle SDK customizations
	 * Disable promotions and dashboard widgets
	 */
	add_filter( 'themeisle_sdk_ran_promos', '__return_true' );
	add_filter( 'themeisle_sdk_hide_dashboard_widget', '__return_true' );

	/**
	 * Register Masteriyo LMS with ThemeIsle SDK
	 */
	add_filter(
		'themeisle_sdk_products',
		function ( $products ) {
			$products[] = MASTERIYO_PLUGIN_FILE;
			return $products;
		},
		10,
		1
	);
}
