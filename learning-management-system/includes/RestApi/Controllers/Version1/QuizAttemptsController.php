<?php
/**
 * Quiz attempt rest controller.
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuizAttemptStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Helper\Permission;
use Masteriyo\Models\QuizAttempt;
use Masteriyo\Query\QuizAttemptQuery;
use Masteriyo\RestApi\Controllers\Version1\CrudController;

class QuizAttemptsController extends CrudController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.3.2
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.3.2
	 *
	 * @var string
	 */
	protected $rest_base = 'quizes/attempts';

	/**
	 * Route base.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	protected $object_type = 'quiz-attempt';

	/**
	 * If object is hierarchical.
	 *
	 * @since 1.3.2
	 *
	 * @var bool
	 */
	protected $hierarchical = false;

	/**
	 * Permission class.
	 *
	 * @since 1.3.2
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.3.2
	 *
	 * @param Permission $permission
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

		// Pro.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/update_answer_points',
			array(
				'args' => array(
					'id'        => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'answer_id' => array(
						'description' => __( 'Answer to update the points.', 'learning-management-system' ),
						'type'        => 'number',
						'required'    => true,
					),
					'points'    => array(
						'description' => __( 'The new points.', 'learning-management-system' ),
						'type'        => 'number',
						'required'    => true,
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_answer_points' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
			)
		);

		// Pro.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/update_answer_correct_status',
			array(
				'args' => array(
					'id'         => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'answer_id'  => array(
						'description' => __( 'Answer to update the points.', 'learning-management-system' ),
						'type'        => 'number',
						'required'    => true,
					),
					'is_correct' => array(
						'description' => __( 'The new status. True if correct otherwise false', 'learning-management-system' ),
						'type'        => 'boolean',
						'required'    => true,
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_answer_correct_status' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/last-attempt',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_last_attempt' ),
					'permission_callback' => function() {
						return is_user_logged_in() || masteriyo( 'session' )->get_user_id();
					},
					'args'                => array(
						'quiz_id' => array(
							'description'       => __( 'Quiz ID', 'learning-management-system' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
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
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'Quiz attempt IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.3.2
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = array(
			'quiz_id'  => array(
				'description'       => __( 'Quiz ID', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'user_id'  => array(
				'description'       => __( 'User ID', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'   => array(
				'description'       => __( 'Quiz attempt status.', 'learning-management-system' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => array(
					'id',
					'course_id',
					'quiz_id',
					'attempt_started_at',
					'attempt_ended_at',
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'     => array(
				'description'       => __( 'Paginate the quiz attempts.', 'learning-management-system' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Limit items per page.', 'learning-management-system' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		return $params;
	}

	/**
	 * Get the item schema, conforming to JSON Schema.
	 *
	 * @since 1.5.4
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'                       => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'course_id'                => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'quiz_id'                  => array(
					'description' => __( 'Quiz ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'user_id'                  => array(
					'description' => __( 'User ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'total_questions'          => array(
					'description' => __( 'Number of questions.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'total_answered_questions' => array(
					'description' => __( 'Number of answered questions.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'total_marks'              => array(
					'description' => __( 'Total marks.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'total_attempts'           => array(
					'description' => __( 'Number of attempts.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'total_correct_answers'    => array(
					'description' => __( 'Number of correct answers.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'total_incorrect_answers'  => array(
					'description' => __( 'Number of incorrect answers.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'earned_marks'             => array(
					'description' => __( 'Total earned marks/points.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'answers'                  => array(
					'description' => __( 'Answers given by user.', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'attempt_status'           => array(
					'description' => __( 'Quiz attempt status.', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => QuizAttemptStatus::STARTED,
					'enum'        => QuizAttemptStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'attempt_started_at'       => array(
					'description' => __( 'Quiz attempt started time.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'attempt_ended_at'         => array(
					'description' => __( 'Quiz attempt ended time.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewed'                 => array(
					'description' => __( 'Quiz attempt reviewed.', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'passed'                   => array(
					'description' => __( 'Whether the quiz attempt passed. Null until the attempt has ended.', 'learning-management-system' ),
					'type'        => array( 'boolean', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get object.
	 *
	 * @since 1.3.2
	 *
	 * @param  int $id Object ID.
	 * @return \Masteriyo\Models\QuizAttempt|false Model object or false.
	 */
	protected function get_object( $id ) {
		try {
			$id           = $id instanceof \stdClass ? $id->id : $id;
			$id           = $id instanceof QuizAttempt ? $id->get_id() : $id;
			$quiz_attempt = masteriyo( 'quiz-attempt' );
			$quiz_attempt->set_id( $id );
			$quiz_attempt_repo = masteriyo( 'quiz-attempt.store' );
			$quiz_attempt_repo->read( $quiz_attempt );
		} catch ( \Exception $e ) {
			return false;
		}

		return $quiz_attempt;
	}

	/**
	 * Get objects.
	 *
	 * @since  1.0.6
	 *
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$request   = masteriyo_current_http_request();
		$course_id = isset( $request['course_id'] ) ? $request['course_id'] : 0;

		if ( masteriyo_is_current_user_instructor() && ! ( $course_id && masteriyo_is_current_user_enrolled_in_course( $course_id ) ) ) {
			$quiz_ids           = masteriyo_get_instructor_quiz_ids();
			$quiz_ids           = empty( $quiz_ids ) ? array( 0 ) : $quiz_ids;
			$query_args['quiz'] = $quiz_ids;
		}

		if ( is_user_logged_in() ) {
			$result = $this->get_quiz_attempts_from_db( $query_args );
		} else {
			$result = $this->get_quiz_attempts_from_session( $query_args );
		}

		return $result;
	}

	/**
	 * Get quiz attempts from session.
	 *
	 * @since 1.3.8
	 *
	 * @param array $query_args
	 * @return array
	 */
	protected function get_quiz_attempts_from_session( $query_args ) {
		$session = masteriyo( 'session' );

		$quiz_id      = isset( $query_args['quiz_id'] ) ? absint( $query_args['quiz_id'] ) : 0;
		$all_attempts = $session->get( 'quiz_attempts', array() );
		$attempts     = isset( $all_attempts[ $quiz_id ] ) ? $all_attempts[ $quiz_id ] : array();
		$total_items  = count( $attempts );

		$attempts = array_map(
			function( $attempt ) {
				$quiz_attempt = masteriyo( 'quiz-attempt' );
				$quiz_attempt->set_id( 0 );
				$quiz_attempt->set_props( $attempt );

				return $quiz_attempt;
			},
			$attempts
		);

		return array(
			'objects' => array_reverse( $attempts ),
			'total'   => (int) $total_items,
			'pages'   => (int) ceil( $total_items / (int) $query_args['per_page'] ),
		);
	}

	/**
	 * Get quiz attempts from database.
	 *
	 * @since 1.3.8
	 *
	 * @param array $query_args
	 * @return array
	 */
	protected function get_quiz_attempts_from_db( $query_args ) {
		global $wpdb;

		$query   = new QuizAttemptQuery( $query_args );
		$objects = $query->get_quiz_attempts();

		/**
		 * Query for counting all quiz attempts rows.
		 */
		if ( ! empty( $query_args['user_id'] ) && ! empty( $query_args['quiz_id'] ) ) {
			$total_items = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts
					WHERE user_id = %d
					AND quiz_id = %d",
					$query_args['user_id'],
					$query_args['quiz_id']
				)
			);
		} elseif ( ! empty( $query_args['user_id'] ) ) {
			$total_items = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts
					WHERE user_id = %d",
					$query_args['user_id']
				)
			);
		} elseif ( ! empty( $query_args['quiz_id'] ) ) {
			$total_items = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts
					WHERE quiz_id = %d",
					$query_args['quiz_id']
				)
			);
		} elseif ( isset( $query_args['quiz'] ) && ! empty( $query_args['quiz'] ) ) {
			$post_ids             = $query_args['quiz'];
			$post_ids_placeholder = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

			// phpcs:disable
			$total_items = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts
					WHERE quiz_id IN ($post_ids_placeholder)",
					$post_ids
				)
			); // phpcs:enable
		} else {
			$total_items = $wpdb->get_var( "SELECT COUNT( * ) FROM {$wpdb->prefix}masteriyo_quiz_attempts" );
		}

		return array(
			'objects' => array_filter( array_map( array( $this, 'get_object' ), $objects ) ),
			'total'   => (int) $total_items,
			'pages'   => (int) ceil( $total_items / (int) $query_args['per_page'] ),
		);
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.3.2
	 *
	 * @param array $objects Orders data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Orders query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'        => $query_results['total'],
				'pages'        => $query_results['pages'],
				'current_page' => $query_args['paged'],
				'per_page'     => $query_args['per_page'],
			),
		);
	}

	/**
	 * Check permissions for an item.
	 *
	 * @since 1.3.2
	 *
	 * @param string $post_type Post type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 *
	 * @return bool
	 */
	protected function check_item_permission( $post_type, $context = 'read', $object_id = 0 ) {
		return true;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.0.6
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = array(
			'per_page' => $request['per_page'],
			'paged'    => $request['page'],
			'order'    => $request['order'],
			'orderby'  => $request['orderby'],
		);

		if ( isset( $request['quiz_id'] ) ) {
			$args['quiz_id'] = absint( $request['quiz_id'] );
		}

		if ( isset( $request['user_id'] ) ) {
			$args['user_id'] = absint( $request['user_id'] );
		}

		#TODO - Need to manage permission for quiz attempts in proper way.
		// Check for the guest user.
		if ( ! is_user_logged_in() && masteriyo( 'session' )->get_user_id() ) {
			$args['user_id'] = masteriyo( 'session' )->get_user_id();
		}

		// Prevent students from viewing other students' quiz attempts if sent request without user_id.
		if ( ! isset( $request['user_id'] ) && masteriyo_is_current_user_student() ) {
			$args['user_id'] = get_current_user_id();
		}

		if ( masteriyo_is_current_user_instructor() ) {
			$current_user_id = get_current_user_id();

			$main_author_courses = get_posts(
				array(
					'post_type'      => PostType::COURSE,
					'post_status'    => PostStatus::PUBLISH,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'author'         => $current_user_id,
				)
			);

			$additional_author_courses = get_posts(
				array(
					'post_type'      => PostType::COURSE,
					'post_status'    => PostStatus::PUBLISH,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => '_additional_authors',
							'value'   => $current_user_id,
							'compare' => 'IN',
						),
					),
				)
			);

			$course_ids = array_unique( array_merge( $main_author_courses, $additional_author_courses ) );

			$quiz_ids = array();

			if ( ! empty( $course_ids ) ) {
				$quiz_ids = get_posts(
					array(
						'post_type'      => PostType::QUIZ,
						'post_status'    => PostStatus::PUBLISH,
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => array(
							array(
								'key'     => '_course_id',
								'value'   => $course_ids,
								'compare' => 'IN',
							),
						),
					)
				);
			}

			$args['quiz'] = empty( $quiz_ids ) ? array( 0 ) : $quiz_ids;
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 1.0.6
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( 'masteriyo_rest_quiz_attempts_object_query', $args, $request );

		return $args;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.0.6
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_quiz_attempt_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		* Filter the data for a response.
		*
		* The dynamic portion of the hook name, $this->object_type,
		* refers to object type being prepared for the response.
		*
		* @since 1.3.2
		*
		* @param WP_REST_Response $response The response object.
		* @param Masteriyo\Database\Model $object   Object data.
		* @param WP_REST_Request  $request  Request object.
		*/
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	* Update obtained points for an answer.
	*
	* @since 2.4.0
	*
	* @param \WP_REST_Request $request Full details about the request.
	*/
	public function update_answer_points( $request ) {
		$id         = absint( $request['id'] );
		$answer_id  = absint( $request['answer_id'] );
		$new_points = (float) $request['points'];
		$note       = isset( $request['note'] ) ? $request['note'] : '';

		if ( $id <= 0 ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$quiz_attempt = masteriyo_get_quiz_attempt( $id );

		if ( is_null( $quiz_attempt ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$answers         = maybe_unserialize( $quiz_attempt->get_answers( 'edit' ) );
		$is_answer_found = false;

		if ( ! is_array( $answers ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'No answers in this quiz attempt.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		foreach ( $answers as $question_id => $attempt_answer ) {
			if ( $answer_id === $question_id ) {
				$is_answer_found               = true;
				$attempt_answer['points']      = $new_points;
				$attempt_answer['is_reviewed'] = true;
				$attempt_answer['note']        = $note;
				$answers[ $question_id ]       = $attempt_answer;
				break;
			}
		}

		if ( ! $is_answer_found ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid answer ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$quiz_attempt->set_answers( $answers );

		masteriyo_sync_quiz_attempt_attributes( $quiz_attempt );

		try {
			$quiz_attempt->save();
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		/**
		 * Fires after the quiz point is updated.
		 *
		 * @since 2.8.0
		 *
		 * @param integer $id The quiz attempt ID.
		 * @param \Masteriyo\Models\QuizAttempt $object The quiz attempt object.
		 */
		do_action( 'masteriyo_rest_quiz_attempt_update_answer_points', $quiz_attempt->get_id(), $quiz_attempt );

		$response = $this->prepare_object_for_response( $quiz_attempt, $request );
		$response = rest_ensure_response( $response );

		$request->set_param( 'context', 'edit' );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $quiz_attempt->get_id() ) ) );

		return $response;
	}

		/**
		* Update correct status of an answer.
		*
		* @since 2.4.0
		*
		* @param \WP_REST_Request $request Full details about the request.
		*/
	public function update_answer_correct_status( $request ) {
		$id         = absint( $request['id'] );
		$answer_id  = absint( $request['answer_id'] );
		$is_correct = masteriyo_string_to_bool( $request['is_correct'] );

		if ( $id <= 0 ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$quiz_attempt = masteriyo_get_quiz_attempt( $id );

		if ( is_null( $quiz_attempt ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$answers         = maybe_unserialize( $quiz_attempt->get_answers( 'edit' ) );
		$is_answer_found = false;

		if ( ! is_array( $answers ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'No answers in this quiz attempt.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		foreach ( $answers as $question_id => $attempt_answer ) {
			if ( $answer_id === $question_id ) {
				$is_answer_found           = true;
				$question                  = masteriyo_get_question( $question_id );
				$attempt_answer['correct'] = $is_correct;

				if ( $question && ! $question->is_reviewable() ) {
					$attempt_answer['points'] = $is_correct ? (float) $question->get_points() : 0;
				}

				$answers[ $question_id ] = $attempt_answer;
				break;
			}
		}

		if ( ! $is_answer_found ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid answer ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$quiz_attempt->set_answers( $answers );

		masteriyo_sync_quiz_attempt_attributes( $quiz_attempt );

		try {
			$quiz_attempt->save();
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		$response = $this->prepare_object_for_response( $quiz_attempt, $request );
		$response = rest_ensure_response( $response );

		$request->set_param( 'context', 'edit' );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $quiz_attempt->get_id() ) ) );

		return $response;
	}

	/**
	 * Get quiz attempt question answers data.
	 *
	 * @since 1.5.1
	 *
	 * @param mixed $attempt_answers
	 * @return array
	 */
	protected function get_answers_data( $attempt_answers, $context = 'view' ) {
		if ( empty( $attempt_answers ) || ! is_array( $attempt_answers ) ) {
			return null;
		}

		$new_attempt_answers = array();

		foreach ( $attempt_answers as $question_id => $attempt_answer ) {
			$question = masteriyo_get_question( $question_id );

			if ( ! $question ) {
				continue;
			}

			/**
			* For backward compatibility when attempt_answers was store in following format.
			* Old format: "answers" : [ '$question_id' => '$given_answered' ]
			* New format: "answers" : [ '$question_id' => [ 'answered' => '$given_answered', 'correct' => 'boolean' ]  ]
			*/
			$given_answers                       = isset( $attempt_answer['answered'] ) ? $attempt_answer['answered'] : $attempt_answer;
			$is_correct                          = isset( $attempt_answer['correct'] ) ? $attempt_answer['correct'] : $question->check_answer( $given_answers );
			$points                              = isset( $attempt_answer['points'] ) && is_numeric( $attempt_answer['points'] ) ? $attempt_answer['points'] : ( $is_correct ? $question->get_points() : 0 );
			$is_reviewed                         = isset( $attempt_answer['is_reviewed'] ) ? (bool) $attempt_answer['is_reviewed'] : ! $question->is_reviewable();
			$note                                = isset( $attempt_answer['note'] ) ? $attempt_answer['note'] : '';
			$name                                = 'view' === $context ? apply_filters( 'the_content', $question->get_name() ) : $question->get_name();
			$new_attempt_answers[ $question_id ] = array(
				'answered'       => $given_answers,
				'correct'        => $is_correct,
				'question'       => $name,
				'points'         => $points,
				'type'           => $question->get_type(),
				'correct_answer' => $question->get_correct_answers(),
				'is_reviewed'    => $is_reviewed,
				'max_points'     => $question->get_points(),
				'note'           => $note,
			);
		}

		/**
		* Filter quiz attempt answers data.
		*
		* @since 1.5.1
		*
		* @param array $new_attempt_answers New attempt answers.
		* @param mixed $attempt_answers Stored attempt answers.
		* @param Masteriyo\RestApi\Controllers\Version1\QuizAttemptsController $controller REST quiz attempts controller object.
		*/
		return apply_filters( 'masteriyo_quiz_attempt_answers', $new_attempt_answers, $attempt_answers, $this );
	}

	/**
	 * Get quiz attempt question answers explanation data.
	 *
	 * @since 2.13.0
	 *
	 * @param number $quiz_id
	 * @return array
	 */
	protected function get_answers_explanation_data( $quiz_id, $context = 'view' ) {
		if ( empty( $quiz_id ) ) {
			return null;
		}

		$quiz_questions = masteriyo_get_quiz_questions( $quiz_id );

		if ( empty( $quiz_questions ) ) {
			return null;
		}

		$answer_explanation = array();

		foreach ( $quiz_questions as $question ) {
			$answer_explanation[ $question->get_id() ] = 'view' === $context ? apply_filters( 'the_content', $question->get_answer_explanation() ) : $question->get_answer_explanation();
		}

		/**
			* Filter quiz attempt answer explanation data.
			*
			* @since 2.13.0
			*
			* @param array $answer_explanation New attempt answers.
			* @param Masteriyo\RestApi\Controllers\Version1\QuizAttemptsController $controller REST quiz attempts controller object.
			*/
		return apply_filters( 'masteriyo_quiz_attempt_answers_explanation', $answer_explanation, $this );
	}

	/**
	 * Get quiz attempt data.
	 *
	 * @since 1.3.2
	 *
	 * @param Masteriyo\Models\QuizAttempt $quiz_attempt quiz attempt instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_quiz_attempt_data( $quiz_attempt, $context = 'view' ) {
		$data = array(
			'id'                       => $quiz_attempt->get_id( $context ),
			'total_questions'          => $quiz_attempt->get_total_questions( $context ),
			'total_answered_questions' => $quiz_attempt->get_total_answered_questions( $context ),
			'total_marks'              => $quiz_attempt->get_total_marks( $context ),
			'total_attempts'           => $quiz_attempt->get_total_attempts( $context ),
			'total_correct_answers'    => $quiz_attempt->get_total_correct_answers( $context ),
			'total_incorrect_answers'  => $quiz_attempt->get_total_incorrect_answers( $context ),
			'earned_marks'             => $quiz_attempt->get_earned_marks( $context ),
			'answers'                  => $this->get_answers_data( $quiz_attempt->get_answers(), $context ),
			'attempt_status'           => $quiz_attempt->get_attempt_status( $context ),
			'attempt_started_at'       => masteriyo_rest_prepare_date_response( $quiz_attempt->get_attempt_started_at( $context ) ),
			'attempt_ended_at'         => masteriyo_rest_prepare_date_response( $quiz_attempt->get_attempt_ended_at( $context ) ),
			'course'                   => null,
			'quiz'                     => null,
			'passed'                   => null,
			'user'                     => null,
			'answer_explanation'       => $this->get_answers_explanation_data(
				$quiz_attempt->get_quiz_id(),
				$context
			),
		);

		$course = masteriyo_get_course( $quiz_attempt->get_course_id( $context ) );
		$quiz   = masteriyo_get_quiz( $quiz_attempt->get_quiz_id( $context ) );
		$user   = masteriyo_get_user( $quiz_attempt->get_user_id( $context ) );

		if ( $course ) {
			$data['course'] = array(
				'id'   => $course->get_id(),
				'name' => $course->get_name(),
			);
		}

		if ( $quiz ) {

			$quiz_attempts_allowed = $quiz->get_attempts_allowed();
			$user_quiz_attempts    = 0;
			$enabled_reveal_mode   = $quiz->get_reveal_mode();
			$user_quiz_attempts    = $quiz_attempt->get_total_attempts( $context );

			$view_last_attempts = false;
			if ( $quiz_attempts_allowed > 0 && $user_quiz_attempts >= $quiz_attempts_allowed && $enabled_reveal_mode ) {
				$view_last_attempts = true;
			}

			// The pass mark this attempt is measured against, not the stored one:
			// a point-mode threshold sits on the quiz's stale full_mark scale.
			$data['quiz'] = array(
				'id'                 => $quiz->get_id(),
				'name'               => $quiz->get_name(),
				'pass_mark'          => masteriyo_get_quiz_effective_pass_mark( $quiz, $data['total_marks'] ),
				'duration'           => $quiz->get_duration(),
				'reveal_mode'        => $quiz->get_reveal_mode(),
				'pass_mark_type'     => $quiz->get_pass_mark_type(),
				'view_last_attempts' => $view_last_attempts,
			);

			if ( QuizAttemptStatus::ENDED === $data['attempt_status'] ) {
				$data['passed'] = masteriyo_is_quiz_attempt_passed( $quiz_attempt, $quiz );
			}
		}

		if ( ! is_null( $user ) && ! is_wp_error( $user ) ) {
			$data['user'] = array(
				'id'           => $user->get_id(),
				'display_name' => $user->get_display_name(),
				'first_name'   => $user->get_first_name(),
				'last_name'    => $user->get_last_name(),
				'email'        => $user->get_email(),
			);
		}

		/**
		* Filter quiz attempt rest response data.
		*
		* @since 1.4.10
		*
		* @param array $data Quiz attempt data.
		* @param Masteriyo\Models\QuizAttempt $quiz_attempt Quiz attempt object.
		* @param string $context What the value is for. Valid values are view and edit.
		* @param Masteriyo\RestApi\Controllers\Version1\QuizAttemptsController $controller REST quiz attempts controller object.
		*/
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $quiz_attempt, $context, $this );
	}

	/**
	 * Prepare a single quiz attempt object for create or update.
	 *
	 * @since 1.5.4
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param boolean $creating If is creating a new object.
	 *
	 * @return \WP_Error|\Masteriyo\Models\QuizAttempt
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id           = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$quiz_attempt = masteriyo( 'quiz-attempt' );

		if ( 0 !== $id ) {
			$quiz_attempt->set_id( $id );
			$quiz_attempt_repo = masteriyo( 'quiz-attempt.store' );
			$quiz_attempt_repo->read( $quiz_attempt );
		}

		if ( isset( $request['course_id'] ) ) {
			$quiz_attempt->set_course_id( $request['course_id'] );
		}

		if ( isset( $request['quiz_id'] ) ) {
			$quiz_attempt->set_quiz_id( $request['quiz_id'] );
		}

		if ( isset( $request['user_id'] ) ) {
			$quiz_attempt->set_user_id( $request['user_id'] );
		}

		if ( isset( $request['total_questions'] ) ) {
			$quiz_attempt->set_total_questions( $request['total_questions'] );
		}

		if ( isset( $request['total_answered_questions'] ) ) {
			$quiz_attempt->set_total_answered_questions( $request['total_answered_questions'] );
		}

		if ( isset( $request['total_marks'] ) ) {
			$quiz_attempt->set_total_marks( $request['total_marks'] );
		}

		if ( isset( $request['total_attempts'] ) ) {
			$quiz_attempt->set_total_attempts( $request['total_attempts'] );
		}

		if ( isset( $request['total_correct_answers'] ) ) {
			$quiz_attempt->set_total_correct_answers( $request['total_correct_answers'] );
		}

		if ( isset( $request['total_incorrect_answers'] ) ) {
			$quiz_attempt->set_total_incorrect_answers( $request['total_incorrect_answers'] );
		}

		if ( isset( $request['earned_marks'] ) ) {
			$quiz_attempt->set_earned_marks( $request['earned_marks'] );
		}

		if ( isset( $request['answers'] ) ) {
			$quiz_attempt->set_answers( $request['answers'] );
		}

		if ( isset( $request['attempt_status'] ) ) {
			$quiz_attempt->set_attempt_status( $request['attempt_status'] );
		}

		if ( isset( $request['attempt_started_at'] ) ) {
			$quiz_attempt->set_attempt_started_at( $request['attempt_started_at'] );
		}

		if ( isset( $request['attempt_ended_at'] ) ) {
			$quiz_attempt->set_attempt_ended_at( $request['attempt_ended_at'] );
		}

		if ( isset( $request['reviewed'] ) ) {
			$quiz_attempt->set_reviewed( $request['reviewed'] );
		}
		/**
		* Filters an object before it is inserted via the REST API.
		*
		* The dynamic portion of the hook name, `$this->object_type`,
		* refers to the object type slug.
		*
		* @since 1.5.4
		*
		* @param Masteriyo\Models\QuizAttempt $quiz_attempt Quiz attempt object.
		* @param WP_REST_Request $request Request object.
		* @param boolean $creating If is creating a new object.
		*/
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $quiz_attempt, $request, $creating );
	}


	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.4.7
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
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

		$attempt_id = absint( $request['id'] );
		$attempt    = $this->get_object( $attempt_id );

		if ( ! $attempt ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$course = masteriyo_get_course( $attempt->get_course_id() );

		if ( ! $course ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		if ( masteriyo_is_current_user_post_author( $course->get_id() ) ) {
			return true;
		}

		return new \WP_Error(
			'masteriyo_rest_cannot_delete',
			__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.5.4
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$quiz_attempt = $this->get_object( absint( $request['id'] ) );

		if ( ! is_object( $quiz_attempt ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$instructor = masteriyo_get_current_instructor();

		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, your account has not been approved.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! masteriyo_is_current_user_post_author( $quiz_attempt->get_course_id() ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update this resource.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.5.4
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$quiz = masteriyo_get_quiz( absint( $request['quiz_id'] ) );

		if ( is_null( $quiz ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Quiz does not exist.', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		$course = masteriyo_get_course( $quiz->get_course_id() );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Course does not exist.', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! empty( $request['course_id'] ) && $quiz->get_course_id() !== absint( $request['course_id'] ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid course ID.', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$instructor = masteriyo_get_current_instructor();

		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, your account has not been approved.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! masteriyo_is_current_user_post_author( $quiz->get_course_id() ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resource for others.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 2.14.4
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_user_logged_in() && masteriyo_is_current_user_has_masteriyo_role() ) {

			// To make sure student don't get quiz attempts using user id param.
			if ( masteriyo_is_current_user_student() ) {
				if ( ! empty( $request['user_id'] ) && masteriyo_get_current_user_id() !== absint( $request['user_id'] ) ) {
					return new \WP_Error(
						'masteriyo_rest_cannot_read',
						__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);

				}
			}
			return true;
		} elseif ( ! is_user_logged_in() && masteriyo( 'session' )->get_user_id() ) {
			return true;
		} else {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
	}

	/**
	 * Return last attempt of quiz.
	 *
	 * @since 1.5.28
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_last_attempt( $request ) {
		$quiz_id = isset( $request['quiz_id'] ) ? absint( $request['quiz_id'] ) : 0;
		$quiz    = masteriyo_get_quiz( $quiz_id );

		if ( is_null( $quiz ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_quiz_id',
				__( 'Invalid quiz ID.', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( is_user_logged_in() ) {
			$query = new QuizAttemptQuery(
				array(
					'quiz_id'  => $quiz_id,
					'user_id'  => masteriyo_get_current_user_id(),
					'order'    => 'desc',
					'per_page' => 1,
				)
			);

			$attempts     = $query->get_quiz_attempts();
			$last_attempt = current( $attempts );

		} else {
			$query_args   = array(
				'quiz_id'  => $quiz_id,
				'per_page' => 1,
			);
			$session_data = $this->get_quiz_attempts_from_session( $query_args );
			$last_attempt = current( $session_data['objects'] );
		}

		if ( empty( $last_attempt ) ) {
			return new \WP_Error(
				'masteriyo_rest_last_quiz_attempt_not_found',
				__( 'Last quiz attempt not found.', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		return $this->prepare_object_for_response( $last_attempt, $request );
	}

	/**
	 * Check if a given request has access to delete items.
	 *
	 * @since 1.6.5
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_items_permissions_check( $request ) {
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

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.6.5
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_items( $request ) {
		$objects         = array_map( 'masteriyo_get_quiz_attempt', $request['ids'] );
		$deleted_objects = array();

		foreach ( $objects as $object ) {
			$data = $this->prepare_object_for_response( $object, $request );

			$object->delete( true, $request->get_params() );

			if ( 0 === $object->get_id() ) {
				$deleted_objects[] = $this->prepare_response_for_collection( $data );
			}
		}

		if ( empty( $deleted_objects ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_bulk_delete',
				/* translators: %s: post type */
				sprintf( __( 'The %s cannot be bulk deleted.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after a multiple objects is deleted or trashed via the REST API.
		 *
		 * @since 1.6.5
		 *
		 * @param array $deleted_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}

	/**
	 * View Attempt
	 *
	 * @since 2.0.4 [Free]
	 *
	 * @param \Masteriyo\Models\QuizAttempt $attempt
	 * @return bool
	 */
	protected function can_view_attempt( QuizAttempt $attempt ) {
		$current_user_id = masteriyo_get_current_user_id();

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$course = masteriyo_get_course( $attempt->get_course_id() );
		if ( $course && masteriyo_is_current_user_post_author( $course->get_id() ) ) {
			$instructor = masteriyo_get_current_instructor();
			if ( ! $instructor || $instructor->is_active() ) {
				return true;
			}
		}

		if ( (int) $attempt->get_user_id() === (int) $current_user_id ) {
			return true;
		}

		return false;
	}


	/**
	 * Check if a given request has access to read the terms.
	 *
	 * @since 2.0.4 [Free]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$attempt_id = absint( $request['id'] );
		$attempt    = $this->get_object( $attempt_id );

		if ( ! $attempt ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'masteriyo_rest_auth_required',
				__( 'Authentication required.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $this->can_view_attempt( $attempt ) ) {
			return new \WP_Error(
				'masteriyo_rest_forbidden',
				__( 'Sorry, you are not allowed to view this resource.', 'learning-management-system' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
