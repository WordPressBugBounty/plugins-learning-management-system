<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

use Masteriyo\Enums\QuestionType;

if ( ! function_exists( 'masteriyo_generate_course_outline_prompt' ) ) {
	/**
	 * Generate a course outline prompt.
	 *
	 * @since 1.6.15
	 *
	 * @param string $course_title    Title of the course.
	 * @param string $course_idea     Idea or description of the course (Optional).
	 * @param int    $num_sections    Number of sections in the course. Default: 4.
	 * @param int    $num_lessons     Number of lessons per section. Default: 3.
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_course_outline_prompt( $course_title, $course_idea, $num_sections = 4, $num_lessons = 3 ) {
		$prompt = "Please design an outline for a course titled '{$course_title}'";

		if ( ! empty( $course_idea ) ) {
			$prompt .= ", inspired by the theme '{$course_idea}'";
		}

		$prompt .= ". Your outline should include {$num_sections} sections, and each section should feature {$num_lessons} unique and descriptive lessons.";

		$sample_json_structure = '{"course":{"sections":[{"title":"Example Section","lessons":[{"title":"Example Lesson 1"},{"title":"Example Lesson 2"}]}]}}';
		$prompt               .= " Format your outline as a minified JSON object, similar to this example: {$sample_json_structure}";

		return $prompt;
	}
}

if ( ! function_exists( 'masteriyo_generate_course_content_prompt' ) ) {
	/**
	 * Generate a course description and highlight prompt.
	 *
	 * @since 1.6.15
	 *
	 * @param string $course_title           Title of the course.
	 * @param string $course_idea            Idea or description of the course (Optional).
	 * @param int    $num_of_paragraphs      Number of paragraphs for course description. Default: 2.
	 * @param int    $course_highlight_points Number of course highlight points. Default: 4.
	 * @param array    $lesson_names Array of lesson names (Optional).
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_course_content_prompt( $course_title, $course_idea, $lesson_names, $num_of_paragraphs = 2, $course_highlight_points = 4 ) {
		$prompt = "Please write a description for the course '{$course_title}' in {$num_of_paragraphs} paragraphs";

		if ( ! empty( $course_idea ) ) {
				$prompt .= ", focusing on the theme '{$course_idea}'";
		}

		if ( ! empty( $lesson_names ) ) {
				$lesson_list = implode( "', '", $lesson_names );
				$prompt     .= ". Ensure that the description and highlights are relevant to the following lessons in the course: '{$lesson_list}'";
		}

		$prompt .= ". Additionally, provide {$course_highlight_points} key highlights of the course as HTML list 'li' items.";

		$sample_json_structure = '{"description": "Your detailed description in ' . $num_of_paragraphs . ' paragraphs.", "highlight_points": "<li>Example Highlight 1</li><li>Example Highlight 2</li>"}';
		$prompt               .= " Present your response in a minified JSON format, similar to this example: {$sample_json_structure}";

		return $prompt;
	}
}

if ( ! function_exists( 'masteriyo_generate_lesson_content_prompt' ) ) {
	/**
	 * Generate lesson description prompt.
	 *
	 * @since 1.6.15
	 *
	 * @param \Masteriyo\Models\Lesson  $lesson             The lesson.
	 * @param string $course_title      Title of the course.
	 * @param string $course_idea       Idea or description of the course (Optional).
	 * @param int    $num_of_paragraphs Number of paragraphs for each lesson description. Default: 4.
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_lesson_content_prompt( $lesson, $course_title, $course_idea, $num_of_paragraphs = 4 ) {
		$lesson_name = $lesson->get_name();
		$prompt      = "Please compose a {$num_of_paragraphs}-paragraph description for the lesson titled '{$lesson_name}', which is part of the course '{$course_title}'";

		if ( ! empty( $course_idea ) ) {
			$prompt .= ", keeping in mind the course theme of '{$course_idea}'";
		}

		$prompt .= ". The description should be comprehensive yet easy to understand for students. Refrain from using headings like 'Paragraph 1:' and focus on the content itself.";

		return $prompt;
	}
}


if ( ! function_exists( 'masteriyo_generate_section_quizzes_prompt' ) ) {
	/**
	 * Generate quizzes for a section of the course prompt.
	 *
	 * @since 1.6.15
	 *
	 * @param string $section_title       The title of the section.
	 * @param string $course_title        Title of the course.
	 * @param string $course_idea         Idea or description of the course (Optional).
	 * @param int    $number_of_quizzes   Number of quizzes for the section. Default: 1.
	 * @param int    $num_of_questions       Number of questions for the quiz. Default: 2.
	 * @param string $question_type       Type of questions for the quiz (true/false, single choice, multiple choice). Default: 'multiple_choice'.
	 * @param int    $points Points for a question. Default: 1.
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_section_quizzes_prompt( $section_title, $course_title, $course_idea, $number_of_quizzes = 1, $num_of_questions = 2, $question_type = 'multiple-choice', $points = 1 ) {
		$prompt = "Please create {$number_of_quizzes} quizzes for the section titled '{$section_title}', which is part of the course '{$course_title}'";

		if ( ! empty( $course_idea ) ) {
				$prompt .= ", and align them with the theme '{$course_idea}'";
		}

		$prompt .= ". Each quiz should contain {$num_of_questions} questions. Choose the most suitable question type from 'true-false', 'single-choice', or 'multiple-choice' for each quiz. If you opt for 'multiple-choice' questions, include an array of correct answers. If you opt for 'single-choice' questions, include the correct answer. If you opt for 'true-false' questions, include the correct answer as either 'true' or 'false' under the 'correct' key. Each question should be worth {$points} points.";

		$sample_json_structure = '{"quizzes":[{"title":"Quiz Title","description":"Quiz Description","pass_mark":40,"full_mark":100,"questions":[{"question":"Question Text","choices":"Answer Choices","correct":"Correct Answer","question_type":"Question Type","points":' . $points . '}]}]}';
		$prompt               .= " Your response should be formatted as a minified JSON object, similar to this example: {$sample_json_structure}";

		return $prompt;

	}
}

if ( ! function_exists( 'masteriyo_generate_content_prompt' ) ) {
	/**
	 * Generate content prompt.
	 *
	 * @since 1.7.1
	 *
	 * @param string $prompt The raw prompt.
	 * @param string $content_type Type of content to generate. Default: 'course highlights'.
	 * @param int    $word_limit The number of words. Default: 200.
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_content_prompt( $prompt, $content_type, $word_limit = 200 ) {
		$prompt = $prompt . "in $word_limit words";

		if ( 'course highlights' === $content_type ) {
			$prompt .= ', highlight point should be in HTML list "li" items. for example: <li>first point</li><li>second point</li>,...';

		}

		return $prompt;
	}
}

if ( ! function_exists( 'masteriyo_generate_quiz_questions_prompt' ) ) {
	/**
	 * Generate quiz questions prompt.
	 *
	 * @since 1.7.1
	 *
	 * @param string $prompt The raw prompt.
	 * @param string $question_type Type of questions for the quiz (true/false, single choice, multiple choice). Default: 'true-false'.
	 * @param int $num_of_questions Number of questions.
	 * @param int $points Points for a question. Default: 1.
	 * @param string $context Course material to ground the questions in (Optional).
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_quiz_questions_prompt( $prompt, $question_type = 'true-false', $num_of_questions = 1, $points = 1, $context = '' ) {
		$prompt .= " Generate {$num_of_questions} question(s) of type \"{$question_type}\". Each question should be worth {$points} points.";

		// Add type-specific instructions
		switch ( $question_type ) {
			case 'true-false':
				$prompt .= " For true-false questions, provide the correct answer as either 'true' or 'false' under the 'correct' key.";
				break;
			case 'single-choice':
				$prompt .= ' For single-choice questions, provide multiple choice options and mark exactly ONE correct answer. IMPORTANT: You must mark one answer as correct.';
				break;
			case 'multiple-choice':
				$prompt .= ' For multiple-choice questions, provide multiple choice options and mark one or more correct answers. IMPORTANT: For multiple-choice, the "correct" field should be an array of correct answers, not a single value. Example: "correct":["Answer1","Answer3"] for multiple correct answers.';
				break;
			case 'text-answer':
				$prompt .= ' For text-answer questions, provide an open-ended question that requires a written response. No choices are needed. You must also set max_character based on the expected answer length (e.g., 200-500 for short answers, 1000-2000 for detailed explanations, or 0 for unlimited length).';
				break;
			case 'matching':
				$prompt .= " For matching questions, provide pairs of items to match. Format as choices array where each item has 'prompt' (left side) and 'match' (right side) properties. Example: [{\"prompt\":\"Item 1\", \"match\":\"Match 1\"}, {\"prompt\":\"Item 2\", \"match\":\"Match 2\"}].";
				break;
			case 'sortable':
				$prompt .= ' For sortable questions, provide items that need to be arranged in the correct order based on the question context. CRITICAL REQUIREMENT: The choices array MUST be in the EXACT correct sorted order that answers the question. DO NOT randomize or shuffle the choices - they must be pre-sorted correctly. Possible sorting types include: - Chronological (by date/time) → choices array must be earliest to latest. - Numerical (by number/quantity) → choices array must be smallest to largest (or as specified). - Alphabetical (by words/letters) → choices array must be A to Z (or Z to A if specified). - Custom (based on domain-specific rules) → choices array must be in logical order. Example 1 (Chronological): question:"Arrange these historical events in chronological order", choices:["Industrial Revolution (1760s)","World War II (1939)","Moon Landing (1969)"] - NOTE: choices are already in correct chronological order. Example 2 (Numerical): question:"Arrange these numbers in ascending order", choices:["7","15","42","101"] - NOTE: choices are already in ascending order. REMEMBER: The choices array is the answer key - it must be correctly ordered.';
				break;
			case 'fill-in-the-blanks':
				$prompt .= " For fill-in-the-blanks questions, create a simple instruction as the question title (like 'Fill in the blanks' or 'Complete the sentence'), and put the actual sentence with blanks marked as {{answer}} in the 'correct' field. You can include multiple blanks in one question. Example: question:\"Fill in the blanks\", correct:\"The capital of {{France}} is {{Paris}} and it is located in {{Europe}}\".";
				break;
		}

		if ( ! empty( $context ) ) {
			$prompt .= ' Base every question strictly on the course material provided below; do not invent facts the material does not support. Prefer application and scenario questions that test understanding over simple recall. Every wrong option must be plausible and reflect a real misconception a student of this material could hold.';
			$prompt .= " Course material:\n\"\"\"\n{$context}\n\"\"\"\n";
		}

		// Provide type-specific JSON examples
		if ( 'single-choice' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"What is the capital of France?","choices":["Paris","London","Berlin","Madrid"],"correct":"Paris","question_type":"single-choice","points":' . $points . '}]}';
		} elseif ( 'multiple-choice' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"Which of these are programming languages?","choices":["Python","HTML","JavaScript","CSS"],"correct":["Python","JavaScript"],"question_type":"multiple-choice","points":' . $points . '}]}';
		} elseif ( 'true-false' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"The Earth is round","choices":[],"correct":"true","question_type":"true-false","points":' . $points . '}]}';
		} elseif ( 'matching' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"Match the following items","choices":[{"prompt":"Apple","match":"Fruit"},{"prompt":"Carrot","match":"Vegetable"}],"correct":"","question_type":"matching","points":' . $points . '}]}';
		} elseif ( 'sortable' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"Arrange these inventions in chronological order","choices":["Telephone (1876)","Light bulb (1879)","Airplane (1903)","Internet (1969)"],"correct":"","question_type":"sortable","points":' . $points . '}]}';
		} elseif ( 'fill-in-the-blanks' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"Complete the sentence","choices":[],"correct":"In a rock song, the power chord typically consists of the root note and the {{fifth}} of the chord","question_type":"fill-in-the-blanks","points":' . $points . '}]}';
		} elseif ( 'text-answer' === $question_type ) {
			$sample_json_structure = '{"questions":[{"question":"Explain your understanding of...","choices":[],"correct":"","max_character":1000,"question_type":"text-answer","points":' . $points . '}]}';
		} else {
			$sample_json_structure = '{"questions":[{"question":"Question Text","choices":[],"correct":"","question_type":"' . $question_type . '","points":' . $points . '}]}';
		}

		$prompt .= " Your response should be formatted as a minified JSON object, similar to this example: {$sample_json_structure}";

		return $prompt;
	}
}


/* End prompt generate functions. */

if ( ! function_exists( 'masteriyo_openai_html_to_text' ) ) {
	/**
	 * HTML to plain text for AI prompts, keeping block boundaries as line
	 * breaks. Bare wp_strip_all_tags() glues adjacent blocks together
	 * ("<p>First</p><p>Second</p>" becomes "FirstSecond").
	 *
	 * @param string $html The HTML (or plain text) to convert.
	 *
	 * @return string Trimmed plain text.
	 */
	function masteriyo_openai_html_to_text( $html ) {
		$html = preg_replace( '#<br\s*/?\s*>#i', "\n", (string) $html );
		$html = preg_replace( '#</(p|div|li|ul|ol|h[1-6]|blockquote|pre|figure|table|tr)\s*>#i', "\n", $html );
		$text = trim( wp_strip_all_tags( $html ) );

		return preg_replace( "/\n{3,}/", "\n\n", $text );
	}
}

if ( ! function_exists( 'masteriyo_openai_cap_context' ) ) {
	/**
	 * Cap an assembled AI grounding context to the filtered maximum length.
	 *
	 * @param string $context The assembled plain-text context.
	 *
	 * @return string The context, truncated when over the cap.
	 */
	function masteriyo_openai_cap_context( $context ) {
		/**
		 * Filters the maximum length of the AI grounding context.
		 *
		 * The model's context window fits far more; this caps API input cost.
		 *
		 * @param int $max_chars Maximum number of characters. 0 disables the cap.
		 */
		$max_chars = absint( apply_filters( 'masteriyo_openai_quiz_context_max_chars', 150000 ) );

		if ( $max_chars && mb_strlen( $context ) > $max_chars ) {
			$context = mb_substr( $context, 0, $max_chars );
		}

		return $context;
	}
}

if ( ! function_exists( 'masteriyo_get_section_ai_context' ) ) {
	/**
	 * Assemble the course material around one section: course title/description
	 * and the section's lessons, as plain text.
	 *
	 * @param int $course_id
	 * @param int $section_id
	 *
	 * @return string Plain-text context, capped for cost control.
	 */
	function masteriyo_get_section_ai_context( $course_id, $section_id ) {
		$parts   = array();
		$summary = array(
			'course'                   => '',
			'course_description_chars' => 0,
			'section'                  => '',
			'lessons'                  => array(),
		);
		$course  = masteriyo_get_course( $course_id );

		if ( $course ) {
			$parts[]           = 'Course: ' . $course->get_name();
			$summary['course'] = $course->get_name();

			$description = masteriyo_openai_html_to_text( $course->get_description() );

			if ( $description ) {
				$parts[]                             = 'Course description: ' . $description;
				$summary['course_description_chars'] = mb_strlen( $description );
			}
		}

		$section = masteriyo_get_section( $section_id );

		if ( $section ) {
			$parts[]            = 'Section: ' . $section->get_name();
			$summary['section'] = $section->get_name();

			$lessons = masteriyo_get_lessons(
				array(
					'parent_id' => $section->get_id(),
					'orderby'   => 'menu_order',
					'order'     => 'ASC',
					'limit'     => -1,
				)
			);

			foreach ( $lessons as $lesson ) {
				$content = masteriyo_openai_html_to_text( $lesson->get_description() );
				$parts[] = 'Lesson: ' . $lesson->get_name() . ( $content ? "\n" . $content : '' );

				$summary['lessons'][ $lesson->get_name() ] = mb_strlen( $content );
			}
		}

		$context = implode( "\n\n", $parts );

		$summary['total_chars'] = mb_strlen( $context );

		$context = masteriyo_openai_cap_context( $context );

		$summary['truncated'] = mb_strlen( $context ) < $summary['total_chars'];

		// Lengths only — lesson content must not land in the logs.
		masteriyo_get_logger()->debug(
			sprintf( 'AI section grounding context for section #%d: %s', $section_id, wp_json_encode( $summary ) ),
			array( 'source' => 'openai' )
		);

		return $context;
	}
}

if ( ! function_exists( 'masteriyo_get_quiz_ai_context' ) ) {
	/**
	 * Assemble the course material a quiz's questions should be grounded in:
	 * the parent section's context and the quiz's existing questions (as a
	 * do-not-repeat list).
	 *
	 * @param \Masteriyo\Models\Quiz $quiz
	 *
	 * @return string Plain-text context, capped for cost control.
	 */
	function masteriyo_get_quiz_ai_context( $quiz ) {
		$parts = array( masteriyo_get_section_ai_context( $quiz->get_course_id(), $quiz->get_parent_id() ) );

		$existing = array_map(
			function ( $question ) {
				/** @var \Masteriyo\Models\Question\Question $question */
				return $question->get_name();
			},
			masteriyo_get_quiz_questions( $quiz->get_id() )
		);

		if ( $existing ) {
			$parts[] = 'Existing questions in this quiz (do not repeat them): ' . implode( '; ', $existing );
		}

		// Re-cap: the existing-questions list lands after the section
		// helper's own cap, and the cost ceiling covers the whole context.
		return masteriyo_openai_cap_context( implode( "\n\n", array_filter( $parts ) ) );
	}
}

if ( ! function_exists( 'masteriyo_openai_parse_question' ) ) {
	/**
	 * Parse one AI-generated question into question-model fields.
	 *
	 * @param array $ques Raw question data from the AI response.
	 *
	 * @return array|null Fields (name, type, answers, points, max_character), or null without a title.
	 */
	function masteriyo_openai_parse_question( $ques ) {
		$title = isset( $ques['question'] ) && ! is_array( $ques['question'] ) ? sanitize_text_field( $ques['question'] ) : '';

		if ( empty( $title ) ) {
			return null;
		}

		$choices = isset( $ques['choices'] ) && is_array( $ques['choices'] ) ? $ques['choices'] : array();
		$correct = isset( $ques['correct'] ) ? $ques['correct'] : '';
		// Multiple-choice answers arrive as an array; sanitizing it as a string
		// would silently blank the whole answer key.
		$correct       = is_array( $correct ) ? array_map( 'sanitize_text_field', $correct ) : sanitize_text_field( $correct );
		$question_type = isset( $ques['question_type'] ) ? sanitize_text_field( $ques['question_type'] ) : '';

		return array(
			'name'          => $title,
			'type'          => $question_type,
			'answers'       => masteriyo_openai_format_answers( $question_type, $choices, $correct ),
			'points'        => isset( $ques['points'] ) ? absint( $ques['points'] ) : 1,
			'max_character' => isset( $ques['max_character'] ) ? absint( $ques['max_character'] ) : 0,
		);
	}
}

if ( ! function_exists( 'masteriyo_openai_create_quiz' ) ) {
	/**
	 * Helper method to create quizzes
	 *
	 * @since 1.6.15
	 *
	 * @param \Masteriyo\Models\Course $course
	 * @param \Masteriyo\Models\Section $section
	 * @param array $section_quiz The array of quiz data.
	 * @param int $i The menu order of the quiz.
	 */
	function masteriyo_openai_create_quiz( \Masteriyo\Models\Course $course, $section, $section_quiz, $i ) {
		$title = isset( $section_quiz['title'] ) ? sanitize_text_field( $section_quiz['title'] ) : '';

		if ( ! $title ) {
			return;
		}

		$description = isset( $section_quiz['description'] ) ? wp_kses_post( $section_quiz['description'] ) : '';
		$pass_mark   = isset( $section_quiz['pass_mark'] ) ? absint( $section_quiz['pass_mark'] ) : 40;
		$full_mark   = isset( $section_quiz['full_mark'] ) ? absint( $section_quiz['full_mark'] ) : 60;

		/** @var \Masteriyo\Models\Quiz */
		$quiz = masteriyo( 'quiz' );
		$quiz->set_parent_id( $section->get_id() );
		$quiz->set_course_id( $course->get_id() );
		$quiz->set_menu_order( $i );
		$quiz->set_name( $title );
		$quiz->set_description( $description );
		$quiz->set_pass_mark( $pass_mark );
		$quiz->set_full_mark( $full_mark );
		$quiz->save();

		if ( $quiz->get_id() && isset( $section_quiz['questions'] ) && is_array( $section_quiz['questions'] ) && ! empty( $section_quiz['questions'] ) ) {
			$j = 0;

			foreach ( $section_quiz['questions'] as $ques ) {
				++$j;
				masteriyo_openai_create_question( $course, $quiz, $ques, $j );
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_openai_create_question' ) ) {
	/**
	 * Helper method to create questions.
	 *
	 * @since 1.6.15
	 *
	 * @param \Masteriyo\Models\Course $course
	 * @param \Masteriyo\Models\Quiz $quiz
	 * @param array $ques The array of question data.
	 * @param int $menu_order The menu order of the question.
	 *
	 * @return void
	 */
	function masteriyo_openai_create_question( $course, $quiz, $ques, $menu_order ) {
		$parsed = masteriyo_openai_parse_question( $ques );

		if ( ! $parsed ) {
			return;
		}

		/** @var \Masteriyo\Models\Question */
		$question = masteriyo( 'question' );

		$question->set_parent_id( $quiz->get_id() );
		$question->set_course_id( $course->get_id() );
		$question->set_menu_order( $menu_order );
		$question->set_name( $parsed['name'] );
		$question->set_type( $parsed['type'] );
		$question->set_answers( $parsed['answers'] );
		$question->set_points( $parsed['points'] );

		// Set max_character for text-answer questions
		if ( QuestionType::TEXT_ANSWER === $parsed['type'] ) {
			$question->update_meta_data( '_max_character', $parsed['max_character'] );
		}

		$question->save();
	}
}

if ( ! function_exists( 'masteriyo_openai_format_answers' ) ) {
	/**
	 * Helper method to format the answers.
	 *
	 * @since 1.6.15
	 *
	 * @param $question_type
	 * @param $choices
	 * @param $correct
	 *
	 * @return array
	 */
	function masteriyo_openai_format_answers( $question_type, $choices, $correct ) {
		switch ( $question_type ) {
			case QuestionType::TRUE_FALSE:
				return array(
					array(
						'name'    => 'true',
						'correct' => 'true' === $correct,
					),
					array(
						'name'    => 'false',
						'correct' => 'false' === $correct,
					),
				);
			case QuestionType::SINGLE_CHOICE:
			case QuestionType::MULTIPLE_CHOICE:
				$formatted_answers = array_map(
					function ( $choice ) use ( $correct ) {
						// $correct is already sanitized upstream; compare like with like,
						// or choices such as '2 < 3' never match their own answer key.
						$choice = sanitize_text_field( $choice );

						return array(
							'name'    => $choice,
							'correct' => is_array( $correct ) ? in_array( $choice, $correct, true ) : ( $correct === $choice ),
						);
					},
					$choices
				);

				// Fallback: Ensure at least one answer is marked as correct
				$has_correct_answer = false;
				foreach ( $formatted_answers as $answer ) {
					if ( isset( $answer['correct'] ) && $answer['correct'] ) {
						$has_correct_answer = true;
						break;
					}
				}

				// If no answer is marked as correct, mark appropriate defaults
				if ( ! $has_correct_answer && ! empty( $formatted_answers ) ) {
					if ( QuestionType::MULTIPLE_CHOICE === $question_type && count( $formatted_answers ) >= 2 ) {
						// For multiple choice, mark first two as correct by default
						$formatted_answers[0]['correct'] = true;
						$formatted_answers[1]['correct'] = true;
					} else {
						// For single choice, mark first one as correct
						$formatted_answers[0]['correct'] = true;
					}
				}

				return $formatted_answers;
			case QuestionType::TEXT_ANSWER:
				// Text answer questions don't need pre-defined answers
				return array( array() );
			case QuestionType::MATCHING:
				// For matching questions, format as prompt and match pairs like TextToText format
				if ( is_array( $choices ) ) {
					return array_map(
						function ( $choice ) {
							return array(
								'prompt' => isset( $choice['prompt'] ) ? sanitize_text_field( $choice['prompt'] ) : ( isset( $choice['left'] ) ? sanitize_text_field( $choice['left'] ) : '' ),
								'match'  => isset( $choice['match'] ) ? sanitize_text_field( $choice['match'] ) : ( isset( $choice['right'] ) ? sanitize_text_field( $choice['right'] ) : '' ),
								'type'   => 'TextToText',
							);
						},
						$choices
					);
				}
				return array();
			case QuestionType::SORTABLE:
				// For sortable questions, format as name items (the order is determined by the array order)
				if ( is_array( $choices ) ) {
					return array_map(
						function ( $choice ) {
							return array(
								'name' => is_array( $choice ) && isset( $choice['name'] ) ? sanitize_text_field( $choice['name'] ) : sanitize_text_field( $choice ),
							);
						},
						$choices
					);
				}
				return array();
			case QuestionType::FILL_IN_THE_BLANKS:
				// For fill-in-the-blanks, the answer is a string with {{ }} placeholders
				// Return the text with blanks, not an array
				return is_string( $correct ) ? $correct : ( is_array( $choices ) && count( $choices ) > 0 ? $choices[0] : '' );
			default:
				return array();
		}
	}
}

if ( ! function_exists( 'masteriyo_openai_models' ) ) {
	/**
	 * The OpenAI models the integration can use.
	 *
	 * Keyed by the API model id. Each entry carries:
	 * - label:   Name shown in the settings picker.
	 * - params:  Request params this model accepts, sent verbatim (models
	 *            reject params they do not support, so nothing is sent unconditionally).
	 * - json:    Whether the model supports response_format json_object.
	 * - efforts: Valid reasoning_effort values, empty/absent when unsupported.
	 *
	 * @return array<string, array{label: string, params: array, json: bool, efforts: array}>
	 */
	function masteriyo_openai_models() {
		// Reasoning tokens count against max_completion_tokens, so the cap is
		// well above the old 3000.
		$efforts = array( 'none', 'low', 'medium', 'high', 'xhigh' );

		$models = array(
			'gpt-5.6-terra' => array(
				'label'   => 'GPT-5.6 Terra',
				'params'  => array( 'max_completion_tokens' => 8000 ),
				'json'    => true,
				'efforts' => $efforts,
			),
			'gpt-5.6-luna'  => array(
				'label'   => 'GPT-5.6 Luna',
				'params'  => array( 'max_completion_tokens' => 8000 ),
				'json'    => true,
				'efforts' => $efforts,
			),
			'gpt-5.6-sol'   => array(
				'label'   => 'GPT-5.6 Sol',
				'params'  => array( 'max_completion_tokens' => 8000 ),
				'json'    => true,
				'efforts' => $efforts,
			),
		);

		/**
		 * Filters the OpenAI models available to the integration.
		 *
		 * Add an entry (same shape as above) to offer a model that is not in
		 * the built-in list, or unset one to remove it from the picker.
		 *
		 * @param array $models The models, keyed by API model id.
		 */
		$filtered = apply_filters( 'masteriyo_openai_models', $models );

		// A filter that empties the list would make every request send an
		// empty model id; keep the built-ins instead.
		return is_array( $filtered ) && count( $filtered ) ? $filtered : $models;
	}
}

if ( ! function_exists( 'masteriyo_openai_default_model' ) ) {
	/**
	 * The model used when none is configured or the configured one is gone.
	 *
	 * @return string API model id.
	 */
	function masteriyo_openai_default_model() {
		return 'gpt-5.6-terra';
	}
}

if ( ! function_exists( 'masteriyo_openai_request_data' ) ) {
	/**
	 * Build the per-call request data from the configured model's entry.
	 *
	 * @param bool $expects_json Whether this call expects a JSON payload back.
	 *
	 * @return array Model id plus the params, response_format and
	 *               reasoning_effort that model supports.
	 */
	function masteriyo_openai_request_data( $expects_json = false ) {
		$models = masteriyo_openai_models();
		$model  = masteriyo_get_setting( 'advance.openai.model' );

		if ( ! is_string( $model ) || ! isset( $models[ $model ] ) ) {
			$model = masteriyo_openai_default_model();
		}

		if ( ! isset( $models[ $model ] ) ) {
			reset( $models );
			$model = (string) key( $models );
		}

		$entry = isset( $models[ $model ] ) ? $models[ $model ] : array();
		$data  = array( 'model' => $model );

		if ( isset( $entry['params'] ) && is_array( $entry['params'] ) ) {
			$data = array_merge( $data, $entry['params'] );
		}

		if ( $expects_json && ! empty( $entry['json'] ) ) {
			$data['response_format'] = array( 'type' => 'json_object' );
		}

		$effort = masteriyo_get_setting( 'advance.openai.reasoning_effort' );

		if ( is_string( $effort ) && isset( $entry['efforts'] ) && in_array( $effort, (array) $entry['efforts'], true ) ) {
			$data['reasoning_effort'] = $effort;
		}

		return $data;
	}
}

if ( ! function_exists( 'masteriyo_ai_decode_json' ) ) {
	/**
	 * Decode a JSON payload from an AI response, tolerating markdown fences.
	 *
	 * Models often wrap JSON in ```json fences; raw json_decode() turns that
	 * into null, which the callers treat as an empty-but-successful response.
	 *
	 * @param mixed $text The response text.
	 *
	 * @return array|null Decoded array, or null when nothing decodable.
	 */
	function masteriyo_ai_decode_json( $text ) {
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return null;
		}

		$decoded = json_decode( $text, true );

		// Fence stripping only as a fallback: valid JSON may legitimately
		// contain ``` inside a string value. Try each fenced block until one
		// decodes — the response may hold a fenced example after the JSON.
		if ( ! is_array( $decoded ) && preg_match_all( '/```[\w+-]*\s*(.*?)```/s', $text, $matches ) ) {
			foreach ( $matches[1] as $candidate ) {
				$decoded = json_decode( trim( $candidate ), true );

				if ( is_array( $decoded ) ) {
					break;
				}
			}
		}

		return is_array( $decoded ) ? $decoded : null;
	}
}

if ( ! function_exists( 'masteriyo_ai' ) ) {
	/**
	 * The AI provider serving the plugin's generation features.
	 *
	 * @return \Masteriyo\AI\Provider
	 */
	function masteriyo_ai() {
		/**
		 * Filters the AI provider.
		 *
		 * The one seam for swapping the backend: return any other
		 * \Masteriyo\AI\Provider subclass (e.g. one backed by the
		 * WordPress core AI Client) to serve all AI features with it.
		 *
		 * @param \Masteriyo\AI\Provider $provider The provider, OpenAI by default.
		 */
		return apply_filters( 'masteriyo_ai_provider', masteriyo( 'openai' ) );
	}
}
