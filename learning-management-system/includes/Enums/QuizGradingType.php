<?php
/**
 * Quiz grading type.
 *
 * @since 2.5.20
 *
 * @package Masteriyo
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;


class QuizGradingType {
	/**
	 * Last attempt.
	 *
	 * @since 2.5.20
	 */
	const LAST_ATTEMPT = 'last-attempt';

	/**
	 * First attempt.
	 * @since 2.5.20
	 * @since 2.5.20
	 */
	const FIRST_ATTEMPT = 'first-attempt';

	/**
	 * Average.
	 *
	 * @since 2.5.20
	 */
	const AVERAGE = 'average';

	/**
	 * Highest.
	 *
	 * @since 2.5.20
	 */
	const HIGHEST = 'highest';

	/**
	 * Return all quiz grading types.
	 *
	 * @since 2.5.20
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters quiz grading types list.
			 *
			 * @since 2.5.20
			 *
			 * @param string[] $types Quiz grading types.
			 */
			apply_filters(
				'masteriyo_quiz_grading_types',
				array(
					self::FIRST_ATTEMPT,
					self::LAST_ATTEMPT,
					self::AVERAGE,
					self::HIGHEST,
				)
			)
		);
	}
}
