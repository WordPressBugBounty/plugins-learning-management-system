<?php
/**
 * Get Course Children ability.
 *
 * @package Masteriyo\Abilities\Domains\Course
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Course;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve all content items belonging to a course.
 *
 * Returns a flat list of sections, lessons, and quizzes in their display order.
 * Useful for generating a course outline without navigating the full builder tree.
 *
 * @since x.x.x
 */
class GetCourseChildrenAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_children.rest';
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
		return 'courses';
	}

	/**
	 * {@inheritdoc}
	 * Routes to /masteriyo/v1/courses/{id}/children.
	 */
	protected function route_suffix(): string {
		return 'children';
	}

	/**
	 * {@inheritdoc}
	 * Prepends a required 'id' field (the course ID) to the collection params
	 * so AI callers know which course to fetch children for.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();

		$schema['required']   = array( 'id' );
		$schema['properties'] = array_merge(
			array(
				'id' => array(
					'type'        => 'integer',
					'description' => __( 'Course ID to retrieve children for.', 'learning-management-system' ),
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
		return 'masteriyo/course-children-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Children', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve all content items (sections, lessons, quizzes) belonging to a course.', 'learning-management-system' );
	}
}
