<?php
/**
 * Restore Lesson ability.
 *
 * @package Masteriyo\Abilities\Domains\Lesson
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Lesson;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: restore a trashed lesson.
 *
 * Returns the lesson to draft status.
 * Only applies to lessons currently in the trash (post_status = 'trash').
 * Requires the `edit_masteriyo_courses` capability on the target lesson.
 *
 * @since x.x.x
 */
class RestoreLessonAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'lesson.rest';
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
		return 'lessons';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/lesson-restore';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Restore Lesson', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Restore a trashed lesson back to draft state. Requires id (lesson ID). Only applies to lessons currently in the trash. Returns the restored lesson object.', 'learning-management-system' );
	}
}
