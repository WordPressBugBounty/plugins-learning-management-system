<?php
/**
 * Question rest controller.
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\Enums\QuestionType;
use Masteriyo\RestApi\Controllers\Version1\PostsController;
use WP_Error;

class QuestionsController extends PostsController {

	/**
	 * A fill-in-the-blanks answer marker, `{{answer}}`. The same grammar the learner
	 * field renders a blank from, so a marker spanning lines is masked too.
	 */
	const BLANK_MARKER_PATTERN = '/{{[^{}]*}}/';
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = 'questions';

	/**
	 * Post type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-question';

	/**
	 * Object type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $object_type = 'question';


	/**
	 * If object is hierarchical.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Question types.
	 *
	 * @since 1.0.0
	 * @deprecated 1.5.3
	 *
	 * @var array
	 */
	protected $types = array(
		'true-false',
		'single-choice',
		'multiple-choice',
	);

	/**
	 * Permission class.
	 *
	 * @since 1.0.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
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
				'args'   => array(
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
					'args'                => array(
						'force'            => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
						'delete_from_bank' => array(
							'default'     => false,
							'description' => __( 'Whether the question is from the question bank.', 'learning-management-system' ),
							'type'        => 'boolean',
							'required'    => false,
						),
						'quiz_id'          => array(
							'description' => __( 'Quiz ID', 'learning-management-system' ),
							'type'        => 'integer',
							'required'    => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/check_answer',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_answer' ),
					'permission_callback' => array( $this, 'check_answer_permissions_check' ),
					'args'                => array(
						'id'            => array(
							'description'       => __( 'Question ID', 'learning-management-system' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'chosen_answer' => array(
							'description' => __( 'Chosen answer or answers.', 'learning-management-system' ),
							'type'        => 'any',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-from-bank',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_bank_questions' ),
				'permission_callback' => array( $this, 'update_bank_questions_permissions_check' ),
				'args'                => array(
					'ids'     => array(
						'description' => __( 'Question IDs', 'learning-management-system' ),
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
						'required'    => true,
					),
					'quiz_id' => array(
						'description' => __( 'Quiz ID', 'learning-management-system' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// @since 2.5.7 Added clone endpoint to lessons REST API.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/clone',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_item' ),
					'permission_callback' => array( $this, 'clone_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to check correct answers.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function check_answer_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( ! $this->permission->rest_check_answer_check_permissions() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check given answer if it's correct or not.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function check_answer( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_id", __( 'Invalid ID', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$chosen_answer        = isset( $request['chosen_answer'] ) ? $request['chosen_answer'] : null;
		$is_correct           = $object->check_answer( $chosen_answer, 'view' );
		$correct_answer_msg   = __( 'The answer is correct.', 'learning-management-system' );
		$incorrect_answer_msg = __( 'The answer is incorrect.', 'learning-management-system' );
		$response             = array(
			'is_correct' => $is_correct,
			'message'    => $is_correct ? $correct_answer_msg : $incorrect_answer_msg,
		);

		/**
		 * Filters answer check API response.
		 *
		 * @since 1.0.7
		 *
		 * @param WP_Error|WP_REST_Response $response Response object.
		 */
		return apply_filters( 'masteriyo_answer_check_rest_response', $response );
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['course_id'] = array(
			'description'       => __( 'Limit result by course id.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to questions assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to questions assigned a specific type.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => QuestionType::all(),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category'] = array(
			'description'       => __( 'Limit result set to courses assigned a specific category ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tag'] = array(
			'description'       => __( 'Limit result set to courses assigned a specific tag ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['random_id'] = array(
			'description'       => __( 'Random ID.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'learning-management-system' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 999,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|WP_Post|Model $object Object ID or WP_Post or Model.
	 *
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Post' ) ? $object->ID : $object->get_id();
			}

			$type     = get_post_meta( $id, '_type', true );
			$question = masteriyo( "question.$type" );
			$question->set_id( $id );
			$question_repo = masteriyo( 'question.store' );
			$question_repo->read( $question );
		} catch ( \Exception $e ) {
			return false;
		}

		return $question;
	}


	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.0.0
	 *
	 * @param  \Masteriyo\Models\Question\Question $object Question object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// The caller-supplied show_correct_answer flag is a request, never an
		// authorization: it was used to defeat answer-key redaction. Staff and
		// authors always get the key; a learner only for a review the quiz's
		// own reveal policy allows.
		$show_correct_answer = masteriyo_is_current_user_admin()
			|| masteriyo_is_current_user_manager()
			|| masteriyo_is_current_user_post_author( $object->get_course_id() )
			|| masteriyo_is_current_user_post_author( $object->get_id() )
			|| ( ! empty( $request['show_correct_answer'] ) && $this->current_user_can_review_answer_key( $object, $this->served_quiz_id( $request, $object ) ) );

		$data = $this->get_question_data( $object, $context, $show_correct_answer );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	/**
	 * Whether the current learner may see this question's answer key on the
	 * "Review Last attempt" screen: the quiz reveals answers, its attempts are
	 * capped, and the learner has used them all — the same rule
	 * QuizAttemptsController uses to offer that screen (view_last_attempts).
	 *
	 * @param \Masteriyo\Models\Question\Question $question Question.
	 * @param int                                   $quiz_id  The quiz being served; the question's parent when 0.
	 * @return bool
	 */
	protected function current_user_can_review_answer_key( $question, $quiz_id = 0 ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// The quiz being served, not the question's origin: a bank question is reused
		// across quizzes, and each quiz has its own reveal policy.
		$quiz = masteriyo_get_quiz( $quiz_id ? $quiz_id : $question->get_parent_id() );

		if ( ! $quiz || ! $quiz->get_reveal_mode() || $quiz->get_attempts_allowed() < 1 ) {
			return false;
		}

		return masteriyo_get_quiz_attempt_count( $quiz->get_id(), $user_id ) >= $quiz->get_attempts_allowed();
	}

	/**
	 * The named quiz this row is served for, which is the quiz that licensed it.
	 *
	 * A request may name several quizzes, and each row follows its own quiz's policy.
	 *
	 * @param \WP_REST_Request                    $request  Request.
	 * @param \Masteriyo\Models\Question\Question $question The row.
	 * @return int 0 when no named quiz serves this row.
	 */
	protected function served_quiz_id( $request, $question ) {
		$parent = isset( $request['parent'] ) ? $request['parent'] : array();
		$named  = array_values( array_filter( array_map( 'absint', (array) $parent ) ) );
		$own    = absint( $question->get_parent_id( 'edit' ) );

		if ( in_array( $own, $named, true ) ) {
			return $own;
		}

		// Reused from the bank: the model resolves a named quiz the question is linked to.
		foreach ( $named as $quiz_id ) {
			if ( absint( $question->get_parent_id( 'edit', $quiz_id ) ) === $quiz_id ) {
				return $quiz_id;
			}
		}

		return 0;
	}

	/**
	 * Get question data.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Question\Question $question Question instance.
	 * @param string   $context Request context.
	 *                          Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_question_data( $question, $context = 'view', $show_correct_answer = false ) {
		/**
		 * Filters question description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description Question description.
		 */
		$description        = 'view' === $context ? apply_filters( 'masteriyo_description', $question->get_description() ) : $question->get_description();
		$name               = 'view' === $context ? apply_filters( 'the_content', $question->get_name() ) : $question->get_name();
		$answer_explanation = 'view' === $context ? apply_filters( 'the_content', $question->get_answer_explanation() ) : $question->get_answer_explanation();

		$data = array(
			'id'                     => $question->get_id(),
			'name'                   => $name,
			'permalink'              => $question->get_permalink(),
			'status'                 => $question->get_status( $context ),
			'description'            => $description,
			'date_created'           => masteriyo_rest_prepare_date_response( $question->get_date_created( $context ) ),
			'date_modified'          => masteriyo_rest_prepare_date_response( $question->get_date_modified( $context ) ),
			'type'                   => $question->get_type( $context ),
			'parent_id'              => $question->get_parent_id( $context ),
			'course_id'              => $question->get_course_id( $context ),
			'menu_order'             => $question->get_menu_order( $context ),
			'answer_required'        => $question->get_answer_required( $context ),
			'randomize'              => $question->get_randomize( $context ),
			'points'                 => $question->get_points( $context ),
			'positive_feedback'      => $question->get_positive_feedback( $context ),
			'negative_feedback'      => $question->get_negative_feedback( $context ),
			'feedback'               => $question->get_feedback( $context ),
			'answers_decode_success' => $question->is_answers_decoded(),
			'enable_description'     => $question->get_enable_description( $context ),
			'answer_explanation'     => $answer_explanation,
		);

		$answers = $question->get_answers( $context );

		$filtered_answers = $show_correct_answer ? $answers : $this->redact_answer_key( $answers, $question );

		// Randomize is for the attempt. An authorized response keeps the stored order:
		// the editor saves it back, and for a sortable it is the key.
		if ( ! $show_correct_answer && is_array( $filtered_answers ) && $question->get_randomize( $context ) ) {
			shuffle( $filtered_answers );
		}

		$data['answers'] = $filtered_answers;

		if ( 'view' === $context ) {
			$data['formatted_answers'] = $this->process_answers( $filtered_answers, $question );
		}

		/**
		 * Filter question rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Question data.
		 * @param Masteriyo\Models\Question $question Question object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\QuestionsController $controller REST Questions controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $question, $context, $this );
	}

	/**
	 * Strip the stored answer key. What remains is what attempting the question takes.
	 *
	 * @param mixed $answers Raw answers.
	 * @param \Masteriyo\Models\Question\Question $question The question being served.
	 * @return mixed
	 */
	protected function redact_answer_key( $answers, $question ) {
		switch ( $question->get_type( 'edit' ) ) {
			case QuestionType::TRUE_FALSE:
			case QuestionType::SINGLE_CHOICE:
			case QuestionType::MULTIPLE_CHOICE:
				if ( ! is_array( $answers ) ) {
					return $answers;
				}

				return array_map(
					function ( $obj ) {
						return (object) array( 'name' => $obj->name );
					},
					$answers
				);

			case QuestionType::SORTABLE:
				if ( ! is_array( $answers ) ) {
					return $answers;
				}

				$redacted = array_values( $answers );
				shuffle( $redacted );

				return $redacted;

			case QuestionType::MATCHING:
				if ( ! is_array( $answers ) ) {
					return $answers;
				}

				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$matches = array();
				foreach ( $answers as $answer ) {
					$answer    = (array) $answer;
					$matches[] = array(
						'match'         => isset( $answer['match'] ) ? $answer['match'] : null,
						'imageMatch'    => isset( $answer['imageMatch'] ) ? $answer['imageMatch'] : null,
						'imageMatchUrl' => isset( $answer['imageMatchUrl'] ) ? $answer['imageMatchUrl'] : null,
					);
				}

				shuffle( $matches );

				$redacted = array();
				foreach ( array_values( $answers ) as $i => $answer ) {
					// get_answers() returns the model's own objects.
					$copy                = is_object( $answer ) ? clone $answer : (object) $answer;
					$copy->match         = $matches[ $i ]['match'];
					$copy->imageMatch    = $matches[ $i ]['imageMatch'];
					$copy->imageMatchUrl = $matches[ $i ]['imageMatchUrl'];
					$redacted[]          = $copy;
				}
				// phpcs:enable

				return $redacted;

			case QuestionType::FILL_IN_THE_BLANKS:
				$answers = is_array( $answers ) ? join( '', $answers ) : $answers;

				return is_string( $answers ) ? preg_replace( self::BLANK_MARKER_PATTERN, '{{blanks}}', $answers ) : $answers;

			default:
				return $answers;
		}
	}

	/**
	 * Process answers based on user roles.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed    $answers Available answer(s).
	 * @param \Masteriyo\Models\Question\Question $question Question object.
	 */
	protected function process_answers( $answers, $question ) {
		if ( QuestionType::FILL_IN_THE_BLANKS === $question->get_type() ) {
			$answers = is_array( $answers ) ? join( '', $answers ) : $answers;
			$answers = preg_replace( self::BLANK_MARKER_PATTERN, '{{blanks}}', $answers );
		}

		return $answers;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// A named parent of 0 asks for parentless questions, which no quiz licenses;
		// the permission check drops it, so the query must too.
		$args['post_parent__in'] = array_filter( (array) $args['post_parent__in'] );

		// Support quiz's question randomization for only single quiz.
		if ( isset( $request['random_id'] ) && 'rand' === $args['orderby'] ) {
			$args['orderby']   = $args['orderby'] . '(' . $request['random_id'] . ')';
			$args['random_id'] = $request['random_id'];
		}

		// `status` is a staff filter over the bank; a learner attempts published questions only.
		$args['post_status'] = $this->current_user_is_staff() ? $request['status'] : PostStatus::PUBLISH;

		if ( ! isset( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		if ( ! empty( $request['course_id'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_course_id',
					'value'   => absint( $request['course_id'] ),
					'compare' => '=',
				),
			);
		}

		if ( isset( $request['selected_quiz_id'] ) ) {
			$exclude_questions    = masteriyo_get_all_question_ids_by_quiz( absint( $request['selected_quiz_id'] ) );
			$args['post__not_in'] = $exclude_questions;

			if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_current_user_admin() ) {
				$args['author__in'] = array( get_current_user_id() );
			}
		}

		$args['meta_query'][] = array(
			'key'     => '_type',
			'value'   => QuestionType::all(),
			'compare' => 'IN',
		);

		// Filter by question type.
		if ( ! empty( $request['question_types'] ) ) {
			$question_types = isset( $request['question_types'] ) ? explode( ',', $request['question_types'] ) : array();

			$args['meta_query']   = isset( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
			$args['meta_query'][] = array(
				'key'     => '_type',
				'value'   => $question_types,
				'compare' => 'IN',
			);
		}

		return $args;
	}

	/**
	 * Get the question's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                     => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                   => array(
					'description' => __( 'Question name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'                   => array(
					'description' => __( 'Question slug', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'              => array(
					'description' => __( 'Question URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'           => array(
					'description' => __( "The date the question was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'       => array(
					'description' => __( 'The date the question was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'          => array(
					'description' => __( "The date the question was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'      => array(
					'description' => __( 'The date the question was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                 => array(
					'description' => __( 'Question status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'              => array(
					'description' => __( 'Course parent ID.', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'              => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'             => array(
					'description' => __( 'Menu order, used to custom sort questions.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'description'            => array(
					'description' => __( 'Question description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'short_description'      => array(
					'description' => __( 'Question short description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'                   => array(
					'description' => __( 'Question type.', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => QuestionType::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'answer_required'        => array(
					'description' => __( 'Whether the question is required or not.', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'answers'                => array(
					'description' => __( 'Given answer list for the question.', 'learning-management-system' ),
					'type'        => 'object|string',
					'context'     => array( 'view', 'edit' ),
				),
				'answers_decode_success' => array(
					'description' => __( 'Return if the answers are decoded successfully.', 'learning-management-system' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'points'                 => array(
					'description' => __( 'Points for the correct answer.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'randomize'              => array(
					'description' => __( 'Whether to randomize answers.', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'enable_description'     => array(
					'description' => __( 'Whether to enable points.', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'answer_explanation'     => array(
					'description' => __( 'Description for answer.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'              => array(
					'description' => __( 'Meta data', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value', 'learning-management-system' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single question for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id       = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$question = masteriyo( 'question' );

		if ( 0 !== $id ) {
			$question->set_id( $id );
			$question_repo = masteriyo( 'question.store' );
			$question_repo->read( $question );
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			// Slash after sanitizing so wp_insert_post/wp_update_post (which unslash)
			// preserve literal backslashes, e.g. LaTeX math like \frac and \int.
			$question->set_name( wp_slash( wp_kses_post( $request['name'] ) ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$question->set_description( wp_slash( wp_kses_post( $request['description'] ) ) );
		}

		// Course ID.
		if ( isset( $request['course_id'] ) ) {
			$question->set_course_id( $request['course_id'] );
		}

		// Post answers.
		if ( isset( $request['answers'] ) ) {
			$question->set_answers( $request['answers'] );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$question->set_status( get_post_status_object( $request['status'] ) ? $request['status'] : PostStatus::DRAFT );
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$question->set_slug( $request['slug'] );
		}

		// Automatically set the menu order if it's not set and the operation is POST.
		if ( ! isset( $request['menu_order'] ) && $creating ) {
			$quiz_id     = absint( $request['parent_id'] );
			$found_posts = masteriyo_get_all_questions_count_by_quiz( $quiz_id );

			$question->set_menu_order( $found_posts + 1 );      }

		// Post type.
		if ( isset( $request['type'] ) ) {
			$question->set_type( $request['type'] );
		}

		// Question parent ID.
		if ( isset( $request['parent_id'] ) ) {
			$question->set_parent_id( $request['parent_id'] );
		}

		// Post answer required.
		if ( isset( $request['answer_required'] ) ) {
			$question->set_answer_required( $request['answer_required'] );
		}

		// Post randomize.
		if ( isset( $request['randomize'] ) ) {
			$question->set_randomize( $request['randomize'] );
		}

		// Post points.
		if ( isset( $request['points'] ) ) {
			$question->set_points( $request['points'] );
		}

		// Post positive feedback.
		if ( isset( $request['positive_feedback'] ) ) {
			$question->set_positive_feedback( $request['positive_feedback'] );
		}

		// Post negative feedback.
		if ( isset( $request['negative_feedback'] ) ) {
			$question->set_negative_feedback( $request['negative_feedback'] );
		}

		// Post feedback.
		if ( isset( $request['feedback'] ) ) {
			$question->set_feedback( $request['feedback'] );
		}

		// Question enable points.
		if ( isset( $request['enable_description'] ) ) {
			$question->set_enable_description( $request['enable_description'] );
		}

		if ( isset( $request['answer_explanation'] ) ) {
			$question->set_answer_explanation( $request['answer_explanation'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$question->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Database\Model $question Question object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->post_type}_object", $question, $request, $creating );
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @since 1.0.0
	 *
	 * @param Question $question  Question instance.
	 * @param array    $terms    Terms data.
	 * @param string   $taxonomy Taxonomy name.
	 *
	 * @return Question
	 */
	protected function save_taxonomy_terms( $question, $terms, $taxonomy = 'cat' ) {
		$term_ids = wp_list_pluck( $terms, 'id' );

		if ( 'cat' === $taxonomy ) {
			$question->set_category_ids( $term_ids );
		} elseif ( 'tag' === $taxonomy ) {
			$question->set_tag_ids( $term_ids );
		} elseif ( 'difficulty' === $taxonomy ) {
			$question->set_difficulty_ids( $term_ids );
		}

		return $question;
	}

	/**
	 * Get question types.
	 *
	 * @since 1.0.0
	 * @deprecated 1.5.3
	 *
	 * @return array
	 */
	protected function get_types() {
		return $this->types;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$course_id = absint( $request['course_id'] );
		$course    = masteriyo_get_course( $course_id );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid course ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$id       = absint( $request['id'] );
		$question = masteriyo_get_question( $id );

		if ( is_null( $question ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'delete', $id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$id       = absint( $request['id'] );
		$question = masteriyo_get_question( $id );

		if ( is_null( $question ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'update', $id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
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
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( $this->current_user_is_staff() ) {
			return true;
		}

		$quiz_ids = array_filter( array_map( 'absint', (array) $request['parent'] ) );

		if ( empty( $quiz_ids ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		foreach ( $quiz_ids as $quiz_id ) {
			if ( ! $this->quiz_is_attemptable( $quiz_id ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_read',
					__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		return true;
	}

	/**
	 * Whether the caller builds quizzes and so works the site-wide question bank.
	 *
	 * @return bool
	 */
	protected function current_user_is_staff() {
		return masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_instructor();
	}

	/**
	 * Whether the current caller may attempt a quiz, decided from the quiz and its course.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return bool
	 */
	protected function quiz_is_attemptable( $quiz_id ) {
		$quiz = masteriyo_get_quiz( $quiz_id );

		// The same statuses the learn page shows a quiz in.
		if ( is_null( $quiz ) || ! in_array( $quiz->get_status(), masteriyo_get_course_content_post_statuses(), true ) ) {
			return false;
		}

		$course = masteriyo_get_course( $quiz->get_course_id() );

		if ( is_null( $course ) || ! in_array( $course->get_status(), array( PostStatus::PUBLISH, PostStatus::PVT ), true ) ) {
			return false;
		}

		// masteriyo_can_start_course() is true for an open course before it looks at the
		// caller, so the course page's own gates have to be asked here.
		if ( post_password_required( get_post( $course->get_id() ) ) ) {
			return false;
		}

		if ( PostStatus::PVT === $course->get_status() && ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) ) {
			return false;
		}

		// Open access included: the cohort start, end and enrolment-close gates live in
		// here, and it is safe for a guest on every access mode. The filter is the quiz
		// start route's last gate (content drip holds an unreleased quiz there); true or
		// a WP_Error comes back.
		$can_start = (bool) masteriyo_can_start_course( $course );

		return true === apply_filters( 'masteriyo_rest_check_quiz_start_permission', $can_start, $quiz, $course );
	}

	/**
	 * Check permissions for an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $object_type Object type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 *
	 * @return bool
	 */
	protected function check_item_permission( $object_type, $context = 'read', $object_id = 0 ) {
		$course_id = get_post_meta( $object_id, '_course_id', true );
		$course    = masteriyo_get_course( $course_id );

		if ( $course && CourseAccessMode::OPEN === $course->get_access_mode() ) {
			return true;
		}

		// Fallback for question bank questions: their _course_id may be empty or wrong.
		// Check the parent quiz's course instead (passed as ?parent= in the request).
		$request        = masteriyo_current_http_request();
		$parent         = $request ? $request->get_param( 'parent' ) : null;
		$parent_id      = absint( is_array( $parent ) ? current( $parent ) : $parent );
		$quiz_course_id = $parent_id ? get_post_meta( $parent_id, '_course_id', true ) : 0;
		$quiz_course    = $quiz_course_id ? masteriyo_get_course( $quiz_course_id ) : null;

		if ( $quiz_course && CourseAccessMode::OPEN === $quiz_course->get_access_mode() ) {
			return true;
		}

		return $this->permission->rest_check_post_permissions( $object_type, 'read', $object_id );
	}

	/**
		 * Check if a given request has access to create questions from bank.
		 *
		 * @since 1.17.0 [Free]
		 *
		 * @param  WP_REST_Request $request Full details about the request.
		 * @return WP_Error|boolean
		 */
	public function update_bank_questions_permissions_check( $request ) {
		if ( ! isset( $request['ids'], $request['quiz_id'] ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_request',
				__( 'Invalid request', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

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

		$quiz_id = absint( $request['quiz_id'] );
		$quiz    = masteriyo_get_quiz( $quiz_id );

		if ( is_null( $quiz ) ) {
			return new \WP_Error(
				'masteriyo_rest_{$this->post_type}_invalid_id',
				__( 'Invalid quiz ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $quiz->get_post_type(), 'update', $quiz_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$question_ids = array_map( 'absint', $request['ids'] );

		if ( empty( $question_ids ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		foreach ( $question_ids as $question_id ) {

			$question = masteriyo_get_question( $question_id );

			if ( is_null( $question ) ) {
				return new \WP_Error(
					"masteriyo_rest_{$this->post_type}_invalid_id",
					__( 'Invalid ID', 'learning-management-system' ),
					array(
						'status' => 404,
					)
				);
			}

			if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'update', $question_id ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_update',
					__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		return true;
	}
	/**
	 * Get objects.
	 *
	 * @since  1.17.0 [Free]
	 *
	 * @param  array $query_args Query args.
	 *
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$request = masteriyo_current_http_request();

		if ( isset( $request['is_from_learn'], $request['parent'] ) ) {
			// From the query args, not the raw request: prepare_objects_query() has dropped a 0.
			$quiz_id = absint( current( (array) $query_args['post_parent__in'] ) );

			$query_args['post_parent'] = $quiz_id;

			$query_args['posts_per_page'] = apply_filters( 'masteriyo_rest_quiz_questions_per_page', $query_args['posts_per_page'] );

			$results = masteriyo_get_questions_by_quiz_with_pagination( $query_args );
		} else {
			$results = parent::get_objects( $query_args );
		}

		return $results;
	}

	/**
	 * Process objects collection.
	 *
	 * @since 2.2.8
	 *
	 * @param array $objects Questions data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Questions query result data.
	*
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		$query_args['posts_per_page'] = apply_filters( 'masteriyo_rest_quiz_questions_per_page', $query_args['posts_per_page'] );

		return array(
			'data' => $objects,
			'meta' => array(
				'total'        => $query_results['total'],
				'pages'        => $query_results['pages'],
				'current_page' => $query_args['paged'],
				'per_page'     => $query_args['posts_per_page'],
				'random_id'    => ( isset( $query_args['random_id'] ) && masteriyo_starts_with( $query_args['orderby'], 'rand' ) ) ? $query_args['random_id'] : current_time( 'timestamp' ),
			),
		);
	}

	/**
	 * Delete a single item.
	 *
	 * @since 1.17.0 [Free]
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		/** Case 1: Delete the question from the bank and remove the original question. */
		if ( isset( $request['delete_from_bank'] ) && masteriyo_string_to_bool( $request['delete_from_bank'] ) ) {
			$object->set_is_from_bank( false );
			$object->save();

			masteriyo_remove_questions_from_bank( $object->get_id() );
		}

		$is_from_bank = $object->get_is_from_bank();
		$quiz_id      = isset( $request['quiz_id'] ) ? absint( $request['quiz_id'] ) : 0;

		/** Case 2: Remove the question only from the quiz; the original question remains intact. */
		if ( $is_from_bank && $quiz_id ) {
			$result = masteriyo_remove_question_from_quiz( $quiz_id, $object->get_id() );

			// Fallback case if the question is not removed from the bank it means it directly linked to the quiz.
			if ( ! $result ) {
				if ( absint( $object->get_parent_id() ) === absint( $quiz_id ) ) {
					$object->set_parent_id( 0 );
					$object->set_menu_order( 0 );
					$object->save();
				}
			}

			return $this->prepare_object_for_response( $object, $request );
		}

		/** Case 3: Delete the question entirely, regardless of whether it's from the bank or not. */
		return parent::delete_item( $request );
	}

	/**
	 * Update bank questions by associating them with a quiz.
	 *
	 * @since 1.17.0 [Free]
	 *
	 * @param WP_REST_Request $request Request object containing 'ids' of questions and 'quiz_id' as quiz ID.
	 * @return WP_REST_Response|WP_Error REST response containing the updated question data or error object.
	 */
	public function update_bank_questions( \WP_REST_Request $request ) {
		$question_ids = array_map( 'absint', $request['ids'] );
		$quiz_id      = absint( $request['quiz_id'] );

		if ( empty( $question_ids ) || ! $quiz_id ) {
			return new \WP_Error( 'invalid_params', __( 'Invalid question IDs or quiz ID.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$responses  = array();
		$menu_order = masteriyo_get_all_questions_count_by_quiz( $quiz_id );

		foreach ( $question_ids as $question_id ) {
			$question = masteriyo_get_question( $question_id );

			if ( ! $question ) {
				continue;
			}

			if ( ! $question->get_is_from_bank() ) {
				$question->set_is_from_bank( true );
			}

			// Advance per question, so a batch does not write one menu order to every
			// row it links. The increment sits below the guards above so a skipped
			// question leaves no gap in the sequence.
			++$menu_order;

			masteriyo_add_question_to_quiz( $quiz_id, $question_id, $menu_order );

			$question->save();

			$responses[] = $this->prepare_object_for_response( $question, $request );
		}

		if ( empty( $responses ) ) {
			return new \WP_Error( 'no_updates', __( 'No valid questions were updated.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $responses );
	}
}
