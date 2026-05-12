<?php
/**
 * Create Course Q&A ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseQA
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseQA;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: post a new question or answer in a course Q&A thread.
 *
 * To create a top-level question, omit the `parent` field.
 * To post an answer, set `parent` to the ID of the question being answered.
 * The parent course ID must always be supplied.
 * Requires the `edit_course_qas` capability.
 *
 * @since x.x.x
 */
class CreateCourseQAAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course-qa.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'create';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function rest_base(): string {
		return 'courses/questions-answers';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-qa-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course Q&A', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Post a new question or answer in a course Q&A thread. Requires course_id and content. Omit parent to create a top-level question; set parent to a question ID to post an answer. Returns the created entry with its ID and status.', 'learning-management-system' );
	}
}
