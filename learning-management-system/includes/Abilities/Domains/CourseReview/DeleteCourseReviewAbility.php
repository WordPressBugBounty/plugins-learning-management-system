<?php
/**
 * Delete Course Review ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseReview
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseReview;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: permanently delete a course review.
 *
 * Course reviews have no trash state — this operation is always permanent.
 * Requires the `edit_course_reviews` capability.
 *
 * @since x.x.x
 */
class DeleteCourseReviewAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'course_review.rest';
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
		return 'courses/reviews';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/course-review-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Course Review', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Delete a course review permanently.', 'learning-management-system' );
	}
}
