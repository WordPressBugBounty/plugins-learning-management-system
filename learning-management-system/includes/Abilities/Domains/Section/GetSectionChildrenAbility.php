<?php
/**
 * Get Section Children ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve all lessons and quizzes belonging to a section.
 *
 * Returns items in their display order (menu_order). Useful for
 * building a section outline without fetching the full course tree.
 */
class GetSectionChildrenAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'section_children.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'list';
	}

	/**
	 * {@inheritdoc}
	 * The section comes from the `section` query param, not a path segment.
	 */
	protected function rest_base(): string {
		return 'sections/children';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/section-children-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Section Children', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Retrieve all lessons and quizzes belonging to a section.';
	}
}
