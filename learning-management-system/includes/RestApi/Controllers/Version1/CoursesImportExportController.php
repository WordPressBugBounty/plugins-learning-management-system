<?php
/**
 * Import export controller class.
 *
 * @since 1.6.0
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Exception;
use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Exporter\CourseExporter;
use Masteriyo\FileHandler;
use Masteriyo\Helper\Permission;
use Masteriyo\Helper\Utils;
use Masteriyo\Importer\CourseImporter;
use Masteriyo\Jobs\CoursesExportJob;
use Masteriyo\Jobs\CoursesImportJob;
use Masteriyo\PostType\PostType;
use WP_Error;

/**
 * CoursesImportExportController class.
 *
 * @since 1.6.0
 */
class CoursesImportExportController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	protected $rest_base = 'courses';

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.6.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'export_items' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_items' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import/sample-courses',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_sample_courses' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
				'args'                => array(
					'status' => array(
						'enum'    => array( 'publish', 'draft' ),
						'default' => 'publish',
						'type'    => 'string',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export/progress-status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_export_progress_status' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import/progress-status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_import_progress_status' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);
	}

	/**
	 * Import items.
	 *
	 * @since 1.6.0
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_items( \WP_REST_Request $request ) {
		$file_params  = $request->get_file_params();
		$chunk_index  = absint( $request->get_param( 'chunkIndex' ) );
		$total_chunks = absint( $request->get_param( 'totalChunks' ) );
		$file_name    = sanitize_file_name( $request->get_param( 'fileName' ) );

		$file_handler  = new FileHandler();
		$file_creation = $file_handler->create_file( 'import/courses', $file_name );

		if ( is_wp_error( $file_creation ) ) {
			return $file_creation;
		}

		$output_file_path = $file_creation['file_path'];
		$output_file      = fopen( $output_file_path, 'a' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		if ( false === $output_file ) {
			return new \WP_Error(
				'file_open_failed',
				__( 'Failed to open output file for writing.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		try {
			if ( 0 === $chunk_index ) {
				ftruncate( $output_file, 0 );
			}

			if ( $file_name && $total_chunks ) {
				$file_info = $this->handle_chunked_upload( $file_handler, $output_file, $output_file_path, $file_params, $file_name, $chunk_index, $total_chunks );

				if ( is_wp_error( $file_info ) ) {
					return $file_info;
				}

				if ( $file_info['current_chunk_index'] < $total_chunks - 1 ) {
					return new \WP_REST_Response(
						array(
							'message'       => __( 'Chunk uploaded successfully.', 'learning-management-system' ),
							'current_chunk' => $file_info['current_chunk_index'],
						),
						200
					);
				}

				$file = $file_info['file'];
			} else {
				$file = $this->get_import_file( $file_params );
			}

			if ( is_wp_error( $file ) ) {
				return $file;
			}

			$importer = new CourseImporter();
			$result   = $importer->start_import( $file );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( $result instanceof \WP_REST_Response ) {
				return $result;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'import_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		} finally {
			if ( is_resource( $output_file ) ) {
				fclose( $output_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}

		return new \WP_REST_Response(
			array(
				'status'  => 'completed',
				'message' => __( 'Import successful.', 'learning-management-system' ),
			),
			200
		);
	}

	/**
	 * Export courses.
	 *
	 * @since 1.6.0
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function export_items( \WP_REST_Request $request ) {
		$course_items = $request->get_param( 'course_items' );
		$course_ids   = $request->get_param( 'course_ids' );

		$course_items = is_array( $course_items ) ? array_map( 'sanitize_text_field', $course_items ) : array();
		$course_ids   = is_array( $course_ids ) ? array_map( 'intval', $course_ids ) : array();

		try {
			$exporter = new CourseExporter();
			$data     = $exporter->export( $course_items, $course_ids );

			return rest_ensure_response( $data );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'masteriyo_rest_export_failed',
				__( 'Exporting courses failed: ', 'learning-management-system' ) . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Import sample courses.
	 *
	 * @since 1.6.0
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_sample_courses( \WP_REST_Request $request ) {
		$status = $request->get_param( 'status' ) ?? PostStatus::PUBLISH;
		$file   = Constants::get( 'MASTERIYO_PLUGIN_DIR' ) . '/sample-data/courses.json';

		if ( ! file_exists( $file ) ) {
			return new \WP_Error(
				'masteriyo_rest_import_sample_courses_file_not_found',
				__( 'Sample courses file not found.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		try {
			$importer = new CourseImporter( $status );
			$importer->import( $file, 'sample-courses' );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'masteriyo_rest_import_sample_courses_error',
				$e->getMessage()
			);
		}

		return new \WP_REST_Response(
			array(
				'message' => __( 'Sample courses installed.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Parse and save the uploaded file chunk.
	 *
	 * @since 1.6.0
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
	 * Handle the chunked file upload process.
	 *
	 * @since 1.14.0
	 *
	 * @param mixed  $file_handler The file handler.
	 * @param string $output_file The output file path.
	 * @param array $output_file_path The output file path.
	 * @param array  $files       The $_FILES array for the chunks.
	 * @param string $file_name   The original file name for reconstruction.
	 * @param int    $chunk_index The current chunk index.
	 * @param int    $total_chunks The total number of chunks.
	 * @return string|\WP_Error File path on success or WP_Error on failure.
	 */
	protected function handle_chunked_upload( $file_handler, $output_file, $output_file_path, $files, $file_name, $chunk_index, $total_chunks ) {
		if ( ! isset( $files['file'] ) ) {
			return new \WP_Error(
				'rest_upload_no_data',
				__( 'No data supplied.', 'learning-management-system' )
			);
		}

		$file = $files['file'];

		if ( ! isset( $file['tmp_name'] ) || ! isset( $file['name'] ) ) {
			$file_handler->delete( $output_file_path );

			return new \WP_Error(
				'invalid_file',
				__( 'Invalid file parameters.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$chunk_content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $chunk_content ) {
			$file_handler->delete( $output_file_path );

			return new \WP_Error(
				'chunk_file_read_failed',
				__( 'Failed to read chunk file content.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		if ( false === fwrite( $output_file, $chunk_content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			$file_handler->delete( $output_file_path );

			return new \WP_Error(
				'final_file_write_failed',
				__( 'Failed to write to final file.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'current_chunk_index' => $chunk_index,
			'file'                => $output_file_path,
		);
	}

	/**
	 * Check if a given request has access to import items.
	 *
	 * @since 1.6.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function import_items_permission_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( PostType::COURSE, 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_import',
				__( 'Sorry, you are not allowed to import courses.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}


	/**
	 * Retrieves the current export progress status.
	 *
	 * @since 1.14.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_export_progress_status() {
		try {
			$created_at = CourseExporter::get_file_creation_time();
			if ( CoursesExportJob::is_tasks_in_progress() ) {
				return rest_ensure_response(
					array(
						'status'  => 'progress',
						'message' => __( 'Export in progress. Please wait...', 'learning-management-system' ),
					)
				);
			}

			if ( CoursesExportJob::is_tasks_completed() ) {
				Utils::set_cookie( 'masteriyo_course_export_is_in_progress_' . get_current_user_id(), '0', time() - HOUR_IN_SECONDS );

				return rest_ensure_response(
					array(
						'status'       => 'completed',
						'download_url' => CourseExporter::get_download_url(),
						'message'      => sprintf(
							/* translators: %s: date and time. */
							__( 'Export completed on %s.', 'learning-management-system' ),
							$created_at
						),
					)
				);
			}

			return rest_ensure_response(
				array(
					'status'       => 'idle',
					'download_url' => CourseExporter::get_download_url(),
					'message'      => sprintf(
						/* translators: %s: date and time. */
						__( 'Export completed on %s.', 'learning-management-system' ),
						$created_at
					),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'export_status_error',
				__( 'Error retrieving export status. Try again later.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Retrieves the current export progress status.
	 *
	 * @since 1.14.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_import_progress_status() {
		try {
			if ( CoursesImportJob::is_task_in_progress() ) {
				return rest_ensure_response(
					array(
						'status'  => 'progress',
						'message' => __( 'Import in progress. Please wait...', 'learning-management-system' ),
					)
				);
			}

			if ( CoursesImportJob::is_task_completed() ) {
				Utils::set_cookie( 'masteriyo_course_import_is_in_progress_' . get_current_user_id(), '0', time() - HOUR_IN_SECONDS );

				return rest_ensure_response(
					array(
						'status'  => 'completed',
						'message' => __( 'Import successful.', 'learning-management-system' ),
					)
				);
			}

			return rest_ensure_response(
				array(
					'status'  => 'idle',
					'message' => __( 'No import in progress.', 'learning-management-system' ),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'import_status_error',
				__( 'Error retrieving import status. Try again later.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}
	}
}
