<?php
/**
 * REST API Payments Readiness Controller.
 *
 * Read-only payment-setup status for the course builder's pricing helper
 * panel. Instructors can edit courses but cannot read the settings
 * endpoint, so this exposes just the status facts, no credentials.
 *
 * @package Masteriyo\RestApi
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Setup\HomeGuide;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Payments Readiness Controller Class.
 *
 * @package Masteriyo\RestApi
 */
class PaymentsReadinessController extends WP_REST_Controller {

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
	protected $rest_base = 'payments/readiness';

	/**
	 * Registers the payments readiness route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Anyone who can edit courses may read the payment-setup status.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'edit_courses' );
	}

	/**
	 * The payment-setup facts the pricing helper panel needs.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		return new WP_REST_Response(
			array(
				'payment_usable' => HomeGuide::is_payment_usable(),
				'store'          => array(
					'country' => (string) masteriyo_get_setting( 'payments.store.country' ),
					'state'   => (string) masteriyo_get_setting( 'payments.store.state' ),
				),
			),
			200
		);
	}
}
