<?php
/**
 * List Questions ability.
 *
 * @package Masteriyo\Abilities\Domains\Question
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Question;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of questions.
 *
 * Supports filtering by quiz ID. Results are ordered by menu_order
 * within the parent quiz.
 *
 * @since x.x.x
 */
class ListQuestionsAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/question-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Questions', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of questions, optionally filtered by quiz.', 'learning-management-system' );
	}
}
