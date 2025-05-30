<?php
/**
 * Course announcement class controller.
 *
 * @since 1.6.16
 * @package Masteriyo\RestApi
 * @subpackage Controllers
 */

namespace Masteriyo\Addons\CourseAnnouncement\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\PostStatus;
use WP_Error;

use Masteriyo\RestApi\Controllers\Version1\PostsController;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;

class CourseAnnouncementController extends PostsController {

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
	protected $rest_base = 'course-announcement';

	/** Post type.
	 *
	 * @var string
	 */
	protected $post_type = PostType::COURSEANNOUNCEMENT;

	/** Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'course_announcement';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Permission class.
	 *
	 * @since 1.6.16
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.6.16
	 *
	 * @param Permission $permission
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.6.16
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
							'description' => __( 'Announcement IDs.', 'learning-management-system' ),
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
							'description' => __( 'Announcement Ids', 'learning-management-system' ),
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
	 * @since 1.6.16
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		// The sections should be order by menu which is the sort order.
		$params['order']['default']   = 'asc';
		$params['orderby']['default'] = 'menu_order';

		$params['course_id'] = array(
			'description'       => __( 'Limit course announcements by course id.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['author_id'] = array(
			'description'       => __( 'Limit course announcements by author id.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['slug']      = array(
			'description'       => __( 'Limit result set to course announcements with a specific slug.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']    = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to course announcements assigned a specific status.', 'learning-management-system' ),
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
	 * @since 1.6.16
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
			$course_announcement = masteriyo( 'course-announcement' );
			$course_announcement->set_id( $id );
			$course_announcement_repo = masteriyo( 'course-announcement.store' );
			$course_announcement_repo->read( $course_announcement );
		} catch ( \Exception $e ) {
			return false;
		}

		return $course_announcement;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 1.6.16
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_course_announcement_data( $object, $context );

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
		 * @since 1.6.16
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
	 * @since 1.6.16
	 *
	 * @param array $objects Course announcement data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Course announcement query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'              => $query_results['total'],
				'pages'              => $query_results['pages'],
				'current_page'       => $query_args['paged'],
				'per_page'           => $query_args['posts_per_page'],
				'announcement_count' => $this->get_announcement_count(),
			),
		);
	}

	/**
	 * Get announcement count by status.
	 *
	 * @since 1.6.16
	 *
	 * @return Array
	 */
	protected function get_announcement_count() {
		$post_count = parent::get_posts_count();

		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), PostStatus::all() ) );
	}

		/**
	 * Get course announcement data.
	 *
	 * @since 1.7.3
	 *
	 * @param \Masteriyo\Models\CourseAnnouncement $course_announcement Course announcement instance.
	 * @param string $context Request context.
	 *
	 * @return object
	 */
	protected function description_data( $course_announcement, $context ) {
		$default_editor_option = masteriyo_get_setting( 'advance.editor.default_editor' );
		$description           = '';
		if ( 'classic_editor' === $default_editor_option ) {
			$description = 'view' === $context ? wpautop( do_shortcode( $course_announcement->get_description() ) ) : $course_announcement->get_description( $context );
		}
		if ( 'block_editor' === $default_editor_option ) {
			$description = 'view' === $context ? do_shortcode( $course_announcement->get_description() ) : $course_announcement->get_description( $context );
		}
		return $description;
	}


	/**
	 * Get course announcement data.
	 *
	 * @since 1.6.16
	 *
	 * @param \Masteriyo\Models\CourseAnnouncement $course_announcement Course announcement instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_course_announcement_data( $course_announcement, $context = 'view' ) {
		$author = masteriyo_get_user( $course_announcement->get_author_id( $context ) );

		$author = is_wp_error( $author ) || is_null( $author ) ? null : array(
			'id'           => $author->get_id(),
			'display_name' => $author->get_display_name(),
			'avatar_url'   => $author->get_avatar_url(),
		);
		$course = masteriyo_get_course( $course_announcement->get_course_id( $context ) );

		$has_user_read = masteriyo_string_to_bool( get_user_meta( get_current_user_id(), 'has_user_read_' . $course_announcement->get_id(), true ) );

		$data = array(
			'id'            => $course_announcement->get_id(),
			'title'         => wp_specialchars_decode( $course_announcement->get_title( $context ) ),
			'slug'          => $course_announcement->get_slug( $context ),
			'permalink'     => $course_announcement->get_permalink(),
			'status'        => $course_announcement->get_status( $context ),
			'description'   => $this->description_data( $course_announcement, $context ),
			'date_created'  => masteriyo_rest_prepare_date_response( $course_announcement->get_date_created( $context ) ),
			'date_modified' => masteriyo_rest_prepare_date_response( $course_announcement->get_date_modified( $context ) ),
			'menu_order'    => $course_announcement->get_menu_order( $context ),
			'course'        => array(
				'id'   => $course_announcement->get_course_id(),
				'name' => $course ? $course->get_name( $context ) : '',
			),
			'author'        => $author,
			'has_user_read_' . $course_announcement->get_id() => empty( $has_user_read ) ? false : $has_user_read,
		);

		/**
		 * Filter Course announcement rest response data.
		 *
		 * @since 1.6.16
		 *
		 * @param array $data Course announcement data.
		 * @param Masteriyo\Models\CourseAnnouncement $course_announcement Course announcement object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\Addons\CourseAnnouncement\Controllers\CourseAnnouncementController $controller REST course-announcements controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $course_announcement, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since 1.6.16
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		if ( ! empty( $request['author_id'] ) ) {
			$args['author'] = $request['author_id'];
		}

		// Instructor need to list only their announcement on backend but might need to get announcement from other instructors or admin as well if they are enrolled in any course.
		if ( masteriyo_is_current_user_instructor() ) {
			if ( isset( $request['request_from'] ) && 'learn' === $request['request_from'] ) {
				$args['author__in'] = '';
			} else {
				$args['author__in'] = array( get_current_user_id() );
			}
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

		return $args;
	}

	/**
	 * Get the course announcements'schema, conforming to JSON Schema.
	 *
	 * @since 1.6.16
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'description' => __( 'Course announcement name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'              => array(
					'description' => __( 'Course announcement slug', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'         => array(
					'description' => __( 'Course announcement URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'      => array(
					'description' => __( "The date the course announcement was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the course announcement was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'     => array(
					'description' => __( "The date the course announcement was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the course announcement was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'Course announcement status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'       => array(
					'description' => __( 'Course announcement description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'         => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'        => array(
					'description' => __( 'Menu order, used to custom sort course announcements.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'         => array(
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
	 * Prepare a single course announcement for create or update.
	 *
	 * @since 1.6.16
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		$course_announcement = masteriyo( 'course-announcement' );

		if ( 0 !== $id ) {
			$course_announcement->set_id( $id );
			$course_announcement_repo = masteriyo( 'course-announcement.store' );
			$course_announcement_repo->read( $course_announcement );
		}

		// Post title.
		if ( isset( $request['title'] ) ) {
			$course_announcement->set_title( sanitize_text_field( $request['title'] ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$course_announcement->set_description( wp_slash( $request['description'] ) );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$course_announcement->set_status( get_post_status_object( $request['status'] ) ? $request['status'] : 'draft' );
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$course_announcement->set_slug( $request['slug'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$course_announcement->set_menu_order( $request['menu_order'] );
		}

		// Automatically set the menu order if it's not set and the operation is POST.
		if ( ! isset( $request['menu_order'] ) && $creating ) {
			$query = new \WP_Query(
				array(
					'post_type'      => PostType::COURSEANNOUNCEMENT,
					'post_status'    => PostStatus::all(),
					'posts_per_page' => 1,
				)
			);

			$course_announcement->set_menu_order( $query->found_posts );
		}

		// Course ID.
		if ( isset( $request['course_id'] ) ) {
			$course_announcement->set_course_id( $request['course_id'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$course_announcement->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		// Update user read.
		if ( isset( $request['has_read'] ) ) {
			update_user_meta( get_current_user_id(), 'has_user_read_' . $id, true );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.6.16
		 *
		 * @param Masteriyo\Database\Model $course_announcement Course announcement object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $course_announcement, $request, $creating );
	}

		/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.6.16
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

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.6.16
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

		$id                  = absint( $request['id'] );
		$course_announcement = masteriyo_get_course_announcement( $id );

		if ( is_null( $course_announcement ) ) {
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
	 * @since 1.6.16
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

		if ( 'learn' === $request['request_from'] ) {
			if ( masteriyo_is_current_user_student() || masteriyo_is_current_user_instructor() ) {
				return true;
			}
		}

		$id                  = absint( $request['id'] );
		$course_announcement = masteriyo_get_course_announcement( $id );

		if ( is_null( $course_announcement ) ) {
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
	 * Restore announcement.
	 *
	 * @since 1.6.16
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		wp_untrash_post( $object->get_id() );

		// Read object again.
		$object = $this->get_object( (int) $request['id'] );

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}


}
