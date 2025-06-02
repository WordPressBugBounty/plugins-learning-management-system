<?php
/**
 * Password strength enums.
 *
 * @since 2.3.0
 * @package Masteriyo\Addons\PasswordStrength
 */

namespace Masteriyo\Addons\PasswordStrength\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Password strength enum class.
 *
 * @since 2.3.0
 */
class PasswordStrength {
	/**
	 * Very low.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const VERY_LOW = 'very_low';

	/**
	 * Low.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const LOW = 'low';

	/**
	 * Medium
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const MEDIUM = 'medium';

	/**
	 * High.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const HIGH = 'high';

	/**
	 * Return all the Password strength.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Password strength list.
			 *
			 * @since 2.3.0
			 *
			 * @param string[] $statuses Password strength list.
			 */
			apply_filters(
				'masteriyo_password_strength_enums',
				array(
					self::VERY_LOW,
					self::LOW,
					self::MEDIUM,
					self::HIGH,
				)
			)
		);
	}
}
