<?php
/**
 * Multiple currency setting controller class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency\Controllers
 */

namespace Masteriyo\Addons\MultipleCurrency\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MultipleCurrency\MaxMind\DatabaseService;
use Masteriyo\Addons\MultipleCurrency\Models\Setting;
use Masteriyo\RestApi\Controllers\Version1\CrudController;
use WP_Error;


/**
 * Manages the multiple currency settings for the application.
 *
 * This controller handles the REST API endpoints for retrieving and saving the
 * multiple currency settings.
 *
 * @since 1.11.0
 */
class MultipleCurrencySettingsController extends CrudController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $rest_base = 'multiple-currency/settings';

	/**
	 * Object type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $object_type = 'multiple-currency-setting';

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
					'callback'            => array( $this, 'get_setting' ),
					'permission_callback' => array( $this, 'get_setting_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_setting' ),
					'permission_callback' => array( $this, 'save_setting_permission_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_setting_permission_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		return current_user_can( 'read_mto_price_zones' );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function save_setting_permission_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		return current_user_can( 'edit_mto_price_zones' );
	}

	/**
	 * Get all settings.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_setting() {
		$data = Setting::all();

		if ( isset( $data['test_mode'] ) && isset( $data['test_mode']['country'] ) ) {
			$code = $data['test_mode']['country'];

			if ( ! empty( $code ) ) {
				$data['test_mode']['country'] = array(
					'value' => $code,
					'label' => masteriyo( 'countries' )->get_country_from_code( $code ),
				);
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Saves the multiple currency settings.
	 *
	 * @since 1.11.0
	 *
	 * @param array $request The request data containing the settings to be saved.
	 *
	 * @return WP_REST_Response|WP_Error The updated settings or error response.
	 */
	public function save_setting( $request ) {
		global $wp_filesystem;

		$enabled = isset( $request['test_mode']['enabled'] ) ? masteriyo_string_to_bool( $request['test_mode']['enabled'] ) : false;
		$country = isset( $request['test_mode']['country'] ) ? sanitize_text_field( $request['test_mode']['country'] ) : '';

		$maxmind_enabled = isset( $request['maxmind']['enabled'] ) ? masteriyo_string_to_bool( $request['maxmind']['enabled'] ) : false;
		$license_key     = isset( $request['maxmind']['license_key'] ) ? sanitize_text_field( $request['maxmind']['license_key'] ) : '';

		if ( $enabled && empty( $country ) ) {
			return new WP_Error( 'invalid_license_key', __( 'Country is required in test mode.', 'learning-management-system' ) );

		}

		if ( $maxmind_enabled && empty( $license_key ) ) {
			return new WP_Error( 'invalid_license_key', __( 'License key is required for MaxMind.', 'learning-management-system' ) );
		}

		$prev_license_key = Setting::get( 'maxmind.license_key' );

		$args = array(
			'test_mode' => array(
				'enabled' => $enabled,
				'country' => $country,
			),
			'maxmind'   => array(
				'enabled'     => ! $maxmind_enabled ? $maxmind_enabled : Setting::get( 'maxmind.enabled' ),
				'license_key' => $prev_license_key,
			),
		);

		if ( $maxmind_enabled ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$database_service     = new DatabaseService();
			$target_database_path = $database_service->get_database_path();

			if ( $prev_license_key === $license_key ) {
				$args['maxmind']['enabled']     = $maxmind_enabled;
				$args['maxmind']['license_key'] = $license_key;
			} elseif ( $prev_license_key !== $license_key || ! file_exists( $target_database_path ) ) {
				$tmp_database_path = $database_service->download_database( $license_key );

				if ( is_wp_error( $tmp_database_path ) ) {
					return $tmp_database_path;
				}

				if ( ! $wp_filesystem ) {
					return new WP_Error( 'filesystem_error', __( 'Failed to initialize WP Filesystem.', 'learning-management-system' ) );
				}

				if ( $wp_filesystem->exists( $target_database_path ) ) {
					$wp_filesystem->delete( $target_database_path );
				}

				if ( ! $wp_filesystem->move( $tmp_database_path, $target_database_path, true ) ) {
					return new WP_Error( 'database_move_failed', __( 'Failed to move database to target location.', 'learning-management-system' ) );
				}

				$wp_filesystem->delete( $tmp_database_path );

				$args['maxmind']['enabled']     = $maxmind_enabled;
				$args['maxmind']['license_key'] = $license_key;
			}
		}

		Setting::set_props( $args );

		return rest_ensure_response( Setting::all() );
	}

	/**
	 * Get the zooms'schema, conforming to JSON Schema.
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
				'test_mode' => array(
					'description' => __( 'Test Mode', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'enabled' => array(
							'description' => __( 'Enable', 'learning-management-system' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'Test Country', 'learning-management-system' ),
							'type'        => 'string',
							'default'     => '',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'maxmind'   => array(
					'description' => __( 'MaxMind', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'enabled'     => array(
							'description' => __( 'Enable', 'learning-management-system' ),
							'type'        => 'boolean',
							'default'     => false,
							'context'     => array( 'view', 'edit' ),
						),
						'license_key' => array(
							'description' => __( 'License Key', 'learning-management-system' ),
							'type'        => 'string',
							'default'     => '',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
