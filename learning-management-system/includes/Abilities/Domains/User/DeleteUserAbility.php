<?php
/**
 * Delete User ability.
 *
 * @package Masteriyo\Abilities\Domains\User
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\User;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: delete a user account.
 *
 * @since x.x.x
 */
class DeleteUserAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'user.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'delete';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users';
	}

	/**
	 * {@inheritdoc}
	 * Hard-delete (force=true) is intentionally removed from the AI-callable schema.
	 * Permanent user deletion wipes enrollments, progress, and order history with no
	 * recovery path. Soft-delete (trash) is AI-callable; hard-delete is not.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();
		unset( $schema['properties']['force'] );
		return $schema;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/user-delete';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Delete User', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Delete a user account from the LMS. Requires id (user ID). Optionally accepts force (boolean). Returns the deleted user object.', 'learning-management-system' );
	}
}
