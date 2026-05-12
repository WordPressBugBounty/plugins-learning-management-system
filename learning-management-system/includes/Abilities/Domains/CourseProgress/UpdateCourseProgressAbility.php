<?php
/**
 * Update Course Progress ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseProgress
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseProgress;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update a student's course progress record.
 *
 * @since x.x.x
 */
class UpdateCourseProgressAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'course-progress.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'update';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'course-progress';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/progress-update';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Course Progress', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Update a student\'s course progress record. Requires id (progress ID). Accepts status (started, progress, completed). Returns the updated progress object.', 'learning-management-system' );
	}
}
