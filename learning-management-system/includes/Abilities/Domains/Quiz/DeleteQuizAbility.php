<?php
/**
 * Delete Quiz ability.
 *
 * @package Masteriyo\Abilities\Domains\Quiz
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Quiz;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: move a quiz to trash or permanently delete it.
 *
 * Without `force=true` the quiz is trashed and can be restored.
 * With `force=true` the quiz and all its questions are permanently removed.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class DeleteQuizAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'quiz.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'delete';
	}

	/**
	 * {@inheritdoc}
	 * Note: REST base uses the intentional 'quizes' typo to match the registered endpoint.
	 */
	protected function rest_base(): string {
		return 'quizes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/quiz-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Quiz', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Move a quiz to trash (use force=true to permanently delete).', 'learning-management-system' );
	}
}
