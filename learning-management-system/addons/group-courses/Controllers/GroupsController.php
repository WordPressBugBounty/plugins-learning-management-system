<?php
/**
 * Groups Controller class.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Addons\GroupCourses
 */

namespace Masteriyo\Addons\GroupCourses\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\GroupCourses\Models\Group;
use Masteriyo\Addons\GroupCourses\Models\Setting;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\Models\Course;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\OrderQuery;
use Masteriyo\RestApi\Controllers\Version1\PostsController;

/**
 * GroupsController class.
 */
class GroupsController extends PostsController {
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
	protected $rest_base = 'groups';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = PostType::GROUP;

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'group';

	/**
	 * Permission class.
	 *
	 * @since 1.9.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.9.0
	 *
	 * @param Permission $permission
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.9.0
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
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => 'Unique identifier for the resource.',
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
							'default'     => false,
							'description' => 'Whether to bypass trash and force deletion.',
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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
							'description' => 'Group IDs.',
							'type'        => 'array',
						),
						'force'    => array(
							'default'     => false,
							'description' => 'Whether to bypass trash and force deletion.',
							'type'        => 'boolean',
						),
						'children' => array(
							'default'     => false,
							'description' => 'Whether to delete the children(sections, lessons, quizzes and questions) under the course.',
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
							'description' => 'Group Ids',
							'type'        => 'array',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/restore',
			array(
				'args' => array(
					'id' => array(
						'description' => 'Unique identifier for the resource.',
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)/progress',
			array(
				'args' => array(
					'id' => array(
						'description' => 'Unique identifier for the resource.',
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_progress' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_group_setting' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_group_setting' ),
					'permission_callback' => array( $this, 'update_group_setting_permission_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get an item.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post_id = (int) $request['id'];

		if ( ! $post_id ) {
			return new \WP_Error(
				'masteriyo_invalid_post_id',
				__( 'Invalid post ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$post_author = get_post_field( 'post_author', $post_id );

		if ( ! $post_author ) {
			return new \WP_Error(
				'masteriyo_post_not_found',
				__( 'Post not found.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		// Allow if user is admin OR is the author
		if ( masteriyo_is_current_user_admin() || get_current_user_id() === absint( $post_author ) ) {
			return true;
		}

		return new \WP_Error(
			'masteriyo_rest_cannot_read',
			__( 'Sorry, you are not allowed to view this resource.', 'learning-management-system' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * How far each member of a group has got in the group's courses.
	 *
	 * Scoped to the group's own emails and linked courses, so a leader never
	 * sees progress outside the group they manage.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item_progress( \WP_REST_Request $request ) {
		$group = $this->get_object( (int) $request['id'] );

		if ( ! $group || PostType::GROUP !== get_post_type( $group->get_id() ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$courses = array();
		$totals  = array();
		foreach ( $this->group_course_ids( $group ) as $course_id ) {
			$course = masteriyo_get_course( $course_id );
			if ( $course ) {
				$courses[]            = $course;
				$totals[ $course_id ] = array(
					'lesson' => $this->course_item_count( $course_id, PostType::LESSON ),
					'quiz'   => $this->course_item_count( $course_id, PostType::QUIZ ),
				);
			}
		}

		$members = array();
		foreach ( $group->get_emails() as $email ) {
			$user    = get_user_by( 'email', $email );
			$user_id = $user ? (int) $user->ID : 0;

			$members[] = array(
				'email'   => $email,
				'user'    => $user ? array(
					'id'           => $user_id,
					'display_name' => $user->display_name,
					'avatar_url'   => get_avatar_url( $user_id ),
				) : null,
				'courses' => array_map(
					fn( Course $course ) => $this->member_course_progress( $user_id, $course, $totals[ $course->get_id() ] ),
					$courses
				),
			);
		}

		return rest_ensure_response(
			array(
				'courses' => array_map(
					fn( Course $course ) => array(
						'id'        => $course->get_id(),
						'name'      => wp_specialchars_decode( $course->get_name() ),
						'permalink' => $course->get_permalink(),
					),
					$courses
				),
				'members' => $members,
			)
		);
	}

	/**
	 * One member's standing in one course: enrollment, progress and item counts.
	 *
	 * @param int                              $user_id Member's user ID, 0 when the email has no account.
	 * @param Course                           $course  A course linked to the group.
	 * @param array{lesson: int, quiz: int}    $totals  Published lessons and quizzes, for a member with no progress record.
	 *
	 * @return array{course_id: int, enrollment_status: string|null, progress_status: string|null, percentage: int, lessons: array{completed: int, total: int}, quizzes: array{completed: int, total: int}, started_at: string|null, modified_at: string|null, completed_at: string|null}
	 */
	private function member_course_progress( int $user_id, Course $course, array $totals ): array {
		$row = array(
			'course_id'         => $course->get_id(),
			'enrollment_status' => null,
			'progress_status'   => null,
			'percentage'        => 0,
			'lessons'           => array(
				'completed' => 0,
				'total'     => $totals['lesson'],
			),
			'quizzes'           => array(
				'completed' => 0,
				'total'     => $totals['quiz'],
			),
			'started_at'        => null,
			'modified_at'       => null,
			'completed_at'      => null,
		);

		if ( ! $user_id ) {
			return $row;
		}

		$enrollment = masteriyo_get_user_course_by_user_and_course( $user_id, $course->get_id() );
		if ( $enrollment ) {
			$row['enrollment_status'] = $enrollment->get_status();
		}

		$progress = current(
			( new CourseProgressQuery(
				array(
					'course_id' => $course->get_id(),
					'user_id'   => $user_id,
					'per_page'  => 1,
				)
			) )->get_course_progress()
		);

		if ( ! $progress ) {
			return $row;
		}

		// The record's own summary, so counts and totals come from one source.
		$summary = $progress->get_summary( 'all' );
		$done    = (int) ( $summary['total']['completed'] ?? 0 );
		$all     = max( 1, (int) ( $summary['total']['total'] ?? 0 ) );

		$row['progress_status'] = $progress->get_status();
		$row['percentage']      = (int) masteriyo_round( $done / $all * 100 );
		$row['lessons']         = array(
			'completed' => (int) ( $summary['lesson']['completed'] ?? 0 ),
			'total'     => (int) ( $summary['lesson']['total'] ?? $totals['lesson'] ),
		);
		$row['quizzes']         = array(
			'completed' => (int) ( $summary['quiz']['completed'] ?? 0 ),
			'total'     => (int) ( $summary['quiz']['total'] ?? $totals['quiz'] ),
		);
		$row['started_at']           = masteriyo_rest_prepare_date_response( $progress->get_started_at() );
		$row['modified_at']          = masteriyo_rest_prepare_date_response( $progress->get_modified_at() );
		$row['completed_at']         = masteriyo_rest_prepare_date_response( $progress->get_completed_at() );

		return $row;
	}

