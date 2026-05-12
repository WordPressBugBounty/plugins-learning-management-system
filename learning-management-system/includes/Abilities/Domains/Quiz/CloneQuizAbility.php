<?php
/**
 * Clone Quiz ability.
 *
 * @package Masteriyo\Abilities\Domains\Quiz
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Quiz;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: duplicate a quiz into the same section.
 *
 * Creates an identical copy of the quiz — settings and all questions —
 * appended after the original in the same parent section.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CloneQuizAbility extends RestProxyAbility {

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
		return 'clone';
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
		return 'masteriyo/quiz-clone';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Clone Quiz', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Duplicate a quiz including its questions into the same section.', 'learning-management-system' );
	}
}
