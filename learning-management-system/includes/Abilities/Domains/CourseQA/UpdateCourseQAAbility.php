<?php
/**
 * Update Course Q&A ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseQA
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseQA;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing course Q&A entry.
 *
 * Supports partial updates. Moderators use this to edit content or change the
 * status (approve/mark as spam) of a question or answer.
 * Requires the `edit_course_qas` capability.
 *
 * @since x.x.x
 */
class UpdateCourseQAAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/course-qa-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Course Q&A', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing Q&A entry content.', 'learning-management-system' );
	}
}
