<?php
/**
 * Shortcodes initializer.
 *
 * @since 1.0.0
 * @class Shortcodes
 * @package Masteriyo\Shortcodes
 */

namespace Masteriyo\Shortcodes;

use Masteriyo\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes initializer.
 */
class Shortcodes {
	use Singleton;

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		foreach ( $this->get_shortcodes() as $shortcode ) {
			masteriyo( $shortcode )->register();
		}
	}

	/**
	 * Get shortcodes list.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_shortcodes() {
		$namespace = '\\Masteriyo\\Shortcodes';

		/**
		 * Filters shortcode classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $classes The shortcode classes.
		 */
		return apply_filters(
			'masteriyo_shortcodes',
			array(
				'account'                 => "{$namespace}\\AccountShortcode",
				'checkout'                => "{$namespace}\\CheckoutShortcode",
				'cart'                    => "{$namespace}\\CartShortcode",
				'courses'                 => "{$namespace}\\CoursesShortcode",
				'course_categories'       => "{$namespace}\\CourseCategoriesShortcode",
				'instructor-registration' => "{$namespace}\\InstructorRegistrationShortcode",
				'featured-courses'        => "{$namespace}\\FeaturedCoursesShortcode",
			)
		);
	}
}
