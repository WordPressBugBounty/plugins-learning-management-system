<?php
/**
 * Create Course ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new course.
 *
 * Accepts title, description, pricing, access mode, category and difficulty
 * assignments, and any other writable course fields.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CreateCourseAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/course-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new course with title, description, pricing, access mode, and category assignments.', 'learning-management-system' );
	}
}
