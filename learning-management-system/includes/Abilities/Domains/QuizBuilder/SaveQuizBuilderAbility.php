<?php
/**
 * Save Quiz Builder ability.
 *
 * @package Masteriyo\Abilities\Domains\QuizBuilder
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\QuizBuilder;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: save the complete ordered question list for a quiz.
 *
 * Replaces the existing question structure with the supplied list.
 * The caller must provide the full desired question order; omitting questions
 * will remove them. Marked non-idempotent because question IDs assigned
 * for new questions differ between calls.
 *
 * @since x.x.x
 */
class SaveQuizBuilderAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'quiz_builder.rest';
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
		return 'quizbuilder';
	}

	/**
	 * {@inheritdoc}
	 * Adds confirm_published_overwrite so AI callers must explicitly acknowledge
	 * when they are replacing the question list in a quiz that belongs to a
	 * published course with enrolled students.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();

		$schema['properties']['confirm_published_overwrite'] = array(
			'type'        => 'boolean',
			'description' => __( 'Set to true to confirm that you intend to overwrite a quiz in a published course. Required when the parent course status is "publish".', 'learning-management-system' ),
			'default'     => false,
		);

		return $schema;
	}

	/**
	 * {@inheritdoc}
	 * Guards against unintentional overwrites of quizzes inside published courses.
	 *
	 * @param mixed $input Ability input (array or null).
	 */
	public function execute( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$quiz_id = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $quiz_id > 0 ) {
			$quiz = masteriyo_get_quiz( $quiz_id );
			if ( $quiz ) {
				$course = masteriyo_get_course( $quiz->get_course_id() );
				if ( $course && 'publish' === $course->get_status() ) {
					if ( empty( $input['confirm_published_overwrite'] ) ) {
						return new \WP_Error(
							'masteriyo_ability_published_course_guard',
							__( 'This quiz belongs to a published course with enrolled students. Set confirm_published_overwrite=true to proceed.', 'learning-management-system' ),
							array( 'status' => 400 )
						);
					}
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
		return 'masteriyo/quiz-builder-save';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Save Quiz Builder', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Save the ordered list of questions for a quiz. Provide the complete desired question structure.', 'learning-management-system' );
	}
}
