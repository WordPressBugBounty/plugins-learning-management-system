<?php

defined( 'ABSPATH' ) || exit;

/**
 * Add stripe integration method flag.
 *
 * @since 1.20.0
 */

use Masteriyo\Database\Migration;

class AddStripeIntegrationMethodFlag extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.20.0
	 */
	public function up() {
		$keys              = masteriyo_array_only(
			get_option( '_masteriyo_stripe_integration_method', array() ),
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
	 * @since 1.20.0
	 */
	public function down() {
		delete_option( '_masteriyo_stripe_integration_method' );
	}
}
