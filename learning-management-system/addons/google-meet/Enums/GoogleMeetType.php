<?php
/**
 * GoogleMeet Meeting types.
 *
 * @since 1.11.0
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Addons\GoogleMeet\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * GoogleMeet Meeting type enum class.
 *
 * @since 1.11.0
 */
class GoogleMeetType {
	/**
	 * GoogleMeet instant meeting type.
	 *
	 * @since 1.11.0
	 * @var integer
	 */
	const INSTANT = '1';

	/**
	 * GoogleMeet scheduled meeting type.
	 *
	 * @since 1.11.0
	 * @var integer
	 */
	const SCHEDULED = '2';

	/**
	 * GoogleMeet recurring not fixed meeting type.
	 *
	 * @since 1.11.0
	 * @var integer
	 */
	const NOT_FIXED_RECURRING = '3';

	/**
	 * GoogleMeet recurring fixed meeting type.
	 *
	 * @since 1.11.0
	 * @var integer
	 */
	const FIXED_RECURRING = '8';

	/**
	 * Return all GoogleMeet Meeting types.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters GoogleMeet Meeting type list.
			 *
			 * @since 1.11.0
			 *
			 * @param integer[] $types GoogleMeet Meeting types.
			 */

			apply_filters(
				'masteriyo_google_meet_types',
				array(
					self::INSTANT,
					self::SCHEDULED,
					self::NOT_FIXED_RECURRING,
					self::FIXED_RECURRING,
				)
			)
		);
	}
}
