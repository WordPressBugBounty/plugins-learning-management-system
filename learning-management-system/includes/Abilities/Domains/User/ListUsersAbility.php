<?php
/**
 * List Users ability.
 *
 * @package Masteriyo\Abilities\Domains\User
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\User;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of users (students and instructors).
 *
 * @since x.x.x
 */
class ListUsersAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'user.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'list';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/user-list';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'List Users', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of Masteriyo users (students and instructors). Optionally filter by role, search term, or enrollment status. Returns data array and pagination metadata.', 'learning-management-system' );
	}
}
