<?php
/**
 * Delete Enrollment ability.
 *
 * @package Masteriyo\Abilities\Domains\Enrollment
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Enrollment;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: unenroll a student from a course.
 *
 * @since x.x.x
 */
class DeleteEnrollmentAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'users.courses';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'delete';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users/courses';
	}

	/**
	 * {@inheritdoc}
	 * Hard-delete (force=true) is intentionally removed from the AI-callable schema.
	 * Permanent enrollment deletion removes the access record with no recovery path.
	 * Soft-delete (trash) is AI-callable; hard-delete is not.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();
		unset( $schema['properties']['force'] );
		return $schema;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/enrollment-delete';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Delete Enrollment', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Unenroll a student from a course. Requires id (enrollment ID). Optionally accepts force (boolean, permanently removes the record when true). Returns the deleted enrollment object.', 'learning-management-system' );
	}
}
