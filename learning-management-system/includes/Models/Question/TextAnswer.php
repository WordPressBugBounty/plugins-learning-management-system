<?php
/**
 * TextAnswer question model.
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
 * TextAnswer question model.
 *
 * @since 2.4.0
 */
class TextAnswer extends Question implements QuestionInterface {
	/**
	 * Question type.
	 *
	 * @since 2.4.0
	 *
	 * @var string $type Question type.
	 */
	protected $type = 'text-answer';

	/**
	 * Return true if the answer should be manually reviewed and manually assigned points.
	 *
	 * @since 2.4.0
	 *
	 * @return boolean
	 */
	public function is_reviewable() {
		return true;
	}

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

		/**
		 * Filters boolean: true if the chosen answer is correct.
		 *
		 * @since 2.4.0
		 *
		 * @param boolean $bool true if the chosen answer is correct.
		 * @param string $context Context.
		 * @param Masteriyo\Models\Question\TextAnswer $text_answer TextAnswer question object.
		 */

		// Text answer need to be manually reviewed so returning true.
		return apply_filters( "masteriyo_question_check_answer_{$this->type}", false, $context, $this );
	}

	/**
	 * Get correct answers only.
	 *
	 * @since 2.4.0
	 *
	 * @return mixed
	 */
	public function get_correct_answers() {
		return array();
	}
}
