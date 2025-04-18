<?php
/**
 * RestAPIAuth class.
 *
 * @package Masteriyo
 *
 * @since 1.16.0
 */

namespace Masteriyo;

use Masteriyo\Enums\RestAuthPermissionType;
use Masteriyo\Helper\RestAuth;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API authentication class.
 *
 * @class RestAPIAuth
 */
class RestAPIAuth {

	/**
	 * Authentication error.
	 *
	 * @since 1.16.0
	 *
	 * @var WP_Error
	 */
	protected $error = null;

	/**
	 * Logged in user data.
	 *
	 * @since 1.16.0
	 *
	 * @var array
	 */
	protected $user = null;

	/**
	 * Current auth method.
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	protected $auth_method = '';

	/**
	 * Initialize the class instance
	 *
	 * @since 1.16.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.16.0
	 */
	protected function init_hooks() {
		add_filter( 'determine_current_user', array( $this, 'authenticate' ) );
		add_filter( 'rest_post_dispatch', array( $this, 'send_unauthorized_headers' ), 50 );
		add_filter( 'rest_pre_dispatch', array( $this, 'check_user_permissions' ), 10, 3 );
	}

	/**
	 * API auth.
	 *
	 * @since 1.16.0
	 *
	 * @param int|false $user_id user id.
	 *
	 * @return int|false
	 */
	public function authenticate( $user_id ) {
		if ( ! empty( $user_id ) || ! self::is_masteriyo_api_request() ) {
			return $user_id;
		}

		if ( ! wp_is_application_passwords_available() ) {
			return $user_id;
		}

		if ( ! isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
			return $user_id;
		}

		$this->auth_method = 'basic_auth';

		$api_key    = $_SERVER['PHP_AUTH_USER']; // WPCS: CSRF ok, sanitization ok.
		$api_secret = $_SERVER['PHP_AUTH_PW']; // WPCS: CSRF ok, sanitization ok.
		$record     = RestAuth::validate_api_key_secret( $api_key, $api_secret, true );

		if ( $record && isset( $record['user_id'] ) ) {
			$this->user = $record;

			return $record['user_id'];
		}

		$this->set_error( new WP_Error( 'masteriyo_rest_authentication_error', __( 'Consumer secret is invalid.', 'learning-management-system' ), array( 'status' => 401 ) ) );

		return $user_id;
	}

	/**
	 * Is request is masteriyo rest api.
	 *
	 * @since 1.16.0
	 *
	 * @return boolean
	 */
	public static function is_masteriyo_api_request() {
		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		$is_masteriyo_api = ( false !== strpos( $request_uri, $rest_prefix . 'masteriyo/' ) );

		return $is_masteriyo_api;
	}

	/**
	 * Sends unauthorized headers for basic authentication.
	 *
	 * @since 1.16.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @return WP_REST_Response The modified response object with authentication headers.
	 */
	public function send_unauthorized_headers( $response ) {
		if ( is_wp_error( $this->get_error() ) && 'basic_auth' === $this->auth_method ) {
			$auth_message = __( 'Masteriyo API. Use a API key in the username field and a API secret in the password field.', 'learning-management-system' );
			$response->header( 'WWW-Authenticate', 'Basic realm="' . $auth_message . '"', true );
		}

		return $response;
	}

	/**
	 * Check for user permissions.
	 *
	 * @since 1.16.0
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function check_user_permissions( $result, $server, $request ) {
		if ( $this->user ) {
			$allowed = $this->check_permissions( $request->get_method() );

			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}
		}

		return $result;
	}

	/**
	 * Check that the API keys provided have the proper key-specific permissions to either read or write API resources.
	 *
	 * @since 1.16.0
	 *
	 * @param string $method Request method.
	 * @return bool|WP_Error
	 */
	private function check_permissions( $method ) {
		$permissions = $this->user['permissions'];

		switch ( $method ) {
			case 'HEAD':
			case 'GET':
				if ( RestAuthPermissionType::READ !== $permissions && RestAuthPermissionType::READ_WRITE !== $permissions ) {
					return new WP_Error( 'masteriyo_rest_authentication_error', __( 'The API key provided does not have read permissions.', 'learning-management-system' ), array( 'status' => 401 ) );
				}
				break;
			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				if ( RestAuthPermissionType::WRITE !== $permissions && RestAuthPermissionType::READ_WRITE !== $permissions ) {
					return new WP_Error( 'masteriyo_rest_authentication_error', __( 'The API key provided does not have write permissions.', 'learning-management-system' ), array( 'status' => 401 ) );
				}
				break;
			case 'OPTIONS':
				return true;

			default:
				return new WP_Error( 'masteriyo_rest_authentication_error', __( 'Unknown request method.', 'learning-management-system' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Set authentication error.
	 *
	 * @since 1.16.0
	 *
	 * @param WP_Error $error Authentication error data.
	 */
	protected function set_error( $error ) {
		$this->user = null;

		$this->error = $error;
	}

	/**
	 * Get authentication error.
	 *
	 * @since 1.16.0
	 *
	 * @return WP_Error|null.
	 */
	protected function get_error() {
		return $this->error;
	}
}
