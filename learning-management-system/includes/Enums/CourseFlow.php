<?php
/**
 * Course flow type enums.
 *
 * @since 1.15.0
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Course flow type enum class.
 *
 * @since 1.15.0
 */
class CourseFlow {
	/**
	 * Course flow sequential type.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const SEQUENTIAL = 'sequential';

	/**
	 * Course flow pending type.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const FREE_FLOW = 'free-flow';

	/**
	 * Course flow date type.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const DATE = 'date';

	/**
	 * Course flow days type.
	 *
	 * @since 2.5.5
	 * @var string
	 */
	const DAYS = 'days';


	/**
	 * Return all Course flow types.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Course flow status list.
			 *
			 * @since 1.15.0
			 *
			 * @param string[] $types Course flow types.
			 */
			apply_filters(
				'masteriyo_course_flow_types',
				array(
					self::SEQUENTIAL,
					self::FREE_FLOW,
					self::DATE,
					self::DAYS,
				)
			)
		);
	}
}
