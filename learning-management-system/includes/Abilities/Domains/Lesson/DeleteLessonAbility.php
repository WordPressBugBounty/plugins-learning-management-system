<?php
/**
 * Delete Lesson ability.
 *
 * @package Masteriyo\Abilities\Domains\Lesson
 */

namespace Masteriyo\Abilities\Domains\Lesson;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: move a lesson to trash or permanently delete it.
 *
 * Without `force=true` the lesson is trashed and can be restored.
 * With `force=true` the lesson is permanently removed.
 * Requires the `edit_masteriyo_courses` capability.
 */
class DeleteLessonAbility extends RestProxyAbility {

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
		return 'delete';
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
		return 'masteriyo/lesson-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Lesson', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Permanently delete a lesson. Pass force=false to move it to the trash instead.';
	}
}
