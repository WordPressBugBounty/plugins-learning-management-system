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
	 *
	 * @return string Generated prompt.
	 */
	function masteriyo_generate_quiz_questions_prompt( $prompt, $question_type = 'true-false', $num_of_questions = 1, $points = 1 ) {
		if ( ! empty( $course_idea ) ) {
			$prompt .= ", and align them with the theme '{$course_idea}'";
		}

		$prompt .= "{$prompt}, number of questions should be {$num_of_questions}  and question type should \"{$question_type}\". If the question type 'multiple-choice' question, include an array of correct answers. If you opt for 'single-choice' questions, include the correct answer. If you opt for 'true-false' questions, include the correct answer as either 'true' or 'false' under the 'correct' key. Each question should be worth {$points} points.";

		$sample_json_structure = '{"questions":[{"question":"Question Text","choices":"Answer Choices","correct":"Correct Answer","question_type":"Question Type","points":' . $points . '}]}';
		$prompt               .= " Your response should be formatted as a minified JSON object, similar to this example: {$sample_json_structure}";

		return $prompt;
	}
}


/* End prompt generate functions. */

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
				$j++;

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
	 * @param int $j The menu order of the question.
	 *
	 * @return void
	 */
	function masteriyo_openai_create_question( $course, $quiz, $ques, $j ) {
		$title = isset( $ques['question'] ) ? sanitize_text_field( $ques['question'] ) : '';

		if ( empty( $title ) ) {
			return;
		}

		$choices       = isset( $ques['choices'] ) && is_array( $ques['choices'] ) ? $ques['choices'] : array();
		$correct       = isset( $ques['correct'] ) ? sanitize_text_field( $ques['correct'] ) : '';
		$points        = isset( $ques['points'] ) ? absint( $ques['points'] ) : 1;
		$question_type = isset( $ques['question_type'] ) ? sanitize_text_field( $ques['question_type'] ) : '';
		$answers       = masteriyo_openai_format_answers( $question_type, $choices, $correct );

		$question = masteriyo( 'question' );

		$question->set_parent_id( $quiz->get_id() );
		$question->set_course_id( $course->get_id() );
		$question->set_menu_order( $j );
		$question->set_name( $title );
		$question->set_type( $question_type );
		$question->set_answers( $answers );
		$question->set_points( $points );
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
				return array_map(
					function ( $choice ) use ( $correct ) {
						return array(
							'name'    => sanitize_text_field( $choice ),
							'correct' => is_array( $correct ) ? in_array( $choice, $correct, true ) : ( $correct === $choice ),
						);
					},
					$choices
				);
			default:
				return array();
		}
	}
}

if ( ! function_exists( 'masteriyo_openai_retry' ) ) {
	/**
	 * Function to handle retry logic
	 *
	 * @since 1.6.15
	 *
	 * @param callable $func The function to execute and retry.
	 * @param array $args The arguments for the function.
	 * @param int $max_attempts Maximum number of attempts before giving up.
	 *
	 * @return mixed
	 */
	function masteriyo_openai_retry( $func, $args = array(), $max_attempts = 3 ) {
		$attempts      = 0;
		$response_text = '';

		do {
			$response = call_user_func_array( $func, $args );

			if ( is_null( $response ) || is_wp_error( $response ) ) {
				return $response;
			}

			$response_data = json_decode( $response, true );

			if ( is_array( $response_data ) && isset( $response_data['choices'][0]['message']['content'] ) ) {
					$response_text = $response_data['choices'][0]['message']['content'];
			}

			$attempts++;

		} while ( empty( $response_text ) && $attempts < $max_attempts );

		return $response_text;
	}
}


