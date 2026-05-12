<?php
/**
 * List Course Reviews ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseReview
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseReview;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of course reviews.
 *
 * Supports filtering by course ID and review status (approved, pending, spam).
 * Returns reviewer name, rating, comment, and approval state.
 *
 * @since x.x.x
 */
class ListCourseReviewsAbility extends RestProxyAbility {

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
		return 'list';
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
		return 'masteriyo/course-review-list';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'List Course Reviews', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course reviews, optionally filtered by course or status.', 'learning-management-system' );
	}
}
