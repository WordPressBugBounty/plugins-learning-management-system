<?php
/**
 * Helper functions for course reviews.
 *
 * @since 1.5.9
 */

use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;

if ( ! function_exists( 'masteriyo_get_course_reviews_infinite_loading_pages_count' ) ) {
	/**
	 * Get count of pages for course reviews infinite loading.
	 *
	 * @since 1.5.9
	 *
	 * @param integer|string|\Masteriyo\Models\Course|\WP_Post $course_id Course ID or object.
	 *
	 * @return integer
	 */
	function masteriyo_get_course_reviews_infinite_loading_pages_count( $course_id ) {
		/**
		 * Filters maximum course reviews per page.
		 *
		 * @since 1.5.9
		 *
		 * @param integer $per_page Course reviews per page.
		 */
		$per_page = apply_filters( 'masteriyo_course_reviews_per_page', 5 );
		$course   = masteriyo_get_course( $course_id );

		if ( is_null( $course ) ) {
			/**
			 * Filters the count of pages for course reviews infinite loading.
			 *
			 * @since 1.5.9
			 *
			 * @param integer $count The count.
			 */
			return apply_filters( 'masteriyo_course_review_pages_count', 0 );
		}

		$result = masteriyo_get_course_reviews(
			array(
				'course_id' => $course->get_id(),
				'status'    => array( CommentStatus::APPROVE_STR, CommentStatus::TRASH ),
				'per_page'  => $per_page,
				'page'      => 1,
				'paginate'  => true,
				'parent'    => 0,
			)
		);

		/**
		 * Filters the count of pages for course reviews infinite loading.
		 *
		 * @since 1.5.9
		 *
		 * @param integer $count The count.
		 */
		return apply_filters( 'masteriyo_course_review_pages_count', $result->max_num_pages );
	}
}

if ( ! function_exists( 'masteriyo_get_replies_of_course_reviews' ) ) {
	/**
	 * Get replies of course reviews.
	 *
	 * @since 1.5.9
	 *
	 * @param integer[] $review_ids Review Ids.
	 *
	 * @return array
	 */
	function masteriyo_get_replies_of_course_reviews( $review_ids ) {
		$replies = masteriyo_get_course_reviews(
			array(
				'status'     => array( 'approve', 'trash' ),
				'parent__in' => $review_ids,
			)
		);

		/**
		 * Filters replies of course reviews.
		 *
		 * @since 1.5.9
		 *
		 * @param \Masteriyo\Models\CourseReview $replies Replies for the given course reviews.
		 * @param integer[] $review_ids Course review IDs.
		 */
		return apply_filters( 'masteriyo_replies_of_course_reviews', $replies, $review_ids );
	}
}

if ( ! function_exists( 'masteriyo_get_course_reviews_infinite_loading_page_html' ) ) {
	/**
	 * Get html for a list of course reviews equivalent to one page for infinite loading.
	 *
	 * @since 1.5.9
	 *
	 * @param integer|\Masteriyo\Models\Course|\WP_Post $course_id Course ID.
	 * @param integer $page Page number.
	 * @param boolean $echo Whether to echo the html or not.
	 *
	 * @return array|void
	 */
	function masteriyo_get_course_reviews_infinite_loading_page_html( $course_id, $page = 1, $echo = false, $search = '', $rating = '' ) {
		/**
		 * Filters maximum course reviews per page.
		 *
		 * @since 1.5.9
		 *
		 * @param integer $per_page Course reviews per page.
		 */
		$per_page            = apply_filters( 'masteriyo_course_reviews_per_page', 5 );
		$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course_id, $page, $per_page, $search, $rating );
		$course_reviews      = $reviews_and_replies['reviews'];
		$all_replies         = $reviews_and_replies['replies'];

		if ( ! $echo ) {
			ob_start();
		}

		foreach ( $course_reviews as $course_review ) {
			$replies = isset( $all_replies[ $course_review->get_id() ] ) ? $all_replies[ $course_review->get_id() ] : array();

			/**
			 * Renders the template to display review item in single course page's reviews list section.
			 *
			 * @since 1.0.5
			 *
			 * @param \Masteriyo\Models\CourseReview $course_review Course review object.
			 * @param \Masteriyo\Models\CourseReview[] $replies Replies to the course review.
			 * @param integer $course_id Course ID.
			 */
			do_action( 'masteriyo_template_course_review', $course_review, $replies, $course_id );
		}

		if ( $echo ) {
			return;
		}
		return ob_get_clean();
	}
}

