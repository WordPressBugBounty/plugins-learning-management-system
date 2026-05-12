<?php
/**
 * Create Lesson ability.
 *
 * @package Masteriyo\Abilities\Domains\Lesson
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Lesson;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new lesson inside a section.
 *
 * The parent course ID and section ID must be supplied.
 * Lessons are appended at the end of the section unless menu_order is given.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CreateLessonAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/lesson-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Lesson', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Create a new lesson inside a section.', 'learning-management-system' );
	}
}
