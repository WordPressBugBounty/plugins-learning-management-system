<?php
/**
 * WooCommerce course product type enums.
 *
 * @package Masteriyo\Addons\WcIntegration\Enums
 */

namespace Masteriyo\Addons\WcIntegration\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * WC product type slugs registered by the Masteriyo WC integration addon.
 */
class WcCourseProductType {

	/**
	 * Standard Masteriyo course product type.
	 *
	 * @var string
	 */
	const COURSE = 'mto_course';

	/**
	 * Recurring (subscription) Masteriyo course product type.
	 *
	 * @var string
	 */
	const COURSE_RECURRING = 'mto_course_recurring';

	/**
	 * Masteriyo course bundle product type.
	 *
	 * @var string
	 */
	const COURSE_BUNDLE = 'mto_course_bundle';

	/**
	 * Recurring (subscription) Masteriyo course bundle product type.
	 *
	 * @var string
	 */
	const COURSE_BUNDLE_RECURRING = 'mto_course_bundle_recurring';

	/**
	 * All Masteriyo WC product type slugs.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			self::COURSE,
			self::COURSE_RECURRING,
			self::COURSE_BUNDLE,
			self::COURSE_BUNDLE_RECURRING,
		);
	}
}
