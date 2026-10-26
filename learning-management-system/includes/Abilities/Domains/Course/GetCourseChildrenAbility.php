<?php
/**
 * Get Course Children ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: list a course's sections in their display order. A section's
 * lessons and quizzes come from masteriyo/section-children-get.
 */
class GetCourseChildrenAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_children.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'list';
	}

	/**
	 * {@inheritdoc}
	 * The course comes from the `course` query param, not a path segment.
	 */
	protected function rest_base(): string {
		return 'courses/children';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-children-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Children', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'List the sections of a course in display order. Use masteriyo/section-children-get for the lessons and quizzes in a section.';
	}
}
