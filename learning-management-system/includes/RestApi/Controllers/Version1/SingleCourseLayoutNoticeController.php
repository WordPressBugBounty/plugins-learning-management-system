<?php
/**
 * REST API Single Course Layout Notice Controller.
 *
 * Handles dismissal of the frontend hint telling admins the single course
 * page has multiple layouts.
 *
 * @category API
 * @package Masteriyo\RestApi
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Single Course Layout Notice Controller Class.
 *
 * @package Masteriyo\RestApi
 */
class SingleCourseLayoutNoticeController extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'single-course-layout-notice';

	/**
	 * Registers the REST API routes for the layout notice endpoint.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dismiss_notice' ),
					'permission_callback' => array( $this, 'dismiss_notice_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if the current user can dismiss the layout notice.
	 *
	 * Only users who can manage Masteriyo settings ever see the notice.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function dismiss_notice_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_masteriyo_settings' ) ) {
			return new WP_Error(
				'masteriyo_rest_cannot_view',
				__( 'Sorry, you cannot dismiss this notice.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Dismisses the layout notice permanently for the current user.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The REST response object.
	 */
	public function dismiss_notice( $request ) {
		update_user_meta( get_current_user_id(), 'masteriyo_dismissed_single_course_layout_notice', 1 );
		return new WP_REST_Response( null, 204 );
	}
}
