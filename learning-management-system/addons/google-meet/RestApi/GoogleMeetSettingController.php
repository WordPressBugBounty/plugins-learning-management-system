<?php
/**
 * Google meet setting controller class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\Google Meet\RestApi
 */

namespace Masteriyo\Addons\GoogleMeet\RestApi;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\GoogleMeet\Models\GoogleMeetSetting;
use Masteriyo\RestApi\Controllers\Version1\CrudController;


use WP_Error;

/**
 * Google Meet Controller class.
 */
class GoogleMeetSettingController extends CrudController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Post type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-google-meet';

	/**
	 * Route base.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $rest_base = 'google-meet/settings';

	/**
	 * Object type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $object_type = 'google-meet-setting';

	/**
	 * Register routes.
	 *
	 * @since 1.11.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_google_meet_setting' ),
					'permission_callback' => array( $this, 'get_google_meet_setting_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_google_meet_setting' ),
					'permission_callback' => array( $this, 'save_google_meet_setting_permission_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reset_google_meet_setting' ),
					'permission_callback' => array( $this, 'save_google_meet_setting_permission_check' ),
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
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function save_google_meet_setting_permission_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}
		if ( current_user_can( 'publish_google-meets' ) ) {
			return true;
		} else {
			return false;
		}
	}

		/**
	 * Add google meet client details to user meta.
	 *
	 * @since 1.11.0
	 *
	 * @param  $request $request Full details about the request.
	 * @return WP_Error|array
	 */
	public function reset_google_meet_setting() {

		$setting = new GoogleMeetSetting();

		$setting->delete();

		return rest_ensure_response( $setting->get_data() );

	}


	/**
	 * Check if tha given request has access to create an Item.
	 *
	 * @since 1.11.0
	 */
	public function get_google_meet_setting_permission_check( $request ) {

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! current_user_can( 'edit_google-meets' ) ) {
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
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function validate_settings_permission_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}
		return current_user_can( 'edit_google-meets' );
	}

	/**
	 * Return validate
	 *
	 * @since 1.11.0
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function validate_settings() {
		if ( ! masteriyo_is_google_meet_credentials_set() ) {
			return new WP_Error(
				'google_meet_credentials_empty',
				__( 'Google credentials are not set', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		$setting = new GoogleMeetSetting();

		return rest_ensure_response( $setting->get_data() );
	}

	/**
	 * Provides the google meet setting data(client_id, client_secret, account_id)  data
	 *
	 * @since 1.11.0
	 *
	 * @return WP_Error|array
	 */
	public function get_google_meet_setting() {
		return ( new GoogleMeetSetting() )->get_data();
	}

	/**
	* Parse Import file.
	*
	* @since 1.11.0
	* @param array $files $_FILES array for a given file.
	* @return string|\WP_Error File path on success and WP_Error on failure.
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
	 * Add google meet client details to user meta.
	 *
	 * @since 1.11.0
	 *
	 * @param  $request $request Full details about the request.
	 * @return WP_Error|array
	 */
	public function save_google_meet_setting( \WP_REST_Request $request ) {

		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$file_system = masteriyo_get_filesystem();

		$file_contents = json_decode( $file_system->get_contents( $file ), true );

		$setting = new GoogleMeetSetting();

		$setting->set( 'client_id', $file_contents['web']['client_id'] );
		$setting->set( 'project_id', $file_contents['web']['project_id'] );
		$setting->set( 'auth_uri', $file_contents['web']['auth_uri'] );
		$setting->set( 'token_uri', $file_contents['web']['token_uri'] );
		$setting->set( 'auth_provider_x509_cert_url', $file_contents['web']['auth_provider_x509_cert_url'] );
		$setting->set( 'client_secret', $file_contents['web']['client_secret'] );
		$setting->set( 'redirect_uris', $file_contents['web']['redirect_uris'] );

		$setting->save();

		return rest_ensure_response( $setting->get_data() );

	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_google_meets_setting_permission_check( $request ) {
		return current_user_can( 'edit_google-meets' );
	}

	/**
	 * Get the google_meet_settings'schema, conforming to JSON Schema.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'client_id'                   => array(
					'description' => __( 'Client Id', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'project_id'                  => array(
					'description' => __( 'Project Id', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'auth_uri'                    => array(
					'description' => __( 'Auth Uri', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'token_uri'                   => array(
					'description' => __( 'Token Uri', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'auth_provider_x509_cert_url' => array(
					'description' => __( 'Auth Provider', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'client_secret'               => array(
					'description' => __( 'Client Secret', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'redirect_uris'               => array(
					'description' => __( 'Redirect Uri', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
