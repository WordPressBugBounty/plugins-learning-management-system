<?php
/**
 * Restore Course Q&A ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseQA
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseQA;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: restore a trashed course Q&A entry.
 *
 * Returns the entry to its previous status.
 * Only applies to entries currently in the trash.
 * Requires the `edit_course_qas` capability.
 *
 * @since x.x.x
 */
class RestoreCourseQAAbility extends RestProxyAbility {

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
		return 'restore';
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
		return 'masteriyo/course-qa-restore';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Restore Course Q&A', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Restore a trashed course Q&A entry to its previous status. Requires id (Q&A entry ID). Only applies to entries currently in the trash. Returns the restored entry object.', 'learning-management-system' );
	}
}
