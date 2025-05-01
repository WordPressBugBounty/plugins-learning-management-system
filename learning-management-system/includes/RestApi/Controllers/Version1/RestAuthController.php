<?php
/**
 * REST API RestAuthController Controller
 *
 * Handles requests to the utilities endpoint, specifically for managing redundant enrollments.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since 1.16.0
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Enums\RestAuthPermissionType;
use Masteriyo\Helper\Permission;
use Masteriyo\Helper\RestAuth;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * REST API RestAuthController Controller Class.
 *
 * @package Masteriyo\RestApi
 */
class RestAuthController extends WP_REST_Controller {
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
	protected $rest_base = 'rest-api';

	/**
	 * Permission class instance.
	 *
	 * @since 1.16.0
	 * @var Permission
	 */
	protected $permission;

	/**
	 * Constructor.
	 *
	 * Sets up the utilities controller.
	 *
	 * @since 1.16.0
	 * @param Permission|null $permission The permission handler instance.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.16.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'user_id'     => array(
							'description'       => __( 'ID of the user to associate with the API key.', 'learning-management-system' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'required'          => true,
						),
						'permissions' => array(
							'description'       => __( 'Permissions for the API key.', 'learning-management-system' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_permissions' ),
							'required'          => true,
						),
						'description' => array(
							'description'       => __( 'Description of the API key.', 'learning-management-system' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'user_id'     => array(
							'description'       => __( 'ID of the user to associate with the API key.', 'learning-management-system' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'required'          => true,
						),
						'permissions' => array(
							'description'       => __( 'New permissions for the API key.', 'learning-management-system' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_permissions' ),
							'required'          => true,
						),
						'description' => array(
							'description'       => __( 'New description for the API key.', 'learning-management-system' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if the current user has permissions to perform deletion.
	 *
	 * @since 1.16.0
	 * @param WP_REST_Request $request The request.
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new WP_Error( 'masteriyo_null_permission', __( 'Sorry, the permission object for this resource is null.', 'learning-management-system' ) );
		}

		if ( ! masteriyo_is_current_user_admin() ) {
			return new \WP_Error(
				'masteriyo_permission_denied',
				__( 'Sorry, you are not allowed to perform this action.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Retrieves the authentication keys for all users.
	 *
	 * @since 1.16.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$meta_ids = $this->fetch_all_meta_ids();

		if ( empty( $meta_ids ) ) {
				return rest_ensure_response( array() );
		}

		$items = array_map(
			function ( $meta_id ) {
				$data = $this->fetch_meta( $meta_id );

				if ( ! is_array( $data ) || ! isset( $data['user_id'], $data['meta_value'] ) ) {
					return null;
				}

				$user_id = absint( $data['user_id'] );
				$user    = masteriyo_get_user( $user_id );

				if ( is_wp_error( $user ) ) {
					return null;
				}

				$user = array(
					'id'           => $user->get_id(),
					'display_name' => $user->get_display_name(),
					'avatar_url'   => $user->get_avatar_url(),
				);

				$item         = json_decode( $data['meta_value'], true );
				$item['id']   = $meta_id;
				$item['user'] = $user;

				return $item;
			},
			$meta_ids
		);

		return rest_ensure_response( array_filter( $items ) );
	}

	/**
	 * Create an API key.
	 *
	 * @since 1.16.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$user_id = $request->get_param( 'user_id' ) ? absint( $request->get_param( 'user_id' ) ) : get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid user.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$permissions = $this->validate_permissions( $request->get_param( 'permissions' ) );

		if ( ! $permissions ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid permissions.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$description = sanitize_textarea_field( $request->get_param( 'description' ) );

		$api_key    = 'key_' . \bin2hex( \random_bytes( 16 ) );
		$api_secret = 'secret_' . \bin2hex( \random_bytes( 32 ) );

		$api_item = array(
			'apiKey'      => $api_key,
			'secret'      => $api_secret,
			'permissions' => $permissions,
			'description' => $description,
		);

		$added = add_user_meta( $user_id, RestAuth::KEYS_USER_META_KEY, wp_json_encode( $api_item ) );

		if ( $added ) {
			$api_item['id'] = $added;
			return rest_ensure_response( $api_item );
		}

		return new WP_Error( 'rest_api_key_create_error', __( 'Failed to create API key.', 'learning-management-system' ), array( 'status' => 500 ) );
	}

	/**
	 * Updates an existing API key.
	 *
	 * @since 1.16.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Updated API key data on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$meta_id = absint( $request->get_param( 'id' ) );
		$user_id = absint( $request->get_param( 'user_id' ) );

		$user = new WP_User( $user_id );

		if ( ! isset( $user->ID ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid user.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		if ( ! $meta_id ) {
			return new WP_Error( 'rest_api_key_update_error', __( 'Invalid API key secret.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$data = $this->fetch_meta( $meta_id );

		if ( ! is_array( $data ) || ! isset( $data['user_id'], $data['meta_value'] ) ) {
			return new WP_Error( 'rest_api_key_update_error', __( 'Could not update API key secret.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$permissions = $this->validate_permissions( $request->get_param( 'permissions' ) );

		if ( ! $permissions ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid permissions.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$description = sanitize_textarea_field( $request->get_param( 'description' ) );

		$nwe_api_item = array(
			'permissions' => $permissions,
			'description' => $description,
		);

		$api_item = json_decode( $data['meta_value'], true );
		$api_item = array_merge( $api_item, $nwe_api_item );

		global $wpdb;

		$update = $wpdb->update(
			$wpdb->usermeta,
			array(
				'user_id'    => $user_id,
				'meta_value' => wp_json_encode( $api_item ),
			),
			array(
				'umeta_id' => $meta_id,
				'meta_key' => RestAuth::KEYS_USER_META_KEY,
			)
		);

		if ( $update ) {
			return rest_ensure_response( $api_item );
		}

		return new WP_Error( 'rest_api_key_update_error', __( 'Sorry, an error occurred while updating the API key secret.', 'learning-management-system' ), array( 'status' => 500 ) );
	}

	/**
	 * Deletes an API key.
	 *
	 * @since 1.16.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response True on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$meta_id = absint( $request->get_param( 'id' ) );

		if ( ! $meta_id ) {
			return new WP_Error( 'rest_api_key_delete_error', __( 'Invalid API key secret.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		global $wpdb;

		$delete = $wpdb->delete(
			$wpdb->usermeta,
			array(
				'umeta_id' => $meta_id,
				'meta_key' => RestAuth::KEYS_USER_META_KEY,
			)
		);

		if ( $delete ) {
			return rest_ensure_response( true );
		}

		return new WP_Error( 'rest_api_key_delete_error', __( 'Could not delete API key secret.', 'learning-management-system' ), array( 'status' => 500 ) );
	}

	/**
	 * Fetches a meta value from usermeta table.
	 *
	 * @since 1.16.0
	 *
	 * @param int $meta_id The meta ID.
	 *
	 * @return array|null The meta value on success, null on failure.
	 */
	private function fetch_meta( $meta_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE umeta_id = %d AND meta_key = %s",
				absint( $meta_id ),
				RestAuth::KEYS_USER_META_KEY
			),
			ARRAY_A
		);
	}

	/**
	 * Fetches all meta IDs from usermeta table.
	 *
	 * @since 1.16.0
	 *
	 * @return array List of meta IDs.
	 */
	private function fetch_all_meta_ids() {
		global $wpdb;

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT umeta_id FROM {$wpdb->usermeta} WHERE meta_key = %s ORDER BY umeta_id DESC",
				RestAuth::KEYS_USER_META_KEY
			)
		);

		return $meta_ids;
	}

	/**
	 * Validates the given permissions string.
	 *
	 * @since 1.16.0
	 * @param string $permissions The permission string to validate.
	 * @return string|false The validated permission string on success, false on failure.
	 */
	public function validate_permissions( $permissions ) {
		$permissions = sanitize_key( $permissions );
		return in_array( $permissions, RestAuthPermissionType::all(), true ) ? $permissions : false;
	}
}
