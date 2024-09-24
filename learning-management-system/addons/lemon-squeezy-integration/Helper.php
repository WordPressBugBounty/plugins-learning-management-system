<?php
/**
 * Lemon Squeezy Integration helper functions.
 *
 * @since 1.9.3
 * @package Masteriyo\Addons\LemonSqueezyIntegration
 */

namespace Masteriyo\Addons\LemonSqueezyIntegration;

class Helper {
	/**
	 * Return webhook endpoint url.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	public static function get_webhook_endpoint_url() {
		return add_query_arg(
			array(
				'action' => 'masteriyo_lemon_squeezy_webhook',
			),
			admin_url( 'admin-ajax.php' )
		);
	}
}
