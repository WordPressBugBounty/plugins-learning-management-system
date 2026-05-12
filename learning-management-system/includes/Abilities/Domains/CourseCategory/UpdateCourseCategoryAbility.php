<?php
/**
 * Update Course Category ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseCategory
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseCategory;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing course category.
 *
 * Supports partial updates — name, slug, description, and parent can all be
 * changed independently.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class UpdateCourseCategoryAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/course-category-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Course Category', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing course category. Requires id (category ID). Optionally accepts name, slug, description, or parent. Returns the updated category object.', 'learning-management-system' );
	}
}
