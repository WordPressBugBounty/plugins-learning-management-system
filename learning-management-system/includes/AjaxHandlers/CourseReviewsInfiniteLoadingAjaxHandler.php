<?php
/**
 * Ajax handler for infinite loading course reviews.
 *
 * @since 1.5.9
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

use Masteriyo\Abstracts\AjaxHandler;

class CourseReviewsInfiniteLoadingAjaxHandler extends AjaxHandler {

	/**
	 * Ajax action name.
	 *
	 * @since 1.5.9
	 *
	 * @var string
	 */
	public $action = 'masteriyo_course_reviews_infinite_loading';

	/**
	 * Register ajax handler.
	 *
	 * @since 1.5.9
	 */
	public function register() {
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'process' ) );
		add_action( "wp_ajax_{$this->action}", array( $this, 'process' ) );
	}

	/**
	 * Process ajax request.
	 *
	 * @since 1.5.9
	 */
	public function process() {
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce is required.', 'learning-management-system' ),
				),
				400
			);
			return;
		}

		try {
			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'masteriyo_course_reviews_infinite_loading_nonce' ) ) {
				throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			}

			$this->validate_request();

			$page      = absint( $_REQUEST['page'] );
			$course_id = absint( $_REQUEST['course_id'] );
			$search    = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
			$rating    = isset( $_REQUEST['rating'] ) ? absint( $_REQUEST['rating'] ) : 0;

			/**
			 * Filters course reviews list html for a page while infinite loading.
			 *
			 * @since 1.5.9
			 *
			 * @since 1.9.3 Added $search and rating.
			 *
			 * @param string $html The course reviews html.
			 * @param integer $course_id Course ID.
			 * @param integer $page Current page number.
			 * @param string $search Search query.
			 * @param integer $rating Rating.
			 */
			$html = apply_filters(
				'masteriyo_course_reviews_infinite_loading_page_html',
				masteriyo_get_course_reviews_infinite_loading_page_html( $course_id, $page, false, $search, $rating ),
				$course_id,
				$page
			);

			$per_page              = apply_filters( 'masteriyo_course_reviews_per_page', 5 );
			$reviews_details       = masteriyo_get_course_reviews_and_replies( $course_id, $page, $per_page, $search, $rating );
			$view_load_more_button = $reviews_details['viewed_total'] / $page;

			wp_send_json_success(
				array(
					'html'                  => $html,
					'view_load_more_button' => $view_load_more_button > 1 ? true : false,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				),
				400
			);
		}
	}

	/**
	 * Validate ajax request.
	 *
	 * @since 1.5.9
	 */
	protected function validate_request() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'masteriyo_course_reviews_infinite_loading_nonce' ) ) {
			throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
		}
		if ( ! isset( $_REQUEST['page'] ) ) {
			throw new \Exception( __( 'Page number is required.', 'learning-management-system' ) );
		}
		if ( ! isset( $_REQUEST['course_id'] ) ) {
			throw new \Exception( __( 'Course ID is required.', 'learning-management-system' ) );
		}

		$course = masteriyo_get_course( absint( $_REQUEST['course_id'] ) );

		if ( is_null( $course ) ) {
			throw new \Exception( __( 'Invalid course ID.', 'learning-management-system' ) );
		}

		/**
		 * Filters validation result for course reviews infinite loading ajax request.
		 * Return true for valid. Return \Throwable instance for error.
		 *
		 * @since 1.5.9
		 *
		 * @param boolean $is_valid True for valid. Return \Throwable instance for error.
		 */
		$validation = apply_filters( 'masteriyo_validate_course_reviews_infinite_loading_ajax_request', true );

		if ( $validation instanceof \Throwable ) {
			throw $validation;
		}

		return true;
	}
}
