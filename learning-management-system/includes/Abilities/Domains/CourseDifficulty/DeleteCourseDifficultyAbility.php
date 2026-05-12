<?php
/**
 * Delete Course Difficulty ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseDifficulty
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseDifficulty;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: permanently delete a course difficulty level.
 *
 * Taxonomy terms have no trash state — this operation is always permanent.
 * Courses previously assigned this difficulty will have that assignment removed.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class DeleteCourseDifficultyAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_difficulty.rest';
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
		return 'courses/difficulties';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-difficulty-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Course Difficulty', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Permanently delete a course difficulty level. Requires id (difficulty ID). Deletion is immediate and irreversible. Returns the deleted difficulty object.', 'learning-management-system' );
	}
}
