<?php
/**
 * Create Course Category ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseCategory
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseCategory;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new course category.
 *
 * Creates a taxonomy term under `course_cat`. A parent category ID may be
 * supplied to build a category hierarchy.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class CreateCourseCategoryAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_cat.rest';
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
		return 'courses/categories';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-category-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course Category', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new course category. Requires name. Optionally accepts slug, description, and parent (ID of a parent category for hierarchical nesting). Returns the created category with its ID and course count.', 'learning-management-system' );
	}
}
