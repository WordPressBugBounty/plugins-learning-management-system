<?php
/**
 * Update Enrollment ability.
 *
 * @package Masteriyo\Abilities\Domains\Enrollment
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Enrollment;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update the status of a course enrollment.
 *
 * @since x.x.x
 */
class UpdateEnrollmentAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'users.courses';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'update';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users/courses';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/enrollment-update';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Enrollment', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Update an existing course enrollment. Requires id (enrollment ID). Optionally accepts status (active, inactive). Returns the updated enrollment object.', 'learning-management-system' );
	}
}