	/**
	 * How many items of one type a course has.
	 *
	 * @param int    $course_id Course ID.
	 * @param string $post_type Item post type.
	 *
	 * @return int
	 */
	private function course_item_count( int $course_id, string $post_type ): int {
		return count(
			get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => PostStatus::PUBLISH,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => '_course_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			)
		);
	}

	/**
	 * IDs of the courses linked to a group.
	 *
	 * @param Group $group Group instance.
	 *
	 * @return list<positive-int>
	 */
	private function group_course_ids( Group $group ): array {
		$courses_data = get_post_meta( $group->get_id(), 'masteriyo_course_data', true );

		return array_values(
			array_filter(
				array_map( 'absint', wp_list_pluck( (array) $courses_data, 'course_id' ) )
			)
		);
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post_id = (int) $request['id'];

		if ( ! $post_id ) {
			return new \WP_Error(
				'masteriyo_invalid_post_id',
				__( 'Invalid post ID or post does not exist.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$post_author = get_post_field( 'post_author', $post_id );

		// Allow if user is admin OR is the author
		if ( masteriyo_is_current_user_admin() || get_current_user_id() === absint( $post_author ) ) {
			return true;
		}

		return new \WP_Error(
			'masteriyo_rest_cannot_update',
			__( 'Sorry, you are not allowed to update this resource.', 'learning-management-system' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Check if a given request has access to delete item.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post_id = (int) $request['id'];

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new \WP_Error(
				'masteriyo_invalid_post_id',
				__( 'Invalid post ID or post does not exist.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$post_author = get_post_field( 'post_author', $post_id );

		// Allow if user is admin OR is the author
		if ( masteriyo_is_current_user_admin() || get_current_user_id() === absint( $post_author ) ) {
			return true;
		}

		return new \WP_Error(
			'masteriyo_rest_cannot_delete',
			__( 'Sorry, you are not allowed to delete this resource.', 'learning-management-system' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Check if a given request has access to delete multiple items.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post_ids = $request['ids'];

		if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
			return new \WP_Error(
				'masteriyo_invalid_post_id',
				__( 'Invalid post ID or post does not exist.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;

			if ( ! $post_id || ! get_post( $post_id ) ) {
					return new \WP_Error(
						'masteriyo_invalid_post_id',
						__( 'Invalid post ID or post does not exist.', 'learning-management-system' ),
						array( 'status' => 404 )
					);
			}

			$post_author = get_post_field( 'post_author', $post_id );

			if ( ! masteriyo_is_current_user_admin() && get_current_user_id() !== absint( $post_author ) ) {
					return new \WP_Error(
						'masteriyo_rest_cannot_delete',
						__( 'Sorry, you are not allowed to delete one or more of these resources.', 'learning-management-system' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
			}
		}

		return true;
	}

	/**
	 * Checks if a given request has access to update items.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function update_group_setting_permission_check( $request ) {
		return current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Provides the group setting data(client_id, client_secret, account_id)  data
	 *
	 * @since 1.9.0
	 *
	 * @return WP_Error|array
	 */
	public function get_group_setting() {
		$data = Setting::all();

		if ( ! isset( $data['groups_count'] ) ) {
			$data['groups_count'] = $this->get_groups_count();
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Add group client details to user meta.
	 *
	 * @since 1.9.0
	 *
	 * @param  $request $request Full details about the request.
	 *
	 * @return \WP_Error|array
	 */
	public function save_group_setting( $request ) {
		$deactivate_enrollment_on_member_change = isset( $request['deactivate_enrollment_on_member_change'] ) ? masteriyo_string_to_bool( $request['deactivate_enrollment_on_member_change'] ) : true;
		$deactivate_enrollment_on_status_change = isset( $request['deactivate_enrollment_on_status_change'] ) ? masteriyo_string_to_bool( $request['deactivate_enrollment_on_status_change'] ) : true;
		$group_buy_button_text                  = isset( $request['group_buy_button_text'] ) ? sanitize_text_field( $request['group_buy_button_text'] ) : '';
		$group_buy_helper_text                  = isset( $request['group_buy_helper_text'] ) ? sanitize_text_field( $request['group_buy_helper_text'] ) : '';
		Setting::set( 'deactivate_enrollment_on_member_change', $deactivate_enrollment_on_member_change );
		Setting::set( 'deactivate_enrollment_on_status_change', $deactivate_enrollment_on_status_change );
		Setting::set( 'group_buy_button_text', $group_buy_button_text );
		Setting::set( 'group_buy_helper_text', $group_buy_helper_text );
		Setting::save();

		return rest_ensure_response( Setting::all() );
	}

	/**
	 * Get the query params for collections of groups.
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['author_id'] = array(
			'description'       => 'Limit groups by author id.',
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']    = array(
			'default'           => 'any',
			'description'       => 'Limit result set to groups assigned a specific status.',
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future', 'trash' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.9.0
	 *
	 * @param int|\Masteriyo\Addons\GroupCourses\Models\Group|\WP_Post $object Object ID or Model or WP_Post object.
	 *
	 * @return false|\Masteriyo\Addons\GroupCourses\Models\Group
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, \WP_Post::class ) ? $object->ID : $object->get_id();
			}

			$group = masteriyo_create_group_object();
			$group->set_id( $id );
			$group_repo = masteriyo_create_group_store();
			$group_repo->read( $group );
		} catch ( \Exception $e ) {
			return false;
		}

		return $group;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 1.9.0
	 *
	 * @param  \Masteriyo\Database\Model $object  Model object.
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_group_data( $object, $context );

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
		 * @since 1.9.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.9.0
	 *
	 * @param array $objects Groups data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Groups query result data.
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
				'per_page'     => $query_args['posts_per_page'],
				'groups_count' => $this->get_groups_count(),
			),
		);
	}

	/**
		 * Get groups count by status.
		 *
		 * @since 1.9.0
		 *
		 * @return Array
		 */
	protected function get_groups_count() {
		$post_count = parent::get_posts_count();

		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), PostStatus::all() ) );
	}

	/**
	 * Get order information associated with a group.
	 *
	 * @since 2.30.0
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array|null
	 */
	protected function get_group_order_info( $group_id ) {
		$course_data = get_post_meta( $group_id, 'masteriyo_course_data', true );
		$order_id    = 0;

		if ( is_array( $course_data ) && ! empty( $course_data[0] ) && isset( $course_data[0]['order_id'] ) ) {
			$order_id = absint( $course_data[0]['order_id'] );
		}

		if ( ! $order_id ) {
			// Fallback: query by _created_group_id meta on orders.
			$query  = new OrderQuery(
				array(
					'meta_query' => array(
						array(
							'key'     => '_created_group_id',
							'value'   => $group_id,
							'compare' => '=',
						),
					),
					'limit'      => 1,
				)
			);
			$orders = $query->get_orders();
			if ( ! empty( $orders ) ) {
				$order = current( $orders );
				if ( $order ) {
					return array(
						'id'     => $order->get_id(),
						'status' => $order->get_status(),
					);
				}
			}
			return null;
		}

		// Use get_post so trashed orders are also visible.
		$order_post = get_post( $order_id );
		if ( ! $order_post ) {
			return null;
		}

		// Surface the real post_status including 'trash'.
		if ( 'trash' === $order_post->post_status ) {
			return array(
				'id'     => $order_id,
				'status' => 'trash',
			);
		}

		$order = masteriyo_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		return array(
			'id'     => $order->get_id(),
			'status' => $order->get_status(),
		);
	}

	/**
	 * Get group data.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group instance.
	 * @param string $context Request context.
	 *
	 * @return object
	 */
	protected function description_data( $group, $context ) {
		if ( 'view' === $context ) {
			return masteriyo_format_content_for_view( wp_kses_post( $group->get_description() ) );
		}
		return $group->get_description( $context );
	}

	/**
	 * Get group data.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group instance.
	 * @param string $context Request context. Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_group_data( $group, $context = 'view' ) {
		$author = masteriyo_get_user( $group->get_author_id( $context ) );

		$author = is_wp_error( $author ) || is_null( $author ) ? null : array(
			'id'           => $author->get_id(),
			'display_name' => $author->get_display_name(),
			'avatar_url'   => $author->get_avatar_url(),
		);

		$course_ids = $this->group_course_ids( $group );
		$courses    = array();

		foreach ( $course_ids as $course_id ) {
			$course = masteriyo_get_course( $course_id );

			if ( ! $course ) {
				continue;
			}

			$courses[] = array(
				'id'        => $course->get_id(),
				'title'     => wp_specialchars_decode( $course->get_title( $context ) ),
				'permalink' => $course->get_permalink(),
			);
		}

		$max_group_size = masteriyo_get_group_max_size( $group->get_id() );

		$order_info    = $this->get_group_order_info( $group->get_id() );
		$display_state = masteriyo_get_group_display_state( $group );

		$data = array(
			'id'             => $group->get_id(),
			'title'          => wp_specialchars_decode( $group->get_title( $context ) ),
			'status'         => $group->get_status( $context ),
			'display_status' => $display_state['display_status'],
			'status_reason'  => $display_state['status_reason'],
			'repurchase_url' => $display_state['repurchase_url'],
			'description'    => $this->description_data( $group, $context ),
			'date_created'   => masteriyo_rest_prepare_date_response( $group->get_date_created( $context ) ),
			'date_modified'  => masteriyo_rest_prepare_date_response( $group->get_date_modified( $context ) ),
			'emails'         => $group->get_emails( $context ),
			'author'         => $author,
			'courses_count'  => $course_ids ? count( $course_ids ) : 0,
			'courses'        => $courses,
			'max_group_size' => $max_group_size,
			'order'          => $order_info,
		);

		/**
		 * Filter Group rest response data.
		 *
		 * @since 1.9.0
		 *
		 * @param array $data Group data.
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param \Masteriyo\Addons\GroupCourses\Controllers\GroupsController $controller REST groups controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $group, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$args['post_status'] = $request['status'];

		if ( ! empty( $request['author_id'] ) ) {
			$args['author'] = $request['author_id'];
		} elseif ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$args['author'] = get_current_user_id();
			// Never surface trashed groups in a customer's own-list.
			if ( 'any' === $args['post_status'] || ( is_array( $args['post_status'] ) && in_array( 'any', $args['post_status'], true ) ) ) {
				$args['post_status'] = array( 'publish', 'draft', 'pending', 'private' );
			} elseif ( is_array( $args['post_status'] ) ) {
				$args['post_status'] = array_diff( $args['post_status'], array( 'trash' ) );
			} elseif ( 'trash' === $args['post_status'] ) {
				$args['post_status'] = array( 'publish' );
			}
		}

		return $args;
	}

	/**
	 * Get the groups'schema, conforming to JSON Schema.
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => 'Unique identifier for the resource.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'          => array(
					'description' => 'Group name',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'   => array(
					'description' => "The date the group was created, in the site's timezone.",
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'  => array(
					'description' => "The date the group was last modified, in the site's timezone.",
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'         => array(
					'description' => 'Group status (post status).',
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'    => array(
					'description' => 'Group description',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'     => array(
					'description' => 'Menu order, used to custom sort groups.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'emails'         => array(
					'description' => 'A list of the emails for the group.',
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'author_id'      => array(
					'description' => 'Group leader ID',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author'         => array(
					'description' => 'Group leader',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'id'           => array(
							'description' => 'Leader ID',
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'display_name' => array(
							'description' => 'Display name of the leader',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'avatar_url'   => array(
							'description' => 'Avatar URL of the leader',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'order'          => array(
					'description' => 'Associated order information',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'type'        => array( 'object', 'null' ),
					'properties'  => array(
						'id'     => array(
							'description' => 'Order ID',
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'status' => array(
							'description' => 'Order status',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'display_status' => array(
					'description' => 'Frontend display status derived from linked order.',
					'type'        => 'string',
					'enum'        => array( 'active', 'pending', 'inactive' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status_reason'  => array(
					'description' => 'Reason key for inactive display status.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'repurchase_url' => array(
					'description' => 'Checkout URL to retry this group purchase (only set when display_status is inactive).',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data'      => array(
					'description' => 'Meta data',
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => 'Meta ID',
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => 'Meta key',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => 'Meta value',
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
	 * Prepare a single group for create or update.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return \WP_Error|\Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		/** @var \Masteriyo\Addons\GroupCourses\Models\Group */
		$group = masteriyo( 'group-courses' );

		if ( 0 !== $id ) {
			$group->set_id( $id );
			/** @var \Masteriyo\Addons\GroupCourses\Repository\GroupRepository */
			$group_repo = masteriyo( 'group-courses.store' );
			$group_repo->read( $group );
		}

		// Check if emails field is being updated for group size validation.
		if ( isset( $request['emails'] ) || array_key_exists( 'emails', $request->get_params() ) ) {
			$submitted_emails = isset( $request['emails'] ) ? $request['emails'] : array();

			// Ensure emails is an array
			if ( ! is_array( $submitted_emails ) ) {
				$submitted_emails = array();
			}

			$group_count    = count( array_unique( $submitted_emails ) );
			$max_group_size = masteriyo_get_group_max_size( $group->get_id() );

			// Only check limit if course-specific limit is set
			if ( $max_group_size > 0 && $group_count > $max_group_size ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_group_limit_reached", __( 'Group member limit reached. Please remove members or create a new group.', 'learning-management-system' ), array( 'status' => 400 ) );
			}
		}

		// Post title.
		if ( isset( $request['title'] ) ) {
			$group->set_title( sanitize_text_field( $request['title'] ) );
		}

		if ( ! isset( $request['title'] ) || empty( $request['title'] ) ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_title", __( 'Group Missing title.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$group->set_description( wp_slash( wp_kses_post( $request['description'] ) ) );
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			// Post author.
			if ( isset( $request['author_id'] ) ) {
				$group->set_author_id( absint( $request['author_id'] ) );
			}

			// Post status. Enrollment status is synced after save by
			// update_enrollments_status_for_groups_update() on masteriyo_update_group,
			// scoped to the group's own courses.
			if ( isset( $request['status'] ) ) {
				$new_status = get_post_status_object( $request['status'] ) ? sanitize_text_field( $request['status'] ) : PostStatus::DRAFT;
				$group->set_status( $new_status );
			}
		}

		// Group emails validation - check if emails field is being updated
		if ( isset( $request['emails'] ) || array_key_exists( 'emails', $request->get_params() ) ) {
			$submitted_emails = isset( $request['emails'] ) ? $request['emails'] : array();

			// Ensure emails is an array
			if ( ! is_array( $submitted_emails ) ) {
				$submitted_emails = array();
			}

			// Group leader cannot be removed
			$group_author_id    = $group->get_author_id();
			$group_author       = get_userdata( $group_author_id );
			$group_author_email = $group_author ? $group_author->user_email : '';

			if ( $group_author_email && ! in_array( $group_author_email, $submitted_emails, true ) ) {
				return new \WP_Error(
					"masteriyo_rest_{$this->post_type}_cannot_remove_author",
					__( 'Group leader cannot be removed from the group.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}

			$group_id = $group->get_id();

			if ( $group_id ) {
				$old_emails     = $group->get_emails();
				$new_emails     = $submitted_emails;
				$removed_emails = array_diff( $old_emails, $new_emails );

				if ( Setting::get( 'deactivate_enrollment_on_member_change' ) ) {
					// Set the enrollment status to inactive only if user are  already enrolled in the group.
					masteriyo_update_user_enrollments_status( $group_id, $removed_emails, 'inactive' );

					// Set the enrollment status to active only if user are  already enrolled in the group.
					$newly_added_emails = array_diff( $new_emails, $old_emails );
					masteriyo_update_user_enrollments_status( $group_id, $newly_added_emails, 'active' );
				}

				foreach ( $removed_emails as $removed_email ) {
					$user = get_user_by( 'email', $removed_email );

					if ( ! $user ) {
						continue;
					}

					$user_group_ids = get_user_meta( $user->ID, 'masteriyo_group_ids', true );

					if ( is_array( $user_group_ids ) ) {
						update_user_meta( $user->ID, 'masteriyo_group_ids', array_values( array_diff( $user_group_ids, array( $group_id ) ) ) );
					}

					/**
					 * Fires after a member is removed from a group.
					 *
					 * @param int      $user_id  The ID of the removed member.
					 * @param \WP_User $user     The user object of the removed member.
					 * @param int      $group_id The ID of the group the user was removed from.
					 */
					do_action( 'masteriyo_group_course_member_removed', $user->ID, $user, $group_id );
				}
			}

			$group->set_emails( $submitted_emails );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$group->set_menu_order( absint( $request['menu_order'] ) );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$group->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		$not_allowed_fields = array( 'title', 'description', 'emails', 'menu_order', 'author_id', 'meta_data' );
		$changes            = $group->get_changes();
		$changed_fields     = array_keys( $changes );
		$restricted_changes = array_intersect( $not_allowed_fields, $changed_fields );

		$post_author = get_post_field( 'post_author', $id );

		if ( $id && get_current_user_id() !== absint( $post_author ) && ! empty( $restricted_changes ) && ! masteriyo_is_current_user_admin() ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_cannot_update", __( 'Sorry, you are not allowed to update this resource.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.9.0
		 *
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $group, $request, $creating );
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_items( $request ) {
		$objects         = array_map( 'masteriyo_get_group', array_map( 'absint', $request['ids'] ) );
		$deleted_objects = array();
		$is_force_delete = isset( $request['force'] ) ? masteriyo_string_to_bool( $request['force'] ) : false;

		foreach ( $objects as $object ) {
			$data = $this->prepare_object_for_response( $object, $request );

			$object->delete( $is_force_delete, $request->get_params() );

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
		 * @since 1.9.0
		 *
		 * @param array $deleted_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}

	/**
	 * Restore group.
	 *
	 * @since 1.9.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function restore_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$object->restore();

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Restore groups.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_items( $request ) {
		$restored_objects = array();

		$objects = $this->get_objects(
			array(
				'post_status'    => PostStatus::TRASH,
				'post_type'      => $this->post_type,
				'post__in'       => $request['ids'],
				'posts_per_page' => -1,
			)
		);

		$objects = isset( $objects['objects'] ) ? $objects['objects'] : array();

		foreach ( $objects as $object ) {
			if ( ! $object || 0 === $object->get_id() ) {
				continue;
			}

			$object->restore();

			$data               = $this->prepare_object_for_response( $object, $request );
			$restored_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $restored_objects );
	}
}
