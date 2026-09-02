<?php
/**
 * Shared plugin bootstrap.
 *
 * Both product entry points — the free `lms.php` and the pro `lms-pro.php` —
 * reduce to a plugin header, an activation guard, the product constants and a
 * require of this file. Every piece of real bootstrap logic lives here, once,
 * so the two entry points cannot drift into two different bootstraps.
 *
 * The entry point is expected to have already defined MASTERIYO_IS_PRO,
 * MASTERIYO_VERSION and MASTERIYO_PLUGIN_FILE.
 *
 * @package Masteriyo
 */

defined( 'ABSPATH' ) || exit;

define( 'MASTERIYO_SLUG', 'learning-management-system' );
define( 'MASTERIYO_PLUGIN_BASENAME', plugin_basename( MASTERIYO_PLUGIN_FILE ) );
define( 'MASTERIYO_PLUGIN_DIR', dirname( MASTERIYO_PLUGIN_FILE ) );
define( 'MASTERIYO_ASSETS', MASTERIYO_PLUGIN_DIR . '/assets' );
define( 'MASTERIYO_TEMPLATES', MASTERIYO_PLUGIN_DIR . '/templates' );
define( 'MASTERIYO_LANGUAGES', MASTERIYO_PLUGIN_DIR . '/i18n/languages' );
define( 'MASTERIYO_PRO_ADDONS_DIR', MASTERIYO_PLUGIN_DIR . '/addons' );
define( 'MASTERIYO_CORE_FEATURES_DIR', MASTERIYO_PLUGIN_DIR . '/includes/core-features' );
define( 'MASTERIYO_LOG_DIR', wp_upload_dir()['basedir'] . '/masteriyo/masteriyo-logs/' );
define( 'MASTERIYO_LOG_URL', wp_upload_dir()['baseurl'] . '/masteriyo/masteriyo-logs/' );
define( 'MASTERIYO_UPLOAD_DIR', 'masteriyo' );

if ( ! function_exists( 'masteriyo_bootstrap_failure' ) ) {
	/**
	 * Bail with an admin notice, deactivating the plugin.
	 *
	 * @param string $message Notice body.
	 */
	function masteriyo_bootstrap_failure( $message ) {
		add_action(
			'admin_notices',
			function() use ( $message ) {
				printf(
					'<div class="notice notice-error is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
					esc_html( 'Masteriyo:' ),
					wp_kses_post( $message ),
					esc_html__( 'Dismiss this notice.', 'learning-management-system' )
				);
			}
		);

		add_action(
			'admin_init',
			function() {
				deactivate_plugins( plugin_basename( MASTERIYO_PLUGIN_FILE ) );

				if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			},
			0
		);
	}
}

// Check for the existence of the autoloader before requiring it.
if ( ! file_exists( MASTERIYO_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	masteriyo_bootstrap_failure(
		sprintf(
			/* translators: %s: plugin directory name */
			__( 'Requires autoloader files to work properly. Run <code>composer update</code> from the wp-content/plugins/%s directory.', 'learning-management-system' ),
			basename( MASTERIYO_PLUGIN_DIR )
		)
	);

	return;
}

/**
 * Include the autoloader.
 */
require_once MASTERIYO_PLUGIN_DIR . '/vendor/autoload.php';

/**
 * Include action scheduler.
 *
 * @since 1.5.35
 */
require_once MASTERIYO_PLUGIN_DIR . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

// Check whether assets are built or not.
if ( masteriyo_is_production() && ! file_exists( MASTERIYO_ASSETS . '/js/build/masteriyo-backend.js' ) ) {
	masteriyo_bootstrap_failure(
		sprintf(
			/* translators: %s: plugin directory name */
			__( 'Assets are need to be built. Run <code>yarn && yarn build</code> from the wp-content/plugins/%s directory.', 'learning-management-system' ),
			basename( MASTERIYO_PLUGIN_DIR )
		)
	);

	return;
}

if ( ! function_exists( 'masteriyo' ) ) {

	/**
	 * Pro bootstrap.
	 *
	 * The free build excludes every pro path, so this file is absent there by
	 * construction. It is the single point at which the shared bootstrap hands
	 * over to pro; everything pro adds beyond it is registered through hooks or
	 * through the service container.
	 */
	if ( MASTERIYO_IS_PRO && file_exists( MASTERIYO_PLUGIN_DIR . '/pro/bootstrap.php' ) ) {
		require_once MASTERIYO_PLUGIN_DIR . '/pro/bootstrap.php';
	}

	// Load all addons.
	( new \Masteriyo\AddonsFramework\Addons() )->load_all();

	// Load free core-features.
	( new \Masteriyo\CoreFeatures( 'free' ) )->load_all();

	/**
	 * Fires once the free core-features have loaded, before the container boots.
	 *
	 * Pro loads its own core-features here so that they are always applied on top
	 * of the free ones, whatever order the bootstrap files were required in.
	 */
	do_action( 'masteriyo_core_features_loaded' );

	/**
	 * Bootstrap the application.
	 */
	$GLOBALS['masteriyo'] = require_once MASTERIYO_PLUGIN_DIR . '/bootstrap/app.php';

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

	// Initialize the addons integration.
	$GLOBALS['masteriyo']->get( 'addons.integration' )->init();

	/**
	 * Fires once the container is built and the shared services are initialised.
	 *
	 * This is where pro initialises its own module. The shared bootstrap cannot
	 * name a pro service, and pro's own bootstrap runs before the container
	 * exists, so this action is the join between the two.
	 */
	do_action( 'masteriyo_container_booted' );

	// Initialize the application.
	$GLOBALS['masteriyo']->get( 'app' );

	/**
	 * ThemeIsle SDK customizations.
	 *
	 * The SDK derives its filter prefix from the plugin folder name, so deriving
	 * it the same way here keeps both products correct without branching.
	 */
	add_filter( 'themeisle_sdk_ran_promos', '__return_true' );
	add_filter( 'themeisle_sdk_hide_dashboard_widget', '__return_true' );
	add_filter( basename( MASTERIYO_PLUGIN_DIR ) . '_sdk_should_review', '__return_false' );

	/**
	 * Register Masteriyo LMS with ThemeIsle SDK.
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
