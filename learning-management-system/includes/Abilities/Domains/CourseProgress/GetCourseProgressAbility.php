<?php
/**
 * Get Course Progress ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseProgress
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseProgress;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a course progress record by ID.
 *
 * @since x.x.x
 */
class GetCourseProgressAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'course-progress.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'get';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'course-progress';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/progress-get';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Get Course Progress', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a course progress record by its ID. Requires id (progress ID). Returns the progress object including user_id, course_id, status (started, progress, completed), and completion percentage.', 'learning-management-system' );
	}
}
