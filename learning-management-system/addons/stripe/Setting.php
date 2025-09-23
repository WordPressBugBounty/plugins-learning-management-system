<?php
/**
 * Masteriyo setting class.
 *
 * @package Masteriyo\Stripe
 *
 * @since 1.14.0
 */

namespace Masteriyo\Addons\Stripe;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo Setting class.
 *
 * @class Masteriyo\Setting
 */

class Setting {
	/**
	 * Setting option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'masteriyo_stripe_settings';

	/**
	 * Setting data.
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	protected static $data = array(
		'enable'               => false,
		'enable_ideal'         => false,
		'title'                => 'Stripe (Credit Card)',
		'sandbox'              => true,
		'description'          => 'Pay with Stripe',
		'test_publishable_key' => '',
		'test_secret_key'      => '',
		'live_publishable_key' => '',
		'live_secret_key'      => '',
		'webhook_secret'       => '',
		'stripe_user_id'       => '',
		'use_platform'         => false,
	);

	/**
	 * Read the settings.
	 *
	 * @since 1.14.0
	 */
	public static function read() {
		$settings   = get_option( self::OPTION_NAME, self::$data );
		self::$data = masteriyo_parse_args( $settings, self::$data );

		return self::$data;
	}

	/**
	 * Return all the settings.
	 *
	 * @since 1.14.0
	 *
	 * @return mixed
	 */
	public static function all() {
		return self::read();
	}

	/**
	 * Return global white field value.
	 *
	 * @since 1.14.0
	 *
	 * @param string $key
	 * @return string|array
	 */
	public static function get( $key ) {
		self::read();

		return masteriyo_array_get( self::$data, $key, null );
	}

	/**
	 * Set global social share field.
	 *
	 * @since 1.14.0
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
	 * @since 1.14.0
	 *
	 * @param array $args
	 */
	public static function set_props( $args ) {
		self::$data = masteriyo_parse_args( $args, self::$data );
	}

	/**
	 * Save the settings.
	 *
	 * @since 1.14.0
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
	 * Return true if the sandbox is enabled.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function is_sandbox_enable() {
		return masteriyo_string_to_bool( self::get( 'sandbox' ) );
	}

	/**
	 * Return true if the stripe is enabled
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function is_enable() {
		return masteriyo_string_to_bool( self::get( 'enable' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return publishable key based on sandbox.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_publishable_key() {
		return self::is_sandbox_enable() ? self::get( 'test_publishable_key' ) : self::get( 'live_publishable_key' );
	}

	/**
	 * Return secret key based on sandbox.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		return self::is_sandbox_enable() ? self::get( 'test_secret_key' ) : self::get( 'live_secret_key' );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get stripe enable.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function get_enable() {
		return masteriyo_string_to_bool( self::get( 'enable' ) );
	}

	/**
	 * Get stripe title.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_title() {
		return self::get( 'title' );
	}

	/**
	 * Get stripe sandbox.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function get_sandbox() {
		return masteriyo_string_to_bool( self::get( 'sandbox' ) );
	}

	/**
	 * Get stripe description.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_description() {
		return self::get( 'description' );
	}

	/**
	 * Get stripe test_publishable_key.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_test_publishable_key() {
		return self::get( 'test_publishable_key' );
	}

	/**
	 * Get stripe test_secret_key.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_test_secret_key() {
		return self::get( 'test_secret_key' );
	}

	/**
	 * Get stripe live_publishable_key.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_live_publishable_key() {
		return self::get( 'live_publishable_key' );
	}

	/**
	 * Get stripe live_secret_key.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_live_secret_key() {
		return self::get( 'live_secret_key' );
	}

	/**
	 * Get stripe webhook_secret.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_webhook_secret() {
		return self::get( 'webhook_secret' );
	}

	/**
	 * Get stripe user id.
	 *
	 * @return string
	 */
	public static function get_stripe_user_id() {
		return self::get( 'stripe_user_id' );
	}
}
