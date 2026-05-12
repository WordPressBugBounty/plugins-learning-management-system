<?php
/**
 * Create Course Tag ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseTag
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseTag;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new course tag.
 *
 * Creates a taxonomy term under `course_tag`.
 * Tags are flat (no hierarchy), unlike categories.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class CreateCourseTagAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_tag.rest';
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
		return 'courses/tags';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-tag-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course Tag', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new course tag. Tags are flat (no hierarchy). Requires name. Optionally accepts slug and description. Returns the created tag object with its ID.', 'learning-management-system' );
	}
}
