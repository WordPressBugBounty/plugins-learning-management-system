<?php
/**
 * Masteriyo Course Coming Soon helper class.
 *
 * @package Masteriyo\CourseComingSoon
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\CourseComingSoon;

use DateTime;
use Masteriyo\Constants;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Models\UserCourse;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\CourseQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Helper CourseComingSoon class.
 *
 * @class Masteriyo\Addons\CourseComingSoon\Helper
 */

class Helper {
	/**
	 * Return false if the courses course coming soon is time is more than current time.
	 *
	 * @since 1.11.0
	 *
	 * @param int|\Masteriyo\Models\Course|WP_Post $course Course object.
	 * @param int|\Masteriyo\Models\User|WP_user $user User object.
	 *
	 * @return boolean
	 */
	public static function course_coming_soon_satisfied( $course, $user = null ) {

		$course_meta = get_post_meta( $course->get_id() );

		$ending_date = isset( $course_meta['_course_coming_soon_ending_date'] ) ? $course_meta['_course_coming_soon_ending_date'] : null;

		if ( ! empty( $ending_date ) ) {
			$ending_date = end( $ending_date );
		} else {
			$ending_date = null;
		}

		$ending_date      = $ending_date ? new DateTime( $ending_date ) : null;
		$ending_timestamp = $ending_date ? $ending_date->getTimestamp() : null;

		$current_time_utc = current_time( 'mysql', 1 );
		$now              = strtotime( $current_time_utc );

		if ( $ending_timestamp < strtotime( $current_time_utc ) ) {
			return true;
		}

		return false;

	}
}
