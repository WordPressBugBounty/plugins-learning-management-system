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

defined( 'ABSPATH' ) || exit;


class InstructorCourseComponentStyles extends CourseComponentStyles {

	/**
	 * Style instructor course list components.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_prefix_selector(): string {
		return '.masteriyo-courses-page ';
	}

	/**
	 * Should print if instructor course page.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	protected function should_print(): bool {
		return masteriyo_is_courses_page();
	}

}

