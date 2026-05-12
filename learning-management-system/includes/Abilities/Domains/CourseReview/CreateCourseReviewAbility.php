<?php
/**
 * Create Course Review ability.
 *
 * @package Masteriyo\Abilities\Domains\CourseReview
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\CourseReview;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: submit a new course review.
 *
 * The caller must supply the parent course ID, a rating (1–5), and a comment.
 * Reviews may require moderator approval before appearing publicly, depending
 * on the LMS settings.
 *
 * @since x.x.x
 */
class CreateCourseReviewAbility extends RestProxyAbility {

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
		return 'create';
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
		return 'masteriyo/course-review-create';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Create Course Review', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Submit a new course review with rating and comment.', 'learning-management-system' );
	}
}
