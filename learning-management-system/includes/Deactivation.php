<?php
/**
 * Deactivation class.
 *
 * @since 1.0.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


class Deactivation {

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		register_deactivation_hook( Constants::get( 'MASTERIYO_PLUGIN_FILE' ), array( __CLASS__, 'on_deactivate' ) );
	}

	/**
	 * Callback for plugin deactivation hook.
	 *
	 * @since 1.0.0
	 */
	public static function on_deactivate() {
		self::remove_roles();

		/**
		 * Fire after masteriyo is deactivated.
		 *
		 * @since 1.5.37
		 */
		do_action( 'masteriyo_deactivation' );
	}

	/**
	 * Remove roles — unless the sibling Free/Pro plugin is still active.
	 *
	 * Roles are shared between Free and Pro; removing them while the sibling is
	 * active breaks user registration. Full cleanup lives in uninstall.php.
	 *
	 * @since 1.0.0
	 * @since x.x.x Skip removal while the sibling plugin is active.
	 */
	public static function remove_roles() {
		if ( self::is_sibling_plugin_active() ) {
			return;
		}

		foreach ( Roles::get_all() as $role_slug => $role ) {
			remove_role( $role_slug );
		}
	}

	/**
	 * Whether the sibling Masteriyo plugin (Free <-> Pro) is still active.
	 *
	 * WordPress rewrites `active_plugins` only after the deactivation hook runs,
	 * so the sibling is still listed here.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	private static function is_sibling_plugin_active() {
		$active   = (array) get_option( 'active_plugins', array() );
		$current  = plugin_basename( Constants::get( 'MASTERIYO_PLUGIN_FILE' ) );
		$siblings = array(
			'learning-management-system/lms.php',
			'learning-management-system-pro/lms.php',
		);

		foreach ( $siblings as $sibling ) {
			if ( $sibling !== $current && in_array( $sibling, $active, true ) ) {
				return true;
			}
		}

		return false;
	}
}
