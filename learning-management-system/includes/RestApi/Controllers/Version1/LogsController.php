<?php

/**
 * REST API Logs Controller
 *
 * Handles requests to the logs endpoint, specifically for managing log files.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since 1.12.2
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Helper\Permission;
use WP_REST_Controller;
use WP_REST_Request;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Logs Controller Class.
 *
 * @package Masteriyo\RestApi
 */
/**
 * REST API Logs Controller
 *
 * Handles requests to the logs endpoint, specifically for managing log files.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since 1.12.2
 */
class LogsController extends CrudController {

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
	protected $rest_base = 'logs';

	/**
	 * Object type.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $object_type = 'logs';

	/**
	 * Permission class instance.
	 *
	 * @since 1.12.2
	 * @var Permission
	 */
	protected $permission;

	/**
	 * Constructor.
	 *
	 * Sets up the Logs controller.
	 *
	 * @since 1.12.2
	 * @param Permission|null $permission The permission handler instance.
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.12.2
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'Log IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-_]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to read/delete item(s).
	 *
	 * @since 1.12.2
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Get the query parameters for the log collection.
	 *
	 * @since 1.12.2
	 *
	 * @param array $params The default query parameters.
	 *
	 * @return array The filtered query parameters.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'learning-management-system' ),
			'default'     => 'date',
			'enum'        => array(
				'name',
				'date',
				'size',
			),
			'type'        => 'string',
		);

		$params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.', 'learning-management-system' ),
			'default'     => 'desc',
			'enum'        => array(
				'asc',
				'desc',
			),
			'type'        => 'string',
		);

		$params['search'] = array(
			'description' => __( 'Search log files by name.', 'learning-management-system' ),
			'type'        => 'string',
			'default'     => '',
		);

		/**
		 * Filters the query parameters for the log collection.
		 *
		 * @since 1.12.2
		 *
		 * @param array $params The query parameters.
		 * @return array The filtered query parameters.
		 */
		return apply_filters( 'masteriyo_log_collection_params', $params );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 1.12.2
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$per_page = absint( $request['per_page'] ?? get_option( 'posts_per_page' ) ) ?? 10;
		$page     = absint( $request['page'] ) ?? 1;
		$orderby  = sanitize_text_field( $request['orderby'] ) ?? 'date';
		$order    = sanitize_text_field( $request['order'] ) ?? 'desc';
		$search   = sanitize_text_field( $request['search'] ) ?? '';

		$log_files = $this->get_log_files();
		if ( $search ) {
			$log_files = array_filter(
				$log_files,
				function( $log_file ) use ( $search ) {
					return stripos( basename( $log_file ), $search ) !== false;
				}
			);
		}

		usort(
			$log_files,
			function( $a, $b ) use ( $orderby, $order ) {
				$result = 0;
				if ( 'size' === $orderby ) {
					$result = filesize( $a ) - filesize( $b );
				} elseif ( 'name' === $orderby ) {
					$result = strcmp( basename( $a ), basename( $b ) );
				} else {
					$result = filemtime( $a ) - filemtime( $b );
				}
				return ( 'asc' === $order ) ? $result : -$result;
			}
		);

		$total_logs  = count( $log_files );
		$total_pages = (int) ceil( $total_logs / $per_page );

		$offset    = ( $page - 1 ) * $per_page;
		$log_files = array_slice( $log_files, $offset, $per_page );

		$items = $this->prepare_items_for_response( $request, $log_files );

		$data = array(
			'data' => $items,
			'meta' => array(
				'total'        => $total_logs,
				'pages'        => $total_pages,
				'current_page' => $page,
				'per_page'     => $per_page,
			),
		);

		$response = rest_ensure_response( $data );

		/**
		 * Filters the prepared response for the log items.
		 *
		 * @since 1.12.2
		 *
		 * @param WP_REST_Response $response The prepared response.
		 * @param array $items The log items.
		 * @param WP_REST_Request $request The original request.
		 *
		 * @return WP_REST_Response The filtered response.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_response", $response, $items, $request );
	}

	/**
	 * Retrieves the list of log files in the log directory.
	 *
	 * The log files are sorted in descending order by their modification time.
	 *
	 * @since 1.12.2
	 *
	 * @return string[] The list of log file paths.
	 */
	private function get_log_files() {
		$log_files = glob( MASTERIYO_LOG_DIR . '*.log' );

		if ( ! $log_files ) {
			return array();
		}

		return $log_files;
	}

