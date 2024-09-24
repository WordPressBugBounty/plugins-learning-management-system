<?php
/**
 * Google Meet Meeting types.
 *
 * @since 1.11.0
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Addons\GoogleMeet\Enums;

use Masteriyo\Enums\PostStatus;

defined( 'ABSPATH' ) || exit;

/**
 * GoogleMeet Meeting Status enum class.
 *
 * @since 1.11.0
 */
class GoogleMeetStatus extends PostStatus {
	/**
	 * GoogleMeet all Meeting type.
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const ALL = 'all';

	/**
	 * GoogleMeet scheduled Meeting type.
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const UPCOMING = 'upcoming';

	/**
	 * GoogleMeet Expired Meeting type.
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const EXPIRED = 'expired';

	/**
	 * GoogleMeet Active Meeting.
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const ACTIVE = 'active';

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
			 * Filters Google Meet Status list.
			 *
			 * @since 1.11.0
			 *
			 * @param string[] $status GoogleMeet Meeting status.
			 */

			apply_filters(
				'masteriyo_google_meet_status',
				array_merge(
					parent::all(),
					array(
						self::ALL,
						self::UPCOMING,
						self::ACTIVE,
						self::EXPIRED,
					)
				)
			)
		);
	}
}
