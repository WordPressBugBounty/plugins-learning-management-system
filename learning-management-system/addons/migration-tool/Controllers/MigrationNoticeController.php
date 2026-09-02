<?php
/**
 * REST controller for the Migration Tool auto-activation notice.
 *
 * Dismissal only, kept apart from LMSMigrationController because that one
 * authorises against course-import permissions, which is not what dismissing a
 * notice means.
 *
 * @category API
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

class MigrationNoticeController extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 */
	const REST_NAMESPACE = 'masteriyo/v1';

	/**
	 * Route base.
	 */
	const REST_BASE = 'migration-notice';

	/**
	 * Route registered under REST_BASE.
	 */
	const DISMISS_ROUTE = 'dismiss';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = self::REST_NAMESPACE;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = self::REST_BASE;

	/**
	 * The dismissal route, relative to the site's REST root.
	 *
	 * The WordPress-side notice in main.php has to reach the same path this
	 * registers. Spelled once here, a rename cannot leave that notice posting to
	 * a route that no longer exists.
	 *
	 * @return string
	 */
	public static function dismiss_path() {
		return self::REST_NAMESPACE . '/' . self::REST_BASE . '/' . self::DISMISS_ROUTE;
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/' . self::DISMISS_ROUTE,
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
	 * Check whether the current user may dismiss the notice.
	 *
	 * The same capability that shows it.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|WP_Error
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
	 * Dismiss the notice for the current user, permanently.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function dismiss_notice( $request ) {
		update_user_meta( get_current_user_id(), Helper::NOTICE_DISMISSED_META, 1 );

		return new WP_REST_Response( null, 204 );
	}
}
