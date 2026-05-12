<?php
/**
 * Get Course Difficulty ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseDifficulty
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseDifficulty;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course difficulty level by ID.
 *
 * Returns the term object including name, slug, description, and course count.
 *
 * @since x.x.x
 */
class GetCourseDifficultyAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/course-difficulty-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Difficulty', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course difficulty level by its ID. Requires id (difficulty ID). Returns the difficulty object including name, slug, description, and course count.', 'learning-management-system' );
	}
}
