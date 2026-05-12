<?php
/**
 * Delete Course Q&A ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseQA
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseQA;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: move a course Q&A entry to trash.
 *
 * Trashed entries can be restored via the masteriyo/course-qa-restore ability.
 * Requires the `edit_course_qas` capability.
 *
 * @since x.x.x
 */
class DeleteCourseQAAbility extends RestProxyAbility {

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
		return 'delete';
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
		return 'masteriyo/course-qa-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Course Q&A', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Move a course Q&A entry to trash. Requires id (Q&A entry ID). Trashed entries can be recovered with masteriyo/course-qa-restore. Use force=true to permanently delete. Returns the deleted entry.', 'learning-management-system' );
	}
}
