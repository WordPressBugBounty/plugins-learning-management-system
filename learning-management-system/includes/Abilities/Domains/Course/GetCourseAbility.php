<?php
/**
 * Get Course ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course by ID.
 *
 * Returns the full course object including metadata, pricing, access mode,
 * category and difficulty assignments, and review statistics.
 *
 * @since x.x.x
 */
class GetCourseAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course.rest';
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
		return 'courses';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course by ID, including all its metadata and settings.', 'learning-management-system' );
	}
}
