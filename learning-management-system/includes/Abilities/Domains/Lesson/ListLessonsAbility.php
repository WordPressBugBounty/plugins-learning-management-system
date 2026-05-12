<?php
/**
 * List Lessons ability.
 *
 * @package Masteriyo\Abilities\Domains\Lesson
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Lesson;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of lessons.
 *
 * Supports filtering by course ID or section ID.
 * Results are ordered by menu_order within their parent section.
 *
 * @since x.x.x
 */
class ListLessonsAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/lesson-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Lessons', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of lessons, optionally filtered by course or section.', 'learning-management-system' );
	}
}
