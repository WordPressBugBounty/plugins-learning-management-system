<?php
/**
 * Style archive course list components.
 *
 * @since 1.11.3
 *
 * @package Masteriyo
 */

namespace Masteriyo\CourseComponentStyles;

use Masteriyo\Abstracts\CourseComponentStyles;

defined( 'ABSPATH' ) || exit;

class ArchiveCourseComponentStyles extends CourseComponentStyles {

	/**
	 * Style course list components.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_prefix_selector(): string {
		return '.masteriyo-course-list-display-section';
	}

	/**
	 * Should print if course archive page.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	protected function should_print(): bool {
		return masteriyo_is_courses_page( true );
	}
}

