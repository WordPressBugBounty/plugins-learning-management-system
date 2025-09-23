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
use Masteriyo\StarterTemplates\Services\ImportService;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class DemosController extends RestController {

	protected $namespace   = 'masteriyo/v1';
	protected $rest_base   = 'demos';
	protected $object_type = 'demo';

	/**
	 * Register REST API routes for demos.
	 *
	 * @since 2.0.0
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
					'callback'            => array( $this, 'get_demos' ),
					'permission_callback' => array( $this, 'get_demos_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/site-data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_data' ),
					'permission_callback' => array( $this, 'get_demos_permissions_check' ),
					'args'                => array(
						'slug' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/install',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'install' ),
						'permission_callback' => function () {
							return current_user_can( 'install_themes' );
						},
						'args'                => array(
							'action' => array(
								'type'     => 'string',
								'required' => 'true',
								'enum'     => array( 'install-plugins', 'import-content', 'import-customizer', 'import-widgets', 'complete' ),
							),
						// 'complete' => array(
						//  'type'     => 'boolean',
						//  'required' => true,
						//  'default'  => false,
						// ),
						// 'demo-data' => [

						// ],
						// 'opts'   => array(
						//  'type'       => 'object',
						//  'required'   => false,
						//  'properties' => array(
						//      'force_install_theme' => array(
						//          'type'    => 'boolean',
						//          'default' => true,
						//      ),
						//      'blogname'            => array(
						//          'type' => 'string',
						//      ),
						//      'blogdescription'     => array(
						//          'type' => 'string',
						//      ),
						//      'logo'                => array(
						//          'type' => 'number',
						//      ),
						//  ),
						// ),
						),
					),
				)
			);
	}

		/**
	 * Install endpoint handler.
	 *
	 * @since 2.0.0
	 *
	 * Ensures the 'elearning' theme is installed and activated before running the import.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function install( $request ) {
		$theme_slug = 'elearning';

		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		if ( ! class_exists( '\Theme_Upgrader' ) || ! class_exists( '\Automatic_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$installed_theme = wp_get_theme( $theme_slug );

		if ( ! $installed_theme->exists() ) {
			$api = themes_api(
				'theme_information',
				array(
					'slug'   => $theme_slug,
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
				return new WP_Error(
					'theme_api_failed',
					__( 'Could not find the "elearning" theme on WordPress.org.', 'themegrill-demo-importer' ),
					array( 'status' => 500 )
				);
			}

			$skin     = new \Automatic_Upgrader_Skin();
			$upgrader = new \Theme_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result ) {
				return new WP_Error(
					'theme_install_failed',
					__( 'Theme installation failed.', 'themegrill-demo-importer' ),
					array( 'status' => 500 )
				);
			}

			wp_clean_themes_cache();
			$installed_theme = wp_get_theme( $theme_slug );
		}

		if ( get_stylesheet() !== $theme_slug ) {
			switch_theme( $theme_slug );
		}

		$action = $request instanceof \WP_REST_Request ? $request->get_param( 'action' ) : ( $request['action'] ?? '' );
		if ( ! $action ) {
			return new WP_Error( 'invalid_action', __( 'Invalid action provided', 'themegrill-demo-importer' ), array( 'status' => 500 ) );
		}

		$demo_config = $request instanceof \WP_REST_Request ? $request->get_param( 'demo_config' ) : ( $request['demo_config'] ?? array() );
		if ( empty( $demo_config ) ) {
			return new WP_Error( 'invalid_demo_config', __( 'Invalid demo config provided', 'themegrill-demo-importer' ), array( 'status' => 500 ) );
		}

		$options        = $request instanceof \WP_REST_Request ? ( $request->get_param( 'opts' ) ?: array() ) : ( $request['opts'] ?? array() );
		$import_service = new ImportService();
		$response       = $import_service->handleImport( $action, $demo_config, $options );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response instanceof \WP_REST_Response ) {
			$data          = (array) $response->get_data();
			$data['theme'] = array(
				'slug'      => $theme_slug,
				'installed' => true,
				'active'    => ( get_stylesheet() === $theme_slug ),
			);
			$response->set_data( $data );
			return $response;
		}

		$data          = is_array( $response ) ? $response : array();
		$data['theme'] = array(
			'slug'      => $theme_slug,
			'installed' => true,
			'active'    => ( get_stylesheet() === $theme_slug ),
		);

		return rest_ensure_response( $data );
	}


	/**
	 * Retrieve a list of available demos.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_demos( WP_REST_Request $request ) {
		$endpoint  = 'https://themegrilldemos.com/wp-json/themegrill-demos/v1/sites';
		$demo_data = $this->get_demo_data_with_cache( $endpoint );

		if ( is_wp_error( $demo_data ) ) {
			return new \WP_REST_Response(
				array(
					'error'   => $demo_data->get_error_code(),
					'message' => $demo_data->get_error_message(),
				),
				502
			);
		}

		$elearning_demos = $this->filter_elearning_demos( is_array( $demo_data ) ? $demo_data : array() );

		return rest_ensure_response(
			array(
				'demos' => $elearning_demos,
			)
		);
	}

	/**
	 * Retrieve a single site's data by slug.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_site_data( WP_REST_Request $request ) {
		$slug = sanitize_key( (string) $request->get_param( 'slug' ) );

		if ( '' === $slug ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required parameter: slug.',
				),
				400
			);
		}

		$base     = 'https://themegrilldemos.com';
		$endpoint = trailingslashit( $base ) . rawurlencode( $slug ) . '/wp-json/themegrill-demos/v1/sites/data';
		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'headers' => array(
					'User-Agent'   => 'Masteriyo/1.0; DemosController',
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Unexpected upstream status code: ' . $code,
				),
				502
			);
		}

		if ( '' === $body ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Empty response body from upstream.',
				),
				502
			);
		}

		$data = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid JSON in API response.',
				),
				502
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Retrieves demo data from a remote URL with transient-based caching.
	 *
	 * @since 2.0.0
	 *
	 * Generates a unique transient key from the URL, checks for cached data,
	 * and fetches fresh data only if the cache is missing or expired.
	 *
	 * @param string $url Remote JSON endpoint containing demo data.
	 * @return array|WP_Error Decoded demo data array on success, or WP_Error on failure.
	 */
	private function get_demo_data_with_cache( $url ) {
		$key = 'masteriyo_demo_data_' . md5( $url );
		// $cache = get_transient( $key );

		// if ( false !== $cache ) {
		//  return $cache;
		// }

		$data = $this->fetch_demo_data( $url );
		usort(
			$data,
			function( $a, $b ) {
				return $b['id'] <=> $a['id'];
			}
		);

		// if ( is_array( $data ) ) {
		//  set_transient( $key, $data, WEEK_IN_SECONDS );
		// }

		return $data;
	}


	/**
	 * Fetches and decodes demo data directly from the given URL.
	 *
	 * @since 2.0.0
	 *
	 * Sends an HTTP GET request with a custom user agent and validates
	 * response code, body content, and JSON format.
	 *
	 * @param string $url Remote JSON endpoint to request.
	 * @return array|WP_Error Associative array of decoded JSON on success,
	 *                        or WP_Error if the request fails or data is invalid.
	 */
	private function fetch_demo_data( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => function_exists( 'masteriyo_is_development' ) ? ! masteriyo_is_development() : true,
				'headers'   => array(
					'User-Agent'   => 'Masteriyo/1.0; DemosController',
					'Content-Type' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'demo_bad_status', 'Unexpected upstream status code: ' . $code );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return new WP_Error( 'demo_empty_body', 'Empty response body from upstream.' );
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error( 'demo_invalid_json', 'Invalid JSON from upstream.' );
		}

		return $decoded;
	}

	/**
	 * Filters a demo data array to include only demos with the `elearning` theme slug.
	 *
	 * @since 2.0.0
	 *
	 * @param array $demo_data Full list of demo definitions.
	 * @return array Filtered list of demos where 'theme_slug' equals 'elearning'.
	 */

	private function filter_elearning_demos( array $demo_data ) {
		$filtered = array_filter(
			$demo_data,
			static function ( $demo ) {
				return is_array( $demo ) && isset( $demo['theme_slug'] ) && 'elearning' === $demo['theme_slug'];
			}
		);

		return array_values( $filtered );
	}



	/**
	 * Check if current user has permission to access demo routes.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function get_demos_permissions_check( WP_REST_Request $request ) {
		return true;
	}
}
