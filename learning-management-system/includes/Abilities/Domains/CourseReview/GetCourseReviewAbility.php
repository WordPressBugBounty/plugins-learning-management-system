<?php
/**
 * Get Course Review ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseReview
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseReview;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single course review by ID.
 *
 * Returns the full review including reviewer, rating, comment body, and status.
 *
 * @since x.x.x
 */
class GetCourseReviewAbility extends RestProxyAbility {

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
		return 'get';
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
		return 'masteriyo/course-review-get';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Get Course Review', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Retrieve a single course review by its ID. Requires id (review ID). Returns the review including reviewer name, rating (1–5), comment body, status (approved, hold, spam), and course_id.', 'learning-management-system' );
	}
}
