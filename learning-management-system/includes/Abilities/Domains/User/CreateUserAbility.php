<?php
/**
 * Create User ability.
 *
 * @package Masteriyo\Abilities\Domains\User
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\User;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: register a new user account.
 *
 * @since x.x.x
 */
class CreateUserAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'user.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'create';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/user-create';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Create User', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Register a new user account. Requires username, email, and password. Optionally accepts display_name and roles. Returns the created user object with its ID.', 'learning-management-system' );
	}
}
