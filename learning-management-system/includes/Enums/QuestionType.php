<?php
/**
 * Question type enums.
 *
 * @since 1.5.3
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Question type enum class.
 *
 * @since 1.5.3
 */
class QuestionType {
	/**
	 * True False question type.
	 *
	 * @since 1.5.3
	 * @var string
	 */
	const TRUE_FALSE = 'true-false';

	/**
	 * Single Choice question type.
	 *
	 * @since 1.5.3
	 * @var string
	 */
	const SINGLE_CHOICE = 'single-choice';

	/**
	 * Multiple Choice question type.
	 *
	 * @since 1.5.3
	 * @var string
	 */
	const MULTIPLE_CHOICE = 'multiple-choice';


	/**
	 * Sortable question type.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const SORTABLE = 'sortable';

	/**
	 * Text answer question type.
	 * Matching question type.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const TEXT_ANSWER = 'text-answer';

	/**
	 * Matching question type.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const MATCHING = 'matching';

	/**
	 * Audio question type.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const AUDIO = 'audio';

	/**
	 * Video question type.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const VIDEO = 'video';

	/**
	 * Fill in the blanks.
	 *
	 * @since 2.4.7
	 *
	 * @var string
	 */
	const FILL_IN_THE_BLANKS = 'fill-in-the-blanks';

	/**
	 * Get all question types.
	 *
	 * @since 1.5.3
	 * @static
	 *
	 * @return array
	 */
	public static function all() {
		// This list must name only the types core itself binds a model for in
		// QuestionServiceProvider, because every consumer treats it as constructable:
		// two REST schema `enum`s advertise it, and masteriyo_get_questions_count_by_quiz()
		// counts by it while masteriyo_get_question() resolves each one through
		// masteriyo( "question.{$type}" ). A type listed here with no container binding is
		// therefore advertised, counted, and then silently dropped on read.
		//
		// AUDIO, VIDEO and FILL_IN_THE_BLANKS are bound by pro's advanced-quiz addon, which
		// appends them through the masteriyo_question_types filter below. The constants stay
		// here because they are the shared spelling both products compare against.
		$types = apply_filters(
			'masteriyo_question_types',
			array(
				self::TRUE_FALSE,
				self::SINGLE_CHOICE,
				self::MULTIPLE_CHOICE,
				self::TEXT_ANSWER,
				self::MATCHING,
				self::SORTABLE,
			)
		);

		return array_unique( $types );
	}
}
