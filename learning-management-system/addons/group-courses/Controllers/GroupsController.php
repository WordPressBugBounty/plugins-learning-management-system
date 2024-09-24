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

use Masteriyo\Addons\GroupCourses\Models\Setting;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
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
	public function __construct( Permission $permission = null ) {
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
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => 'is_user_logged_in',
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
						'force' => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
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
							'description' => __( 'Group IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force'    => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
						'children' => array(
							'default'     => false,
							'description' => __( 'Whether to delete the children(sections, lessons, quizzes and questions) under the course.', 'learning-management-system' ),
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
							'description' => __( 'Group Ids', 'learning-management-system' ),
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

		if ( ! current_user_can( 'edit_others_posts' ) && get_current_user_id() !== absint( $post_author ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to view this resource.', 'learning-management-system' ),
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

		if ( ! current_user_can( 'edit_others_posts', $post_id ) && get_current_user_id() !== absint( $post_author ) ) {
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

		if ( ! current_user_can( 'delete_others_posts', $post_id ) && get_current_user_id() !== absint( $post_author ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this resource.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
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

			if ( ! current_user_can( 'delete_others_posts', $post_id ) && get_current_user_id() !== absint( $post_author ) ) {
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
		$max_members                            = isset( $request['max_members'] ) ? sanitize_text_field( $request['max_members'] ) : '';
		$deactivate_enrollment_on_member_change = isset( $request['deactivate_enrollment_on_member_change'] ) ? masteriyo_string_to_bool( $request['deactivate_enrollment_on_member_change'] ) : true;
		$deactivate_enrollment_on_status_change = isset( $request['deactivate_enrollment_on_status_change'] ) ? masteriyo_string_to_bool( $request['deactivate_enrollment_on_status_change'] ) : true;

		Setting::set( 'max_members', $max_members );
		Setting::set( 'deactivate_enrollment_on_member_change', $deactivate_enrollment_on_member_change );
		Setting::set( 'deactivate_enrollment_on_status_change', $deactivate_enrollment_on_status_change );
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
			'description'       => __( 'Limit groups by author id.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']    = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to groups assigned a specific status.', 'learning-management-system' ),
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
		$default_editor_option = masteriyo_get_setting( 'general.editor.default_editor' );

		if ( 'classic_editor' === $default_editor_option ) {
			$description = 'view' === $context ? wpautop( do_shortcode( $group->get_description() ) ) : $group->get_description( $context );
		}
		if ( 'block_editor' === $default_editor_option ) {
			$description = 'view' === $context ? do_shortcode( $group->get_description() ) : $group->get_description( $context );
		}
		return $description;
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

		$course_ids = masteriyo_get_active_course_ids_for_group( $group->get_id(), 'active' );

		$data = array(
			'id'            => $group->get_id(),
			'title'         => wp_specialchars_decode( $group->get_title( $context ) ),
			'status'        => $group->get_status( $context ),
			'description'   => $this->description_data( $group, $context ),
			'date_created'  => masteriyo_rest_prepare_date_response( $group->get_date_created( $context ) ),
			'date_modified' => masteriyo_rest_prepare_date_response( $group->get_date_modified( $context ) ),
			'emails'        => $group->get_emails( $context ),
			'author'        => $author,
			'courses_count' => $course_ids ? count( $course_ids ) : 0,
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
				'id'            => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'         => array(
					'description' => __( 'Group name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'  => array(
					'description' => __( "The date the group was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified' => array(
					'description' => __( "The date the group was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'        => array(
					'description' => __( 'Group status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'   => array(
					'description' => __( 'Group description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'    => array(
					'description' => __( 'Menu order, used to custom sort groups.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'emails'        => array(
					'description' => __( 'A list of the emails for the group.', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'author_id'     => array(
					'description' => __( 'Group author ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author'        => array(
					'description' => __( 'Group author', 'learning-management-system' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'id'           => array(
							'description' => __( 'Author ID', 'learning-management-system' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'display_name' => array(
							'description' => __( 'Display name of the author', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'avatar_url'   => array(
							'description' => __( 'Avatar URL of the author', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'meta_data'     => array(
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

		$group = masteriyo( 'group-courses' );

		if ( 0 !== $id ) {
			$group->set_id( $id );
			$group_repo = masteriyo( 'group-courses.store' );
			$group_repo->read( $group );
		}

		if ( isset( $request['emails'] ) && ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			if ( is_array( $request['emails'] ) ) {
				$group_count         = count( array_unique( $request['emails'] ) );
				$max_members_setting = masteriyo_get_groups_limit();

				if ( $max_members_setting && $group_count > $max_members_setting ) {
					return new \WP_Error( "masteriyo_rest_{$this->post_type}_group_limit_reached", __( 'You have reached the group limit.', 'learning-management-system' ), array( 'status' => 400 ) );
				}
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
			$group->set_description( wp_slash( $request['description'] ) );
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			// Post author.
			if ( isset( $request['author_id'] ) ) {
				$group->set_author_id( absint( $request['author_id'] ) );
			}

			// Post status.
			if ( isset( $request['status'] ) ) {
				$new_status     = get_post_status_object( $request['status'] ) ? sanitize_text_field( $request['status'] ) : PostStatus::DRAFT;
				$current_status = $group->get_status();

				// Update  all the enrollments related with this group.
				$this->update_enrollments_status( $group, $current_status, $new_status );
				$group->set_status( $new_status );
			}
		}

		// Group emails.
		if ( isset( $request['emails'] ) ) {

			if ( Setting::get( 'deactivate_enrollment_on_member_change' ) || ! empty( $request['emails'] ) ) {

				$group_id = $group->get_id();

				if ( $group_id ) {
					$old_emails = $group->get_emails();
					$new_emails = $request['emails'];

					// Set the enrollment status to inactive only if user are  already enrolled in the group.
					$removed_emails = array_diff( $old_emails, $new_emails );
					masteriyo_update_user_enrollments_status( $group_id, $removed_emails, 'inactive' );

					// Set the enrollment status to active only if user are  already enrolled in the group.
					$newly_added_emails = array_diff( $new_emails, $old_emails );
					masteriyo_update_user_enrollments_status( $group_id, $newly_added_emails, 'active' );
				}
			}

			$group->set_emails( $request['emails'] );
		}

		if ( ! ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) && empty( $group->get_emails() ) ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_emails", __( 'Group Missing emails.', 'learning-management-system' ), array( 'status' => 400 ) );
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

		if ( $id && get_current_user_id() !== absint( $post_author ) && ! empty( $restricted_changes ) ) {
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

	/**
	 * Update enrollments status for members of a specified group.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\ModelsGroup $group Group object.
	 * @param string $current_status Current status of the enrollment.
	 * @param string $new_status New status to update the enrollment to.
	 */
	private function update_enrollments_status( $group, $current_status, $new_status ) {
		global $wpdb;

		if ( ! $wpdb || ! $group ) {
			return;
		}

		if ( $current_status === $new_status ) {
			return;
		}

		$group_members = $group->get_emails();
		if ( empty( $group_members ) ) {
			return;
		}

		foreach ( $group_members as $group_member ) {
			$user = get_user_by( 'email', $group_member );
			if ( ! $user ) {
				continue;
			}

			$actual_new_status = PostStatus::PUBLISH !== $new_status ? 'inactive' : 'active';

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}masteriyo_user_items
						SET status = %s
						WHERE user_id = %d
						AND item_type = 'user_course'",
					$actual_new_status,
					$user->ID
				)
			);
		}
	}
}
