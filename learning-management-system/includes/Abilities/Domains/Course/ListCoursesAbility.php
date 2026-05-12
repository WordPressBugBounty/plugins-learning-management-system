<?php
/**
 * List Courses ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of courses.
 *
 * Supports filtering by status, category, instructor, difficulty, and search term.
 * Returns data + pagination envelope via ResponseEnvelope.
 *
 * @since x.x.x
 */
class ListCoursesAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'list';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function rest_base(): string {
		return 'courses';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Courses', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of courses. Supports filtering by status, category, instructor, and search term.', 'learning-management-system' );
	}
}
