<?php
/**
 * Update Course Difficulty ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseDifficulty
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseDifficulty;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing course difficulty level.
 *
 * Supports partial updates — name, slug, and description can be changed
 * independently.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class UpdateCourseDifficultyAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/course-difficulty-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Course Difficulty', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing course difficulty level. Requires id (difficulty ID). Optionally accepts name, slug, or description. Returns the updated difficulty object.', 'learning-management-system' );
	}
}
