<?php
/**
 * Create Quiz ability.
 *
 * @package Masteriyo\Abilities\Domains\Quiz
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Quiz;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new quiz inside a section.
 *
 * The parent course ID and section ID must be supplied.
 * Quiz settings such as pass mark, time limit, and attempts allowed
 * may be provided at creation time.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CreateQuizAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/quiz-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Quiz', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new quiz inside a section.', 'learning-management-system' );
	}
}
