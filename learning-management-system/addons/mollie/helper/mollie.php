<?php

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\Mollie\Setting;

if ( ! function_exists( 'masteriyo_mollie_get_api_key' ) ) {
	/**
	 * Get the Mollie publishable and api keys based on the sandbox mode.
	 *
	 * If the sandbox mode is enabled, it returns the test publishable and api keys.
	 * Otherwise, it returns the live publishable and api keys.
	 *
	 * @since 1.16.0
	 *
	 * @return array An array containing the publishable and api keys.
	 */
	function masteriyo_mollie_get_api_key() {
		if ( masteriyo_mollie_test_mode_enabled() ) {
			return Setting::get( 'test_api_key' );
		}

		return Setting::get( 'live_api_key' );
	}
}

if ( ! function_exists( 'masteriyo_mollie_test_mode_enabled' ) ) {
	/**
	 * Checks if Mollie test mode is enabled.
	 *
	 * @since 1.16.0
	 *
	 * @return bool True if test mode is enabled, false otherwise.
	 */
	function masteriyo_mollie_test_mode_enabled() {
		return masteriyo_string_to_bool( Setting::get( 'sandbox' ) );
	}
}
