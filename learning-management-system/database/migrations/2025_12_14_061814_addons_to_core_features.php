<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since  2.1.0
 */

use Masteriyo\Database\Migration;

class AddonsToCoreFeatures extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 2.1.0
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

		update_option( 'masteriyo_settings', $settings );
	}


	/**
	 * Reverse the migrations.
	 */
	public function down() {
	}
}
