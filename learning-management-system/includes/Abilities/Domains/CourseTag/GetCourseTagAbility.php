<?php
/**
 * Get Course Tag ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseTag
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseTag;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course tag by ID.
 *
 * Returns the term object including name, slug, description, and course count.
 *
 * @since x.x.x
 */
class GetCourseTagAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/course-tag-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Tag', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course tag by its ID. Requires id (tag ID). Returns the tag object including name, slug, description, and course count.', 'learning-management-system' );
	}
}
