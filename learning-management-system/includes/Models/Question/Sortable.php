<?php
/**
 * Sortable question model.
 *
 * @since 2.4.0
 *
 * @package Masteriyo\Models
 */

namespace Masteriyo\Models\Question;

use Masteriyo\Models\Question\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Sortable question model.
 *
 * @since 2.4.0
 */
class Sortable extends Question implements QuestionInterface {
	/**
	 * Question type.
	 *
	 * @since 2.4.0
	 *
	 * @var string $type Question type.
	 */
	protected $type = 'sortable';

	/**
	 * Check whether the chosen answer is correct or not.
	 *
	 * @since 2.4.0
	 *
	 * @param array  $chosen_answer Answer chosen by user.
	 * @param string $context Options: 'edit', 'view'.
	 *
	 * @return bool
	 */
	public function check_answer( $chosen_answer, $context = 'edit' ) {
		// Structure : [ { "name" : "<answer1>", "name": "<answer2>"}]
		$new_chosen_answer = wp_list_pluck( $chosen_answer, 'name' );
		$chosen_answer     = empty( $new_chosen_answer ) ? $chosen_answer : $new_chosen_answer;

		$answers = (array) $this->get_answers( 'edit' );
		$answers = wp_list_pluck( $answers, 'name' );
		$correct = true;

		if ( count( $chosen_answer ) === count( $answers ) ) {
			foreach ( $answers as $index => $value ) {
				if ( $value !== $chosen_answer[ $index ] ) {
					$correct = false;
					break;
				}
			}
		} else {
			$correct = false;
		}

		/**
		 * Filters boolean: true if the chosen answer is correct.
		 *
		 * @since 2.4.0
		 *
		 * @param boolean $bool true if the chosen answer is correct.
		 * @param array $chosen_answer Chosen answer.
		 * @param string $context Context.
		 * @param Masteriyo\Models\Question\Sortable $sortable Sortable question object.
		 */
		return apply_filters( "masteriyo_question_check_answer_{$this->type}", $correct, $chosen_answer, $context, $this );
	}

	/**
	 * Get correct answers only.
	 *
	 * @since 2.4.0
	 *
	 * @return mixed
	 */
	public function get_correct_answers() {
		return $this->get_answers( 'edit' );
	}

	/**
	 * Get question answers.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array|mixed
	 */
	public function get_answers( $context = 'view' ) {
		$answers = $this->get_prop( 'answers', $context );

		// Convert array of stdClass to arrays.
		$answers = array_map(
			function( $answer ) {
				return is_array( $answer ) ? $answer : (array) $answer;
			},
			$answers
		);

		if ( $answers && ! isset( $answers[0]['name'] ) ) {
			$answers = array_reduce(
				$answers,
				function( $result, $answer ) {
					$result[] = array(
						'name' => $answer,
					);

					return $result;
				},
				array()
			);
		}

		return $answers;
	}
}
