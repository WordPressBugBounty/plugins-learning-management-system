<?php
/**
 * Style course list components.
 *
 * @since 1.11.3
 *
 * @package Masteriyo
 */

namespace Masteriyo\CourseComponentStyles;

use Masteriyo\Abstracts\CourseComponentStyles;

class CategoryCourseComponentStyles extends CourseComponentStyles {

	/**
	 * Style category course list components.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_prefix_selector(): string {
		return '.masteriyo-course-category-page';
	}

	/**
	 * Should print if category course page.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	protected function should_print(): bool {
		return is_tax( 'course_cat' );
	}

}

