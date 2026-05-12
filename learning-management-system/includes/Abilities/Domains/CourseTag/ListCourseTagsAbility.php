<?php
/**
 * List Course Tags ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseTag
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseTag;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of course tags.
 *
 * Returns taxonomy terms registered under `course_tag`, including
 * name, slug, description, and course count.
 *
 * @since x.x.x
 */
class ListCourseTagsAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_tag.rest';
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
		return 'courses/tags';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-tag-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Course Tags', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course tags. Optionally filter by search term. Returns data array with name, slug, description, and course count per tag, plus pagination metadata.', 'learning-management-system' );
	}
}