if ( ! function_exists( 'masteriyo_get_new_course_reviews_count' ) ) {
	/**
	 * Retrieves the count of new course reviews.
	 *
	 * This function queries the WordPress comments database to count how many
	 * new reviews have been left for courses. A review is considered "new" if
	 * its '_is_new' meta value is set to '1'. It utilizes the WP_Comment_Query
	 * class to perform this operation.
	 *
	 * @since 1.9.0
	 *
	 * @return int The count of new course reviews. Returns 0 on failure or if there are no new reviews.
	 */
	function masteriyo_get_new_course_reviews_count() {
		$args = array(
			'type'       => CommentType::COURSE_REVIEW,
			'count'      => true,
			'meta_query' => array(
				array(
					'key'     => '_is_new',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);

		$comments_query = new \WP_Comment_Query();

		$count = $comments_query->query( $args );

		return is_numeric( $count ) ? (int) $count : 0;
	}
}

if ( ! function_exists( 'masteriyo_is_reviews_enabled_in_learn_page' ) ) {
	/**
	 * Reviews allowed or not in learn page.
	 *
	 * This function returns boolean based on the information provided by global masteriyo setting based on the reviews setting.
	 *
	 *
	 * @since 1.9.2
	 *
	 * @return boolean review allowed true otherwise false.
	 */
	function masteriyo_is_reviews_enabled_in_learn_page() {
		$review_settings_info = masteriyo_get_setting( 'single_course.display' );
		if ( false === $review_settings_info['enable_review'] ) {
			return false;
		} elseif ( true === $review_settings_info['enable_review'] && false === $review_settings_info['enable_review_enrolled_users_only'] ) {
			return true;
		} elseif ( true === $review_settings_info['enable_review'] && true === $review_settings_info['enable_review_enrolled_users_only'] && is_user_logged_in() ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'masteriyo_get_course_review_distribution_by_rating' ) ) {
	/**
	 * Get the percentage distribution of course review ratings.
	 *
	 * Retrieves the percentage of reviews that were rated 1, 2, 3, 4, or 5 stars for the given course ID.
	 *
	 * @since 1.10.0
	 *
	 * @param int $course_id The ID of the course to get the review rating distribution for.
	 *
	 * @return array An associative array containing the percentage of reviews for each rating, with array keys '1', '2', '3', '4', '5'.
	 */
	function masteriyo_get_course_review_distribution_by_rating( $course_id ) {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$ratings = array(
			'5' => '0%',
			'4' => '0%',
			'3' => '0%',
			'2' => '0%',
			'1' => '0%',
		);

		$raw_counts = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT comment_karma, COUNT( * ) as rating_count FROM $wpdb->comments
        WHERE comment_post_ID = %d
        AND comment_approved = '1'
        AND comment_karma > 0
        AND comment_type = %s
        GROUP BY comment_karma
        ",
				$course_id,
				CommentType::COURSE_REVIEW
			)
		);

		if ( ! empty( $raw_counts ) ) {
			$total_reviews = 0;
			foreach ( $raw_counts as $count ) {
				$rating             = (int) $count->comment_karma;
				$ratings[ $rating ] = $count->rating_count;
				$total_reviews     += $count->rating_count;
			}

			if ( $total_reviews > 0 ) {
				foreach ( $ratings as  &$count ) {
					$count = masteriyo_round( ( absint( $count ) / $total_reviews ) * 100 ) . '%';
				}
			} else {
				$ratings = array_fill_keys( array_keys( $ratings ), '0%' );
			}
		}

		return $ratings;
	}
}
