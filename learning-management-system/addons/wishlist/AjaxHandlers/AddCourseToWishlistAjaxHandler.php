<?php
/**
 * Ajax handler to add course in a user's wishlist.
 *
 * @since 1.12.2
 *
 * @package Masteriyo\Addons\WishList\AjaxHandlers
 */

namespace Masteriyo\Addons\WishList\AjaxHandlers;

use Masteriyo\Abstracts\AjaxHandler;

class AddCourseToWishlistAjaxHandler extends AjaxHandler {

	/**
	 * Ajax action name.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	public $action = 'masteriyo_add_course_to_wishlist';

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

			$course = masteriyo_get_course( absint( $_POST['course_id'] ) );
			$user   = masteriyo_get_current_user();

			if ( is_null( $course ) ) {
				throw new \Exception( __( 'Invalid course ID.', 'learning-management-system' ) );
			}

			if ( masteriyo_current_user_has_course_in_wishlist( $course->get_id() ) ) {
				$response = array(
					'message' => __( 'The course is already in your wishlist.', 'learning-management-system' ),
				);

				/**
				 * Fires when trying to add a course to a wishlist but it has already been added.
				 *
				 * @since 1.12.2
				 *
				 * @param integer $course_id Course ID.
				 * @param integer $user_id ID of the user that the wishlist belongs to.
				 */
				do_action( 'masteriyo_ajax_course_already_added_to_wishlist', $course->get_id(), $user->get_id() );
			} else {
				$wishlist_item = masteriyo_create_wishlist_item_object();

				$wishlist_item->set_course_title( $course->get_name() );
				$wishlist_item->set_course_category_ids( $course->get_category_ids() );
				$wishlist_item->set_course_difficulty( $course->get_difficulty() );
				$wishlist_item->set_course_id( $course->get_id() );
				$wishlist_item->set_author_id( $user->get_id() );
				$wishlist_item->save();

				$response = array(
					/* translators: %1$d: Course ID, %2$s Course Name */
					'message' => sprintf( __( 'Added #%1$d %2$s to wishlist.', 'learning-management-system' ), $course->get_id(), $course->get_name() ),
				);

				/**
				 * Fires after adding a course to wishlist.
				 *
				 * @since 1.12.2
				 *
				 * @param integer $course_id Course ID.
				 * @param integer $user_id ID of the user that the wishlist belongs to.
				 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
				 */
				do_action( 'masteriyo_ajax_after_course_added_to_wishlist', $course->get_id(), $user->get_id(), $wishlist_item );
			}

			$response = wp_parse_args(
				$response,
				array(
					'course' => array(
						'id'   => $course->get_id(),
						'name' => $course->get_name(),
					),
					'user'   => array(
						'id'           => $user->get_id(),
						'display_name' => $user->get_display_name(),
						'roles'        => $user->get_roles(),
					),
				)
			);

			/**
			 * Filters the response for Add Course to Wishlist AJAX endpoint.
			 *
			 * @since 1.12.2
			 *
			 * @param array $response The AJAX response.
			 * @param integer $course_id Course ID.
			 * @param integer $user_id ID of the user that the wishlist belongs to.
			 */
			$response = apply_filters( 'masteriyo_ajax_add_course_to_wishlist_success_response', $response, $course->get_id(), $user->get_id() );

			wp_send_json_success( $response );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				),
				400
			);
		}
	}
}
