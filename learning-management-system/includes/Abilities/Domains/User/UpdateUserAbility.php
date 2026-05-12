<?php
/**
 * Update User ability.
 *
 * @package Masteriyo\Abilities\Domains\User
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\User;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update a user's profile.
 *
 * @since x.x.x
 */
class UpdateUserAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'user.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'update';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/user-update';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update User', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Update an existing user\'s profile. Requires id (user ID). Optionally accepts display_name, email, or profile fields. Returns the updated user object.', 'learning-management-system' );
	}
}
