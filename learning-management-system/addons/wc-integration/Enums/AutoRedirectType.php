<?php
/**
 * Where the WooCommerce integration sends a student after a completed order.
 *
 * @package Masteriyo\Addons\WcIntegration\Enums
 */

namespace Masteriyo\Addons\WcIntegration\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Values of the `auto_redirect.display_type` setting. `default`, `wp_pages` and
 * `url` match General > Pages > Post-Checkout Landing Page.
 */
class AutoRedirectType {

	/**
	 * No redirect: WooCommerce's own order-received page.
	 *
	 * @var string
	 */
	const NONE = 'default';

	/**
	 * The account page's Courses tab.
	 *
	 * @var string
	 */
	const COURSES = 'courses';

	/**
	 * A WordPress page, by id.
	 *
	 * @var string
	 */
	const WP_PAGE = 'wp_pages';

	/**
	 * A custom URL.
	 *
	 * @var string
	 */
	const URL = 'url';

	/**
	 * All redirect types.
	 *
	 * @return list<self::*>
	 */
	public static function all(): array {
		return array( self::NONE, self::COURSES, self::WP_PAGE, self::URL );
	}
}
