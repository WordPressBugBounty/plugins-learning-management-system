<?php
/**
 * Get Question ability.
 *
 * @package Masteriyo\Abilities\Domains\Question
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Question;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single question by ID.
 *
 * Returns the question including its type (single-choice, multi-choice, true/false,
 * short answer, etc.), all answer options, and the correct answer flag.
 *
 * @since x.x.x
 */
class GetQuestionAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/question-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Question', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single question by ID including answer choices.', 'learning-management-system' );
	}
}
