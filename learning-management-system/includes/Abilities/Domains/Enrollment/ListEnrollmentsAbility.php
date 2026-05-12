<?php
/**
 * List Enrollments ability.
 *
 * @package Masteriyo\Abilities\Domains\Enrollment
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Enrollment;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of course enrollments.
 *
 * @since x.x.x
 */
class ListEnrollmentsAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'users.courses';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'list';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'users/courses';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/enrollment-list';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'List Enrollments', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course enrollments. Optionally filter by course_id, user_id, or status. Returns data array and pagination metadata (total, total_pages, page, per_page).', 'learning-management-system' );
	}
}
