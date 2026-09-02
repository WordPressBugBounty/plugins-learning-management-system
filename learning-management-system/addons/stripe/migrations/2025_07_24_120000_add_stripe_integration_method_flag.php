<?php

defined( 'ABSPATH' ) || exit;

/**
 * Add stripe integration method flag.
 *
 * @since 2.30.0
 */

use Masteriyo\Addons\Stripe\Setting;
use Masteriyo\Database\Migration;

class AddStripeIntegrationMethodFlag extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 2.30.0
	 */
	public function up() {
		// The connect flow already wrote the flag as a string; nothing to derive.
		if ( false !== get_option( '_masteriyo_stripe_integration_method', false ) ) {
			return;
		}

		$settings = get_option( Setting::OPTION_NAME, array() );

		$keys              = masteriyo_array_only(
			is_array( $settings ) ? $settings : array(),
			array(
				'test_publishable_key',
				'test_secret_key',
				'live_publishable_key',
				'live_secret_key',
			)
		);
		$has_existing_keys = false;

		foreach ( $keys as $key ) {
			if ( ! empty( $key ) ) {
				$has_existing_keys = true;
				break;
			}
		}

		if ( $has_existing_keys ) {
			update_option( '_masteriyo_stripe_integration_method', 'manual' );
		} else {
			update_option( '_masteriyo_stripe_integration_method', 'connect' );
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 2.30.0
	 */
	public function down() {
		delete_option( '_masteriyo_stripe_integration_method' );
	}
}
