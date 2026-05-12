<?php
/**
 * Update Question ability.
 *
 * @package Masteriyo\Abilities\Domains\Question
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Question;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing quiz question.
 *
 * Supports partial updates — only the supplied fields are changed.
 * Commonly used to correct question text, change the type, or update answer choices.
 *
 * @since x.x.x
 */
class UpdateQuestionAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/question-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Question', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing question text, type, or answer choices.', 'learning-management-system' );
	}
}
