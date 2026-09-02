<?php
/**
 * Course flow type enums.
 *
 * @since 2.4.1
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Course flow type enum class.
 *
 * @since 2.4.1
 */
class CourseFlow {
	/**
	 * Course flow sequential type.
	 *
	 * @since 2.4.1
	 * @var string
	 */
	const SEQUENTIAL = 'sequential';

	/**
	 * Course flow pending type.
	 *
	 * @since 2.4.1
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
	 * @since 2.4.1
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Course flow status list.
			 *
			 * @since 2.4.1
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
