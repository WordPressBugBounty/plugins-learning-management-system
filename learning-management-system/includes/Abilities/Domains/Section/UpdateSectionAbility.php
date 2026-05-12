<?php
/**
 * Update Section ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing section.
 *
 * Supports partial updates — only the supplied fields are changed.
 * Commonly used to rename a section or reorder it within the course.
 *
 * @since x.x.x
 */
class UpdateSectionAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/section-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Section', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing section title or order.', 'learning-management-system' );
	}
}
