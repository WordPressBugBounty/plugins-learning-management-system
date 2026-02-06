<?php
/**
 * Courses filter and sorting AJAX handler.
 *
 * @since 2.5.18
 *
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\AjaxHandler;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

/**
 * Courses filter and sorting AJAX handler.
 *
 * @since 2.5.18
 */
class CourseFilterAndSortingAjaxHandler extends AjaxHandler {

	/**
	 * AJAX action name.
	 *
	 * @since 2.5.18
	 *
	 * @var string
	 */
	public $action = 'masteriyo_course_filter_and_sorting';

	/**
	 * Register the AJAX action.
	 *
	 * @since 2.5.18
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'process' ) );
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'process' ) );
	}

	/**
	 * Process the AJAX request.
	 *
	 * @since 2.5.18
	 */
	public function process() {
		// Bail early if there no nonce.
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce is required.', 'learning-management-system' ),
				)
			);
		}

		try {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'masteriyo_course_filter_and_sorting_nonce' ) ) {
				throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			}

			$args      = $this->prepare_query_args();
			$query     = new \WP_Query( $args );
			$courses   = array_filter( array_map( 'masteriyo_get_course', $query->posts ) );
			$fragments = array();

			/**
			 * Filters courses that will be rendered in the course filter and sorting AJAX call.
			 *
			 * @since 2.5.18
			 *
			 * @param Masteriyo\Models\Course[] $courses The courses objects.
			 */
			$courses = apply_filters( 'masteriyo_course_filter_and_sorting_ajax_courses_result', $courses );

			masteriyo_set_loop_prop( 'columns', masteriyo_get_setting( 'course_archive.display.per_row' ) );

			$layout_param = isset( $_POST['layout'] ) ? sanitize_text_field( $_POST['layout'] ) : 'default'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( 'layout_1' === $layout_param ) {
				$GLOBALS['masteriyo_block_template'] = 'layout1';
			} elseif ( 'layout_2' === $layout_param ) {
				$GLOBALS['masteriyo_block_template'] = 'layout2';
			}

			\ob_start();

			if ( count( $courses ) > 0 ) {
				$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

				/**
				 * Fires before course loop in courses shortcode.
				 *
				 * @since 1.0.0
				 *
				 * @param \Masteriyo\Models\Course[] $courses The courses objects.
				 */
				do_action( 'masteriyo_before_courses_loop', $courses );

				masteriyo_course_loop_start();
				$layout = isset( $_POST['layout'] ) ? sanitize_text_field( $_POST['layout'] ) : 'default'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$layout = preg_match( '/^layout_(\d+)$/', $layout, $matches ) ? $matches[1] : '';
				foreach ( $courses as $course ) {
					$GLOBALS['course'] = $course;
					$course_part       = 'course' . ( '' !== $layout ? '-' . $layout : '' );

					\masteriyo_get_template_part( 'content', $course_part );
				}

				$GLOBALS['course'] = $original_course;

				masteriyo_course_loop_end();

				/**
				 * Fires after course loop in courses shortcode.
				 *
				 * @since 1.0.0
				 *
				 * @param \Masteriyo\Models\Course[] $courses The courses objects.
				 */
				do_action( 'masteriyo_after_courses_loop', $courses );

				masteriyo_reset_loop();
			} else {
				/**
				 * Fires when there is no course to display in courses shortcode.
				 *
				 * @since 1.0.0
				 */
				do_action( 'masteriyo_no_courses_found' );
			}

			$fragments['.masteriyo-courses-wrapper'] = \ob_get_clean();
			$fragments['ul.page-numbers']            = masteriyo_paginate_links(
				array(
					'type'      => 'list',
					'prev_text' => masteriyo_get_svg( 'left-arrow' ),
					'next_text' => masteriyo_get_svg( 'right-arrow' ),
					'base'      => masteriyo_get_page_permalink( 'courses' ) . '%_%',
				),
				$query
			);

			if ( empty( $fragments['ul.page-numbers'] ) ) {
				$fragments['ul.page-numbers'] = '<ul class="page-numbers"></ul>';
			}

			/**
			 * Filters fragments that will be returned to course filter and sorting AJAX request.
			 *
			 * @since 2.5.18
			 *
			 * @param array $fragments
			 * @param array $args
			 * @param \WP_Query $query
			 */
			apply_filters( 'masteriyo_course_filter_and_sorting_ajax_fragments', $fragments, $args, $query );

			wp_send_json_success(
				array(
					'fragments' => $fragments,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Prepare query args for the course list query.
	 *
	 * @since 2.5.18
	 *
	 * @throws \Exception
	 *
	 * @return array
	 */
	protected function prepare_query_args() {
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'masteriyo_course_filter_and_sorting_nonce' ) ) {
			throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
		}

		$args = array(
			'post_type'      => PostType::COURSE,
			'post_status'    => PostStatus::PUBLISH,
			'posts_per_page' => masteriyo_get_setting( 'course_archive.display.per_page' ),
			'paged'          => 1,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'tax_query'      => array(
				'relation' => 'AND',
			),
			'meta_query'     => array(
				'relation' => 'AND',
			),
		);

		$this->add_search_filter_args( $args );
		$this->add_courses_order_args( $args );
		$this->add_categories_filter_args( $args );
		$this->add_difficulties_filter_args( $args );
		$this->add_price_type_filter_args( $args );
		$this->add_rating_filter_args( $args );
		$this->add_pagination_args( $args );
		$this->add_price_range_filter_args( $args );

		/**
		 * Filters the prepared query args for the course list query.
		 *
		 * @since 2.5.18
		 *
		 * @param array $args
		 */
		return apply_filters( 'masteriyo_course_filter_ajax_prepare_query_args', $args );
	}

	/**
	 * Add query args for search filter.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_search_filter_args( &$args ) {
		if ( ! empty( $_POST['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['s'] = masteriyo_clean( (string) wp_unslash( $_POST['search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Add query args for categories filter.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_categories_filter_args( &$args ) {
		if ( ! empty( $_POST['categories'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$category_ids        = array_filter( array_map( 'absint', (array) $_POST['categories'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['tax_query'][] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $category_ids,
			);
		}
	}

	/**
	 * Add query args for difficulties filter.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_difficulties_filter_args( &$args ) {
		if ( ! empty( $_POST['difficulties'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$difficulty_ids      = array_filter( array_map( 'absint', (array) $_POST['difficulties'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['tax_query'][] = array(
				'taxonomy' => Taxonomy::COURSE_DIFFICULTY,
				'terms'    => $difficulty_ids,
			);
		}
	}

	/**
	 * Add query args for price type filter.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_price_type_filter_args( &$args ) {
		if ( ! empty( $_POST['price-type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$price_type = sanitize_text_field( $_POST['price-type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( in_array( $price_type, array( 'free', 'paid' ), true ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => Taxonomy::COURSE_VISIBILITY,
					'field'    => 'name',
					'terms'    => $price_type,
				);
			}
		}
	}

	/**
	 * Add query args for rating filter.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_rating_filter_args( &$args ) {
		if ( ! empty( $_POST['rating'] ) && is_array( $_POST['rating'] ) ) {
			$ratings = array_filter(
				array_map( 'intval', $_POST['rating'] ),
				function( $val ) {
					return $val > 0;
				}
			);

			if ( ! empty( $ratings ) ) {
				$rating_meta_queries = array();

				foreach ( $ratings as $rating ) {
					$rating_meta_queries[] = array(
						'key'     => '_average_rating',
						'value'   => $rating,
						'compare' => '=',
						'type'    => 'NUMERIC',
					);
				}

				$args['meta_query'][] = array_merge(
					array( 'relation' => 'OR' ),
					$rating_meta_queries
				);
			}
		}
	}



	/**
	 * Add query args for pagination.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_pagination_args( &$args ) {
		if ( ! empty( $_POST['page'] ) && absint( $_POST['page'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['paged'] = absint( $_POST['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Add query args for price range.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_price_range_filter_args( &$args ) {
		$price_from = isset( $_POST['price-from'] ) ? $_POST['price-from'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$price_to   = isset( $_POST['price-to'] ) ? $_POST['price-to'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Sort min/max prices, 'price-from' should be smaller and 'price-to' should be larger.
		if ( is_numeric( $price_from ) && is_numeric( $price_to ) && $price_from > $price_to ) {
			$temp       = $price_to;
			$price_to   = $price_from;
			$price_from = $temp;
		}

		// Add price_from filter.
		if ( is_numeric( $price_from ) ) {
			$args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => floatval( $price_from ),
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		// Add price_to filter.
		if ( is_numeric( $price_to ) ) {
			$args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => floatval( $price_to ),
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
	}

	/**
	 * Add query args for ordering courses.
	 *
	 * @since 2.5.18
	 *
	 * @param array $args
	 */
	protected function add_courses_order_args( &$args ) {
		if ( empty( $_POST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$orderby = masteriyo_clean( (string) wp_unslash( $_POST['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$order   = masteriyo_clean( (string) wp_unslash( $_POST['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		switch ( $orderby ) {
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;

			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price';
				$args['order']    = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'rating':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_average_rating';
				$args['order']    = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
		}
	}
}
