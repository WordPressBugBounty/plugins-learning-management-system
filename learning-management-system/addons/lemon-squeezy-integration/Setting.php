<?php
/**
 * Masteriyo Lemon Squeezy integration setting class.
 *
 * @since 1.9.3
 * @package Masteriyo\Addons\LemonSqueezyIntegration
 */

namespace Masteriyo\Addons\LemonSqueezyIntegration;

use Masteriyo\Enums\OrderStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo Lemon Squeezy integration setting class.
 *
 * @class Masteriyo\Addons\LemonSqueezyIntegration\Setting
 */

class Setting {
	/**
	 * Setting option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'masteriyo_lemon_squeezy_integration_setting';


	/**
	 * Setting data.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	protected static $data = array(
		'unenrollment_status' => array( OrderStatus::REFUNDED ),
		'api_key'             => '',
		'store_id'            => '',
		'webhook_secret'      => '',
		'enable'              => false,
		'title'               => 'Lemon Squeezy',
		'description'         => 'Pay with Lemon Squeezy.',
	);

		/**
	 * Read the settings.
	 *
	 * @since 1.9.3
	 */
	public static function read() {
		$settings   = get_option( self::OPTION_NAME, self::$data );
		self::$data = masteriyo_parse_args( $settings, self::$data );

		return self::$data;
	}

	/**
	 * Return all the settings.
	 *
	 * @since 1.9.3
	 *
	 * @return mixed
	 */
	public static function all() {
		return self::read();
	}

	/**
	 * Return global white field value.
	 *
	 * @since 1.9.3
	 *
	 * @param string $key
	 * @return string|array
	 */
	public static function get( $key ) {
		self::read();

		return masteriyo_array_get( self::$data, $key, null );
	}

	/**
	 * Set field.
	 *
	 * @since 1.9.3
	 *
	 * @param string $key Setting key.
	 * @param mixed $value Setting value.
	 */
	public static function set( $key, $value ) {
		masteriyo_array_set( self::$data, $key, $value );
		self::save();
	}

	/**
	 * Set multiple settings.
	 *
	 * @since 1.9.3
	 *
	 * @param array $args
	 */
	public static function set_props( $args ) {
		self::$data = masteriyo_parse_args( $args, self::$data );
	}

	/**
	 * Save the settings.
	 *
	 * @since 1.9.3
	 */
	public static function save() {
		update_option( self::OPTION_NAME, self::$data );
	}


	/*
	|--------------------------------------------------------------------------
	| Conditional functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return true if the Lemon Squeezy Integration is enabled.
	 *
	 * @since 1.9.3
	 *
	 * @return boolean
	 */
	public static function is_enable() {
		return masteriyo_string_to_bool( self::get( 'enable' ) );
	}

	/**
	 * Get webhook_secret.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	public static function get_webhook_secret() {
		return self::get( 'webhook_secret' );
	}

	/**
	 * Get the API key.
	 *
	 * @since 1.9.3
	 *
	 * @return string The API key.
	 */
	public static function get_api_key() {
		return masteriyo_string_to_bool( self::get( 'api_key' ) );
	}
}
