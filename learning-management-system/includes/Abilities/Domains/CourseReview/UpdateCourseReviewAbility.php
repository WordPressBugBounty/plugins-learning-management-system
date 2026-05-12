<?php
/**
 * Update Course Review ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseReview
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseReview;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing course review.
 *
 * Supports partial updates. Moderators use this to approve or reject reviews
 * (update `status`), or to edit the comment body.
 * Requires the `edit_course_reviews` capability for other authors' reviews.
 *
 * @since x.x.x
 */
class UpdateCourseReviewAbility extends RestProxyAbility {

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
		return 'update';
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
		return 'masteriyo/course-review-update';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Update Course Review', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Update an existing course review (approve, reject, or edit content).', 'learning-management-system' );
	}
}
