<?php
/**
 * Get Section Children ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve all lessons and quizzes belonging to a section.
 *
 * Returns items in their display order (menu_order). Useful for
 * building a section outline without fetching the full course tree.
 *
 * @since x.x.x
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
	 */
	protected function rest_base(): string {
		return 'sections';
	}

	/**
	 * {@inheritdoc}
	 * Routes to /masteriyo/v1/sections/{id}/children.
	 */
	protected function route_suffix(): string {
		return 'children';
	}

	/**
	 * {@inheritdoc}
	 * Prepends a required 'id' field (the section ID) to the collection params
	 * so AI callers know which section to fetch children for.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();

		$schema['required']   = array( 'id' );
		$schema['properties'] = array_merge(
			array(
				'id' => array(
					'type'        => 'integer',
					'description' => __( 'Section ID to retrieve children for.', 'learning-management-system' ),
					'minimum'     => 1,
				),
			),
			isset( $schema['properties'] ) ? $schema['properties'] : array()
		);

		return $schema;
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
		return __( 'Retrieve all lessons and quizzes belonging to a section.', 'learning-management-system' );
	}
}
