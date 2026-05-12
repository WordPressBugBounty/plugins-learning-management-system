<?php
/**
 * List Quizzes ability.
 *
 * @package Masteriyo\Abilities\Domains\Quiz
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Quiz;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of quizzes.
 *
 * Supports filtering by course ID or section ID.
 * Results are ordered by menu_order within their parent section.
 *
 * @since x.x.x
 */
class ListQuizzesAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/quiz-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Quizzes', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of quizzes, optionally filtered by course or section.', 'learning-management-system' );
	}
}
