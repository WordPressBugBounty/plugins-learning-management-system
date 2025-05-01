<?php
/**
 * Quizzes import/export controller class.
 *
 * @since 1.6.15
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\AdminFileDownloadHandler;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\Exporter\QuizExporter;
use Masteriyo\Helper\Permission;
use Masteriyo\Importer\QuizImporter;
use Masteriyo\PostType\PostType;

/**
 * QuizzesImportExportController class.
 *
 * @since 1.6.15
 */
class QuizzesImportExportController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.6.15
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.6.15
	 *
	 * @var string
	 */
	protected $rest_base = 'quizzes';

	/**
	 * Permission class.
	 *
	 * @since 1.6.15
	 *
	 * @var \Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.6.15
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.6.15
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
			'/' . $this->rest_base . '/single-export',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'export_item' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/single-import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_item' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);
	}

	/**
	 * Import items.
	 *
	 * @since 1.6.15
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_items( \WP_REST_Request $request ) {
		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		try {
			$importer = new QuizImporter( $file );
			$importer->import();
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'import_failed',
				$e->getMessage()
			);
		}

		return new \WP_REST_Response(
			array(
				'message' => __( 'Import successful.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Export items.
	 *
	 * @since 1.6.15
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function export_items( \WP_REST_Request $request ) {
		$exporter = new QuizExporter();
		$data     = $exporter->export();

		if ( ! $data ) {
			return new \WP_Error( 'quizzes_export_failure', 'Something went wrong while exporting quizzes.', array( 'status' => 500 ) );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Parse Import file.
	 *
	 * @since 1.6.15
	 *
	 * @param array $files $_FILES array for a given file.
	 *
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
	 * Check if a given request has access to import items.
	 *
	 * @since 1.6.15
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
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

		if ( ! $this->permission->rest_check_post_permissions( PostType::QUIZ, 'create' ) ) {
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

	/*
	|--------------------------------------------------------------------------
	| Single Quiz Export/Import.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Export single quiz.
	 *
	 * @since 1.16.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function export_item( \WP_REST_Request $request ) {
		$quiz_id = absint( $request->get_param( 'quiz_id' ) );

		if ( ! $quiz_id || PostType::QUIZ !== get_post_type( $quiz_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_quiz_id',
				__( 'Invalid quiz ID.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$exporter = new QuizExporter();
		$data     = $exporter->export( $quiz_id );

		if ( ! $data ) {
			return new \WP_Error( 'masteriyo_rest_quiz_export_failure', 'Something went wrong while exporting quizzes.', array( 'status' => 500 ) );
		}

		$filename     = $data['filename'];
		$download_url = AdminFileDownloadHandler::get_download_url( QuizExporter::FILE_PATH_ID, $filename );

		return rest_ensure_response(
			array(
				'download_url' => $download_url,
				'message'      => __( 'Quiz exported successfully.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Import a quiz.
	 *
	 * @since 1.16.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_item( \WP_REST_Request $request ) {
		$course_id  = absint( $request->get_param( 'course_id' ) );
		$section_id = absint( $request->get_param( 'section_id' ) );

		$validation_error = $this->validate_request_params( $course_id, $section_id );

		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		try {
			$wp_filesystem = $this->initialize_filesystem();
			$file_content  = $this->read_import_file( $file, $wp_filesystem );

			$items = $this->decode_and_validate_import_file( $file_content );

			if ( is_wp_error( $items ) ) {
				return $items;
			}

			$quizzes   = $items['quizzes'];
			$questions = $items['questions'];

			$count_error = $this->validate_quiz_count( count( $quizzes ) );

			if ( is_wp_error( $count_error ) ) {
				return $count_error;
			}

			$quiz_id = $this->process_quizzes( $quizzes, $questions, $section_id, $course_id );

			if ( is_wp_error( $quiz_id ) ) {
				return $quiz_id;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'import_failed',
				$e->getMessage(),
				array( 'status' => $e->getCode() )
			);
		}

		return rest_ensure_response(
			array(
				'message'  => __( 'Quiz imported successfully.', 'learning-management-system' ),
				'quizId'   => $quiz_id,
				'courseId' => $course_id,
			)
		);
	}

	/**
	 * Initializes the WordPress filesystem.
	 *
	 * @since 1.16.0
	 *
	 * @throws \Exception If the filesystem initialization fails.
	 *
	 * @return \WP_Filesystem_Direct The initialized filesystem object.
	 */
	private function initialize_filesystem() {
		$wp_filesystem = masteriyo_get_filesystem();
		if ( ! $wp_filesystem ) {
			throw new \Exception( __( 'Filesystem initialization failed.', 'learning-management-system' ) );
		}

		return $wp_filesystem;
	}

	/**
	 * Reads the import file and returns its content.
	 *
	 * @since 1.16.0
	 *
	 * @param string $file The file to read.
	 * @param \WP_Filesystem_Direct $wp_filesystem The initialized filesystem object.
	 *
	 * @throws \Exception If the file does not exist or is unreadable.
	 *
	 * @return string The content of the file.
	 */
	private function read_import_file( $file, $wp_filesystem ) {
		if ( ! $wp_filesystem->exists( $file ) ) {
			throw new \Exception( __( 'Invalid or unreadable JSON file.', 'learning-management-system' ) );
		}

		wp_raise_memory_limit( 'admin' );

		return $wp_filesystem->get_contents( $file );
	}

	/**
	 * Decodes and validates the import file content.
	 *
	 * @since 1.16.0
	 *
	 * @param string $file_content The content of the import file.
	 *
	 * @throws \Exception If the file content is invalid or can not be decoded.
	 *
	 * @return array The decoded and validated import file content.
	 */
	private function decode_and_validate_import_file( $file_content ) {
		$items = json_decode( $file_content, true );

		if ( ! is_array( $items ) || ! isset( $items['quizzes'], $items['questions'] ) ) {
			return new \WP_Error(
				'invalid_import_file',
				__( 'Invalid import file structure.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return $items;
	}

	/**
	 * Process the quizzes to be imported.
	 *
	 * @since 1.16.0
	 *
	 * @param array $quizzes The quizzes to be imported.
	 * @param array $questions The questions to be imported.
	 * @param int $section_id The section ID where the quizzes will be imported.
	 * @param int $course_id The course ID where the quizzes will be imported.
	 *
	 * @return int|\WP_Error The ID of the last imported quiz or a WP_Error object if any of the quizzes could not be imported.
	 */
	private function process_quizzes( $quizzes, $questions, $section_id, $course_id ) {
		foreach ( $quizzes as $quiz ) {
			$quiz_id = $this->import_quiz( $quiz, $section_id, $course_id );

			if ( is_wp_error( $quiz_id ) ) {
				return $quiz_id;
			}

			$processed_questions = array();

			// Insert question bank data.
			if ( isset( $quiz['bank_data'] ) && is_array( $quiz['bank_data'] ) ) {
				foreach ( $quiz['bank_data'] as $bank_data ) {
					$question_id = isset( $bank_data['question_id'] ) ? absint( $bank_data['question_id'] ) : 0;
					$menu_order  = isset( $bank_data['menu_order'] ) ? absint( $bank_data['menu_order'] ) : 0;

					if ( $question_id ) {
						masteriyo_add_question_to_quiz( $quiz_id, $question_id, $menu_order );

						$processed_questions[] = $question_id;
					}
				}
			}

			if ( ! empty( $questions ) ) {
					$this->import_quiz_questions( $questions, $quiz_id, $course_id, $processed_questions );
			}
		}

		return $quiz_id;
	}

	/**
	 * Validates the course ID and section ID request parameters.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id The course ID.
	 * @param int $section_id The section ID.
	 *
	 * @return true|\WP_Error Returns true if the course ID and section ID are valid or a WP_Error object if any of the IDs are invalid.
	 */
	private function validate_request_params( int $course_id, int $section_id ) {
		if ( ! $course_id || PostType::COURSE !== get_post_type( $course_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid course ID.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $section_id || PostType::SECTION !== get_post_type( $section_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_section_id',
				__( 'Invalid section ID.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate the number of quizzes in the import file.
	 *
	 * @since 1.16.0
	 *
	 * @param int $quizzes_count The number of quizzes found.
	 *
	 * @return true|\WP_Error
	 */
	private function validate_quiz_count( int $quizzes_count ) {
		if ( ! $quizzes_count ) {
			return new \WP_Error(
				'import_failed',
				__( 'No quiz found in the import file.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		if ( $quizzes_count > 1 ) {
			return new \WP_Error(
				'import_failed',
				__( 'You can only import one quiz at a time.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Import a quiz and return its ID.
	 *
	 * @since 1.16.0
	 *
	 * @param array $quiz The quiz data.
	 * @param int   $section_id The section ID.
	 * @param int   $course_id The course ID.
	 *
	 * @return int|\WP_Error
	 */
	private function import_quiz( $quiz, $section_id, $course_id ) {
		if ( ! is_array( $quiz ) || PostType::QUIZ !== $quiz['post_type'] ) {
			return new \WP_Error(
				'import_failed',
				__( 'Invalid quiz data.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$quiz['post_parent']        = $section_id;
		$quiz['meta']['_course_id'] = $course_id;

		$metas = $quiz['meta'];

		$quiz = masteriyo_array_except(
			$quiz,
			array( 'ID', 'terms', 'meta', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'post_author' )
		);

		$quiz['author'] = get_current_user_id();

		$query = new \WP_Query(
			array(
				'post_type'      => SectionChildrenPostType::all(),
				'post_status'    => PostStatus::all(),
				'posts_per_page' => 1,
				'post_parent'    => $section_id,
			)
		);

		$quiz['menu_order'] = $query->found_posts;

		$quiz_id = \wp_insert_post( $quiz );

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		foreach ( $metas as $key => $value ) {
			$value = is_array( $value ) && isset( $value[0] ) ? $value[0] : maybe_unserialize( $value );

			\add_post_meta( $quiz_id, $key, $value );
		}

		return $quiz_id;
	}

	/**
	 * Import quiz questions.
	 *
	 * @since 1.16.0
	 *
	 * @param array $questions The list of questions.
	 * @param int $quiz_id The new quiz ID.
	 * @param int $course_id The course ID.
	 * @param array $processed_questions The list of processed questions.
	 *
	 * @return void
	 */
	private function import_quiz_questions( $questions, $quiz_id, $course_id, $processed_questions = array() ) {
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) || PostType::QUESTION !== $question['post_type'] ) {
				continue;
			}

			if ( in_array( $question['ID'], $processed_questions, true ) ) {
				continue;
			}

			$question['post_parent']        = $quiz_id;
			$question['meta']['_course_id'] = $course_id;

			$metas = $question['meta'];

			$question = masteriyo_array_except(
				$question,
				array( 'ID', 'terms', 'meta', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'post_author' )
			);

			$question['author']       = get_current_user_id();
			$question['post_content'] = addslashes( wp_json_encode( json_decode( $question['post_content'] ), JSON_UNESCAPED_UNICODE ) );

			$question_id = \wp_insert_post( $question );

			if ( is_wp_error( $question_id ) ) {
				return $question_id;
			}

			foreach ( $metas as $key => $value ) {
				$value = is_array( $value ) && isset( $value[0] ) ? $value[0] : maybe_unserialize( $value );

				\add_post_meta( $question_id, $key, $value );
			}
		}
	}
}
