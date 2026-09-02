<?php
/**
 * Plugin Name: Masteriyo LMS
 * Plugin URI: https://masteriyo.com/wordpress-lms/
 * Description: A Complete WordPress LMS plugin to create and sell online courses in no time.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Version: 3.4.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Text Domain: learning-management-system
 * Domain Path: /i18n/languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WordPress Available: yes
 * Requires License: no
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activation guard — order independent.
 *
 * The free entry stays inert whenever a pro build of Masteriyo is active, no
 * matter which plugin WordPress happens to load first:
 *
 * 1. A Masteriyo bootstrap has already run in this request — pro loaded first.
 *    In the source checkout, where both entry files live in this folder and
 *    either may be activated, this is also what keeps free inert when both
 *    are active: lms-pro.php sorts before lms.php, so pro always boots first.
 * 2. A pro entry file is active in another folder and has not loaded yet — pro
 *    loads later in this request and would otherwise define nothing in time.
 *
 * Pro applies rule 1 only, so it never has to wait for free and never
 * requires free to be deactivated.
 */
if ( defined( 'MASTERIYO_PLUGIN_FILE' ) ) {
	return;
}

$masteriyo_pro_is_active = call_user_func(
	function() {
		$plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		foreach ( $plugins as $plugin ) {
			if ( plugin_basename( __FILE__ ) === $plugin || 'lms.php' !== basename( $plugin ) ) {
				continue;
			}

			// Only the pro build ships a pro directory, whatever its folder is named.
			if ( is_dir( WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/pro' ) ) {
				return true;
			}
		}

		return false;
	}
);

if ( $masteriyo_pro_is_active ) {
	unset( $masteriyo_pro_is_active );
	return;
}

unset( $masteriyo_pro_is_active );

define( 'MASTERIYO_IS_PRO', false );
define( 'MASTERIYO_VERSION', '3.4.0' );
define( 'MASTERIYO_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/bootstrap/plugin.php';
