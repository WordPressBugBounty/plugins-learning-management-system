<?php
/**
 * Get User ability.
 *
 * @package Masteriyo\Abilities\Domains\User
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\User;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single user profile by ID.
 *
 * @since x.x.x
 */
class GetUserAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'user.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'get';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/user-get';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Get User', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a single user profile by their ID. Requires id (user ID). Returns the user object including display_name, email, roles, and profile metadata.', 'learning-management-system' );
	}
}
