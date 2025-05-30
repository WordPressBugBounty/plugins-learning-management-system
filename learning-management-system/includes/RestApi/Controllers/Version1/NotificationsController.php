<?php
/**
 * Notifications rest controller.
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Permission;
use Masteriyo\Models\Notification;
use Masteriyo\Enums\NotificationType;
use Masteriyo\Enums\NotificationLevel;
use Masteriyo\Enums\NotificationStatus;
use Masteriyo\Query\NotificationQuery;

class NotificationsController extends CrudController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.4.1
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';


	/**
	 * Object type.
	 *
	 * @since 1.4.1
	 *
	 * @var string
	 */
	protected $object_type = 'notification';

	/**
	 * Route base.
	 *
	 * @since 1.4.1
	 *
	 * @var string
	 */
	protected $rest_base = 'notifications';

	/**
	 * Permission class.
	 *
	 * @since 1.4.1
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.4.1
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
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'notification Id', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'notification Id', 'learning-management-system' ),
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
	 * @since 1.4.1
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = array(
			'user_id'    => array(
				'description'       => __( 'Notification for User ID', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'created_by' => array(
				'description'       => __( 'Notification created by.', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'type'       => array(
				'description'       => __( 'Notification type.', 'learning-management-system' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'post_id'    => array(
				'description'       => __( 'Notification from Post ID', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'topic_url'  => array(
				'description'       => __( 'Topic URL.', 'learning-management-system' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'     => array(
				'description'       => __( 'Notification status.', 'learning-management-system' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'    => array(
				'description'       => __( 'Sort collection by object attribute.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => array(
					'id',
					'user_id',
					'post_id',
					'topic_url',
					'status',
					'type',
					'created_at',
					'modified_at',
					'expire_at',
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'      => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'       => array(
				'description'       => __( 'Paginate the notifications.', 'learning-management-system' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page'   => array(
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
	 * Get the notifications'schema, conforming to JSON Schema.
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
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'       => array(
					'description' => __( 'Notification title', 'learning-management-system' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Notification description.', 'learning-management-system' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'user_id'     => array(
					'user_id' => __( 'Notification user ID.', 'learning-management-system' ),
					'type'    => 'integer',
					'context' => array( 'view', 'edit' ),
				),
				'created_by'  => array(
					'created_by' => __( 'Notification created by.', 'learning-management-system' ),
					'type'       => 'integer',
					'context'    => array( 'view', 'edit' ),
				),
				'status'      => array(
					'description' => __( 'Notification status.', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => NotificationStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'type'        => array(
					'description' => __( 'Notification type.', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => NotificationType::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'topic_url'   => array(
					'description' => __( 'Topic URL', 'learning-management-system' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'post_id'     => array(
					'user_id'  => __( 'Post ID.', 'learning-management-system' ),
					'type'     => 'integer',
					'required' => true,
					'context'  => array( 'view', 'edit' ),
				),
				'created_at'  => array(
					'description' => __( 'Notification created date, in the GMT timezone.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'modified_at' => array(
					'description' => __( 'Notification modified date, in the GMT timezone.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'expire_at'   => array(
					'description' => __( 'Notification expired date, in the GMT timezone.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get object.
	 *
	 * @since 1.4.1
	 *
	 * @param  int $id Object ID.
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $id ) {
		try {
			$id           = $id instanceof \stdClass ? $id->id : $id;
			$id           = $id instanceof Notification ? $id->get_id() : $id;
			$notification = masteriyo( 'notification' );
			$notification->set_id( $id );
			$notification_repo = masteriyo( 'notification.store' );
			$notification_repo->read( $notification );
		} catch ( \Exception $e ) {
			return false;
		}

		return $notification;
	}

	/**
	 * Get objects.
	 *
	 * @since  1.4.1
	 *
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$query   = new NotificationQuery( $query_args );
		$objects = $query->get_notifications();

		return array(
			'objects' => $objects,
			'total'   => (int) $query->rows_count,
			'pages'   => (int) ceil( $query->rows_count / (int) $query_args['per_page'] ),
		);
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.4.1
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
	 * @since 1.4.1
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
	 * @since  1.4.1
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = array(
			'per_page'   => $request['per_page'],
			'paged'      => $request['page'],
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'user_id'    => get_current_user_id(),
			'created_by' => absint( $request['created_by'] ),
			'status'     => $request['status'],
			'type'       => $request['type'],
			'topic_url'  => $request['topic_url'],
			'post_id'    => absint( $request['post_id'] ),
		);

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 1.4.1
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( 'masteriyo_rest_notifications_object_query', $args, $request );

		return $args;
	}

	/**
	 * Prepare a single notification for create or update.
	 *
	 * @since 1.4.1
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id           = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$notification = masteriyo( 'notification' );

		$notification_data_keys = array(
			'title',
			'description',
			'user_id',
			'created_by',
			'type',
			'topic_url',
			'post_id',
			'created_at',
			'expire_at',
		);

		// Update only required field if user is not admin or manager.
		if ( ! current_user_can( 'manage_options' ) ) {
			foreach ( $notification_data_keys as $key ) {
				$request->offsetUnset( $key );
			}
		}

		if ( 0 !== $id ) {
			$notification->set_id( $id );
			$notification_repo = masteriyo( 'notification.store' );
			$notification_repo->read( $notification );
		}

		// Notification title.
		if ( isset( $request['title'] ) ) {
			$notification->set_title( wp_filter_post_kses( $request['title'] ) );
		}

		// Notification description.
		if ( isset( $request['description'] ) ) {
			$notification->set_description( wp_filter_post_kses( $request['description'] ) );
		}

		// Notification user ID.
		if ( isset( $request['user_id'] ) ) {
			$notification->set_user_id( $request['user_id'] );
		}

		// Notification created by.
		if ( isset( $request['created_by'] ) ) {
			$notification->set_created_by( $request['created_by'] );
		}

		// Notification status.
		if ( isset( $request['status'] ) ) {
			$notification->set_status( $request['status'] );
		}

		// Notification type.
		if ( isset( $request['type'] ) ) {
			$notification->set_type( $request['type'] );
		}

		//topic url
		if ( isset( $request['topic_url'] ) ) {
			$notification->set_type( $request['topic_url'] );
		}

		//post_id
		if ( isset( $request['post_id'] ) ) {
			$notification->set_post_id( $request['post_id'] );
		}

		// Notification created at.
		if ( isset( $request['created_at'] ) ) {
			$notification->set_created_at( $request['created_at'] );
		}

		// Notification modified at.
		if ( isset( $request['modified_at'] ) ) {
			$notification->set_modified_at( $request['modified_at'] );
		}

		// Notification expire at.
		if ( isset( $request['expire_at'] ) ) {
			$notification->set_expire_at( $request['expire_at'] );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.4.1
		 *
		 * @param Masteriyo\Database\Model $notification Notification object.
		 * @param WP_REST_Request $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $notification, $request, $creating );
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.4.1
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_notification_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.4.1
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get notification data.
	 *
	 * @since 1.4.1
	 *
	 * @param Masteriyo\Models\Notification $notification notification instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_notification_data( $notification, $context = 'view' ) {
		$data = array(
			'id'          => $notification->get_id( $context ),
			'title'       => $notification->get_title( $context ),
			'description' => $notification->get_description( $context ),
			'user_id'     => $notification->get_user_id( $context ),
			'created_by'  => $notification->get_created_by( $context ),
			'status'      => $notification->get_status( $context ),
			'type'        => $notification->get_type( $context ),
			'topic_url'   => $notification->get_topic_url( $context ),
			'post_id'     => $notification->get_post_id( $context ),
			'created_at'  => masteriyo_rest_prepare_date_response( $notification->get_created_at( $context ) ),
			'modified_at' => masteriyo_rest_prepare_date_response( $notification->get_modified_at( $context ) ),
			'expire_at'   => masteriyo_rest_prepare_date_response( $notification->get_expire_at( $context ) ),
		);

		/**
		 * Filter notification rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Notification data.
		 * @param Masteriyo\Models\Notification $notification Notification object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\NotificationsController $controller REST Notifications controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $notification, $context, $this );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.4.1
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

		if ( ! masteriyo_is_current_user_admin() || ! masteriyo_is_current_user_manager() ) {
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
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 1.4.1
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

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$notification = $this->get_object( absint( $request['id'] ) );

		if ( ! $notification ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( get_current_user_id() !== $notification->get_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				/* translators: %s: post type */
				sprintf( __( 'Sorry, you are not allowed to read other %s.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.4.1
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

		$notification = $this->get_object( absint( $request['id'] ) );

		if ( ! $notification ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( get_current_user_id() !== $notification->get_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				/* translators: %s: post type */
				sprintf( __( 'Sorry, you are not allowed to delete other %s.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.4.1
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

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'masteriyo_user_not_logged_in',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
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
	 * @since 1.7.4
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

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'masteriyo_user_not_logged_in',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$notification = $this->get_object( absint( $request['id'] ) );

		if ( ! $notification ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( get_current_user_id() !== $notification->get_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				/* translators: %s: post type */
				sprintf( __( 'Sorry, you are not allowed to update other %s.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

		/**
	 * Prepare objects query for batch.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *P
	 * @since  1.7.1
	 * @return array
	 */
	protected function prepare_objects_query_for_batch( $request ) {
		$query_args = array(
			'status'    => NotificationStatus::all(),
			'item_type' => NotificationType::ALL,
			'include'   => wp_parse_id_list( $request['ids'] ),
			'per_page'  => -1,
			'user_id'   => get_current_user_id(),
		);

		/**
		 * Filters objects query for batch operation.
		 *
		 * @since 2.6.6
		 *
		 * @param array $query_args Query arguments.
		 * @param WP_REST_Request $request
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller
		 */
		return apply_filters( 'masteriyo_rest_objects_query_for_batch', $query_args, $request, $this );
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.7.1
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_items( $request ) {

		$deleted_objects = array();

		$request->set_param( 'context', 'edit' );

		$objects = $this->get_objects( $this->prepare_objects_query_for_batch( $request ) );

		$objects = isset( $objects['objects'] ) ? $objects['objects'] : array();

		foreach ( $objects as $object ) {
			if ( ! $this->check_item_permission( $this->post_type, 'delete', $object->get_id() ) ) {
				continue;
			}

			$data = $this->prepare_object_for_response( $object, $request );

			$object->delete( true );

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
		 * @since 1.7.1
		 *
		 * @param array $deleted_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.7.1
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_items( $request ) {

		$updated_objects = array();

		$request->set_param( 'context', 'edit' );

		$objects = $this->get_objects( $this->prepare_objects_query_for_batch( $request ) );

		$objects = isset( $objects['objects'] ) ? $objects['objects'] : array();
		foreach ( $objects as $object ) {
			if ( ! $this->check_item_permission( $this->post_type, 'update', $object->get_id() ) ) {
				continue;
			}

			$data = $this->prepare_object_for_response( $object, $request );

			$object->set_status( 'read' );
			$object->save();

			if ( 0 !== $object->get_id() ) {
				$updated_objects[] = $this->prepare_response_for_collection( $data );
			}
		}

		if ( empty( $updated_objects ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_bulk_update',
				/* translators: %s: post type */
				sprintf( __( 'The %s cannot be bulk updated.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => 501 )
			);
		}

		/**
		 * Fires after a multiple objects is deleted or trashed via the REST API.
		 *
		 * @since 2.6.6
		 *
		 * @param array $updated_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_update_{$this->object_type}_objects", $updated_objects, $objects, $request );

		return rest_ensure_response( $updated_objects );
	}

}
