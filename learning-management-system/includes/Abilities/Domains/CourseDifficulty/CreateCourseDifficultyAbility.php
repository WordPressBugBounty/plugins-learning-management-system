<?php
/**
 * Create Course Difficulty ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseDifficulty
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseDifficulty;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new course difficulty level.
 *
 * Creates a taxonomy term under `course_difficulty`.
 * Common values are Beginner, Intermediate, and Expert, but custom levels are supported.
 * Requires the `manage_masteriyo_settings` capability.
 *
 * @since x.x.x
 */
class CreateCourseDifficultyAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/course-difficulty-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course Difficulty', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new course difficulty level (e.g. Beginner, Intermediate, Advanced). Requires name. Optionally accepts slug and description. Returns the created difficulty object with its ID.', 'learning-management-system' );
	}
}
