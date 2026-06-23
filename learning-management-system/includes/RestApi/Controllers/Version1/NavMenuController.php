<?php
/**
 * REST API Nav Menu Controller.
 *
 * Handles requests to the nav menu endpoints, specifically for notice dismissal.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since x.x.x
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Nav Menu Controller Class.
 *
 * Handles requests to the nav menu endpoints, such as notice dismissal.
 *
 * @since x.x.x
 * @package Masteriyo\RestApi
 */
class NavMenuController extends WP_REST_Controller {

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
	protected $rest_base = 'nav-menu-notice';

	/**
	 * Registers the REST API routes for the nav menu notice endpoint.
	 *
	 * @since x.x.x
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
	 * Checks if the current user has capability to dismiss the nav menu notice.
	 *
	 * @since x.x.x
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function dismiss_notice_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'masteriyo_rest_cannot_view',
				__( 'Sorry, you cannot dismiss this notice.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Dismisses the nav menu notice by updating user metadata.
	 *
	 * @since x.x.x
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The REST response object.
	 */
	public function dismiss_notice( $request ) {
		update_user_meta( get_current_user_id(), 'masteriyo_dismissed_nav_menu_notice', 1 );
		return new WP_REST_Response( null, 204 );
	}
}
