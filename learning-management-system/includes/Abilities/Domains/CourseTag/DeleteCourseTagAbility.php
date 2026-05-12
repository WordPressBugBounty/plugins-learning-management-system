<?php
/**
 * Delete Course Tag ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseTag
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseTag;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: permanently delete a course tag.
 *
 * Taxonomy terms have no trash state — this operation is always permanent.
 * Courses previously assigned this tag will have that assignment removed.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class DeleteCourseTagAbility extends RestProxyAbility {

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
		return 'delete';
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
		return 'masteriyo/course-tag-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Course Tag', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Permanently delete a course tag. Requires id (tag ID). Deletion is immediate and irreversible. Returns the deleted tag object.', 'learning-management-system' );
	}
}
