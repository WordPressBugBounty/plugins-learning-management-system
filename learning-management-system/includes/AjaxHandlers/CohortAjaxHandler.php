<?php
/**
 * Course Password Protection Handler
 *
 * Handles AJAX requests for password-protected courses in the Masteriyo plugin.
 *
 * @since 3.1.0
 *
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\AjaxHandler;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\UserCourseQuery;

/**
 * Class CohortAjaxHandler
 *
 * This class is responsible for handling the cohort feature
 * for courses in the Masteriyo plugin.
 */
class CohortAjaxHandler extends AjaxHandler {

	/**
	 * Course Cohort ajax action.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $action = 'masteriyo_course_cohort';

	/**
	 * Register AJAX actions.
	 *
	 * @since 3.1.0
	 *
	 * Hooks the process method to both logged in and not logged in AJAX actions.
	 */
	public function register() {
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'process' ) );
		add_action( "wp_ajax_{$this->action}", array( $this, 'process' ) );
	}

	/**
	 * Process the AJAX request.
	 *
	 * Handles the AJAX request by validating the nonce, password, and course ID.
	 * Sends JSON response back to the client.
	 *
	 * @since 3.1.0
	 */
	public function process() {
		$nonce     = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $this->validate_nonce( $nonce ) || ! is_user_logged_in() ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid Course ID.', 'learning-management-system' ) ),
				405
			);
			return;
		}

			$is_free = ( 'free' === $course->get_price_type() || floatval( $course->get_price() ) <= 0 );

		if ( ! $is_free ) {
			return;
		}

		$user_id = get_current_user_id();
		$query   = new UserCourseQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $user_id,
			)
		);

		$user_courses = $query->get_user_courses();
		$user_course  = empty( $user_courses ) ? masteriyo( 'user-course' ) : current( $user_courses );
		$user_course->set_course_id( $course->get_id() );
		$user_course->set_user_id( $user_id );
		$user_course->set_price( $course->get_price() );
		$user_course->set_status( UserCourseStatus::ACTIVE ); // mark as active immediately
		$user_course->set_date_start( current_time( 'mysql', true ) );

		$user_course->save();
		wp_send_json_success(
			array(
				'message'   => __( 'You have been enrolled successfully!', 'learning-management-system' ),
				'course_id' => $course_id,
			),
			200
		);
	}


	/**
	 * Validate the nonce.
	 *
	 * @since 3.1.0
	 *
	 * @param string $nonce The nonce to validate.
	 * @return bool Returns true if nonce is valid, otherwise false.
	 */
	private function validate_nonce( $nonce ) {
		if ( ! $nonce || ! wp_verify_nonce( sanitize_key( wp_unslash( $nonce ) ), 'masteriyo_course_cohort_nonce' ) ) {
			$this->send_error_response( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			return false;
		}
		return true;
	}
}
