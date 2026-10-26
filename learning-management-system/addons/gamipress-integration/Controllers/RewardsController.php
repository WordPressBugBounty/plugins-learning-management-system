<?php
/**
 * REST controller for the signed-in learner's GamiPress rewards.
 *
 * @package Masteriyo\Addons\GamiPressIntegration
 */

namespace Masteriyo\Addons\GamiPressIntegration\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\GamiPressIntegration\Helper;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The learn page's rewards, refetched after each completion so the header
 * pill and the completion toast can show what was just earned.
 */
class RewardsController extends WP_REST_Controller {

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
	protected $rest_base = 'gamipress-rewards';

	/**
	 * Register routes.
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
	 * Only a signed-in learner has rewards.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read rewards.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * The same payload the learn page is localized with.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		return rest_ensure_response(
			array(
				'point_types'       => Helper::get_point_types(),
				'rank_types'        => Helper::get_rank_types(),
				'achievement_types' => Helper::get_achievement_types(),
			)
		);
	}
}
