<?php
/**
 * Create Question ability.
 *
 * @package Masteriyo\Abilities\Domains\Question
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Question;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new question for a quiz.
 *
 * Accepts question text, type (single-choice, multi-choice, true/false, short answer),
 * answer options, and the correct answer designation.
 * The parent quiz ID must be supplied.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CreateQuestionAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'question.rest';
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
		return 'questions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/question-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Question', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new question for a quiz with type, answers, and correct answer settings.', 'learning-management-system' );
	}
}
