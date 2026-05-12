<?php
/**
 * Get Quiz Builder ability.
 *
 * @package Masteriyo\Abilities\Domains\QuizBuilder
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\QuizBuilder;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve the ordered question list for a quiz in builder format.
 *
 * Returns every question with its type, answers, correct answer flag,
 * and display order — the same representation consumed by the quiz builder UI.
 *
 * @since x.x.x
 */
class GetQuizBuilderAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'quiz_builder.rest';
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
		return 'quizbuilder';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/quiz-builder-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Quiz Builder', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve the full question list for a quiz in builder format.', 'learning-management-system' );
	}
}
