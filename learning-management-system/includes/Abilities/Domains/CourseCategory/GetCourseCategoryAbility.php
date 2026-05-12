<?php
/**
 * Get Course Category ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseCategory
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseCategory;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course category by ID.
 *
 * Returns the term object including name, slug, description, and course count.
 *
 * @since x.x.x
 */
class GetCourseCategoryAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/course-category-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Category', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course category by its ID. Requires id (category ID). Returns the category object including name, slug, description, parent ID, and course count.', 'learning-management-system' );
	}
}
