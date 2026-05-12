<?php
/**
 * Get Enrollment ability.
 *
 * @package Masteriyo\Abilities\Domains\Enrollment
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Enrollment;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course enrollment by ID.
 *
 * @since x.x.x
 */
class GetEnrollmentAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'users.courses';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'get';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users/courses';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/enrollment-get';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Get Enrollment', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a single course enrollment record by its ID. Requires id (enrollment ID). Returns the enrollment object including user_id, course_id, status, and date_start.', 'learning-management-system' );
	}
}
