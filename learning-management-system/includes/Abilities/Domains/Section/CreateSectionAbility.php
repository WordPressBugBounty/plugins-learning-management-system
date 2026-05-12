<?php
/**
 * Create Section ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new section inside a course.
 *
 * The parent course ID must be supplied in the request body.
 * Sections are appended at the end unless a menu_order is specified.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CreateSectionAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/section-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Section', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new section inside a course.', 'learning-management-system' );
	}
}
