<?php
/**
 * REST API Demos controller class.
 *
 * Handles demo data retrieval and import operations.
 *
 * @since 1.20.0 [Free]
 */
namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * REST controller for handling demo data and import.
 *
 * @since 1.20.0 [Free]
 */
class DemosController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.20.0 [Free]
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.20.0 [Free]
	 * @var string
	 */
	protected $rest_base = 'demos';

	/**
	 * Object type.
	 *
	 * @since 1.20.0 [Free]
	 * @var string
	 */
	protected $object_type = 'demo';

	/**
	 * Register routes.
	 *
	 * @since 1.20.0 [Free]
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_demos' ),
					'permission_callback' => array( $this, 'get_demos_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import-themegrill-demo',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_themegrill_demo' ),
					'permission_callback' => array( $this, 'import_permissions_check' ),
					'args'                => array(
						'slug'    => array(
							'required' => true,
							'type'     => 'string',
						),

						'builder' => array(
							'required' => false,
							'type'     => 'string',
							'enum'     => array( 'elementor', 'blockart' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import-progress-details',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_detailed_import_progress' ),
					'permission_callback' => array( $this, 'import_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cancel-import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_import' ),
				'permission_callback' => array( $this, 'import_permissions_check' ),
			)
		);

	}

	/**
	 * Retrieve available demos.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_demos( WP_REST_Request $request ) {
		$url      = 'https://d1sb0nhp4t2db4.cloudfront.net/configs/elearning.json';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => ! masteriyo_is_development(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'demos_fetch_error', __( 'Unable to fetch demo data.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
			return new WP_Error( 'demos_invalid_json', __( 'Invalid demo JSON received.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$imported_slug  = get_option( 'themegrill_demo_importer_activated_id' );
		$demos          = $data['demos'];
		$filtered_demos = array();

		$v2_base_slugs = array();
		foreach ( $demos as $slug => $demo ) {
			if ( str_ends_with( $slug, '-v2' ) ) {
				$base_slug                   = preg_replace( '/-v2$/i', '', $slug );
				$v2_base_slugs[ $base_slug ] = true;
			}
		}

		foreach ( $demos as $slug => $demo ) {
			if ( in_array( $slug, array( 'elearning-default' ), true ) ) {
				continue;
			}

			$base_slug = preg_replace( '/-v2$/i', '', $slug );

			if ( isset( $v2_base_slugs[ $base_slug ] ) && ! str_ends_with( $slug, '-v2' ) ) {
				continue;
			}

			$demo['title']          = preg_replace( '/\s*v2$/i', '', $demo['title'] );
			$demo['screenshot_url'] = "https://d1sb0nhp4t2db4.cloudfront.net/resources/elearning/{$slug}/screenshot.jpg";
			$demo['tag']            = 'eLearning';
			$demo['is_imported']    = ( $slug === $imported_slug );

			$filtered_demos[ $slug ] = $demo;
		}

		return rest_ensure_response(
			array(
				'demos'    => $filtered_demos,
				'home_url' => home_url(),
			)
		);
	}

	/**
	 * Import ThemeGrill demo content.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_themegrill_demo( WP_REST_Request $request ) {
		$slug       = sanitize_key( $request->get_param( 'slug' ) );
		$builder    = sanitize_key( (string) $request->get_param( 'builder' ) ); // 'elementor' | 'blockart' | ''
		$theme_slug = 'elearning';

		$download_slug = $this->resolve_demo_slug_for_builder( $slug, $builder );


		$this->update_import_step( 'theme_install', 'in_progress' );

		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! wp_get_theme( $theme_slug )->exists() ) {
			$api = themes_api( 'theme_information', array( 'slug' => $theme_slug ) );
			if ( is_wp_error( $api ) ) {
				$this->update_import_step( 'theme_install', 'failed' );
				return new WP_Error( 'theme_fetch_error', 'Failed to fetch theme.', array( 'status' => 500 ) );
			}
			$upgrader = new \Theme_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );
			if ( is_wp_error( $result ) ) {
				$this->update_import_step( 'theme_install', 'failed' );
				return $result;
			}
		}
		$this->update_import_step( 'theme_install', 'completed' );

		$this->update_import_step( 'theme_switch', 'in_progress' );
		switch_theme( $theme_slug );
		$this->update_import_step( 'theme_switch', 'completed' );

		$plugin_slug = 'themegrill-demo-importer';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$this->update_import_step( $plugin_file, 'in_progress' );

		if ( ! is_plugin_active( $plugin_file ) ) {
			$api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );
			if ( is_wp_error( $api ) ) {
				$this->update_import_step( $plugin_file, 'failed' );
				return $api;
			}
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );
			if ( is_wp_error( $result ) ) {
				$this->update_import_step( $plugin_file, 'failed' );
				return $result;
			}
			$activate = activate_plugin( $plugin_file );
			if ( is_wp_error( $activate ) ) {
				$this->update_import_step( $plugin_file, 'failed' );
				return $activate;
			}
		}
		$this->update_import_step( $plugin_file, 'completed' );

		// Install additional required plugins for demo
		$config_url = 'https://d1sb0nhp4t2db4.cloudfront.net/configs/elearning.json';
		$response   = wp_remote_get( $config_url );
		$demo_data  = json_decode( wp_remote_retrieve_body( $response ), true );


		if ( ! isset( $demo_data['demos'][ $download_slug ]['plugins_list'] ) ) {
			return new WP_Error( 'missing_plugins_list', 'No plugin list found for this demo.', array( 'status' => 400 ) );
		}
		$plugins = $demo_data['demos'][ $download_slug ]['plugins_list'];


		foreach ( $plugins as $plugin => $plugin_data ) {
			$plugin_path = $plugin_data['slug'];

			if ( $plugin_path === 'learning-management-system/lms.php' ) {
				continue;
			}

			$this->update_import_step( $plugin_path, 'in_progress' );

			if ( ! isset( get_plugins()[ $plugin_path ] ) ) {
				$plugin_dir = dirname( $plugin_path );
				$api        = plugins_api(
					'plugin_information',
					array( 'slug' => $plugin_dir )
				);
				if ( ! is_wp_error( $api ) ) {
					$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
					$upgrader->install( $api->download_link );
				}
			}

			if ( ! is_plugin_active( $plugin_path ) ) {
				activate_plugin( $plugin_path );
			}

			if ( is_plugin_active( $plugin_path ) ) {
				$this->update_import_step( $plugin_path, 'completed' );
			} else {
				$this->update_import_step( $plugin_path, 'failed' );
			}
		}

		$this->update_import_step( 'import_content', 'in_progress' );

		// ---------- CHANGED: import the content for the *download* slug ----------
		$response = wp_remote_post(
			admin_url( 'admin-ajax.php' ),
			array(
				'timeout'   => 300,
				'sslverify' => ! masteriyo_is_development(),
				'body'      => array(
					'action'      => 'import-demo',
					'slug'        => $download_slug,
					'_ajax_nonce' => wp_create_nonce( 'updates' ),
				),
				'cookies'   => $_COOKIE,
			)
		);
		// ------------------------------------------------------------------------

		if ( is_wp_error( $response ) ) {
			$this->update_import_step( 'import_content', 'failed' );
			return $response;
		}

		// Keep the *original* selected slug as activated so UI marks that card.
		update_option( 'themegrill_demo_importer_activated_id', $slug );
		$this->update_import_step( 'import_content', 'completed' );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Map a selected slug to the actual demo slug to import based on builder.
	 *
	 * For Elementor, v2 slugs map to their Elementor counterparts.
	 * For BlockArt (or anything else), we keep the selected slug.
	 *
	 * @param string $slug     Selected slug (e.g., 'elearning-v2').
	 * @param string $builder  'elementor' | 'blockart' | ''.
	 * @return string          Slug used for downloading/importing.
	 */
	protected function resolve_demo_slug_for_builder( $slug, $builder ) {
		if ( 'elementor' !== $builder ) {
			return $slug;
		}

		$map = array(
			'elearning-v2'         => 'elearning-default',
			'elearning-eduflex-v2' => 'elearning-eduflex',
		);

		return isset( $map[ $slug ] ) ? $map[ $slug ] : $slug;
	}

	/**
	 * Permission check for retrieving demos.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	public function get_demos_permissions_check( WP_REST_Request $request ) {
		return true;
	}

	/**
	 * Checks if a given request has access to import demos.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @return bool
	 */
	public function import_permissions_check() {
		return current_user_can( 'manage_masteriyo_settings' ) || is_super_admin();
	}

	/**
	 * REST callback to get detailed import progress.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_detailed_import_progress( WP_REST_Request $request ) {
		return rest_ensure_response( $this->get_import_progress_details() );
	}

	/**
	 * Update step status in progress option.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param string $step_name Step identifier.
	 * @param string $status Step status.
	 */
	protected function update_import_step( $step_name, $status = 'in_progress' ) {
		$progress               = get_option( 'demo_import_progress_details', array() );
		$progress[ $step_name ] = $status;
		update_option( 'demo_import_progress_details', $progress );
	}

	/**
	 * Get full detailed progress.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @return array
	 */
	protected function get_import_progress_details() {
		return get_option( 'demo_import_progress_details', array() );
	}

	/**
	 * Cancels the ongoing demo import process.
	 *
	 * @since 1.20.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function cancel_import( WP_REST_Request $request ) {
		delete_option( 'demo_import_progress_details' );
		delete_option( 'demo_import_progress' );
		delete_option( 'demo_import_current_step' );
		return rest_ensure_response( array( 'cancelled' => true ) );
	}
}
