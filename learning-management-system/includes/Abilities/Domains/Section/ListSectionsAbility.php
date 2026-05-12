<?php
/**
 * List Sections ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of sections.
 *
 * Supports filtering by course ID. Results are ordered by menu_order (display order).
 *
 * @since x.x.x
 */
class ListSectionsAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'section.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'list';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function rest_base(): string {
		return 'sections';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/section-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Sections', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of sections, optionally filtered by course.', 'learning-management-system' );
	}
}
