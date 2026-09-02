<?php

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;


/**
 * Class CreateLessonsContentJob
 *
 * This class is responsible for handling the action hook related to the create_lessons_content_job
 *
 * @since 1.6.15
 *
 * @package Masteriyo\Jobs
 */
class CreateLessonsContentJob {
	/**
	 * The unique identifier for scheduling and handling the create_lessons_content_job action.
	 *
	 * @since 1.6.15
	 */
	const NAME = 'masteriyo/job/create_lessons_content_job';

	/**
	 * Register the action hook handler
	 *
	 * @since 1.6.15
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle' ), 10, 4 );
	}

	/**
	 * Handle the action
	 *
	 * Create content for the lessons of a course.
	 *
	 * @since 1.6.15
	 *
	 * @param  int $num_lesson_description_paragraphs
	 * @param string $course_title The title of the course.
	 * @param string $course_idea  The main idea behind the course.
	 * @param int  $course_id      The course ID.
	 */
	public function handle( $num_lesson_description_paragraphs, $course_title, $course_idea, $course ) {

		$course = masteriyo_get_course( $course );

		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return;
		}

		$ai = masteriyo_ai();

		if ( ! $ai->is_configured() ) {
			return;
		}

		if ( 1 > $num_lesson_description_paragraphs ) {
			return;
		}

		try {
			$lessons = masteriyo_get_lessons( array( 'course_id' => $course->get_id() ) );

			if ( ! count( $lessons ) ) {
				return;
			}

			foreach ( $lessons as $lesson ) {
				$lessons_content_prompt = masteriyo_generate_lesson_content_prompt( $lesson, $course_title, $course_idea, $num_lesson_description_paragraphs );
				$response_text          = $ai->generate_text( $lessons_content_prompt, array( 'max_attempts' => 2 ) );

				if ( is_wp_error( $response_text ) ) {
					continue;
				}

				$lesson->set_description( wp_kses_post( $response_text ) );
				$lesson->save();
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}
}
