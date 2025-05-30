<?php
/**
 * User courses controller.
 *
 * @since 1.3.1
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Helper\Utils;
use Masteriyo\Helper\Permission;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Exceptions\RestException;
use Masteriyo\Query\CourseProgressQuery;

/**
 * User activities controller class.
 */
class UserCoursesController extends CrudController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.3.1
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.3.1
	 *
	 * @var string
	 */
	protected $rest_base = 'users/courses';

	/**
	 * Object type.
	 *
	 * @since 1.3.1
	 *
	 * @var string
	 */
	protected $object_type = 'user_course';

	/**
	 * Post type.
	 *
	 * @since 1.3.1
	 *
	 * @var string
	 */
	protected $post_type = 'user_course';

	/**
	 * If object is hierarchical.
	 *
	 * @since 1.3.1
	 *
	 * @var bool
	 */
	protected $hierarchical = false;

	/**
	 * Permission class.
	 *
	 * @since 1.3.1
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.3.1
	 *
	 * @param Permission $permission Permission object.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.3.1
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
			$this->rest_base . '/(?P<id>[\d]+)',
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
						'force' => array(
							'default'     => true,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.3.1
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params['page'] = array(
			'description'       => __( 'Paginate the course progress.', 'learning-management-system' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['per_page'] = array(
			'description'       => __( 'Limit course progress per page.', 'learning-management-system' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'User ID', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'default'           => 0,
		);

		$params['status'] = array(
			'description'       => __( 'User course status', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'active',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => UserCourseStatus::all(),
		);

		$params['started_at'] = array(
			'description'       => __( 'Limit response to resources started after a given ISO8601 compliant date.', 'learning-management-system' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['modified_at'] = array(
			'description'       => __( 'Limit response to resources started after a given ISO8601 compliant date.', 'learning-management-system' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'learning-management-system' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'learning-management-system' ),
			'type'              => 'string',
			'default'           => 'id',
			'enum'              => array(
				'id',
				'type',
				'date_start',
				'date_modified',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['from_account_dashboard'] = array(
			'description'       => __( 'Limit response to resources started after a given ISO8601 compliant date.', 'learning-management-system' ),
			'type'              => 'boolean',
			'default'           => false,
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.3.1
	 *
	 * @param  int|UserCourse $id User course object ID.
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $id ) {
		try {
			$id          = is_a( $id, 'Masteriyo\Database\Model' ) ? $id->get_id() : $id;
			$user_course = masteriyo_get_user_course( $id );
		} catch ( \Exception $e ) {
			return false;
		}

		return $user_course;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.3.1
	 *
	 * @param  \Masteriyo\Database\Model $object  Model object.
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_user_course_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.3.1
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get user course data.
	 *
	 * @since 1.3.1
	 *
	 * @param Masteriyo\Models\UserCourse  $user_course User course instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_user_course_data( $user_course, $context = 'view' ) {
		$course = masteriyo_get_course( $user_course->get_course_id( $context ) );

		$data = array(
			'id'          => $user_course->get_id( $context ),
			'user_id'     => $user_course->get_user_id( $context ),
			'course'      => null,
			'type'        => $user_course->get_type( $context ),
			'status'      => $user_course->get_status( $context ),
			'started_at'  => masteriyo_rest_prepare_date_response( $user_course->get_date_start( $context ) ),
			'modified_at' => masteriyo_rest_prepare_date_response( $user_course->get_date_modified( $context ) ),
		);

		if ( $course ) {
			$course_progress_query = new CourseProgressQuery(
				array(
					'course_id' => $course->get_id(),
					'user_id'   => $user_course->get_user_id(),
				)
			);

			$progress                    = current( $course_progress_query->get_course_progress() );
			$progress_data               = $course->get_progress_data( $user_course->get_user_id() );
			$progress_data['percentage'] = $course->get_progress_status( false, $user_course->get_user_id() );

			$data['course'] = array(
				'id'                   => $course->get_id(),
				'name'                 => wp_specialchars_decode( $course->get_name( $context ) ),
				'permalink'            => $course->get_permalink( $context ),
				'featured_image_url'   => $course->get_featured_image_url( 'masteriyo_thumbnail' ),
				'categories'           => $this->get_taxonomy_terms( $course, 'cat' ),
				'difficulty'           => $this->get_taxonomy_terms( $course, 'difficulty' ),
				'duration'             => $course->get_duration( $context ),
				'average_rating'       => $course->get_average_rating( $context ),
				'review_count'         => $course->get_review_count( $context ),
				'start_course_url'     => $course->start_course_url(),
				'continue_course_url'  => $progress ? $course->continue_course_url( $progress ) : $course->start_course_url(),
				'progress_data'        => $progress_data,
				'author'               => null,
				'enable_course_retake' => $course->get_enable_course_retake( $context ),
				'status'               => $course->get_status( $context ),
				'is_password_required' => post_password_required( get_post( $course->get_id() ) ),
			);

			$author = masteriyo_get_user( $course->get_author_id( $context ) );

			if ( ! is_wp_error( $author ) ) {
				$data['course']['author'] = array(
					'id'           => $author->get_id(),
					'display_name' => $author->get_display_name( $context ),
					'avatar_url'   => $author->profile_image_url(),
				);
			}
		}

		/**
		 * Filter user courses rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data User course data.
		 * @param Masteriyo\Models\UserCourse $user_course User Course object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\UserCoursesController $controller REST user courses controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $user_course, $context, $this );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.3.1
	 *
	 * @param array $objects Courses data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Courses query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		$response = array(
			'data' => $objects,
			'meta' => array(
				'total'        => $query_results['total'],
				'pages'        => $query_results['pages'],
				'current_page' => $query_args['paged'],
				'per_page'     => $query_args['per_page'],
			),
		);

		if ( masteriyo_is_request_from_account_dashboard() ) {
			$response['courses_stat'] = $this->get_courses_stat( current( $query_args['user__in'] ) );
		}

		return $response;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  1.3.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = wp_parse_args(
			$request->get_params(),
			array(
				'page'        => 1,
				'per_page'    => 10,
				'user__in'    => isset( $request['user'] ) && ! empty( $request['user'] ) ? $request['user'] : array( get_current_user_id() ),
				'status'      => '',
				'started_at'  => null,
				'modified_at' => null,
			)
		);

		$args['paged'] = $args['page'];

		if ( masteriyo_is_request_from_account_dashboard( $request ) ) {
			$course_ids = masteriyo_get_user_course_ids_by_course_status( current( $args['user__in'] ) );
			// requires for course listing(inprogress) in dashboard page so by default it would not list every course there is.
			if ( empty( $course_ids ) ) {
				$args['course__in'] = array( 0 );
			} else {
				$args['course__in'] = $course_ids;
			}
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 1.3.1
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "masteriyo_rest_{$this->object_type}_object_query", $args, $request );

		return $args;
	}

	/**
	 * Get the orders' schema, conforming to JSON Schema.
	 *
	 * @since 1.3.1
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_id'     => array(
					'description' => __( 'User ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'   => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'type'        => array(
					'description' => __( 'Item type (e.g. Course)', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status'      => array(
					'description' => __( 'Course progress status.', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => masteriyo_get_user_course_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'started_at'  => array(
					'description' => __( 'Course progress start date in GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'modified_at' => array(
					'description' => __( 'Course progress modified date in GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single course progress for create or update.
	 *
	 * @since 1.3.1
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		/** @var \Masteriyo\Models\UserCourse $user_course */
		$user_course = masteriyo( 'user-course' );

		if ( 0 !== $id ) {
			$user_course->set_id( $id );
			/** @var \Masteriyo\Repository\UserCourseRepository */
			$user_course_repo = masteriyo( 'user-course.store' );
			$user_course_repo->read( $user_course );
		}

		try {
			$user_id = $this->validate_user_id( $request, $creating );
			$user_course->set_user_id( $user_id );

			$course_id = $this->validate_course_id( $request, $creating );
			if ( ! is_null( $course_id ) ) {
				$user_course->set_course_id( $course_id );
			}
		} catch ( RestException $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		// course status.
		if ( isset( $request['status'] ) ) {
			$user_course->set_status( $request['status'] );
		}

		// course start date.
		if ( isset( $request['started_at'] ) ) {
			$user_course->set_date_start( $request['started_at'] );
		}

		// course update date.
		if ( isset( $request['modified_at'] ) ) {
			$user_course->set_date_modified( $request['modified_at'] );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.3.1
		 *
		 * @param Masteriyo\Database\Model $user_course User course object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $user_course, $request, $creating );
	}

	/**
	 * Get objects.
	 *
	 * @since  1.3.1
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$query   = new UserCourseQuery( $query_args );
		$objects = $query->get_user_courses();

		return array(
			'objects' => $objects,
			'total'   => (int) $query->found_rows,
			'pages'   => (int) ceil( $query->found_rows / (int) $query_args['per_page'] ),
		);
	}

	/**
	 * Check if a given request has access to read item.
	 *
	 * @since 1.3.1
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.3.1
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

		if ( ! $this->permission->rest_check_user_course_permissions( 'read' ) ) {
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
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.3.1
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

		if ( ! $this->permission->rest_check_user_course_permissions( 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to create/update an item.
	 *
	 * @since 1.3.1
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

		$user_course = masteriyo_get_user_course( (int) $request['id'] );

		if ( $user_course && ! $this->permission->rest_check_user_course_permissions( 'update', $request['id'] ) ) {
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
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.3.1
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

		$progress = masteriyo_get_user_course( (int) $request['id'] );

		if ( $progress && ! $this->permission->rest_check_user_course_permissions( 'delete', $request['id'] ) ) {
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
	 * Check permissions for an item.
	 *
	 * @since 1.3.1
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
	 * Validate the user ID in the request.
	 *
	 * @since 1.3.1
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Model
	 */
	protected function validate_user_id( $request, $creating = false ) {
		$user_id = null;

		// User ID.
		if ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ) {
			$user_id = $request['user_id'];
		} else {
			$user_id = get_current_user_id();
		}

		// Validate the user ID.
		$user = get_user_by( 'id', $user_id );
		if ( is_user_logged_in() && ! $user ) {
			throw new RestException(
				'masteriyo_rest_invalid_user_id',
				__( 'User ID is invalid.', 'learning-management-system' ),
				400
			);
		}

		// If the current user is not administrator or manager, then the current
		// user must be same of the request suer id.
		if ( ( masteriyo_is_current_user_student() || masteriyo_is_current_user_instructor() ) && get_current_user_id() !== $user_id ) {
			throw new RestException(
				'masteriyo_rest_access_denied_user_course',
				__( 'User cannot access other\'s course progress.', 'learning-management-system' ),
				400
			);
		}

		return $user_id;
	}

	/**
	 * Validate the course ID in the request.
	 *
	 * @since 1.3.1
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Model
	 */
	protected function validate_course_id( $request, $creating = false ) {
		$course_id = null;

		// Course ID.
		if ( isset( $request['course_id'] ) && ! empty( $request['course_id'] ) ) {
			$course_id = $request['course_id'];

			// Validate course ID.
			$course_post = get_post( $course_id );
			if ( ! $course_post || 'mto-course' !== $course_post->post_type ) {
				throw new RestException(
					'masteriyo_rest_invalid_course_id',
					__( 'Course ID is invalid.', 'learning-management-system' ),
					400
				);
			}
		}

		if ( is_null( $course_id ) ) {
			throw new RestException(
				'masteriyo_rest_invalid_course_id',
				__( 'Course ID is invalid.', 'learning-management-system' ),
				400
			);
		}

		return $course_id;
	}

	/**
	 * Get taxonomy terms.
	 *
	 * @since 1.3.1
	 *
	 * @param Course $course Course object.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected function get_taxonomy_terms( $course, $taxonomy = 'cat' ) {
		$terms = Utils::get_object_terms( $course->get_id(), 'course_' . $taxonomy );

		$terms = array_map(
			function( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			},
			$terms
		);

		$terms = 'difficulty' === $taxonomy ? array_shift( $terms ) : $terms;

		return $terms;
	}

	/**
	 * Get courses statistics.
	 *
	 * @since 1.14.2
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array Associative array of course statistics.
	 *               Keys: completed_count, in_progress_count, enrolled_count.
	 */
	private function get_courses_stat( $user_id = null ) {
		$courses_stat = array(
			'completed_count'   => masteriyo_get_user_courses_count_by_course_status( $user_id, CourseProgressStatus::COMPLETED ),
			'in_progress_count' => masteriyo_get_user_courses_count_by_course_status( $user_id ),
			'enrolled_count'    => masteriyo_get_user_enrolled_courses_count( $user_id ),
		);

		return $courses_stat;
	}
}
