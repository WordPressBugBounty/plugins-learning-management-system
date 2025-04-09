<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since  1.16.0
 */

use Masteriyo\Database\Migration;

class OpenAi extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.16.0
	 */
	public function up() {
		$settings         = get_option( 'masteriyo_settings' );
		$open_api_key     = masteriyo_get_setting( 'advance.openai.api_key' );
		$is_openai_enable = masteriyo_get_setting( 'advance.openai.enable' );

		if ( ! ( $is_openai_enable ) && ! empty( $open_api_key ) ) {
			masteriyo_array_set( $settings, 'advance.openai.enable', true );
			update_option( 'masteriyo_settings', $settings );
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down() {
	}
}
