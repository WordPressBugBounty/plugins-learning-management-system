<?php
/**
 * Webhooks controller class.
 *
 * @since 1.6.9
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Enums\UserStatus;
use Masteriyo\Enums\WebhookStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseProgressItemQuery;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Resources\WebhookResource;
use Masteriyo\Roles;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User_Query;

class WebhooksController extends PostsController {
	/**
	 * Webhook events that grant an instructor account and require the site-wide admin token.
	 * Non-privileged events such as masteriyo_create_student stay available to instructor tokens.
	 *
	 * @var string[]
	 */
	const ADMIN_ONLY_WEBHOOK_EVENTS = array( 'masteriyo_create_instructor' );

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
	protected $rest_base = 'webhooks';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $object_type = 'webhook';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = PostType::WEBHOOK;

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Permission class.
	 *
	 * @since 1.6.9
	 *
	 * @var \Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.6.9
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.6.9
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
			'/' . $this->rest_base . '/events',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_listeners' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/action',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'masteriyo_process_webhook' ),
					'permission_callback' => array( $this, 'masteriyo_webhook_action_permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_webhook_settings_data' ),
					'permission_callback' => array( $this, 'get_webhook_settings_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'regenerate_webhook_settings_data' ),
					'permission_callback' => array( $this, 'get_webhook_settings_permission_check' ),
				),
			)
		);
	}

	/**
	 * Regenerate webhook settings data with a new secure key.
	 *
	 * This method generates a new secure key and stores it based on user role:
	 * - For admin/manager: Stores in wp_options table
	 * - For other users: Stores in user meta
	 *
	 * @since 2.19.0
	 *
	 * @param \WP_REST_Request $request The incoming request object.
	 *
	 * @return \WP_REST_Response Response object containing the new webhook action token.
	 */
	public function regenerate_webhook_settings_data( \WP_REST_Request $request ) {
		$secure_key = apply_filters( 'masteriyo_generate_secure_key', wp_generate_password( 32, true, true ) );

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			update_option( 'MASTERIYO_WEBHOOK_SETTINGS', $secure_key );
		} else {
			update_user_meta( get_current_user_id(), 'webhook_action_token', $secure_key );
		}

		return rest_ensure_response( array( 'webhook_action_token' => $secure_key ) );
	}

	/**
	 * Permission callback for webhook settings
	 *
	 * @since 2.19.0
	 */
	public function webhook_settings_permission_check( \WP_REST_Request $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access these settings.', 'learning-management-system' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Permission callback for webhook settings
	 *
	 * @since 2.19.0
	 *
	 */
	public function get_webhook_settings_permission_check( \WP_REST_Request $request ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_instructor() ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to access these settings.', 'learning-management-system' ),
			array( 'status' => rest_authorization_required_code() )
		);

	}

	/**
	 * get webhook settings data
	 *
	 * @since 2.19.0
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function get_webhook_settings_data( \WP_REST_Request $request ) {
		$secure_key = wp_generate_password( 32, true, true );
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			$web_action_token = get_option( 'MASTERIYO_WEBHOOK_SETTINGS', '' );
			if ( ! $web_action_token ) {
				update_option( 'MASTERIYO_WEBHOOK_SETTINGS', $secure_key );
				$web_action_token = get_option( 'MASTERIYO_WEBHOOK_SETTINGS', '' );
			}
			$response = array(
				'webhook_action_token' => $web_action_token,
			);
			return rest_ensure_response( $response );
		}

		$user_id          = get_current_user_id();
		$web_action_token = get_user_meta( $user_id, 'webhook_action_token', true );
		if ( ! $web_action_token ) {
			update_user_meta( $user_id, 'webhook_action_token', $secure_key );
			$web_action_token = get_user_meta( $user_id, 'webhook_action_token', true );
		}

			$response = array(
				'webhook_action_token' => $web_action_token,
			);
			return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to get a specific webhook.
	 *
	 * The `read` capability used by the base permissions check is WordPress's generic
	 * primitive capability (held by every logged-in user, including Subscribers) and is
	 * never mapped against post ownership, so it must not be relied on here. Only admins,
	 * managers, and the webhook's own author may read a webhook, since the response
	 * includes the HMAC signing secret and delivery URL.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post = get_post( (int) $request['id'] );

		if ( ! $post || $this->post_type !== $post->post_type ) {
			return new WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( get_current_user_id() === (int) $post->post_author ) {
			return true;
		}

		return new WP_Error(
			'masteriyo_rest_cannot_read',
			__( 'Sorry, you are not allowed to read this resource.', 'learning-management-system' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Verify webhook permissions and validate the incoming request.
	 *
	 * This method handles webhook authentication and authorization by:
	 * 1. Validating the webhook token from headers or request parameters
	 * 2. Checking content type is JSON
	 * 3. Validating JSON payload format
	 * 4. Verifying instructor permissions for course-related actions
	 *
	 * @since 2.19.0
	 *
	 * @param WP_REST_Request $request The incoming request object.
	 *
	 * @return bool|WP_Error True if authorized, WP_Error if not authorized or validation fails.
	 */
	public function masteriyo_webhook_action_permission_callback( \WP_REST_Request $request ) {
		$provided_token = $request->get_header( 'X-WP-Webhook-Token' );

		if ( ! isset( $_SERVER['CONTENT_TYPE'] ) || false === strpos( $_SERVER['CONTENT_TYPE'], 'application/json' ) ) {
			masteriyo_get_logger()->info(
				'Unsupported payload type format, only JSON is supported.',
				array( 'source' => 'webhook-action' )
			);
			return new \WP_Error( 'masteriyo_rest_unsupported_payload_type', 'Unsupported payload type format, only JSON is supported.', array( 'status' => rest_authorization_required_code() ) );
		}

		$raw_body = $request->get_body();
		$data     = json_decode( $raw_body, true );

		if ( null === $data ) {
			masteriyo_get_logger()->info( 'Invalid JSON received.', array( 'source' => 'webhook-action' ) );
			return new \WP_Error( 'masteriyo_rest_invalid_json', 'Invalid JSON payload.', array( 'status' => rest_authorization_required_code() ) );
		}

		if ( isset( $data[0] ) ) {
			masteriyo_get_logger()->info( 'Request payload is wrapped in an array. Please do not wrap in array', array( 'source' => 'webhook-action' ) );
			return new \WP_Error( 'masteriyo_rest_request_incorrect_payload', 'Request payload is wrapped in an array. Please do not wrap in array', array( 'status' => rest_authorization_required_code() ) );
		}

		if ( empty( $provided_token ) ) {
			$provided_token = $request->get_param( 'token' );
		}

		$stored_webhook_settings = get_option( 'MASTERIYO_WEBHOOK_SETTINGS', '' );

		// Note: this is to for future reference that we can add whitelist IPs option for webhook actions.
		if ( ! empty( $stored_webhook_settings ) && hash_equals( $stored_webhook_settings, $provided_token ) ) {
			return true;
		}

		$user_query  = new WP_User_Query(
			array(
				'meta_key'   => 'webhook_action_token',
				'meta_value' => $provided_token,
				'role__in'   => array( 'masteriyo_instructor' ),
				'number'     => 1,
			)
		);
		$instructors = $user_query->get_results();

		if ( ! empty( $instructors ) ) {
			// Instructor-scoped tokens may only run non-privileged, course-scoped actions; events that grant an instructor account stay admin-only.
			if ( in_array( isset( $data['event'] ) ? $data['event'] : '', self::ADMIN_ONLY_WEBHOOK_EVENTS, true ) ) {
				masteriyo_get_logger()->error( 'Instructor token attempted a privileged webhook event.', array( 'source' => 'webhook-action' ) );
				return new \WP_Error(
					'masteriyo_rest_user_not_approved',
					__( 'Sorry, you are not allowed to perform this action.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			$course_id = $request->get_param( 'course_id' );
			if ( empty( $course_id ) ) {
				return false;
			}

			$course = masteriyo_get_course( absint( $course_id ) );
			if ( ! $course ) {
				return false;
			}

			foreach ( $instructors as $instructor ) {
				if ( $instructor->ID === $course->get_author_id() ) {
					return true;
				}

				$additional_author_ids = (array) $course->get_meta( '_additional_authors', false );
				$additional_author_ids = array_unique( array_values( $additional_author_ids ) );

				if ( in_array( $instructor->ID, $additional_author_ids, true ) ) {
					return true;
				}
			}

			masteriyo_get_logger()->error( 'Unauthorized access attempted.', array( 'source' => 'webhook-action' ) );

			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved to make changes in this course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return false;
	}



	/**
	 * Process action for webhook.
	 *
	 * @since 2.19.0
	 *
	 * @param WP_REST_Request $request The incoming request object.
	 * @return WP_REST_Response
	 */
	public function masteriyo_process_webhook( $request ) {
		if ( ! isset( $_SERVER['CONTENT_TYPE'] ) || strpos( $_SERVER['CONTENT_TYPE'], 'application/json' ) === false ) {
			masteriyo_get_logger()->error(
				'Invalid content type. Only JSON is supported.',
				array( 'source' => 'webhook-action' )
			);

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid content type. Only JSON is supported.',
				),
				rest_authorization_required_code()
			);
		}
		$data = json_decode( $request->get_body(), true );

		if ( null === $data ) {
			masteriyo_get_logger()->error( 'Invalid JSON payload.', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid JSON payload.',
				),
				rest_authorization_required_code()
			);
		}

		if ( ! isset( $data['event'] ) || empty( $data['event'] ) ) {
			masteriyo_get_logger()->error( 'Missing required event parameter.', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required event parameter.',
				),
				rest_authorization_required_code()
			);
		}

		$valid_events = array(
			'masteriyo_user_enrolled_to_course',
			'masteriyo_create_student',
			'masteriyo_create_instructor',
			'masteriyo_complete_lesson',
			'masteriyo_course_progress_reset',
			'masteriyo_unenrolled_user_from_course',
			'masteriyo_course_user_completed',
		);

		if ( ! in_array( $data['event'], $valid_events, true ) ) {
			masteriyo_get_logger()->error( 'No event with this name found', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'No event with this name found',
				),
				rest_authorization_required_code()
			);
		}
		switch ( $data['event'] ) {
			case 'masteriyo_user_enrolled_to_course':
				return $this->masteriyo_user_enrollment( $data );
			case 'masteriyo_create_student':
				return $this->masteriyo_create_user( $data );
			case 'masteriyo_create_instructor':
				return $this->masteriyo_create_user( $data, Roles::INSTRUCTOR );
			case 'masteriyo_complete_lesson':
				return $this->masteriyo_complete_lesson( $data );
			case 'masteriyo_course_progress_reset':
				return $this->masteriyo_course_progress_reset( $data );
			case 'masteriyo_unenrolled_user_from_course':
				return $this->masteriyo_unenrolled_user_from_course( $data );
			case 'masteriyo_course_user_completed':
				return $this->masteriyo_course_user_completed( $data );
			default:
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Unsupported event type',
					),
					rest_authorization_required_code()
				);
		}
	}

	/**
	 * Marks a course as completed for a specific user.
	 *
	 * This function handles the webhook action to mark a course as completed for a user.
	 * It validates the course and user existence, checks enrollment status, and updates
	 * the course progress accordingly.
	 *
	 * @since 2.19.0
	 *
	 * @param array $data Request data containing:
	 *                    - course_id (int) The ID of the course to mark as completed
	 *                    - user_id (int) The ID of the user completing the course
	 * @return WP_REST_Response|WP_Error Response object on success with course progress data,
	 *                                   or WP_Error object on failure
	 */
	public function masteriyo_course_user_completed( $data ) {
		if ( ! isset( $data['course_id'] ) ) {
			masteriyo_get_logger()->error( 'Missing required parameter: course_id', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Missing required parameter: course_id', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		if ( ! isset( $data['user_id'] ) ) {
			masteriyo_get_logger()->error( 'Missing required parameter: user_id', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Missing required parameter: user_id', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		$course_id  = absint( $data['course_id'] );
		$student_id = absint( $data['user_id'] );

		if ( ! $course_id || ! $student_id ) {
			masteriyo_get_logger()->error( 'Invalid course_id or user_id values.', array( 'source' => 'webhook-action' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid course_id or user_id values.', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		$user_course = masteriyo_get_user_course_by_user_and_course( $student_id, $course_id );

		if ( ! $user_course ) {
			masteriyo_get_logger()->error( 'User is not enrolled in the course.', array( 'source' => 'webhook-action' ) );
			return $this->error_response( __( 'User is not enrolled in the course.', 'learning-management-system' ), rest_authorization_required_code() );
		}

		$result = masteriyo_complete_specific_course_for_user( $course_id, $student_id, array() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'            => true,
				'message'            => __( 'Course completed successfully.', 'learning-management-system' ),
				'course_progress_id' => $result['course_progress']->get_id(),
				'completed_items'    => $result['completed_items'],
			)
		);
	}


	/**
	 * unenrolled user from a specific course
	 *
	 * @since 2.19.0
	 *
	 * @param array $data Request data containing:
	 *                    - course_id (int) The ID of the course to unenroll from
	 *                    - user_id (int) The ID of the user to unenroll
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure
	 */
	public function masteriyo_unenrolled_user_from_course( $data ) {
		$course_id  = absint( $data['course_id'] );
		$student_id = absint( $data['user_id'] );

		if ( empty( $course_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid course id.', 'learning-management-system' )
			);
		}

		if ( empty( $student_id ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_student_id',
				__( 'Invalid student id.', 'learning-management-system' )
			);
		}

		$user_course = masteriyo_get_user_course_by_user_and_course( $student_id, $course_id );

		if ( ! $user_course ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_user_course',
				__( 'User course not found.', 'learning-management-system' )
			);
		}

		if ( $user_course->delete() ) {
			delete_course_progress_and_related_data( $student_id, $course_id );
		}

		return rest_ensure_response(
			array(
				'data'    => array(
					'course_id' => $course_id,
					'user_id'   => $student_id,
				),
				'message' => __( 'User unenrolled successfully', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Resets the course progress for a specific user.
	 *
	 * This function handles the webhook action to reset a user's progress in a course.
	 * It validates the course and user existence, checks enrollment status, and removes
	 * all progress data including completed lessons and course progress records.
	 *
	 * @since 2.19.0
	 *
	 * @param array $data Request data containing:
	 *                    - course_id (int) The ID of the course to reset progress for
	 *                    - user_id (int) The ID of the user whose progress should be reset
	 * @return WP_REST_Response|WP_Error Response object on success with reset confirmation,
	 *                                   or WP_Error object on failure
	 */
	public function masteriyo_course_progress_reset( $data ) {
		$course_id  = absint( $data['course_id'] );
		$student_id = absint( $data['user_id'] );

		if ( empty( $course_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid course id.', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		if ( empty( $student_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid student id.', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		$user_course = masteriyo_get_user_course_by_user_and_course( $student_id, $course_id );
		if ( ! $user_course ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No user with that id is enrolled in this course.', 'learning-management-system' ),
				),
				rest_authorization_required_code()
			);
		}

		$progress = masteriyo_get_course_progress_by_user_and_course( $student_id, $course_id );
		if ( is_wp_error( $progress ) || ! $progress ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No course progress found for the student.', 'learning-management-system' ),
				),
				600
			);
		}

		delete_course_progress_and_related_data( $student_id, $course_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Progress reset successfully.', 'learning-management-system' ),
			),
			200
		);
	}

	/**
	 * Marks a lesson as complete for a specific user in a course.
	 *
	 * This function handles the webhook action to mark a lesson as completed for a user.
	 * It validates the lesson, user, and course existence, checks enrollment status,
	 * creates or retrieves course progress records, and updates the lesson completion status.
	 *
	 * @since 2.19.0
	 *
	 * @param array $data Request data containing:
	 *                    - lesson_id (int) The ID of the lesson to mark as completed
	 *                    - user_id (int) The ID of the user completing the lesson
	 *                    - course_id (int) The ID of the course containing the lesson
	 * @return WP_REST_Response|WP_Error Response object on success with lesson progress data,
	 *                                   or WP_Error object on failure
	 */
	public function masteriyo_complete_lesson( $data ) {
		$params = $this->validate_complete_lesson_params( $data );
		if ( is_wp_error( $params ) ) {
			return $params;
		}
		$lesson_id = $params['lesson_id'];
		$user_id   = $params['user_id'];
		$course_id = $params['course_id'];

		$objects = $this->get_valid_objects( $lesson_id, $user_id, $course_id );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}
		list( $lesson, $user, $course ) = $objects;

		$user_course = masteriyo_get_user_course_by_user_and_course( $user_id, $course_id );
		if ( ! $user_course ) {
			return $this->error_response( __( 'User is not enrolled in the course.', 'learning-management-system' ), rest_authorization_required_code() );
		}

		$course_progress = $this->get_or_create_course_progress( $user_id, $course_id );
		if ( is_wp_error( $course_progress ) ) {
			return $course_progress;
		}
		$course_progress_id = $course_progress->get_id();

		$course_progress_item = $this->get_course_progress_item( $lesson, $user_id, $course_id, $course_progress_id );
		if ( is_wp_error( $course_progress_item ) ) {
			return $course_progress_item;
		}

		$course_progress_item = $this->mark_lesson_completed( $course_progress_item );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $course_progress_item->get_data(),
				'message' => 'Lesson completed successfully.',
			),
			200
		);
	}

	/**
	 * Validates and extracts lesson_id, user_id, and course_id from input data.
	 *
	 * @since 2.19.0
	 *
	 * @param array $data Input data.
	 * @return array|WP_Error
	 */
	protected function validate_complete_lesson_params( $data ) {
		$lesson_id = $data['lesson_id'] ?? null;
		$user_id   = $data['user_id'] ?? null;
		$course_id = $data['course_id'] ?? null;

		if ( ! $lesson_id || ! $user_id || ! $course_id ) {
			return $this->error_response( __( 'Please provide lesson_id, user_id, and course_id.', 'learning-management-system' ), 403 );
		}

		return array(
			'lesson_id' => $lesson_id,
			'user_id'   => $user_id,
			'course_id' => $course_id,
		);
	}

	/**
	 * Retrieves the lesson, user, and course objects and validates them.
	 *
	 * @since 2.19.0
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 * @param int $course_id
	 * @return array|WP_Error Array containing lesson, user, course.
	 */
	protected function get_valid_objects( $lesson_id, $user_id, $course_id ) {
		$lesson = masteriyo_get_lesson( $lesson_id );
		$user   = masteriyo_get_user( $user_id );
		$course = masteriyo_get_course( $course_id );

		if ( is_wp_error( $lesson ) || ! $lesson->get_id() ) {
			return $this->error_response( __( 'The specified lesson could not be found. Please provide a valid lesson id.', 'learning-management-system' ), 404 );
		}
		if ( ! $user->get_id() ) {
			return $this->error_response(
				sprintf(
					/* translators: %s: the product's name */
					__( 'The specified user could not be found in the database. Please register as a %s student before proceeding.', 'learning-management-system' ),
					masteriyo_get_plugin_name()
				),
				404
			);
		}
		if ( ! $course->get_id() ) {
			return $this->error_response( __( 'The specified course could not be found.', 'learning-management-system' ), 404 );
		}

		return array( $lesson, $user, $course );
	}

	/**
	 * Retrieves or creates the course progress record.
	 *
	 * @since 2.19.0
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @return object|WP_Error Course progress object.
	 */
	protected function get_or_create_course_progress( $user_id, $course_id ) {
		$course_progress = masteriyo_get_course_progress_by_user_and_course( $user_id, $course_id );

		if ( is_wp_error( $course_progress ) || ! $course_progress ) {
			/** @var \Masteriyo\Models\CourseProgress $course_progress */
			$course_progress = masteriyo( 'course-progress' );
			$course_progress->set_course_id( $course_id );
			$course_progress->set_user_id( $user_id );
			$course_progress->set_status( CourseProgressStatus::STARTED );
			$course_progress->save();
		}
		if ( is_wp_error( $course_progress ) || ! $course_progress ) {
			return $this->error_response( __( 'No course progress found for the student.', 'learning-management-system' ), 600 );
		}

		return $course_progress;
	}

	/**
	 * Retrieves an existing course progress item for a lesson or creates a new one.
	 *
	 * @since 2.19.0
	 *
	 * @param object $lesson Lesson object.
	 * @param int    $user_id
	 * @param int    $course_id
	 * @param int    $course_progress_id
	 * @return object|WP_Error Course progress item.
	 */
	protected function get_course_progress_item( $lesson, $user_id, $course_id, $course_progress_id ) {
		$query_args          = array(
			'item_id'     => $lesson->get_id(),
			'user_id'     => $user_id,
			'progress_id' => $course_progress_id,
			'per_page'    => 1,
			'course_id'   => $course_id,
			'item_type'   => $lesson->get_post_type(),
		);
		$progress_item_query = new CourseProgressItemQuery( $query_args );
		$progress_items      = $progress_item_query->get_course_progress_items();

		/** @var Masteriyo\Repository\CourseProgressItemRepository $course_progress_item_repo */
		$course_progress_item_repo = masteriyo( 'course-progress-item.store' );

		if ( ! empty( $progress_items ) && $progress_items[0]->get_id() > 0 ) {
			$course_progress_item = $progress_items[0];
			$course_progress_item_repo->read( $course_progress_item );
			if ( $course_progress_item->get_completed() ) {
					return $this->error_response( __( 'Lesson already completed.', 'learning-management-system' ), rest_authorization_required_code() );
			}
		} else {
			/** @var Masteriyo\RestApi\Controllers\Version1\CourseProgressItemsController $course_progress_item */
			$course_progress_item = masteriyo( 'course-progress-item' );
			$item_type            = str_replace( 'mto-', '', $lesson->get_post_type() );
			$course_progress_item->set_progress_id( $course_progress_id );
			$course_progress_item->set_course_id( $course_id );
			$course_progress_item->set_item_type( $item_type );
			$course_progress_item->set_item_id( $lesson->get_id() );
			$course_progress_item->set_user_id( $user_id );
			$course_progress_item->set_started_at( current_time( 'mysql', true ) );
		}

		if ( $course_progress_item->get_id() !== 0 ) {
			$course_progress_item_repo->read( $course_progress_item );
		}

		return $course_progress_item;
	}

	/**
	 * Marks the course progress item as completed.
	 *
	 * @since 2.19.0
	 *
	 * @param object $course_progress_item
	 * @return object Updated course progress item.
	 */
	protected function mark_lesson_completed( $course_progress_item ) {
		$current_time = current_time( 'mysql', true );
		$course_progress_item->set_completed( true );
		$course_progress_item->set_modified_at( $current_time );
		$course_progress_item->set_completed_at( $current_time );
		$course_progress_item->save();

		return $course_progress_item;
	}

	/**
	 * Returns a standardized error response.
	 *
	 * @since 2.19.0
	 *
	 * @param string $message Error message.
	 * @param int    $code    HTTP status code.
	 * @return WP_REST_Response
	 */
	protected function error_response( $message, $code ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $message,
			),
			$code
		);
	}

	/**
	 * Handle user enrollment
	 *
	 * @since 2.19.0
	 *
	 * @param array $data
	 *
	 * @return array|\WP_Error
	 */
	public function masteriyo_user_enrollment( $data ) {
		if ( ! isset( $data['user_id'] ) || ! isset( $data['course_id'] ) ) {
			return $this->error_response(
				__( 'Please provide user_id and course_id.', 'learning-management-system' ),
				403
			);
		}

		$course = masteriyo_get_course( $data['course_id'] );
		$user   = masteriyo_get_user( $data['user_id'] );

		if ( ! $course ) {
			return $this->error_response(
				__( 'The specified course could not be found.', 'learning-management-system' ),
				404
			);
		}

		if ( $course->get_enrollment_limit() > 0 && $course->get_enrollment_limit() <= masteriyo_count_enrolled_users( $course->get_id() ) ) {
			return $this->error_response(
				__( 'The enrollment limit has been reached.', 'learning-management-system' ),
				403
			);
		}

		if ( is_wp_error( $user ) || ! $user->get_id() ) {
			$error_message = is_wp_error( $user ) ? $user->get_error_message() : __( 'The specified user could not be found.', 'learning-management-system' );
			return $this->error_response(
				$error_message,
				404
			);
		}

		$query        = new UserCourseQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $data['user_id'],
			)
		);
		$user_courses = $query->get_user_courses();

		if ( empty( $user_courses ) ) {
			$user_course = masteriyo( 'user-course' );
			/** @var \Masteriyo\Models\UserCourse $user_course */
			$user_course->set_course_id( $course->get_id() );
			$user_course->set_user_id( $data['user_id'] );
			$user_course->set_price( $course->get_price() );
			$user_course->set_status( UserCourseStatus::ACTIVE );
			$user_course->set_date_start( current_time( 'mysql', true ) );
			$user_course->save();

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $user_course->get_data(),
					/* translators: %s: user->get_username() type */
					'message' => sprintf( __( 'User %s enrolled successfully.', 'learning-management-system' ), $user->get_username() ),
				),
				200
			);
		}

		return $this->error_response(
		/* translators: %s: user->get_username() */
			sprintf( __( 'User %s is already enrolled in this course.', 'learning-management-system' ), $user->get_username() ),
			rest_authorization_required_code()
		);
	}

	/**
	 * Handle user creation based on role and emails
	 *
	 * @since  2.19.0
	 *
	 * @param array $data User data
	 * @param string $role User role (default: Roles::STUDENT)
	 * @return WP_REST_Response|WP_Error
	 */
	public function masteriyo_create_user( $data, $role = Roles::STUDENT ) {
		if ( empty( $data['email'] ) ) {
			return $this->error_response(
				__( 'Email is required to create a user.', 'learning-management-system' ),
				rest_authorization_required_code()
			);
		}

		if ( ! is_email( $data['email'] ) ) {
			return $this->error_response(
				__( 'Invalid email format. Please enter a valid email address.', 'learning-management-system' ),
				rest_authorization_required_code()
			);
		}

		if ( isset( $data['username'] ) && $data['username'] ) {
			return $this->error_response(
				__( 'username is not a valid key. Please use user_name as key instead.', 'learning-management-system' ),
				rest_authorization_required_code()
			);
		}

		$user_info = get_user_by( 'email', $data['email'] );
		if ( $user_info ) {
			/** @var Masteriyo\Database\User $user */
			$user = masteriyo( 'user' );
			/** @var Masteriyo\Repository\UserRepository $store */
			$store = masteriyo( 'user.store' );
			$user->set_id( $user_info->ID );
			$store->read( $user );
			if ( ! $user->has_role( $role ) ) {
				$user->add_role( $role );
				$user->save();
			}
			$user_data = $user->get_data();
			unset( $user_data['password'] );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'User is already registered.', 'learning-management-system' ),
					'data'    => $user_data,
				),
				200
			);
		}

		add_filter( 'masteriyo_registration_is_generate_password', '__return_true' );

		try {
			$user = masteriyo_create_new_user(
				$data['email'],
				masteriyo_create_new_user_username( $data['user_name'] ?? $data['email'] ),
				'',
				$role,
				array_merge(
					array(
						'first_name'   => $data['firstname'] ?? '',
						'last_name'    => $data['lastname'] ?? '',
						'display_name' => ( ! empty( $data['firstname'] ) && ! empty( $data['lastname'] ) )
								? $data['firstname'] . ' ' . $data['lastname']
								: ( $data['user_name'] ?? '' ),
					),
					$data
				)
			);

			if ( is_null( $user ) || is_wp_error( $user ) ) {
				masteriyo_get_logger()->error(
					$user->get_error_message(),
					array( 'source' => 'webhook-action' )
				);
				throw new \Exception( $user->get_error_message() );
			}

			$user->set_status( UserStatus::ACTIVE );
			$user->save();

			$wp_user = get_user_by( 'email', $user->get_email() );

			if ( ! $wp_user ) {
				throw new \Exception( __( 'Invalid username or email', 'learning-management-system' ) );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'User created successfully.', 'learning-management-system' ),
					'data'    => array(
						'username'   => $user->get_username(),
						'email'      => $user->get_email(),
						'first_name' => $user->get_first_name(),
						'user_id'    => $user->get_id(),
					),
				),
				200
			);

		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error(
				$e->getMessage(),
				array( 'source' => 'webhook-action' )
			);

			return $this->error_response(
				$e->getMessage(),
				rest_authorization_required_code()
			);
		}
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.6.9
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to webhooks assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), WebhookStatus::all() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.6.9
	 *
	 * @param  \Masteriyo\Models\Webhook|\WP_Post $object Model or WP_Post object.
	 *
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = $object instanceof \WP_Post ? $object->ID : $object->get_id();
			}

			/** @var Masteriyo\Database\Webhook $webhook */
			$webhook = masteriyo( 'webhook' );
			$webhook->set_id( $id );
			/** @var Masteriyo\Repository\WebhookRepository $webhook_repo */
			$webhook_repo = masteriyo( 'webhook.store' );
			$webhook_repo->read( $webhook );
		} catch ( \Exception $e ) {
			return false;
		}

		return $webhook;
	}

	/**
	 * Get webhook description data
	 *
	 * @since 1.7.3
	 *
	 * @param  \Masteriyo\Models\Webhook|\WP_Post $object Model or WP_Post object.
	 * @param string $context Request context.
	 *
	 * @return object
	 */
	protected function description_data( $webhook, $context ) {
		if ( 'view' === $context ) {
			return masteriyo_format_content_for_view( wp_kses_post( $webhook->get_description() ) );
		}
		return $webhook->get_description( $context );
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 1.6.9
	 *
	 * @param \Masteriyo\Models\Webhook $object Model object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context             = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                = $this->get_webhook_data( $object, $context );
		$data['description'] = $this->description_data( $object, $context );
		$data                = $this->add_additional_fields_to_object( $data, $request );
		$data                = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );
		/**
		 * Filter the data for a response.
		 *
		 * @since 1.6.9
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param \Masteriyo\Models\Webhook $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.6.9
	 *
	 * @param array $objects Webhooks data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Webhooks query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'          => $query_results['total'],
				'pages'          => $query_results['pages'],
				'current_page'   => $query_args['paged'],
				'per_page'       => $query_args['posts_per_page'],
				'webhooks_count' => $this->get_webhooks_count(),
			),
		);
	}

	/**
	 * Get webhooks count by status.
	 *
	 * @since 1.6.9
	 *
	 * @return Array
	 */
	protected function get_webhooks_count() {
		$post_count = parent::get_posts_count();

		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), WebhookStatus::all() ) );
	}

	/**
	 * Get webhook data.
	 *
	 * @since 1.6.9
	 *
	 * @param \Masteriyo\Models\Webhook $webhook Webhook instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_webhook_data( $webhook, $context = 'view' ) {
		/**
		 * Filter webhook rest response data.
		 *
		 * @since 1.6.9
		 *
		 * @param array $data Webhook data.
		 * @param \Masteriyo\Models\Webhook $webhook Webhook object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\WebhooksController $controller REST webhooks controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", WebhookResource::to_array( $webhook ), $webhook, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.6.9
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		if ( ! masteriyo_is_current_user_admin() ) {
			$args['author'] = get_current_user_id();
		}

		return $args;
	}

	/**
	 * Get the webhooks'schema, conforming to JSON Schema.
	 *
	 * @since 1.6.9
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
				'name'         => array(
					'description' => __( 'Webhook name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status'       => array(
					'description' => __( 'Webhook status', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => WebhookStatus::INACTIVE,
					'enum'        => WebhookStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'events'       => array(
					'description' => __( 'Webhook events', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'delivery_url' => array(
					'description'       => __( 'Webhook delivery URL', 'learning-management-system' ),
					'type'              => 'string',
					'validate_callback' => 'wp_http_validate_url',
					'context'           => array( 'view', 'edit' ),
				),
				'description'  => array(
					'description' => __( 'Webhook description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'secret'       => array(
					'description' => __( 'Webhook secret', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'author_id'    => array(
					'description' => __( 'Webhook author ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author'       => array(
					'description' => __( 'Webhook author', 'learning-management-system' ),
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
				'created_at'   => array(
					'description' => __( 'The date the course was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'modified_at'  => array(
					'description' => __( 'The date the course was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
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
	 * Prepare a single webhook for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|\Masteriyo\Models\Webhook
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
					/** @var Masteriyo\Database\Webhook $webhook */
		$webhook = masteriyo( 'webhook' );

		if ( 0 !== $id ) {
			$webhook->set_id( $id );
			/** @var Masteriyo\Repository\WebhookRepository $webhook_repo */
			$webhook_repo = masteriyo( \Masteriyo\Repository\WebhookRepository::class );
			$webhook_repo->read( $webhook );
		}

		// Webhook title.
		if ( isset( $request['name'] ) ) {
			$webhook->set_name( sanitize_text_field( $request['name'] ) );
		}

		// Webhook description.
		if ( isset( $request['description'] ) ) {
			$webhook->set_description( wp_slash( wp_kses_post( $request['description'] ) ) );
		}

		// Webhook status.
		if ( isset( $request['status'] ) ) {
			$webhook->set_status( $request['status'] );
		}

		// Webhook events.
		if ( isset( $request['events'] ) ) {
			$webhook->set_events( $request['events'] );
		}

		// Webhook delivery_url.
		if ( isset( $request['delivery_url'] ) ) {
			$webhook->set_delivery_url( $request['delivery_url'] );
		}

		// Secret.
		if ( isset( $request['secret'] ) ) {
			$webhook->set_secret( $request['secret'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$webhook->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.6.9
		 *
		 * @param \Masteriyo\Models\Webhook $webhook  Webhook object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $webhook, $request, $creating );
	}

	/**
	 * Restore webhook.
	 *
	 * @since 1.6.9
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
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

	/**
	 * Get available webhook events.
	 *
	 * @since 1.6.9
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_listeners( $request ) {
		$listeners = masteriyo_get_webhook_listeners();
		$user      = masteriyo_get_current_user();
		$results   = array();

		foreach ( $listeners as $name => $listener ) {
			if ( $user && $listener->is_allowed( $user ) ) {
				$results[] = array(
					'name'  => $listener->get_name(),
					'label' => $listener->get_label(),
				);
			}
		}

		return rest_ensure_response( $results );
	}
}
