<?php
/**
 * Store global Mollie options.
 *
 * @since 1.16.0
 * @package \Masteriyo\Addons\Mollie
 */

namespace Masteriyo\Addons\Mollie;

defined( 'ABSPATH' ) || exit;


class Setting {

	/**
	 * Global option name.
	 *
	 * @since 1.16.0
	 */
	const OPTION_NAME = 'masteriyo_mollie_settings';

	/**
	 * Data.
	 *
	 * @since 1.16.0
	 *
	 * @var array
	 */
	protected static $data = array(
		'enable'         => false,
		'title'          => 'Mollie',
		'sandbox'        => true,
		'description'    => 'Pay with Mollie',
		'test_api_key'   => '',
		'live_api_key'   => '',
		'webhook_secret' => '',
		'error_message'  => '',
	);

	/**
	 * Read the settings.
	 *
	 * @since 1.16.0
	 */
	protected static function read() {
		$settings   = get_option( self::OPTION_NAME, self::$data );
		self::$data = masteriyo_parse_args( $settings, self::$data );

		return self::$data;
	}

	/**
	 * Return all the settings.
	 *
	 * @since 1.16.0
	 *
	 * @return mixed
	 */
	public static function all() {
		return self::read();
	}

	/**
	 * Return global Razorpay field value.
	 *
	 * @since 1.16.0
	 *
	 * @param string $key
	 *
	 * @return string|array
	 */
	public static function get( $key ) {
		self::read();

		return masteriyo_array_get( self::$data, $key, null );
	}

	/**
	 * Set global Razorpay field.
	 *
	 * @since 1.16.0
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
	 * @since 1.16.0
	 *
	 * @param array $args
	 */
	public static function set_props( $args ) {
		self::$data = masteriyo_parse_args( $args, self::$data );
	}

	/**
	 * Save the settings.
	 *
	 * @since 1.16.0
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
	 * @since 1.16.0
	 *
	 * @return boolean
	 */
	public static function is_enable() {
		return masteriyo_string_to_bool( self::get( 'enable' ) );
	}

	/**
	 * Get webhook_secret.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public static function get_webhook_secret() {
		return self::get( 'webhook_secret' );
	}

	/**
	 * Get the API key.
	 *
	 * @since 1.16.0
	 *
	 * @return string The API key.
	 */
	public static function get_api_key() {
		return masteriyo_string_to_bool( self::get( 'api_key' ) );
	}
}
