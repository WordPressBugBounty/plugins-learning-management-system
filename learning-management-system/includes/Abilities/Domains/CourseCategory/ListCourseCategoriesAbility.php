<?php
/**
 * List Course Categories ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseCategory
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseCategory;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of course categories.
 *
 * Returns taxonomy terms registered under `course_cat`, including name,
 * slug, description, and course count.
 *
 * @since x.x.x
 */
class ListCourseCategoriesAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/course-category-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Course Categories', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course categories. Optionally filter by search term or parent ID. Returns data array with name, slug, description, parent, and course count per category, plus pagination metadata.', 'learning-management-system' );
	}
}
