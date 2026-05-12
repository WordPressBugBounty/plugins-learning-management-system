<?php
/**
 * Update Course Tag ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseTag
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseTag;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing course tag.
 *
 * Supports partial updates — name, slug, and description can be changed
 * independently.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class UpdateCourseTagAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/course-tag-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Course Tag', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing course tag. Requires id (tag ID). Optionally accepts name, slug, or description. Returns the updated tag object.', 'learning-management-system' );
	}
}
