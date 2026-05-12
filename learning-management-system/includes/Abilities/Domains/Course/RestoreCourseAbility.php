<?php
/**
 * Restore Course ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: restore a trashed course.
 *
 * Returns the course to its previous published or draft status.
 * Only applies to courses currently in the trash (post_status = 'trash').
 * Requires the `edit_masteriyo_courses` capability on the target course.
 *
 * @since x.x.x
 */
class RestoreCourseAbility extends RestProxyAbility {

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
		return 'restore';
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
		return 'masteriyo/course-restore';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Restore Course', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Restore a trashed course back to its previous published or draft state.', 'learning-management-system' );
	}
}
