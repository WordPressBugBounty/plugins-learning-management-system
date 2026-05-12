<?php
/**
 * Save Course Builder ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseBuilder
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseBuilder;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: atomically save the entire course structure.
 *
 * Replaces the existing sections/lessons/quizzes tree with the supplied one.
 * The caller must provide the complete desired structure — partial trees will
 * overwrite existing content. Marked destructive and non-idempotent because
 * successive calls with different inputs produce different results.
 *
 * @since x.x.x
 */
class SaveCourseBuilderAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_builder.rest';
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
		return 'coursebuilder';
	}

	/**
	 * {@inheritdoc}
	 * Overridden because saving the builder replaces existing course content.
	 */
	public function is_destructive(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 * Overridden because each call may produce a different stored structure.
	 */
	public function is_idempotent(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 * Adds confirm_published_overwrite so AI callers must explicitly acknowledge
	 * when they are replacing the structure of a live, published course.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();

		$schema['properties']['confirm_published_overwrite'] = array(
			'type'        => 'boolean',
			'description' => __( 'Set to true to confirm that you intend to overwrite a published course. Required when the course status is "publish".', 'learning-management-system' ),
			'default'     => false,
		);

		return $schema;
	}

	/**
	 * {@inheritdoc}
	 * Guards against unintentional overwrites of published courses with enrolled students.
	 *
	 * @param mixed $input Ability input (array or null).
	 */
	public function execute( $input = null ) {
		$input     = is_array( $input ) ? $input : array();
		$course_id = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $course_id > 0 ) {
			$course = masteriyo_get_course( $course_id );
			if ( $course && 'publish' === $course->get_status() ) {
				if ( empty( $input['confirm_published_overwrite'] ) ) {
					return new \WP_Error(
						'masteriyo_ability_published_course_guard',
						__( 'This course is published with enrolled students. Set confirm_published_overwrite=true to proceed.', 'learning-management-system' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		unset( $input['confirm_published_overwrite'] );
		return parent::execute( $input );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-builder-save';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Save Course Builder', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Atomically save the entire course structure (sections, lessons, quizzes). Replaces the existing structure — provide the full desired tree.', 'learning-management-system' );
	}
}
