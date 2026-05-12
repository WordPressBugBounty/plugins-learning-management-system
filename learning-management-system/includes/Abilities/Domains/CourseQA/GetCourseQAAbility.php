<?php
/**
 * Get Course Q&A ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseQA
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseQA;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course Q&A entry by ID.
 *
 * Returns the entry including content, author, status, and parent (if it is
 * an answer rather than a top-level question).
 * Requires the `read_course_qas` capability.
 *
 * @since x.x.x
 */
class GetCourseQAAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/course-qa-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Q&A', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course Q&A entry by its ID. Requires id (Q&A entry ID). Returns the entry including content, author, status, course_id, and parent (set when the entry is an answer to another question).', 'learning-management-system' );
	}
}
