<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since  3.1.0
 */

use Masteriyo\Database\Migration;

class AddonsToCoreFeatures extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 3.1.0
	 */
	public function up() {
		$settings = get_option( 'masteriyo_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		$addons = get_option( 'masteriyo_active_addons', array() );
		$addons = is_array( $addons ) ? $addons : array();

		// Password Strength
		if ( isset( $addons['password-strength'] ) ) {
			if ( ! isset( $settings['advance'] ) || ! is_array( $settings['advance'] ) ) {
				$settings['advance'] = array();
			}
			if ( ! isset( $settings['advance']['password_strength'] ) || ! is_array( $settings['advance']['password_strength'] ) ) {
				$settings['advance']['password_strength'] = array();
			}

			$settings['advance']['password_strength']['enable'] = true;

			$password_settings           = get_option( 'masteriyo_password_strength_settings', array() );
			$password_settings           = is_array( $password_settings ) ? $password_settings : array();
			$password_settings['enable'] = true;

			update_option( 'masteriyo_password_strength_settings', $password_settings );
		}

		// Two Factor Authentication
		if ( isset( $addons['two-factor-authentication'] ) ) {
			if ( ! isset( $settings['authentication'] ) || ! is_array( $settings['authentication'] ) ) {
				$settings['authentication'] = array();
			}
			if ( ! isset( $settings['authentication']['two_factor_authentication'] ) || ! is_array( $settings['authentication']['two_factor_authentication'] ) ) {
				$settings['authentication']['two_factor_authentication'] = array();
			}

			$settings['authentication']['two_factor_authentication']['enable'] = true;

			$two_factor_settings           = get_option( 'masteriyo_two_factor_authentication_settings', array() );
			$two_factor_settings           = is_array( $two_factor_settings ) ? $two_factor_settings : array();
			$two_factor_settings['enable'] = true;

			update_option( 'masteriyo_two_factor_authentication_settings', $two_factor_settings );
		}

		// Social Share
		if ( isset( $addons['social-share'] ) ) {
			if ( ! isset( $settings['single_course'] ) || ! is_array( $settings['single_course'] ) ) {
				$settings['single_course'] = array();
			}
			if ( ! isset( $settings['single_course']['social_share'] ) || ! is_array( $settings['single_course']['social_share'] ) ) {
				$settings['single_course']['social_share'] = array();
			}

			$settings['single_course']['social_share']['enable'] = true;

			$social_share_settings           = get_option( 'masteriyo_social_share_settings', array() );
			$social_share_settings           = is_array( $social_share_settings ) ? $social_share_settings : array();
			$social_share_settings['enable'] = true;

			update_option( 'masteriyo_social_share_settings', $social_share_settings );
		}

		update_option( 'masteriyo_settings', $settings );
	}


	/**
	 * Reverse the migrations.
	 */
	public function down() {
	}
}
