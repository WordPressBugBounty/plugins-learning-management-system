<?php
/**
 * Ajax handler to remove course from a user's wishlist.
 *
 * @since 1.12.2
 *
 * @package Masteriyo\Addons\WishList\AjaxHandlers
 */

namespace Masteriyo\Addons\WishList\AjaxHandlers;

use Masteriyo\Abstracts\AjaxHandler;

class RemoveCourseFromWishlistAjaxHandler extends AjaxHandler {

	/**
	 * Ajax action name.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	public $action = 'masteriyo_remove_course_from_wishlist';

	/**
	 * Register ajax handler.
	 *
	 * @since 1.12.2
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'handle' ) );
	}

	/**
	 * Handle the ajax request.
	 *
	 * @since 1.12.2
	 */
	public function handle() {
		try {
			if ( ! isset( $_POST['_wpnonce'] ) ) {
				throw new \Exception( __( 'Nonce is required.', 'learning-management-system' ) );
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'masteriyo-wishlist-toggle-nonce' ) ) {
				throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			}

			if ( ! is_user_logged_in() ) {
				throw new \Exception( __( 'User must be logged in.', 'learning-management-system' ) );
			}

			$course_id = absint( $_REQUEST['course_id'] );
			$user_id   = get_current_user_id();

			if ( masteriyo_current_user_has_course_in_wishlist( $course_id ) ) {
				$result = masteriyo_remove_course_from_wishlist( $course_id, get_current_user_id() );

				if ( ! $result ) {
					throw new \Exception( __( 'Failed to remove course from wishlist.', 'learning-management-system' ) );
				}

				$response = array(
					'message' => __( 'Removed the course from your wishlist.', 'learning-management-system' ),
				);

				/**
				 * Fires after adding a course to wishlist.
				 *
				 * @since 1.12.2
				 *
				 * @param integer $course_id Course ID.
				 * @param integer $user_id ID of the user that the wishlist belongs to.
				 */
				do_action( 'masteriyo_ajax_after_course_removed_from_wishlist', $course_id, $user_id );
			} else {
				$response = array(
					'message' => __( 'The course is not in your wishlist.', 'learning-management-system' ),
				);

				/**
				 * Fires when trying to remove a course from a wishlist but the course isn't in the wishlist.
				 *
				 * @since 1.12.2
				 *
				 * @param integer $course_id Course ID.
				 * @param integer $user_id ID of the user that the wishlist belongs to.
				 */
				do_action( 'masteriyo_ajax_course_not_in_wishlist', $course_id, $user_id );
			}

			/**
			 * Filters the response for Remove Course from Wishlist AJAX endpoint.
			 *
			 * @since 1.12.2
			 *
			 * @param array $response The AJAX response.
			 * @param integer $course_id Course ID.
			 * @param integer $user_id ID of the user that the wishlist belongs to.
			 */
			$response = apply_filters( 'masteriyo_ajax_remove_course_from_wishlist_success_response', $response, $course_id, $user_id );

			wp_send_json_success( $response );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
}
