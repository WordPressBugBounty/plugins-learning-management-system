<?php
/**
 * Brevo Integration REST Controller.
 *
 * @since 1.13.3
 *
 * @subpackage Masteriyo\Addons\BrevoIntegration
 */

namespace Masteriyo\Addons\BrevoIntegration\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\BrevoIntegration\BrevoIntegrationSettings;
use Masteriyo\Helper\Permission;
use Masteriyo\Addons\BrevoIntegration\API\API;
use Masteriyo\RestApi\Controllers\Version1\CrudController;

/**
 * BrevoIntegrationController class.
 *
 * @since 1.13.3
 */
class BrevoIntegrationController extends CrudController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.13.3
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.13.3
	 *
	 * @var string
	 */
	protected $rest_base = 'brevo-integration';

	/** Object type.
	 *
	 *  @since 1.13.3
	 *
	 * @var string
	 */
	protected $object_type = 'brevo-integration';

	/**
	 * Permission class.
	 *
	 * @since 1.13.3
	 *
	 * @var \Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * API client instance.
	 *
	 * @since 1.13.3
	 *
	 * @var \Masteriyo\Addons\BrevoIntegration\API\API
	 */
	private $api_client;

	/**
	 * Constructor.
	 *
	 * @since 1.13.3
	 *
	 * @param \Masteriyo\Helper\Permission $permission
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
		$this->api_client = new API( BrevoIntegrationSettings::get_api_key() );
	}

	/**
	 * Register routes.
	 *
	 * @since 1.13.3
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/lists',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lists' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'connect' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/disconnect',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'disconnect' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.13.3
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'The permission object is missing. Unable to verify access rights.', 'learning-management-system' ),
				array( 'status' => 403 )
			);
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Get Brevo lists.
	 *
	 * This function fetches the Brevo lists from the API and caches them in the database.
	 *
	 * @since 1.13.3
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The response object containing the Brevo lists.
	 */
	public function get_lists( $request ) {
		$force_fetch = masteriyo_string_to_bool( $request->get_param( 'force' ) );

		if ( $force_fetch ) {
			delete_option( 'masteriyo_brevo_lists' );
		}

		$lists = get_option( 'masteriyo_brevo_lists', array() );

		if ( empty( $lists ) ) {
			$lists = $this->api_client->get_lists();

			if ( is_wp_error( $lists ) ) {
				return $lists;
			}

			if ( isset( $lists['lists'] ) ) {
				$lists = $lists['lists'];
				update_option( 'masteriyo_brevo_lists', $lists );
			} else {
				return new \WP_Error(
					'masteriyo_invalid_api_response',
					__( 'Failed to retrieve Brevo lists. Please check the API key or try again later.', 'learning-management-system' ),
					array( 'status' => 500 )
				);
			}
		}

		return rest_ensure_response( $lists );
	}

	/**
	 * Connect to Brevo with validating the API key.
	 *
	 * This function validates the provided API key and stores it in the settings if valid.
	 *
	 * @since 1.13.3
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *                                 Required parameter: api_key
	 *
	 * @return \WP_REST_Response The response object containing the success status and message.
	 *                           Error response: masteriyo_invalid_api_key
	 *
	 * @throws \WP_Error If the API key is invalid.
	 */
	public function connect( $request ) {
		$api_key      = sanitize_text_field( $request['api_key'] ?? '' );
		$verify_again = masteriyo_string_to_bool( $request['verify_again'] ?? false );

		if ( $verify_again ) {
			$api_key = BrevoIntegrationSettings::get_api_key();
		}

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'masteriyo_invalid_api_key',
				__( 'API key is required to establish a connection.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$is_valid = $this->api_client->validate_api_key( $api_key );

		if ( ! $is_valid ) {
			return new \WP_Error(
				'masteriyo_invalid_api_key',
				__( 'The provided API key is invalid. Please verify the key and try again.', 'learning-management-system' ),
				array( 'status' => 401 )
			);
		}

		BrevoIntegrationSettings::set( 'api_key', $api_key );
		BrevoIntegrationSettings::set( 'is_connected', true );

		$message = $verify_again
		? __( 'API key re-verified successfully.', 'learning-management-system' )
		: __( 'Connected to Brevo successfully.', 'learning-management-system' );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => $message,
			)
		);
	}

	/**
	 * Disconnects from Brevo integration.
	 *
	 * This function clears the API key and sets the is_connected flag to false.
	 *
	 * @since 1.13.3
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response The response object containing the success status and message.
	 */
	public function disconnect( $request ) {
		BrevoIntegrationSettings::set( 'api_key', '' );
		BrevoIntegrationSettings::set( 'is_connected', false );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Disconnected from Brevo successfully.', 'learning-management-system' ),
			)
		);
	}
}
