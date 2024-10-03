<?php
/**
 * IntegrationSettings class.
 *
 * @package Masteriyo\EmailMarketingAndCRM
 *
 * @since 1.13.3
 */
namespace Masteriyo\EmailMarketingAndCRM;

/**
 * IntegrationSettings class.
 *
 * @since 1.13.3
 */
abstract class IntegrationSettings {

	/**
	 * Settings data.
	 *
	 * @since 1.13.3
	 *
	 * @var array
	 */
	protected static $data = array();

	/**
	 * Get the option name for the settings.
	 *
	 * @since 1.13.3
	 *
	 * @return string
	 */
	abstract protected static function get_option_name();

	/**
	 * Read the settings from the database.
	 *
	 * @since 1.13.3
	 *
	 * @return array The settings.
	 */
	protected static function read() {
		$settings     = get_option( static::get_option_name(), static::$data );
		static::$data = masteriyo_parse_args( $settings, static::$data );

		return static::$data;
	}

	/**
	 * Return all the settings.
	 *
	 * @since 1.13.3
	 *
	 * @param array $exclude_keys The keys to exclude from the returned settings.
	 *
	 * @return array The filtered settings.
	 */
	public static function all( $exclude_keys = array() ) {
		$data = static::read();

		if ( empty( $exclude_keys ) ) {
			return $data;
		}

		foreach ( $exclude_keys as $key ) {
			unset( $data[ $key ] );
		}

		return $data;
	}

	/**
	 * Return a specific setting value.
	 *
	 * @since 1.13.3
	 *
	 * @param string $key The key to retrieve.
	 *
	 * @return mixed The setting value.
	 */
	public static function get( $key ) {
		static::read();

		return masteriyo_array_get( static::$data, $key, null );

	}

	/**
	 * Set a specific setting value.
	 *
	 * @since 1.13.3
	 *
	 * @param string $key The key to set.
	 * @param mixed $value The value to set.
	 */
	public static function set( $key, $value ) {
		masteriyo_array_set( static::$data, $key, $value );

		static::save();
	}

	/**
	 * Set multiple settings.
	 *
	 * @since 1.13.3
	 *
	 * @param array $args The settings to set.
	 */
	public static function set_props( $args ) {
		static::$data = masteriyo_parse_args( $args, static::$data );
		static::save();
	}

	/**
	 * Save the settings to the database.
	 *
	 * @since 1.13.3
	 */
	public static function save() {
		update_option( static::get_option_name(), static::$data );
	}
}
