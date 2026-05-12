<?php
/**
 * Delete Question ability.
 *
 * @package Masteriyo\Abilities\Domains\Question
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Question;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: permanently delete a question from a quiz.
 *
 * Unlike course/lesson/quiz deletes, questions do not support a trash state —
 * this operation is always permanent.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class DeleteQuestionAbility extends RestProxyAbility {

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
		return 'delete';
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
		return 'masteriyo/question-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Question', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Permanently delete a question from a quiz.', 'learning-management-system' );
	}
}
