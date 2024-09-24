<?php
/**
 * Group Courses Setting class.
 *
 * @package Masteriyo\Addons\GroupCourses\Models
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Group Courses Setting class.
 */
class Setting {
	/**
	 * Global option name.
	 *
	 * @since 1.9.0
	 */
	const OPTION_NAME = 'masteriyo_group_courses_settings';

	/**
	 * Data.
	 *
	 * @since 1.9.0
	 *
	 * @var array
	 */
	protected static $data = array(
		'max_members'                            => '',
		'deactivate_enrollment_on_member_change' => true,
		'deactivate_enrollment_on_status_change' => true,
	);

	/**
	 * Read the settings.
	 *
	 * @since 1.9.0
	 */
	protected static function read() {
		$settings   = get_option( self::OPTION_NAME, self::$data );
		self::$data = masteriyo_parse_args( $settings, self::$data );

		return self::$data;
	}

	/**
	 * Return all the settings.
	 *
	 * @since 1.9.0
	 *
	 * @return mixed
	 */
	public static function all() {
		return self::read();
	}

	/**
	 * Return global white field value.
	 *
	 * @since 1.9.0
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
	 * Set global group courses field.
	 *
	 * @since 1.9.0
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
	 * @since 1.9.0
	 *
	 * @param array $args
	 */
	public static function set_props( $args ) {
		self::$data = masteriyo_parse_args( $args, self::$data );
	}

	/**
	 * Save the settings.
	 *
	 * @since 1.9.0
	 */
	public static function save() {
		update_option( self::OPTION_NAME, self::$data );
	}
}
