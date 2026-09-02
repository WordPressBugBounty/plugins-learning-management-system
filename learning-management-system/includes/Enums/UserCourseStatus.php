<?php
/**
 * User Course status enums.
 *
 * @since 2.2.0
 * @since 1.5.3
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * User course status enum class.
 *
 * @since 2.2.0
 * @since 1.5.3
 */
class UserCourseStatus {
	/**
	 * User course any status.
	 *
	 * @since 2.2.0
	 * @since 1.5.3
	 * @var string
	 */
	const ANY = 'any';

	/**
	 * User course enrolled status.
	 *
	 * @since 2.2.0
	 * @since 1.5.3
	 * @var string
	 */
	const ENROLLED = 'active';

	/**
	 * User course active status.
	 *
	 * @since 2.2.0
	 * @since 1.5.3
	 * @var string
	 */
	const ACTIVE = 'active';

	/**
	 * User course inactive status.
	 *
	 * @since 2.2.0
	 * @since 1.5.3
	 * @var string
	 */
	const INACTIVE = 'inactive';

	/**
	 * Return user course statuses.
	 *
	 * @since 2.2.0
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public static function all() {
		/**
		 * Filters statuses for user course.
		 *
		 * @since 1.0.0
		 *
		 * @param array $statuses The statuses for user course.
		 */
		$statuses = apply_filters(
			'masteriyo_user_course_statuses',
			array(
				self::ANY,
				self::ENROLLED,
				self::ACTIVE,
				self::INACTIVE,
			)
		);

		return array_unique( $statuses );
	}
}
