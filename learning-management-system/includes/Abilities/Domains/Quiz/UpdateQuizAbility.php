<?php
/**
 * Update Quiz ability.
 *
 * @package Masteriyo\Abilities\Domains\Quiz
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Quiz;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing quiz.
 *
 * Supports partial updates — only the supplied fields are changed.
 * Commonly used to adjust pass mark, time limit, or question display settings.
 *
 * @since x.x.x
 */
class UpdateQuizAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/quiz-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Quiz', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing quiz settings, title, or pass mark.', 'learning-management-system' );
	}
}
