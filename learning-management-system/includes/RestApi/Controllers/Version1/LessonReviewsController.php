<?php
/**
 * LessonReviewsController class.
 *
 * @since 2.15.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Permission;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\PostStatus;

/**
 * Main class for LessonReviewsController.
 */
class LessonReviewsController extends CourseReviewsController {
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
	protected $rest_base = 'lessons/reviews';

	/**
	 * Object Type.
	 *
	 * @var string
	 */
	protected $object_type = 'lesson_review';

	/**
	 * Comment Type.
	 *
	 * @var string
	 */
	protected $comment_type = 'mto_lesson_review';

	/**
	 * Permission class.
	 *
	 * @since 2.15.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;


	/**
	 * Constructor.
	 *
	 * @since 2.15.0
	 *
	 * @param Permission $permission Permission instance.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register Routes.
	 *
	 * @since 2.15.0
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
			'/' . $this->rest_base . '/items',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lesson_comments' ),
					'permission_callback' => array( $this, 'get_lesson_permissions_check' ),
					'args'                => $this->get_collection_params(),
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
	 * Gets all lesson comments and its replies for specific lesson.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_lesson_comments( $request ) {
		$params            = $request->get_params();
		$data              = $this->masteriyo_get_lesson_reviews_and_replies( $params['lesson_id'], $params['page'], $params['per_page'] );
		$comments_object   = $data['reviews'];
		$all_replies       = $data['replies'];
		$response          = array();
		$comments_array    = array();
		$converted_replies = array();
		foreach ( $comments_object as $comment_object ) {
			$comments_array[] = $this->get_lesson_review_data( $comment_object );
		}

		foreach ( $all_replies as $key => $replies ) {
			$converted_replies[ $key ] = array();

			foreach ( $replies as $reply ) {
				$converted_replies[ $key ][] = $this->get_lesson_review_data( $reply );
			}
		}

		$response = array(
			'comments' => $comments_array,
			'replies'  => $converted_replies,
		);
		return $response;
	}

	/**
	 * Get lesson reviews and replies.
	 *
	 * @since 2.15.0
	 * @since 2.15.0  Added parameter $page.
	 * @since 2.15.0  Added parameter $per_page.
	 *
	 * @param integer|string|\Masteriyo\Models\Lesson|\WP_Post $lesson_id Lesson ID or object.
	 * @param integer                                          $page Page number if paginating. Default 1.
	 * @param integer|string                                   $per_page Items per page if paginating. Default empty string, gets all items.
	 * @param string                                           $search Search query. Default empty string.
	 * @param integer                                          $rating Rating. Default 0.
	 *
	 * @return array
	 */
	public function masteriyo_get_lesson_reviews_and_replies( $lesson_id, $page = 1, $per_page = '', $search = '', $rating = 0 ) {
		$lesson = masteriyo_get_lesson( $lesson_id );

		if ( is_null( $lesson ) ) {
			return array(
				'reviews' => array(),
				'replies' => array(),
			);
		}

		$args = array(
			'lesson_id' => $lesson->get_id(),
			'status'    => array( 'approve' ),
			'per_page'  => $per_page,
			'search'    => $search,
			'page'      => $page,
			'paginate'  => true,
			'parent'    => 0,
		);

		$result = $this->masteriyo_get_lesson_reviews( $args );

		if ( 0 === $result->total ) {
			return array(
				'reviews' => $result->lesson_review,
				'replies' => array(),
			);
		}

		$lesson_reviews     = $result->lesson_review;
		$filtered_reviews   = array();
		$indexed_replies    = array();
		$reply_counts       = array();
		$trash_reply_counts = array();
		$lesson_review_ids  = array();

		foreach ( $lesson_reviews as $review ) {
			$lesson_review_ids[] = $review->get_id();
		}

		$all_replies = $this->masteriyo_get_replies_of_lesson_reviews( $lesson_review_ids );

		// Count replies.
		foreach ( $all_replies as $reply ) {
			$review_id = $reply->get_parent();

			if ( ! isset( $trash_reply_counts[ $review_id ] ) ) {
				$trash_reply_counts[ $review_id ] = 0;
			}
			if ( CommentStatus::TRASH === $reply->get_status() ) {
				$trash_reply_counts[ $review_id ] += 1;
			}
			if ( ! isset( $reply_counts[ $review_id ] ) ) {
				$reply_counts[ $review_id ] = 0;
			}

			$reply_counts[ $review_id ] += 1;

			if ( ! isset( $indexed_replies[ $review_id ] ) ) {
				$indexed_replies[ $review_id ] = array();
			}

			if ( CommentStatus::TRASH === $reply->get_status() ) {
				continue;
			}

			$indexed_replies[ $review_id ][] = $reply;
		}

		// Remove unnecessary items.
		foreach ( $lesson_reviews as $review ) {
			$review_id = $review->get_id();

			if ( CommentStatus::TRASH === $review->get_status() ) {
				if (
				! isset( $indexed_replies[ $review_id ] ) ||
				$reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ]
				) {
					continue;
				}
			}
			$filtered_reviews[] = $review;

			if ( isset( $indexed_replies[ $review_id ] ) && $reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ] ) {
				unset( $indexed_replies[ $review_id ] );
			}
		}

		return array(
			'reviews'       => $filtered_reviews,
			'replies'       => $indexed_replies,
			'viewed_total'  => $result->total,
			'max_num_pages' => $result->max_num_pages,
		);
	}

	/**
	 * Get lesson reviews.
	 *
	 * @since 2.15.0
	 *
	 * @param array $args Query arguments.
	 *
	 * @return object|array[LessonReview]
	 */
	public function masteriyo_get_lesson_reviews( $args = array() ) {
		$lesson_reviews = masteriyo( 'query.lesson-reviews' )->set_args( $args )->get_lessons_reviews();

		/**
		 * Filters queried lesson review objects.
		 *
		 * @since 2.15.0
		 *
		 * @param \Masteriyo\Models\LessonReview|\Masteriyo\Models\LessonReview[] $lesson_reviews Queried Lesson reviews.
		 * @param array $args Query args.
		 */
		return apply_filters( 'masteriyo_get_lesson_reviews', $lesson_reviews, $args );
	}

	/**
	 * Get replies of lesson reviews.
	 *
	 * @since 2.15.0
	 *
	 * @param integer[] $review_ids Review Ids.
	 *
	 * @return array
	 */
	public function masteriyo_get_replies_of_lesson_reviews( $review_ids ) {
		$replies = $this->masteriyo_get_lesson_reviews(
			array(
				'status'     => array( 'approve', 'trash' ),
				'parent__in' => $review_ids,
			)
		);

		/**
		 * Filters replies of lesson reviews.
		 *
		 * @since 2.15.0
		 *
		 * @param \Masteriyo\Models\LessonReview $replies Replies for the given lesson reviews.
		 * @param integer[] $review_ids lesson review IDs.
		 */
		return apply_filters( 'masteriyo_replies_of_lesson_reviews', $replies, $review_ids );
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		unset( $params['post'] );

		$params['lesson'] = array(
			'default'     => array(),
			'description' => __( 'Limit result set to lesson reviews assigned to specific lesson IDs.', 'learning-management-system' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);

		/**
		 * Filters REST API collection parameters for the lesson reviews controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Comment_Query parameter. Use the
		 * `rest_comment_query` filter to set WP_Comment_Query parameters.
		 *
		 * @since 2.15.0
		 *
		 * @param array $params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'masteriyo_rest_lesson_review_collection_params', $params );
	}

	/**
	 * Get object.
	 *
	 * @since 2.15.0
	 *
	 * @param int|\WP_Comment|\Masteriyo\Models\lessonReview $object Object ID or WP_Comment or Model.
	 *
	 * @return \Masteriyo\Models\lessonReview Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Comment' ) ? $object->comment_ID : $object->get_id();
			}
			$lesson_review = masteriyo( 'lesson_review' );
			$lesson_review->set_id( $id );
			$lesson_review_repo = masteriyo( 'lesson_review.store' );
			$lesson_review_repo->read( $lesson_review );
		} catch ( \Exception $e ) {
			return false;
		}

		return $lesson_review;
	}

	/**
	 * Get objects.
	 *
	 * @since  2.15.0
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$lesson_ids             = masteriyo_get_instructor_lesson_ids();
			$lesson_ids             = empty( $lesson_ids ) ? array( 0 ) : $lesson_ids;
			$query_args['post__in'] = $lesson_ids;
		}

		$query          = new \WP_Comment_Query( $query_args );
		$lesson_reviews = $query->comments;
		$total_comments = $this->get_total_comments( $query_args );

		if ( $total_comments < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			$total_comments = $this->get_total_comments( $query_args );
		}

		return array(
			'objects' => array_filter( array_map( array( $this, 'get_object' ), $lesson_reviews ) ),
			'total'   => (int) $total_comments,
			'pages'   => (int) ceil( $total_comments / (int) $query_args['number'] ),
		);
	}

	/**
	 * Get the total number of comments by comment type.
	 *
	 * @since 2.15.0
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
	 * @since  2.15.0
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->get_lesson_review_data( $object, $context );
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
		 * @since 2.15.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get lesson review data.
	 *
	 * @since 2.15.0
	 *
	 * @param Masteriyo\Models\LessonReview $lesson_review Lesson Review instance.
	 * @param string       $context Request context.
	 *                             Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_lesson_review_data( $lesson_review, $context = 'view' ) {
		$author = masteriyo_get_user( $lesson_review->get_author_id( $context ) );

		$data = array(
			'id'                => $lesson_review->get_id(),
			'author_id'         => $lesson_review->get_author_id( $context ),
			'author_name'       => $lesson_review->get_author_name( $context ),
			'author_email'      => $lesson_review->get_author_email( $context ),
			'author_url'        => $lesson_review->get_author_url( $context ),
			'author_avatar_url' => is_wp_error( $author ) ? '' : $author->profile_image_url(),
			'ip_address'        => $lesson_review->get_ip_address( $context ),
			'date_created'      => masteriyo_rest_prepare_date_response( $lesson_review->get_date_created( $context ) ),
			'description'       => $lesson_review->get_content( $context ),
			'status'            => $lesson_review->get_status( $context ),
			'agent'             => $lesson_review->get_agent( $context ),
			'type'              => $lesson_review->get_type( $context ),
			'parent'            => $lesson_review->get_parent( $context ),
			'lesson'            => null,
			'replies_count'     => $lesson_review->total_replies_count(),
		);

		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'learn_page.display.auto_approve_comments' ) ) ) {
			$data['is_new'] = masteriyo_string_to_bool( $lesson_review->get_is_new( $context ) );
		}

		$lesson = masteriyo_get_lesson( $lesson_review->get_lesson_id() );

		if ( $lesson ) {
			$course = masteriyo_get_course( $lesson->get_course_id() );
			if ( ! empty( $course->get_author_id() ) ) {
				$data['course_author_id'] = $course->get_author_id();
			}
			if ( ! empty( $course->get_id() ) && ! empty( $course->get_name() ) ) {
				$data['course'] = array(
					'id'   => $course->get_id(),
					'name' => $course->get_name(),
				);
			}
			$data['lesson'] = array(
				'id'          => $lesson->get_id(),
				'name'        => $lesson->get_name(),
				'access_mode' => $course->get_access_mode(),
			);
		}

		/**
			* Filter lesson reviews rest response data.
			*
			* @since 2.15.0
			*
			* @param array $data Lesson review data.
			* @param Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			* @param string $context What the value is for. Valid values are view and edit.
			* @param Masteriyo\RestApi\Controllers\Version1\LessonReviewsController $controller REST lessons controller object.
			*/
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $lesson_review, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  2.15.0
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$args['post__in'] = $request['lesson'];

		return $args;
	}

	/**
	 * Get the Lesson review's schema, conforming to JSON Schema.
	 *
	 * @since 2.15.0
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
				'lesson_id'    => array(
					'description' => __( 'Lesson ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name'         => array(
					'description' => __( 'Lesson Reviewer Author.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email'        => array(
					'description' => __( 'Lesson Reviewer Author Email.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'url'          => array(
					'description' => __( 'Lesson Reviewer Author URL.', 'learning-management-system' ),
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
					'description' => __( "The date the lesson was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'  => array(
					'description' => __( 'Lesson Review Description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'content'      => array(
					'description' => __( 'Lesson Review Content.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'status'       => array(
					'description' => __( 'Lesson Review Status.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'agent'        => array(
					'description' => __( 'Lesson Review Agent.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'         => array(
					'description' => __( 'Lesson Review Type.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent'       => array(
					'description' => __( 'Lesson Review Parent.', 'learning-management-system' ),
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
	 * Prepare a single lesson review object for create or update.
	 *
	 * @since 2.15.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Models\LessonReview
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
			/** @var \Masteriyo\Models\LessonReview */
		$lesson_review = masteriyo( 'lesson_review' );
		$user          = masteriyo_get_current_user();

		if ( 0 !== $id ) {
			$lesson_review->set_id( $id );
			/** @var \Masteriyo\Repository\LessonReviewRepository */
			$lesson_review_repo = masteriyo( \Masteriyo\Repository\LessonReviewRepository::class );
			$lesson_review_repo->read( $lesson_review );
			$lesson_review->set_is_new( false );
		}

		if (
		! $lesson_review &&
		! is_null( $user ) &&
		! isset( $request['author_id'] ) &&
		! isset( $request['author_name'] ) &&
		! isset( $request['author_email'] )
		) {
			$lesson_review->set_author_id( $user->get_id() );
			$lesson_review->set_author_email( $user->get_email() );
			$lesson_review->set_author_name( $user->get_display_name() );
			$lesson_review->set_author_url( $user->get_url() );
		}

		// Lesson Review Author.
		if ( isset( $request['author_name'] ) ) {
			$lesson_review->set_author_name( $request['author_name'] );
		}

		// Lesson Review Author Email.
		if ( isset( $request['author_email'] ) ) {
			$lesson_review->set_author_email( $request['author_email'] );
		}

		// Lesson Review Author URL.
		if ( isset( $request['author_url'] ) ) {
			$lesson_review->set_author_url( $request['author_url'] );
		}

		// Lesson Review Author IP.
		if ( isset( $request['ip_address'] ) ) {
			$lesson_review->set_ip_address( $request['ip_address'] );
		}

		// Lesson Review Date.
		if ( isset( $request['date_created'] ) ) {
			$lesson_review->set_date_created( $request['date_created'] );
		}

		// Lesson ID.
		if ( isset( $request['lesson_id'] ) ) {
			$lesson_review->set_lesson_id( $request['lesson_id'] );
		}

		$lesson    = masteriyo_get_lesson( $lesson_review->get_lesson_id() );
		$is_author = ! empty( $lesson ) ? masteriyo_is_current_user_post_author( $lesson->get_course_id() ) : false;

		// Lesson Review Content.
		if ( isset( $request['content'] ) ) {
			$lesson_review->set_content( $request['content'] );
		}

		$status = CommentStatus::APPROVE_STR;

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() && ! $is_author ) {
			if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'learn_page.display.auto_approve_comments' ) ) ) {
				$status = CommentStatus::HOLD_STR;
				$lesson_review->set_is_new( true );
			}
		} else {
			// Lesson Review Approved.
			if ( isset( $request['status'] ) ) {
				$status = sanitize_text_field( $request['status'] );
			}
		}

		$lesson_review->set_status( $status );

		// Set is new status.
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'learn_page.display.auto_approve_comments' ) ) && isset( $request['is_new'] ) ) {
			$lesson_review->set_is_new( $request['is_new'] );
		}

		// Lesson Review Agent.
		if ( isset( $request['agent'] ) ) {
			$lesson_review->set_agent( $request['agent'] );
		}

		// Lesson Review Type.
		if ( isset( $request['type'] ) ) {
			$lesson_review->set_type( $request['type'] );
		}

		// Lesson Review Parent.
		if ( isset( $request['parent'] ) ) {
			$lesson_review->set_parent( $request['parent'] );
		}

		// User ID.
		if ( isset( $request['author_id'] ) ) {
			$lesson_review->set_author_id( $request['author_id'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$lesson_review->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 2.15.0
		 *
		 * @param Masteriyo\Models\LessonReview $comment Lesson review object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $lesson_review, $request, $creating );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 2.15.0
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
	 * Check if a given request has access to read lesson comments.
	 *
	 * @since 2.15.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_lesson_permissions_check( $request ) {
		$lesson = masteriyo_get_lesson( $request['lesson_id'] );
		$course = masteriyo_get_course( $lesson->get_course_id() );

		if ( ! empty( $course ) && 'open' === $course->get_access_mode() ) {
			return true;
		}

		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( ! $this->permission->rest_check_lesson_reviews_permissions( 'read' ) ) {
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
	 * @since 2.15.0
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

		if ( ! $this->permission->rest_check_lesson_reviews_permissions( 'read', absint( $request['id'] ) ) ) {
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
	 * @since 2.15.0
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

		$lesson = masteriyo_get_lesson( absint( $request['lesson_id'] ) );

		if ( is_null( $lesson ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid lesson ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( PostStatus::PUBLISH !== $lesson->get_status() ) {
			return new \WP_Error(
				'masteriyo_rest_lesson_not_published',
				__( 'Sorry, you can only create review for published lessons.', 'learning-management-system' ),
				array(
					'status' => 403,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_lesson_reviews_permissions( 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to lesson reviews.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'learn_page.display.enable_lesson_comment' ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_reviews_disabled',
				__( 'Sorry , lesson reviews are currently disabled.', 'learning-management-system' ),
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
				__( 'Sorry, you are not allowed to create lesson reviews for others.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( $lesson->get_author_id() === get_current_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you cannot create review for your own lesson.', 'learning-management-system' ),
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
	 * @since 2.15.0
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
		$lesson = ! empty( $review ) ? masteriyo_get_lesson( $review->get_lesson_id() ) : false;

		if ( ! is_object( $review ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( $lesson && masteriyo_is_current_user_post_author( $lesson->get_course_id() ) ) {
			return true;
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

		if ( ! $this->permission->rest_check_lesson_reviews_permissions( 'delete', absint( $request['id'] ) ) ) {
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
	 * @since 2.15.0
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

		$review_lesson_id  = $review->get_lesson_id();
		$request_lesson_id = absint( $request['lesson_id'] );
		$lesson            = masteriyo_get_lesson( $request['lesson_id'] );

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_post_author( $review_lesson_id ) ) {
			return true;
		}

		if ( $lesson && masteriyo_is_current_user_post_author( $lesson->get_course_id() ) ) {
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

		if ( ! $this->permission->rest_check_lesson_reviews_permissions( 'edit', absint( $request['id'] ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( isset( $request['lesson_id'] ) && $review_lesson_id !== $request_lesson_id ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you cannot move a review to another lesson.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Restore lesson review.
	 *
	 * @since 2.15.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( $request ) {
		$lesson_review = $this->get_object( (int) $request['id'] );

		if ( ! $lesson_review || 0 === $lesson_review->get_id() ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->comment_type}_invalid_id",
				__( 'Invalid ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		wp_untrash_comment( $lesson_review->get_id() );

		// Read lesson review again.
		$lesson_review = $this->get_object( (int) $request['id'] );

		$data     = $this->prepare_object_for_response( $lesson_review, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Process objects collection.
	 *
	 * @since 2.15.0
	 *
	 * @param array $objects Course reviews data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Course reviews query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		$lesson_ids = array();
		if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$lesson_ids = masteriyo_get_instructor_lesson_ids();
			$lesson_ids = empty( $lesson_ids ) ? array( 0 ) : $lesson_ids;
		}

		return array(
			'data' => $objects,
			'meta' => array(
				'total'              => $query_results['total'],
				'pages'              => $query_results['pages'],
				'current_page'       => $query_args['paged'],
				'per_page'           => $query_args['number'],
				'reviews_count'      => $this->get_comments_count( 0, $lesson_ids ),
				'pending_hold_count' => masteriyo_get_pending_course_reviews_and_lesson_comments_count(),
			),
		);
	}
}

