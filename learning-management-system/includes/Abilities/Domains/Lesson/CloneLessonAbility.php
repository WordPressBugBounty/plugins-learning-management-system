<?php
/**
 * Clone Lesson ability.
 *
 * @package Masteriyo\Abilities\Domains\Lesson
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Lesson;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: duplicate a lesson into the same section.
 *
 * Creates an identical copy of the lesson — title, content, and video settings —
 * appended after the original in the same parent section.
 * Requires the `edit_masteriyo_courses` capability.
 *
 * @since x.x.x
 */
class CloneLessonAbility extends RestProxyAbility {

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
		return 'clone';
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
		return 'masteriyo/lesson-clone';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Clone Lesson', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Duplicate a lesson, including its content, into the same section.', 'learning-management-system' );
	}
}
