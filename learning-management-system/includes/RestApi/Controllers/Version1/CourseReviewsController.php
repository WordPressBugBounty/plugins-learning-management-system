<?php
/**
 * CommentController class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Permission;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\PostStatus;

/**
 * Main class for CommentController.
 */
class CourseReviewsController extends CommentsController {
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
	protected $rest_base = 'courses/reviews';

	/**
	 * Object Type.
	 *
	 * @var string
	 */
	protected $object_type = 'course_review';

	/**
	 * Comment Type.
	 *
	 * @var string
	 */
	protected $comment_type = 'mto_course_review';

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
	 * @param Permission $permission Permission instance.
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register Routes.
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
						'force_delete' => array(
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
							'default'     => false,
						),
						'children'     => array(
							'description' => __( 'Whether to delete the replies.', 'learning-management-system' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		/**
		 * @since 1.5.0 Added restore route.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/restore',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
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
						'ids'      => array(
							'required'    => true,
							'description' => __( 'Review IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force'    => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
						'children' => array(
							'default'     => false,
							'description' => __( 'Whether to delete the replies.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/restore',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'Review Ids', 'learning-management-system' ),
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
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		unset( $params['post'] );

		$params['course'] = array(
			'default'     => array(),
			'description' => __( 'Limit result set to course reviews assigned to specific course IDs.', 'learning-management-system' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);

		/**
		 * Filters REST API collection parameters for the course reviews controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Comment_Query parameter. Use the
		 * `rest_comment_query` filter to set WP_Comment_Query parameters.
		 *
		 * @since 1.4.10
		 *
		 * @param array $params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'masteriyo_rest_course_review_collection_params', $params );
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Comment|\Masteriyo\Models\CourseReview $object Object ID or WP_Comment or Model.
	 *
	 * @return \Masteriyo\Models\CourseReview Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Comment' ) ? $object->comment_ID : $object->get_id();
			}
			$course_review = masteriyo( 'course_review' );
			$course_review->set_id( $id );
			$course_review_repo = masteriyo( 'course_review.store' );
			$course_review_repo->read( $course_review );
		} catch ( \Exception $e ) {
			return false;
		}

		return $course_review;
	}

	/**
	 * Get objects.
	 *
	 * @since  1.0.0
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$course_ids             = masteriyo_get_instructor_course_ids();
			$course_ids             = empty( $course_ids ) ? array( 0 ) : $course_ids;
			$query_args['post__in'] = $course_ids;
		}

		$query          = new \WP_Comment_Query( $query_args );
		$course_reviews = $query->comments;
		$total_comments = $this->get_total_comments( $query_args );

		if ( $total_comments < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			$total_comments = $this->get_total_comments( $query_args );
		}

		return array(
			'objects' => array_filter( array_map( array( $this, 'get_object' ), $course_reviews ) ),
			'total'   => (int) $total_comments,
			'pages'   => (int) ceil( $total_comments / (int) $query_args['number'] ),
		);
	}

	/**
	 * Get the total number of comments by comment type.
	 *
	 * @since 1.4.10
	 *
	 * @param array $query_args WP_Comment_Query args.
	 * @return int
	 */
	protected function get_total_comments( $query_args ) {
		if ( isset( $query_args['paged'] ) ) {
			unset( $query_args['paged'] );
		}

		if ( isset( $query_args['number'] ) ) {
			unset( $query_args['number'] );
		}

		if ( isset( $query_args['offset'] ) ) {
			unset( $query_args['offset'] );
		}

		$query_args['fields'] = 'ids';

		$comments = get_comments( $query_args );

		return count( $comments );
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.0.0
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->get_course_review_data( $object, $context );
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get course review data.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\CourseReview $course_review Course Review instance.
	 * @param string       $context Request context.
	 *                             Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_course_review_data( $course_review, $context = 'view' ) {
		$author = masteriyo_get_user( $course_review->get_author_id( $context ) );

		$data = array(
			'id'                => $course_review->get_id(),
			'author_id'         => $course_review->get_author_id( $context ),
			'author_name'       => $course_review->get_author_name( $context ),
			'author_email'      => $course_review->get_author_email( $context ),
			'author_url'        => $course_review->get_author_url( $context ),
			'author_avatar_url' => is_wp_error( $author ) ? '' : $author->get_avatar_url(),
			'ip_address'        => $course_review->get_ip_address( $context ),
			'date_created'      => masteriyo_rest_prepare_date_response( $course_review->get_date_created( $context ) ),
			'title'             => $course_review->get_title( $context ),
			'description'       => $course_review->get_content( $context ),
			'rating'            => $course_review->get_rating( $context ),
			'status'            => $course_review->get_status( $context ),
			'agent'             => $course_review->get_agent( $context ),
			'type'              => $course_review->get_type( $context ),
			'parent'            => $course_review->get_parent( $context ),
			'course'            => null,
			'replies_count'     => $course_review->total_replies_count(),
		);

		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ) ) {
			$data['is_new'] = masteriyo_string_to_bool( $course_review->get_is_new( $context ) );
		}

		$course = masteriyo_get_course( $course_review->get_course_id() );
		if ( $course ) {
			$data['course'] = array(
				'id'   => $course->get_id(),
				'name' => $course->get_name(),
			);
		}

		/**
		 * Filter course reviews rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Course review data.
		 * @param Masteriyo\Models\CourseReview $course_review Course review object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\CourseReviewsController $controller REST courses controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $course_review, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$args['post__in'] = $request['course'];

		return $args;
	}

	/**
	 * Get the Course review's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'course_id'    => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name'         => array(
					'description' => __( 'Course Reviewer Author.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email'        => array(
					'description' => __( 'Course Reviewer Author Email.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'url'          => array(
					'description' => __( 'Course Reviewer Author URL.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'ip_address'   => array(
					'description' => __( 'The IP address of the reviewer', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the course was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'  => array(
					'description' => __( 'Course Review Description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'title'        => array(
					'description' => __( 'Course Review Title.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'content'      => array(
					'description' => __( 'Course Review Content.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'rating'       => array(
					'description' => __( 'Course Review rating.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'status'       => array(
					'description' => __( 'Course Review Status.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'agent'        => array(
					'description' => __( 'Course Review Agent.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'         => array(
					'description' => __( 'Course Review Type.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent'       => array(
					'description' => __( 'Course Review Parent.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author_id'    => array(
					'description' => __( 'The User ID.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data'    => array(
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
	 * Prepare a single course review object for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Models\CourseReview
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id            = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$course_review = masteriyo( 'course_review' );
		$user          = masteriyo_get_current_user();

		if ( 0 !== $id ) {
			$course_review->set_id( $id );
			$course_review_repo = masteriyo( \Masteriyo\Repository\CourseReviewRepository::class );
			$course_review_repo->read( $course_review );
		}

		if (
		! $course_review &&
		! is_null( $user ) &&
		! isset( $request['author_id'] ) &&
		! isset( $request['author_name'] ) &&
		! isset( $request['author_email'] )
		) {
			$course_review->set_author_id( $user->get_id() );
			$course_review->set_author_email( $user->get_email() );
			$course_review->set_author_name( $user->get_display_name() );
			$course_review->set_author_url( $user->get_url() );
		}

		// Course Review Author.
		if ( isset( $request['author_name'] ) ) {
			$course_review->set_author_name( $request['author_name'] );
		}

		// Course Review Author Email.
		if ( isset( $request['author_email'] ) ) {
			$course_review->set_author_email( $request['author_email'] );
		}

		// Course Review Author URL.
		if ( isset( $request['author_url'] ) ) {
			$course_review->set_author_url( $request['author_url'] );
		}

		// Course Review Author IP.
		if ( isset( $request['ip_address'] ) ) {
			$course_review->set_ip_address( $request['ip_address'] );
		}

		// Course Review Date.
		if ( isset( $request['date_created'] ) ) {
			$course_review->set_date_created( $request['date_created'] );
		}

		// Course Review Title.
		if ( isset( $request['title'] ) ) {
			$course_review->set_title( $request['title'] );
		}

		// Course Review Content.
		if ( isset( $request['content'] ) ) {
			$course_review->set_content( $request['content'] );
		}

		// Course Review Rating.
		if ( isset( $request['rating'] ) ) {
			$course_review->set_rating( $request['rating'] );
		}

		$status = CommentStatus::APPROVE_STR;

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() ) {
			if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ) ) {
				$status = CommentStatus::HOLD_STR;
				$course_review->set_is_new( true );
			}
		} else {
			// Course Review Approved.
			if ( isset( $request['status'] ) ) {
				$status = sanitize_text_field( $request['status'] );
			}
		}

		$course_review->set_status( $status );

		// Set is new status.
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ) && isset( $request['is_new'] ) ) {
			$course_review->set_is_new( $request['is_new'] );
		}

		// Course Review Agent.
		if ( isset( $request['agent'] ) ) {
			$course_review->set_agent( $request['agent'] );
		}

		// Course Review Type.
		if ( isset( $request['type'] ) ) {
			$course_review->set_type( $request['type'] );
		}

		// Course ID.
		if ( isset( $request['course_id'] ) ) {
			$course_review->set_course_id( $request['course_id'] );
		}

		// Course Review Parent.
		if ( isset( $request['parent'] ) ) {
			$course_review->set_parent( $request['parent'] );
		}

		// User ID.
		if ( isset( $request['author_id'] ) ) {
			$course_review->set_author_id( $request['author_id'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$course_review->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Models\CourseReview $comment Course review object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $course_review, $request, $creating );
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

		if ( ! $this->permission->rest_check_course_reviews_permissions( 'read' ) ) {
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
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( ! $this->permission->rest_check_course_reviews_permissions( 'read', absint( $request['id'] ) ) ) {
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

		$course = masteriyo_get_course( absint( $request['course_id'] ) );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid course ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( PostStatus::PUBLISH !== $course->get_status() ) {
			return new \WP_Error(
				'masteriyo_rest_course_not_published',
				__( 'Sorry, you can only create review for published courses.', 'learning-management-system' ),
				array(
					'status' => 403,
				)
			);
		}

		$rating = absint( $request['rating'] );

		if ( empty( $request['parent'] ) && $rating <= 0 ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, rating cannot be zero or less.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_course_reviews_permissions( 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create course reviews.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.enable_review' ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_reviews_disabled',
				__( 'Sorry , course reviews are currently disabled.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( isset( $request['author_id'] ) && absint( $request['author_id'] ) === 0 ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, author ID cannot be empty or zero.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( isset( $request['author_id'] ) && absint( $request['author_id'] ) !== get_current_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create course reviews for others.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $course->is_review_allowed() ) {
			return new \WP_Error(
				'masteriyo_rest_reviews_disabled',
				__( 'Course reviews are not allowed for this course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.enable_review_enrolled_users_only' ) && ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_not_enrolled',
				__( 'You must be enrolled in this course to create a review. Please enroll in the course first.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( $course->get_author_id() === get_current_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you cannot create review for your own course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
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

		$review = $this->get_object( absint( $request['id'] ) );

		if ( ! is_object( $review ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( get_current_user_id() !== $review->get_author_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this resource.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_course_reviews_permissions( 'delete', absint( $request['id'] ) ) ) {
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

		$review = $this->get_object( absint( $request['id'] ) );

		if ( ! is_object( $review ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$rating            = absint( $request['rating'] );
		$review_course_id  = $review->get_course_id();
		$request_course_id = absint( $request['course_id'] );

		if ( ! $review->is_reply() && $rating <= 0 ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, rating cannot be zero or less.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_post_author( $review_course_id ) ) {
			return true;
		}

		if ( get_current_user_id() !== $review->get_author_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update this resource.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_course_reviews_permissions( 'edit', absint( $request['id'] ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( isset( $request['course_id'] ) && $review_course_id !== $request_course_id ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you cannot move a review to another course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
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
		return true;
	}

	/**
	 * Restore course review.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( $request ) {
		$course_review = $this->get_object( (int) $request['id'] );

		if ( ! $course_review || 0 === $course_review->get_id() ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->comment_type}_invalid_id",
				__( 'Invalid ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		wp_untrash_comment( $course_review->get_id() );

		// Read course review again.
		$course_review = $this->get_object( (int) $request['id'] );

		$data     = $this->prepare_object_for_response( $course_review, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.6.7
	 * @since 2.2.7
	 *
	 * @param array $objects Course reviews data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Course reviews query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		$course_ids = array();
		if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$course_ids = masteriyo_get_instructor_course_ids();
			$course_ids = empty( $course_ids ) ? array( 0 ) : $course_ids;
		}

		return array(
			'data' => $objects,
			'meta' => array(
				'total'         => $query_results['total'],
				'pages'         => $query_results['pages'],
				'current_page'  => $query_args['paged'],
				'per_page'      => $query_args['number'],
				'reviews_count' => $this->get_comments_count( 0, $course_ids ),
			),
		);
	}
}
