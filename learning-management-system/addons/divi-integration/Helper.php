<?php
/**
 * Divi Integration helper functions.
 *
 * @package Masteriyo\Addons\DiviIntegration
 *
 * @since 1.6.13
 */

namespace Masteriyo\Addons\DiviIntegration;

defined( 'ABSPATH' ) || exit;


/**
 * Divi Integration helper functions.
 *
 * @package Masteriyo\Addons\DiviIntegration
 *
 * @since 1.6.13
 */
class Helper {

	/**
	 * Return if Divi is active.
	 *
	 * @since 1.6.13
	 *
	 * @return boolean
	 */
	public static function is_divi_active() {
		$theme = wp_get_theme();
		return 'Divi' === $theme->name || 'Divi' === $theme->parent_theme || in_array( 'divi-builder/divi-builder.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Check if the current page is Divi builder.
	 *
	 * @since 1.6.13
	 *
	 * @return boolean
	 */
	public static function is_divi_builder() {
		return isset( $_GET['et_fb'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
