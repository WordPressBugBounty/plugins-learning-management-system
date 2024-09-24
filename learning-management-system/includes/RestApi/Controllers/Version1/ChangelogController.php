<?php
/**
 * Changelog controller.
 *
 * @since  1.10.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Helper\Permission;


/**
 * Changelog controller class.
 */
class ChangelogController extends CrudController {

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
	protected $rest_base = 'changelog';


	protected $hierarchical = false;

	/**
	 * Permission class.
	 *
	 * @since 1.10.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.10.0
	 *
	 * @param Permission $permission Permission object.
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Retrieves items, including parsed changelog data, based on the request.
	 *
	 * This method retrieves items based on the provided request . It first reads the changelog content using the read_changelog() method and then parses the changelog using the parse_changelog() method . The parsed changelog data is returned as part of a WP_REST_Response object with a success status and the changelog data.
	 *
	 * @since  1.10.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response A response object containing a success status and the parsed changelog data.
	 *
	 */
	public function get_items( $request ) {
		$cache     = masteriyo_transient_cache();
		$cache_key = 'changelog_data';
		$changelog = $cache->get_cache( $cache_key );

		if ( ! $changelog ) {
			$changelog = $this->read_changelog();
			$changelog = $this->parse_changelog( $changelog );
			$cache->set_cache( $cache_key, $changelog, 12 * HOUR_IN_SECONDS );
		}

		return new \WP_REST_Response(
			array(
				'success'   => true,
				'changelog' => $changelog,
			),
			200
		);
	}

	/**
	 * Reads the changelog file and retrieves its content.
	 *
	 * This method invokes the masteriyo_file_get_contents() function to retrieve the content of the 'CHANGELOG.txt' file . if the content is successfully retrieved, it is returned . if there is an error reading the file, a WP_Error object is returned indicating the failure to read the changelog.
	 *
	 * @since  1.10.0
	 *
	 * @return string | WP_Error The content of the changelog file if it can be read, otherwise returns a WP_Error object indicating the failure.
	 *
	 */
	public function read_changelog() {
		$raw_changelog = $this->masteriyo_file_get_contents( 'changelog.txt' );

		if ( ! $raw_changelog ) {
			return new \WP_Error( 'changelog_read_error', esc_html__( 'Failed to read changelog.', 'learning-management-system' ) );
		}

		return $raw_changelog;
	}

	/**
	 * Parses the raw changelog content into structured data.
	 *
	 * This method takes the raw changelog content as input and splits it into individual entries based on version numbers and dates . Each entry is then parsed to extract the version number, date, and associated changes . The parsed data is returned as an array of associative arrays, each containing version, date, and changes information.
	 *
	 * @since  1.10.0
	 *
	 * @param string $raw_changelog The raw content of the changelog.
	 *
	 * @return array An array of parsed changelog entries, each containing version, date, and changes information.
	 */
	protected function parse_changelog( $raw_changelog ) {
		if ( ! is_string( $raw_changelog ) ) {
			return array();
		}

		$entries = preg_split( '/(?=\=\s\d+\.\d+\.\d+|\Z)/', $raw_changelog, -1, PREG_SPLIT_NO_EMPTY );

		$parsed_changelog = array();

		foreach ( $entries as $entry ) {
			$date    = null;
			$version = null;

			if ( preg_match( '/^\=\s([\d\.]+)\s\-\s([\d-]+)\s\=$/m', $entry, $matches ) ) {
				$version = $matches[1] ?? null;
				$date    = gmdate( 'Y-m-d', strtotime( $matches[2] ?? '' ) );
			}

			$changes_arr = array();

			if ( preg_match_all( '/^\-\s(.+)$/m', $entry, $matches ) ) {
				$changes = $matches[1] ?? null;

				if ( is_array( $changes ) ) {
					foreach ( $changes as $change ) {
						$parts = explode( ' - ', $change );
						$tag   = trim( $parts[0] ?? '' );
						$data  = isset( $parts[1] ) ? trim( $parts[1] ) : '';

						if ( isset( $changes_arr[ $tag ] ) ) {
							$changes_arr[ $tag ][] = $data;
						} else {
							$changes_arr[ $tag ] = array( $data );
						}
					}
				}
			}

			if ( $version && $date && $changes_arr ) {
				$parsed_changelog[] = array(
					'version' => $version,
					'date'    => $date,
					'changes' => $changes_arr,
				);
			}
		}

		return $parsed_changelog;
	}

	/**
	 * Retrieves the content of a specified file.
	 *
	 * This method retrieves the content of the file specified by the provided WP_REST_Request object. It first ensures the existence of the file using WordPress Filesystem API, and if found, it reads the contents of the file using the get_contents() method. The file path is constructed based on the plugin directory path and the provided file name.
	 *
	 * @since  1.10.0
	 *
	 * @param WP_REST_Request $file Full details about the request.
	 *
	 * @return string|WP_Error The content of the file if it exists and can be read, otherwise returns a WP_Error object.
	 */
	public function masteriyo_file_get_contents( $file ) {
		if ( $file ) {
			global $wp_filesystem;
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
			$local_file = preg_replace( '/\\\\|\/\//', '/', plugin_dir_path( MASTERIYO_PLUGIN_FILE ) . $file );

			if ( $wp_filesystem->exists( $local_file ) ) {
				$response = $wp_filesystem->get_contents( $local_file );
				return $response;
			}
		}
	}

	/**
	 * Checks the capability of user. Request is only valid if user is admin or manager.
	 *
	 * @since  1.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean|WP_Error true if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );

	}

}
