<?php
/**
 * Masteriyo setting class.
 *
 * @package Masteriyo\Scorm
 *
 * @since 1.14.0
 */

namespace Masteriyo\Addons\Scorm;

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
	const OPTION_NAME = 'masteriyo_scorm_settings';

	/**
	 * Setting data.
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	protected static $data = array(
		'allowed_extensions' => '',
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
	 * Return scorm setting data.
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
	 * Set scorm data.
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
	| Getters functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get allowed extensions.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_allowed_extensions() {
		return self::get( 'allowed_extensions' );
	}

}
