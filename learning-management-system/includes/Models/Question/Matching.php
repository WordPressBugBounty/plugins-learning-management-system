<?php
/**
 * Matching question model.
 *
 * @since 2.4.0
 *
 * @package Masteriyo\Models
 */

namespace Masteriyo\Models\Question;

use Masteriyo\Models\Question\Question;
use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Matching question model.
 *
 * @since 2.4.0
 */
class Matching extends Question implements QuestionInterface {
	/**
	 * Question type.
	 *
	 * @since 2.4.0
	 *
	 * @var string $type Question type.
	 */
	protected $type = 'matching';

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
		$correct       = true;
		$answers       = $this->get_answers( 'edit' );
		$chosen_answer = (array) $chosen_answer;

		// Convert answers to dictionary.
		$answers = array_reduce(
			$answers,
			function( $carry, $answer ) {

				if ( 'ImageToImage' === ( $answer->type ?? '' ) ) {
					// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if (
						isset( $answer->imagePrompt, $answer->imageMatch ) &&
						$answer->imagePrompt !== '' &&
						$answer->imageMatch !== ''
					) {
						$carry[ $answer->imagePrompt ] = $answer->imageMatch;
					}
					// phpcs:enable
				} elseif ( isset( $answer->prompt ) ) {
						$carry[ $answer->prompt ] = $answer->match ?? null;
				}

				return $carry;
			},
			array()
		);

		// Convert chosen answer to dictionary.
		$chosen_answer = array_reduce(
			$chosen_answer,
			function( $carry, $answer ) {

				if ( ! is_array( $answer ) ) {
					return $carry;
				}

				$prompt = $answer['prompt'] ?? $answer['imagePrompt'] ?? null;
				$match  = $answer['match'] ?? $answer['imageMatch'] ?? null;

				if ( null !== $prompt ) {
					$carry[ $prompt ] = $match;
				}

				return $carry;
			},
			array()
		);

		// Only array with keys from chosen answer if it is also in stored answers.
		$chosen_answer = masteriyo_array_only( $chosen_answer, array_keys( $answers ) );

		foreach ( $answers as $prompt => $match ) {
			if ( ! array_key_exists( $prompt, $chosen_answer ) || $chosen_answer[ $prompt ] !== $match ) {
				$correct = false;
				break;
			}
		}

		/**
		 * Filters boolean: true if the chosen answer is correct.
		 *
		 * @since 2.4.0
		 *
		 * @param boolean $bool true if the chosen answer is correct.
		 * @param array $chosen_answer Chosen answer.
		 * @param string $context Context.
		 * @param Masteriyo\Models\Question\Matching $true_false Matching question object.
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
	 * @since  2.7.1
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array|mixed
	 */
	public function get_answers( $context = 'view' ) {
		$answers = $this->get_prop( 'answers', $context );

		if ( is_array( $answers ) ) {
			foreach ( $answers as $key => $answer ) {
				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$img_prompt_url         = ! empty( $answer->imagePrompt ) ? wp_get_attachment_image_url( $answer->imagePrompt, 'medium' ) : '';
				$img_match_url          = ! empty( $answer->imageMatch ) ? wp_get_attachment_image_url( $answer->imageMatch, 'medium' ) : '';
				$img_url                = ! empty( $answer->image ) ? wp_get_attachment_image_url( $answer->image, 'medium' ) : '';
				$answer->imagePromptUrl = $img_prompt_url;
				$answer->imageMatchUrl  = $img_match_url;
				$answer->imageUrl       = $img_url;
				// phpcs:enable
			}
		}

		return $answers;
	}
}