	/**
	 * Retrieves the URL of a log file.
	 *
	 * @since 1.12.2
	 *
	 * @param string $file_name The name of the log file.
	 *
	 * @return string The URL of the log file.
	 */
	private function get_log_file_url( $file_name ) {
		if ( ! $file_name ) {
			return '';
		}

		$log_url = trailingslashit( MASTERIYO_LOG_URL ) . $file_name;

		return $log_url;
	}

	/**
	 * Prepares the log items for the REST API response.
	 *
	 * The log files are sorted in descending order by their modification time.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param array $log_files Optional. The list of log file paths. If not provided, the list will be retrieved using `get_log_files()`.
	 * @return array The prepared log items.
	 */
	protected function prepare_items_for_response( \WP_REST_Request $request, $log_files = array() ) {
		if ( empty( $log_files ) ) {
			$log_files = $this->get_log_files();
		}

		$items = array();

		if ( ! empty( $log_files ) ) {
			foreach ( $log_files as $log_file ) {
				$log_id     = basename( $log_file, '.log' );
				$size_bytes = filesize( $log_file );
				$size_kb    = round( $size_bytes / 1024, 2 );
				$items[]    = array(
					'id'             => $log_id,
					'name'           => basename( $log_file ),
					'url'            => $this->get_log_file_url( basename( $log_file ) ),
					'path'           => $log_file,
					'size'           => $size_kb,
					'formatted_size' => $size_kb . ' KB',
					'date'           => masteriyo_rest_prepare_date_response( filemtime( $log_file ) ),
				);
			}
		}

		/**
		 * Filters rest prepared log items.
		 *
		 * @since 1.12.2
		 *
		 * @param array $items Items data.
		 * @param \WP_REST_Request $request Request.
		 */
		return apply_filters( 'masteriyo_rest_prepared_log_items', $items, $request );
	}

	/**
	 * Retrieves a single log item.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$id       = sanitize_text_field( $request['id'] );
		$log_file = MASTERIYO_LOG_DIR . "/{$id}.log";

		if ( ! file_exists( $log_file ) ) {
			return new WP_Error( 'masteriyo_log_not_found', __( 'Log file not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$log_data   = \file_get_contents( $log_file );
		$size_bytes = filesize( $log_file );
		$size_kb    = round( $size_bytes / 1024, 2 );

		$response = rest_ensure_response(
			array(
				'id'             => $id,
				'name'           => basename( $log_file ),
				'path'           => $log_file,
				'url'            => $this->get_log_file_url( basename( $log_file ) ),
				'log'            => $log_data,
				'size'           => $size_kb,
				'formatted_size' => $size_kb . ' KB',
				'date'           => masteriyo_rest_prepare_date_response( filemtime( $log_file ) ),
			)
		);

		return $response;
	}

	/**
	 * Deletes a single log item.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$id       = sanitize_text_field( $request['id'] );
		$log_file = MASTERIYO_LOG_DIR . "/{$id}.log";

		if ( ! file_exists( $log_file ) ) {
			return new WP_Error( 'masteriyo_log_not_found', __( 'Log file not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! unlink( $log_file ) ) {
			return new WP_Error( 'masteriyo_log_not_deleted', __( 'Failed to delete log file.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Deletes a single log item.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_items( $request ) {
		$ids     = $request['ids'];
		$deleted = array();
		$errors  = array();

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return new WP_Error( 'masteriyo_invalid_ids', __( 'Invalid log IDs.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$deleted = array();

		foreach ( $ids as $id ) {
			$id = sanitize_text_field( $id );

			$log_file = MASTERIYO_LOG_DIR . "/{$id}.log";

			if ( file_exists( $log_file ) && unlink( $log_file ) ) {
				$deleted[] = $id;
			} else {
				$errors[] = $id;
			}
		}

		return rest_ensure_response(
			array(
				'deleted' => $deleted,
				'errors'  => $errors,
			)
		);
	}
}
