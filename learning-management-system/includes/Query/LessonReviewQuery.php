<?php
/**
 * Class for parameter-based lesson Review querying
 *
 * @package  Masteriyo\Query
 * @since   1.14.0
 */

namespace Masteriyo\Query;

use Masteriyo\Abstracts\ObjectQuery;

defined( 'ABSPATH' ) || exit;

/**
 * lesson query class.
 */
class LessonReviewQuery extends ObjectQuery {

	/**
	 * Valid query vars for lessons reviews.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'lesson_id' => '',
				'status'    => 'all',
			)
		);
	}

	/**
	 * Get lessons reviews matching the current query vars.
	 *
	 * @since 1.14.0
	 *
	 * @return Masteriyo\Models\LessonReview[] Lesson review objects
	 */
	public function get_lessons_reviews() {
		/**
		 * Filters lesson review object query args.
		 *
		 * @since 1.14.0
		 *
		 * @param array $query_args The object query args.
		 */
		$args    = apply_filters( 'masteriyo_lesson_review_object_query_args', $this->get_query_vars() );
		$results = masteriyo( 'lesson_review.store' )->query( $args );

		/**
		 * Filters lesson review object query results.
		 *
		 * @since 1.14.0
		 *
		 * @param Masteriyo\Models\LessonReview[] $results The query results.
		 * @param array $query_args The object query args.
		 */
		return apply_filters( 'masteriyo_lesson_review_object_query', $results, $args );
	}
}
