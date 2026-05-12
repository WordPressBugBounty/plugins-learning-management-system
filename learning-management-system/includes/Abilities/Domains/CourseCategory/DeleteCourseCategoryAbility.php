<?php
/**
 * Delete Course Category ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseCategory
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseCategory;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: permanently delete a course category.
 *
 * Taxonomy terms have no trash state — this operation is always permanent.
 * Courses previously assigned to the deleted category will have that assignment removed.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class DeleteCourseCategoryAbility extends RestProxyAbility {

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
		return 'delete';
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
		return 'masteriyo/course-category-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Course Category', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Permanently delete a course category. Requires id (category ID). Taxonomy terms have no trash state — deletion is immediate and irreversible. Returns the deleted category object.', 'learning-management-system' );
	}
}
