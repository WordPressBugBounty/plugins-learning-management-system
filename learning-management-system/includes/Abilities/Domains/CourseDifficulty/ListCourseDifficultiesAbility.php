<?php
/**
 * List Course Difficulties ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseDifficulty
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseDifficulty;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of course difficulty levels.
 *
 * Returns taxonomy terms registered under `course_difficulty`, including
 * name, slug, description, and course count.
 *
 * @since x.x.x
 */
class ListCourseDifficultiesAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/course-difficulty-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Course Difficulties', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course difficulty levels. Optionally filter by search term. Returns data array with name, slug, description, and course count per difficulty, plus pagination metadata.', 'learning-management-system' );
	}
}
