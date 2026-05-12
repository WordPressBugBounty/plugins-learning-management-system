<?php
/**
 * Get Course Builder ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseBuilder
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseBuilder;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve the full course structure tree.
 *
 * Returns sections with their ordered children (lessons and quizzes) as a
 * nested structure suitable for rendering or modifying the course builder UI.
 *
 * @since x.x.x
 */
class GetCourseBuilderAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_builder.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'get';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function rest_base(): string {
		return 'coursebuilder';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-builder-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Builder', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve the full course structure tree: all sections with their lessons and quizzes in order.', 'learning-management-system' );
	}
}
