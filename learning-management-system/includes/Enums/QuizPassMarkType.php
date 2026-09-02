<?php
/**
 * Quiz pass mark type enums.
 *
 * @since 2.2.7
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Quiz pass mark type enum class.
 *
 * @since 2.2.7
 */
class QuizPassMarkType {
	/**
	 * Quiz pass mark point.
	 *
	 * @since 2.2.7
	 * @var string
	 */
	const POINT = 'point';

	/**
	 * Quiz pass mark percentage.
	 *
	 * @since 2.2.7
	 * @var string
	 */
	const PERCENTAGE = 'percentage';

	/**
	 * Return pass mark quiz types.
	 *
	 * @since 2.2.7
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters quiz pass mark types list.
			 *
			 * @since 2.2.7
			 *
			 * @param string[] $types Quiz pass mark list.
			 */
			apply_filters(
				'masteriyo_quiz_pass_mark_types',
				array(
					self::POINT,
					self::PERCENTAGE,
				)
			)
		);
	}
}
