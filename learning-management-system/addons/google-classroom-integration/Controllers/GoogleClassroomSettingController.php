<?php
/**
 * Google classroom setting controller class.
 *
 * @since 1.8.3
 *
 * @package Masteriyo\Addons\GoogleClassroom\RestApi
 */

namespace Masteriyo\Addons\GoogleClassroomIntegration\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\GoogleClassroomIntegration\Models\GoogleClassroomSetting;
use Masteriyo\RestApi\Controllers\Version1\CrudController;


use WP_Error;

/**
 * GoogleClassroom Controller class.
 */
class GoogleClassroomSettingController extends CrudController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Post type.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	protected $post_type = 'mto-google_classroom';

	/**
	 * Route base.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	protected $rest_base = 'google-classroom/settings';

	/**
	 * Object type.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	protected $object_type = 'google-classroom-setting';

	/**
	 * Register routes.
	 *
	 * @since 1.8.3
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_google_classroom_setting' ),
					'permission_callback' => array( $this, 'get_google_classroom_setting_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_google_classroom_setting' ),
					'permission_callback' => array( $this, 'save_google_classroom_setting_permission_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reset_google_classroom_setting' ),
					'permission_callback' => array( $this, 'save_google_classroom_setting_permission_check' ),
				),

			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/validate',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'validate_settings' ),
					'permission_callback' => array( $this, 'validate_settings_permission_check' ),
				),
			)
		);
	}


	/**
	 * get import file.
	 *
	 * @since 1.14.0
	 *
	 * @param  Array $files Full files array.
	 * @return WP_Error|boolean
	 */
	protected function get_import_file( $files ) {
		if ( ! isset( $files['file']['tmp_name'] ) ) {
			return new \WP_Error(
				'rest_upload_no_data',
				__( 'No data supplied.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		if (
			! isset( $files['file']['name'] ) ||
			'json' !== pathinfo( $files['file']['name'], PATHINFO_EXTENSION )
		) {
			return new \WP_Error(
				'invalid_file_ext',
				__( 'Invalid file type for import.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return $files['file']['tmp_name'];
	}


	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.8.3
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function save_google_classroom_setting_permission_check( $request ) {
		if ( current_user_can( 'publish_google_classrooms' ) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Check if tha given request has access to create an Item.
	 *
	 * @since 1.8.3
	 */
	public function get_google_classroom_setting_permission_check( $request ) {
		if ( ! current_user_can( 'get_google_classroom' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to check validate.
	 *
	 * @since 1.8.3
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function validate_settings_permission_check( $request ) {
		return current_user_can( 'edit_google_classrooms' );
	}

	/**
	 * Return validate
	 *
	 * @since 1.8.3
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function validate_settings() {
		if ( ! masteriyo_is_google_classroom_credentials_set() ) {
			return new WP_Error(
				'google_classroom_credentials_empty',
				__( 'Google credentials are not set', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		$setting = new GoogleClassroomSetting();

		return rest_ensure_response( $setting->get_data() );
	}



	/**
	 * Reset google classroom client details
	 *
	 * @since 1.11.0
	 *
	 * @param  $request $request Full details about the request.
	 * @return WP_Error|array
	 */

	public function reset_google_classroom_setting() {

		$setting = new GoogleClassroomSetting();

		$setting->delete();

		return rest_ensure_response( $setting->get_data() );

	}


	/**
	 * Provides the google classroom setting data(client_id, client_secret, account_id)  data
	 *
	 * @since 1.8.3
	 *
	 * @return WP_Error|array
	 */
	public function get_google_classroom_setting() {
		return ( new GoogleClassroomSetting() )->get_data();
	}

	/**
	 * Add google classroom client details to user meta.
	 *
	 * @since 1.8.3
	 *
	 * @param  $request $request Full details about the request.
	 * @return WP_Error|array
	 */
	public function save_google_classroom_setting( $request ) {

		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$file_system = masteriyo_get_filesystem();

		$file_contents = json_decode( $file_system->get_contents( $file ), true );

		$setting = new GoogleClassroomSetting();
		$setting->set( 'client_id', $file_contents['web']['client_id'] );
		$setting->set( 'refresh_token', $file_contents['web']['token_uri'] );
		$setting->set( 'client_secret', $file_contents['web']['client_secret'] );

		if ( $setting->get( 'client_id' ) !== $file_contents['web']['client_id'] || $setting->get( 'client_secret' ) !== $file_contents['web']['client_secret'] ) {
			update_option( 'masteriyo_google_classroom_data_' . masteriyo_get_current_user_id(), array() );
		}
		$setting->save();
		return rest_ensure_response( $setting->get_data() );

	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 1.8.3
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_google_classrooms_setting_permission_check( $request ) {
		return current_user_can( 'edit_google_classrooms' );
	}

	/**
	 * Get the google_classroom_settings'schema, conforming to JSON Schema.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'client_id'     => array(
					'description' => __( 'Client Id', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'client_secret' => array(
					'description' => __( 'Client Secret', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'access_code'   => array(
					'description' => __( 'Access Code', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
