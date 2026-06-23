<?php
/**
 * WooCommerce course product type enums.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\WcIntegration\Enums
 */

namespace Masteriyo\Addons\WcIntegration\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * WC product type slugs registered by the Masteriyo WC integration addon.
 *
 * @since x.x.x
 */
class WcCourseProductType {

	/**
	 * Standard Masteriyo course product type.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	const COURSE = 'mto_course';

	/**
	 * All Masteriyo WC product type slugs.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			self::COURSE,
		);
	}
}
