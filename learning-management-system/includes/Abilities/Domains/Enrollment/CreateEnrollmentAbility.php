<?php
/**
 * Create Enrollment ability.
 *
 * @package Masteriyo\Abilities\Domains\Enrollment
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Enrollment;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: enroll a student in a course.
 *
 * @since x.x.x
 */
class CreateEnrollmentAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'users.courses';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'create';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users/courses';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/enrollment-create';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Create Enrollment', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Enroll a student in a course. Requires course_id and user_id. Optionally accepts status (active, inactive). Returns the created enrollment object with its ID.', 'learning-management-system' );
	}
}
