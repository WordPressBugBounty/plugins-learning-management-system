<?php
/**
 * UsersController class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use chillerlan\QRCode\QRCode;
use WP_HTTP_Response;
use Masteriyo\Enums\InstructorApplyStatus;
use Masteriyo\Enums\UserStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\Query\WPUserQuery;
use Masteriyo\Roles;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Session_Tokens;

/**
 * UsersController class.
 */
class UsersController extends CrudController {
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
	protected $rest_base = 'users';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $object_type = 'user';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = false;

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
	public function __construct( ?Permission $permission = null ) {
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
			'/' . $this->rest_base . '/me',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_logged_in_user' ),
					'permission_callback' => function ( $request ) {
						return is_user_logged_in();
					},
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
					'callback'            => array( $this, 'update_logged_in_user' ),
					'permission_callback' => function ( $request ) {
						return is_user_logged_in();
					},
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
			)
		);

		/**
		 * @since 1.4.5 Added reassign parameter.
		 */
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
						'force'    => array(
							'default'     => true,
							'type'        => 'boolean',
							'description' => __( 'Required to be true, as the resource does not support trashing.', 'learning-management-system' ),
						),
						'reassign' => array(
							'default'     => null,
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Reassign posts and links to new User ID.', 'learning-management-system' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		/**
		 * @since 1.4.6
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/logout',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'logout' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		/**
		 * @since 1.4.7
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/account/profile-image',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_profile_image' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_profile_image' ),
					'permission_callback' => 'is_user_logged_in',
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
							'description' => __( 'User IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'reassign' => array(
							'default'     => null,
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Reassign posts and links to new User ID.', 'learning-management-system' ),
						),
					),
				),
			)
		);

		/**
		 * @since 1.6.13
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/account/apply-for-instructor',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_instructor_update_status' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
			)
		);

		/**
		 * Registers REST API routes for managing QR-based user login.
		 *
		 * @since 1.9.0
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_login_qr_code' ),
					'permission_callback' => 'is_user_logged_in',
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
					'callback'            => array( $this, 'generate_login_qr_code' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_login_qr_code' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		/**
		 * Registers REST API routes for managing session data for user login.
		 *
		 * @since 1.9.3
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/session-update',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_session_data' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'context'  => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
						'umeta_id' => array(
							'description' => __( 'meta id for user meta information about device information.', 'learning-management-system' ),
							'type'        => 'string',
						// 'validate_callback' => 'rest_validate_request_arg',
						),
						'token'    => array(
							'description' => __( 'Token for users session that is used to delete the user information.', 'learning-management-system' ),
							'type'        => 'string',
						// 'validate_callback' => 'rest_validate_request_arg',
						),
						'meta_key' => array(
							'description' => __( 'Token for users session that is used to delete the user information.', 'learning-management-system' ),
							'type'        => 'string',
						// 'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * Deleted the user session data based on the token provided.
	 *
	 * @since 1.9.3
	 */
	public function update_session_data( $request ) {
		$sessions = WP_Session_Tokens::get_instance( masteriyo_get_current_user_id() );
		$sessions->destroy( $request['token'] );
		delete_user_meta( get_current_user_id(), $request['meta_key'] );
		return new \WP_REST_Response(
			array(
				'success' => true,
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

		$params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'learning-management-system' ),
			'default'     => 'id',
			'enum'        => array(
				'id',
				'display_name',
				'name',
				'include',
				'login',
				'nicename',
				'email',
				'url',
				'registered',
			),
			'type'        => 'string',
		);

		$params['roles'] = array(
			'description'       => __( 'Limit result set to users matching at least one specific role provided. Accepts CSV list or single role.', 'learning-management-system' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => masteriyo_get_wp_roles(),
			),
			'validate_callback' => 'rest_validate_request_arg',

		);

		$params['instructor_applied'] = array(
			'description'       => __( 'Whether the student has applied or not for the instructor.', 'learning-management-system' ),
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the query params for collections of users.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params The query params for collections of users.
		 */
		return apply_filters( 'masteriyo_user_collection_params', $params );
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|WP_user|Model $object Object ID or WP_user or Model.
	 *
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_User' ) ? $object->ID : $object->get_id();
			}
			$user = masteriyo( 'user' );
			$user->set_id( $id );
			$user_repo = masteriyo( 'user.store' );
			$user_repo->read( $user );
		} catch ( \Exception $e ) {
			return false;
		}

		return $user;
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
			$user_ids              = masteriyo_get_instructor_user_ids();
			$user_ids              = empty( $user_ids ) ? array( 0 ) : $user_ids;
			$query_args['include'] = $user_ids;
		}

		$users  = new WPUserQuery( $query_args );
		$result = $users->get_results();

		$total_users = $users->total_users;
		if ( $total_users < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_users = new WPUserQuery( $query_args );
			$count_users->get_results();
			$total_users = $count_users->total_users;
		}

		return array(
			'objects' => array_filter( array_map( array( $this, 'get_object' ), $result ) ),
			'total'   => (int) $total_users,
			'pages'   => (int) ceil( $total_users / (int) $query_args['number'] ),
		);
	}

	/**
	 * Update user profile image.
	 *
	 * @since 1.4.7
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_profile_image( $request ) {
		$image_data = $request->get_file_params()['image_data'];
		$image_size = $image_data['size'];
		$image_type = $image_data['type'];

		if ( strpos( $image_type, 'image' ) !== false && $image_size ) {
			if ( ! function_exists( 'wp_handle_sideload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$upload_overrides = array( 'test_form' => false );
			$uploaded_file    = wp_handle_sideload( $image_data, $upload_overrides );

			if ( isset( $uploaded_file['error'] ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_upload',
					$uploaded_file['error'],
					array( 'status' => 400 )
				);
			}

			$file_name = $uploaded_file['file'];
			$local_url = $uploaded_file['url'];
			$mime_type = mime_content_type( $file_name );
			$is_image  = strpos( $mime_type, 'image/' ) === 0;

			if ( $is_image ) {
				// Strip EXIF metadata using Imagick
				if ( extension_loaded( 'imagick' ) ) {
					try {
						$imagick = new \Imagick( $file_name );
						$imagick->stripImage();
						$imagick->writeImage( $file_name );
						$imagick->clear();
					} catch ( Exception $e ) {
						return new \WP_Error(
							'masteriyo_rest_cannot_strip_exif',
							'EXIF stripping failed with Imagick: ' . $e->getMessage(),
							array( 'status' => 400 )
						);
					}
				} else {
					if ( 'image/jpeg' === $mime_type && function_exists( 'imagecreatefromjpeg' ) ) {
						$image = imagecreatefromjpeg( $file_name );
						if ( false !== $image ) {
							$width     = imagesx( $image );
							$height    = imagesy( $image );
							$new_image = imagecreatetruecolor( $width, $height );

							imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $width, $height, $width, $height );
							imagejpeg( $new_image, $file_name, 90 );

							imagedestroy( $image );
							imagedestroy( $new_image );
						}
					}

					// Strip EXIF for PNG
					if ( 'image/png' === $mime_type && function_exists( 'imagecreatefrompng' ) ) {
						$image = imagecreatefrompng( $file_name );
						if ( false !== $image ) {
							$width     = imagesx( $image );
							$height    = imagesy( $image );
							$new_image = imagecreatetruecolor( $width, $height );

							// Preserve alpha channel
							imagesavealpha( $new_image, true );
							$transparent = imagecolorallocatealpha( $new_image, 0, 0, 0, 127 );
							imagefill( $new_image, 0, 0, $transparent );

							imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $width, $height, $width, $height );
							imagepng( $new_image, $file_name, 9 );

							imagedestroy( $image );
							imagedestroy( $new_image );
						}
					}
				}
			}

			// Continue with media attachment creation
			$media_id = wp_insert_attachment(
				array(
					'guid'           => $file_name,
					'post_mime_type' => mime_content_type( $file_name ),
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $local_url ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$file_name,
				0,
				true
			);

			if ( is_wp_error( $media_id ) ) {
				return $media_id;
			}

			require_once ABSPATH . 'wp-admin/includes/image.php';
			wp_update_attachment_metadata( $media_id, wp_generate_attachment_metadata( $media_id, $file_name ) );

			$user = masteriyo_get_current_user();
			wp_delete_attachment( $user->get_profile_image_id(), true );
			$user->set_profile_image_id( $media_id );
			$user->save();

			return new \WP_REST_Response(
				array(
					'id'  => $user->get_profile_image_id(),
					'url' => $user->profile_image_url(),
				)
			);
		}
	}


	/**
	 * Delete User's profile image.
	 *
	 * @since 1.4.7
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function delete_profile_image() {
		$user     = masteriyo_get_current_user();
		$image_id = $user->get_profile_image_id();

		wp_delete_attachment( $image_id, true );

		$user->set_profile_image_id( 0 );
		$user->save();

		return new \WP_REST_Response(
			array(
				'id'  => $user->get_profile_image_id(),
				'url' => $user->profile_image_url(),
			)
		);
	}

	/**
	 * Updates the instructor apply status for the student.
	 *
	 * @since 1.6.13
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response|\WP_Error The REST response on success, or WP_Error object on failure.
	 */
	public function update_instructor_update_status( $request ) {
		$current_user_id = get_current_user_id();
		$user_id         = absint( $request->get_param( 'user_id' ) );

		if ( $user_id !== $current_user_id ) {
			return new \WP_Error( 'forbidden', 'You can only apply for an instructor account for yourself.', array( 'status' => 403 ) );
		}

		// Validate the user ID parameter.
		if ( empty( $user_id ) ) {
			return new \WP_Error( 'user_id_is_required', 'User ID is required.', array( 'status' => 400 ) );
		}

		$user = masteriyo_get_user( $user_id );

		// Check if the user exists.
		if ( ! $user ) {
			return new \WP_Error( 'invalid_user', 'Invalid user ID.', array( 'status' => 404 ) );
		}

		// Apply for instructor and save the user.
		$user->set_instructor_apply_status( InstructorApplyStatus::APPLIED );
		$user->save();

		/**
		 * Action Hook: masteriyo_apply_for_instructor.
		 *
		 * This action is triggered when a user applies to become an instructor on the Masteriyo platform.
		 *
		 * @since 1.6.13
		 *
		 * @param \Masteriyo\Models\User $user The user object who is applying for instructor status.
		 */
		do_action( 'masteriyo_apply_for_instructor', $user );

		// Return a success response.
		$response = array( 'id' => $user_id );

		return new \WP_REST_Response( $response, 200 );
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
		$data     = $this->get_user_data( $object, $context );
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

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
	 * Process objects collection.
	 *
	 * @since 1.2.0
	 *
	 * @param array $objects Users data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Users query result data.
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
				'per_page'     => $query_args['number'],
				'inactive'     => masteriyo_get_total_user_count_by_roles_and_statuses( array( Roles::INSTRUCTOR, Roles::STUDENT ), UserStatus::INACTIVE ),
			),
		);
	}

	/**
	 * Logout User.
	 *
	 * @since 1.4.6
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function logout() {
		$url = masteriyo_get_page_permalink( 'account', home_url() );

		/**
		 * Filter redirect logout url.
		 * Redirect url will be home page url if account page url is empty.
		 *
		 * @since 1.4.6
		 *
		 * @param string $url Redirect url.
		 */
		$redirect = apply_filters( 'masteriyo_redirect_logout_url', $url );

		masteriyo_delete_session_info_current_user();
		wp_logout();

		return new WP_HTTP_Response(
			array(
				'redirect_url' => $redirect,
			)
		);
	}

	/**
	 * Get user data.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\User $user User instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_user_data( $user, $context = 'view' ) {
		$user_sessions_info = masteriyo_get_all_session_data();

		$data = array(
			'id'                      => $user->get_id(),
			'username'                => $user->get_username( $context ),
			'nicename'                => $user->get_nicename( $context ),
			'email'                   => $user->get_email( $context ),
			'url'                     => $user->get_url( $context ),
			'date_created'            => masteriyo_rest_prepare_date_response( $user->get_date_created( $context ) ),
			'status'                  => $user->get_status( $context ),
			'display_name'            => $user->get_display_name( $context ),
			'nickname'                => $user->get_nickname( $context ),
			'first_name'              => $user->get_first_name( $context ),
			'last_name'               => $user->get_last_name( $context ),
			'description'             => $user->get_description( $context ),
			'rich_editing'            => $user->get_rich_editing( $context ),
			'syntax_highlighting'     => $user->get_syntax_highlighting( $context ),
			'comment_shortcuts'       => $user->get_comment_shortcuts( $context ),
			'use_ssl'                 => $user->get_use_ssl( $context ),
			'show_admin_bar_front'    => $user->get_show_admin_bar_front( $context ),
			'locale'                  => $user->get_locale( $context ),
			'roles'                   => $user->get_roles( $context ),
			'formatted_roles'         => $this->get_formatted_roles( $user->get_roles( $context ) ),
			'profile_image'           => array(
				'id'  => $user->get_profile_image_id( $context ),
				'url' => $user->profile_image_url(),
			),
			'billing'                 => array(
				'first_name'   => $user->get_billing_first_name( $context ),
				'last_name'    => $user->get_billing_last_name( $context ),
				'company_name' => $user->get_billing_company_name( $context ),
				'company_id'   => $user->get_billing_company_id( $context ),
				'address_1'    => $user->get_billing_address_1( $context ),
				'address_2'    => $user->get_billing_address_2( $context ),
				'city'         => $user->get_billing_city( $context ),
				'postcode'     => $user->get_billing_postcode( $context ),
				'country'      => $user->get_billing_country( $context ),
				'state'        => $user->get_billing_state( $context ),
				'country_name' => masteriyo( 'countries' )->get_country_from_code( $user->get_billing_country( $context ) ),
				'state_name'   => masteriyo( 'countries' )->get_state_from_code( $user->get_billing_country( $context ), $user->get_billing_state( $context ) ),
				'email'        => $user->get_billing_email( $context ),
				'phone'        => $user->get_billing_phone( $context ),
			),
			'avatar_url'              => $user->get_avatar_url(),
			'instructor_apply_status' => $user->get_instructor_apply_status( $context ),
			'user_sessions_info'      => $user_sessions_info,
		);

		/**
		 * Filter users rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data User data.
		 * @param Masteriyo\Models\User $user User object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\UsersController $controller REST users controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $user, $context, $this );
	}

	/**
	 * Return roles with their labels.
	 *
	 * @since 1.7.3
	 *
	 * @param string[] $user_roles
	 * @return array
	 */
	public function get_formatted_roles( $user_roles ) {
		$roles  = get_editable_roles();
		$result = array();
		foreach ( $roles as $key => $role ) {
			if ( in_array( $key, $user_roles ) ) {
				$result[] = array(
					'value' => $key,
					'label' => $role['name'],
				);
			}
		}
		return $result;
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
		$args = array(
			'offset'         => $request['offset'],
			'order'          => $request['order'],
			'orderby'        => $request['orderby'],
			'paged'          => $request['page'],
			'search'         => '*' . esc_attr( $request['search'] ) . '*',
			'search_columns' => array( 'ID', 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
			'role'           => $request['role'],
			'number'         => $request['per_page'],
		);

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}
		if ( isset( $request['roles'] ) ) {
			$args['role__in'] = (array) $request['roles'];
		}

		if ( isset( $request['instructor_applied'] ) && true === masteriyo_string_to_bool( $request['instructor_applied'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_instructor_apply_status',
					'value'   => InstructorApplyStatus::APPLIED,
					'compare' => '=',
				),
			);
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "masteriyo_rest_{$this->object_type}_object_query", $args, $request );

		return $args;
	}

	/**
	 * Get the User's schema, conforming to JSON Schema.
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
				'id'                      => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'                => array(
					'description' => __( 'User login username.', 'learning-management-system' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'old_password'            => array(
					'description' => __( 'User login old password.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'password'                => array(
					'description' => __( 'User login password.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'nicename'                => array(
					'description' => __( 'User nickname', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email'                   => array(
					'description' => __( 'User email', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'url'                     => array(
					'description' => __( 'Site url of the user.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'            => array(
					'description' => __( 'User date created', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'activation_key'          => array(
					'description' => __( 'User activation key.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                  => array(
					'description' => __( 'User status', 'learning-management-system' ),
					'type'        => 'integer',
					'enum'        => UserStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'display_name'            => array(
					'description' => __( 'Display name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'nickname'                => array(
					'description' => __( 'User nickname', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'first_name'              => array(
					'description' => __( 'User first name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'last_name'               => array(
					'description' => __( 'User last name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description'             => array(
					'description' => __( 'User description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'rich_editing'            => array(
					'description' => __( 'Enable rich editing.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'syntax_highlighting'     => array(
					'description' => __( 'Enable syntax highlighting.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'comment_shortcuts'       => array(
					'description' => __( 'Enable comment shortcuts.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'spam'                    => array(
					'description' => __( 'Mark the user as spam. Multi site only.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'use_ssl'                 => array(
					'description' => __( 'Use SSL.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'show_admin_bar_front'    => array(
					'description' => __( 'Whether to show the admin bar on the frontend.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'locale'                  => array(
					'description' => __( 'User specific locale', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'roles'                   => array(
					'description' => __( 'User role', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'profile_image'           => array(
					'description' => __( 'User profile image', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'instructor_apply_status' => array(
					'description' => __( 'Instructor Apply Status', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => InstructorApplyStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'billing'                 => array(
					'description' => __( 'User billing details.', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'         => 'object',
						'first_name'   => array(
							'description' => __( 'User billing first name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'    => array(
							'description' => __( 'User billing last name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company_name' => array(
							'description' => __( 'User billing company name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company_id'   => array(
							'description' => __( 'User billing company id.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'    => array(
							'description' => __( 'User billing address 1.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'    => array(
							'description' => __( 'User billing address 1.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'    => array(
							'description' => __( 'User billing address 2.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'         => array(
							'description' => __( 'User billing city.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'     => array(
							'description' => __( 'User billing post code.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'      => array(
							'description' => __( 'User billing country code.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'        => array(
							'description' => __( 'User billing state code.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country_name' => array(
							'description' => __( 'Formatted User billing country.', 'learning-management-system' ),
							'type'        => 'string',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'state_name'   => array(
							'description' => __( 'Formatted User billing state.', 'learning-management-system' ),
							'type'        => 'string',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'email'        => array(
							'description' => __( 'User billing email address.', 'learning-management-system' ),
							'type'        => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'        => array(
							'description' => __( 'User billing phone number.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'created_via'             => array(
					'description' => __( 'How the user was created.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'               => array(
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
	 * Prepare a single user object for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		/** @var Masteriyo\Database\Model $user */
		$user = masteriyo( 'user' );

		if ( 0 !== $id ) {
			$user->set_id( $id );
			/** @var Masteriyo\Repository\UserRepository $user_repo */
			$user_repo = masteriyo( 'user.store' );
			$user_repo->read( $user );
		}

		// User's username.
		if ( isset( $request['username'] ) ) {
			$user->set_username( $request['username'] );
		}

		// User's password.
		if ( isset( $request['password'] ) ) {
			$user->set_password( $request['password'] );
		}

		// User's nicename.
		if ( isset( $request['nicename'] ) ) {
			$user->set_nicename( $request['nicename'] );
		}

		// User's email.
		if ( isset( $request['email'] ) ) {
			$user->set_email( $request['email'] );
		}

		// User's url.
		if ( isset( $request['url'] ) ) {
			$user->set_url( $request['url'] );
		}

		// User's activation_key.
		if ( isset( $request['activation_key'] ) ) {
			$user->set_activation_key( $request['activation_key'] );
		}

		// User's status.
		if ( isset( $request['status'] ) ) {
			$user->set_status( $request['status'] );
		}

		// User's display_name.
		if ( isset( $request['display_name'] ) ) {
			$user->set_display_name( $request['display_name'] );
		}

		// User's nickname.
		if ( isset( $request['nickname'] ) ) {
			$user->set_nickname( $request['nickname'] );
		}

		// User's first_name.
		if ( isset( $request['first_name'] ) ) {
			$user->set_first_name( $request['first_name'] );
		}

		// User's last_name.
		if ( isset( $request['last_name'] ) ) {
			$user->set_last_name( $request['last_name'] );
		}

		// User's description.
		if ( isset( $request['description'] ) ) {
			$user->set_description( $request['description'] );
		}

		// User's rich_editing.
		if ( isset( $request['rich_editing'] ) ) {
			$user->set_rich_editing( $request['rich_editing'] );
		}

		// User's syntax_highlighting.
		if ( isset( $request['syntax_highlighting'] ) ) {
			$user->set_syntax_highlighting( $request['syntax_highlighting'] );
		}

		// User's comment_shortcuts.
		if ( isset( $request['comment_shortcuts'] ) ) {
			$user->set_comment_shortcuts( $request['comment_shortcuts'] );
		}

		// User's use_ssl.
		if ( isset( $request['use_ssl'] ) ) {
			$user->set_use_ssl( $request['use_ssl'] );
		}

		// User's show_admin_bar_front.
		if ( isset( $request['show_admin_bar_front'] ) ) {
			$user->set_show_admin_bar_front( $request['show_admin_bar_front'] );
		}

		// User's locale.
		if ( isset( $request['locale'] ) ) {
			$user->set_locale( $request['locale'] );
		}

		// User's role.
		if ( current_user_can( 'manage_options' ) && isset( $request['roles'] ) ) {
			$user->set_roles( $request['roles'] );
		}

		// User's profile_image.
		if ( isset( $request['profile_image'] ) ) {
			$user->set_profile_image( $request['profile_image'] );
		}

		// User's instructor_apply_status.
		if ( isset( $request['instructor_apply_status'] ) ) {
			$user->set_instructor_apply_status( $request['instructor_apply_status'] );
		}

		// User billing details.
		if ( isset( $request['billing']['first_name'] ) ) {
			$user->set_billing_first_name( $request['billing']['first_name'] );
		}

		if ( isset( $request['billing']['last_name'] ) ) {
			$user->set_billing_last_name( $request['billing']['last_name'] );
		}

		if ( isset( $request['billing']['company_name'] ) ) {
			$user->set_billing_company_name( $request['billing']['company_name'] );
		}

		if ( isset( $request['billing']['company_id'] ) ) {
			$user->set_billing_company_id( $request['billing']['company_id'] );
		}

		if ( isset( $request['billing']['address_1'] ) ) {
			$user->set_billing_address_1( $request['billing']['address_1'] );
		}

		if ( isset( $request['billing']['address_2'] ) ) {
			$user->set_billing_address_2( $request['billing']['address_2'] );
		}

		if ( isset( $request['billing']['city'] ) ) {
			$user->set_billing_city( $request['billing']['city'] );
		}

		if ( isset( $request['billing']['postcode'] ) ) {
			$user->set_billing_postcode( $request['billing']['postcode'] );
		}

		if ( isset( $request['billing']['country'] ) ) {
			$user->set_billing_country( $request['billing']['country'] );
		}

		if ( isset( $request['billing']['state'] ) ) {
			$user->set_billing_state( $request['billing']['state'] );
		}

		if ( isset( $request['billing']['email'] ) ) {
			$user->set_billing_email( $request['billing']['email'] );
		}

		if ( isset( $request['billing']['phone'] ) ) {
			$user->set_billing_phone( $request['billing']['phone'] );
		}

		if ( isset( $request['created_via'] ) && 'admin' === $request['created_via'] ) {
			$user->set_auto_create_user( true );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$user->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
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
		 * @param Masteriyo\Database\Model $user User object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $user, $request, $creating );
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

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// Set the current user id for the user/me endpoint.
		$user = get_user_by( 'id', (int) $request['id'] );

		if ( $user && ! $this->permission->rest_check_users_manipulation_permissions( 'read' ) ) {
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
	 * Check if a given request has access to read items.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_users_manipulation_permissions( 'read' ) ) {
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
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_users_manipulation_permissions( 'create' ) ) {
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
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$user_id = (int) $request['id'];

		if ( get_current_user_id() === $user_id ) {
			return new \WP_Error(
				'masteriyo_cannot_delete_yourself',
				__( 'Sorry, you cannot delete yourself.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$user = get_user_by( 'id', $user_id );

		if ( $user && ! $this->permission->rest_check_users_manipulation_permissions( 'delete' ) ) {
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

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_users_manipulation_permissions( 'delete' ) ) {
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
	 * Delete multiple items.
	 *
	 * @since 1.6.5
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_items( $request ) {
		$deleted_objects = array();

		$request->set_param( 'context', 'edit' );

		$reassign_id = 1 === count( $request['ids'] ) ? ( $request['reassign'] ?? null ) : null;

		$ids = array_filter(
			$request['ids'],
			function ( $id ) {
				return get_current_user_id() !== absint( $id );
			}
		);

		$users = get_users(
			array(
				'include' => $ids,
				'number'  => -1,
			)
		);

		$objects = array_map( array( $this, 'get_object' ), $users );

		foreach ( $objects as $object ) {
			if ( ! $this->permission->rest_check_users_manipulation_permissions( 'delete', $object->get_id() ) ) {
				continue;
			}

			$data = $this->prepare_object_for_response( $object, $request );

			$object->delete(
				true,
				array(
					'reassign' => $reassign_id,
				)
			);

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
		 * Fires after multiple objects are deleted via the REST API.
		 *
		 * @since 1.6.5
		 *
		 * @param \Masteriyo\Database\Model $object The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
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

		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_instructor_active() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$user = get_user_by( 'id', (int) $request['id'] );

		if ( ! $user || ! $this->permission->rest_check_users_manipulation_permissions( 'edit', $user->ID ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( isset( $request['old_password'] ) && ! wp_check_password( $request['old_password'], $user->user_pass, $user->ID ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, invalid old password.', 'learning-management-system' ),
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
	 * @param int    $object_id Object ID.
	 *
	 * @return bool
	 */
	protected function check_item_permission( $object_type, $context = 'read', $object_id = 0 ) {
		return true;
	}

	/**
	 * Return logged in user data. Handle `users/me` endpoint.
	 *
	 * @since 1.6.8
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_logged_in_user( $request ) {
		$request->set_param( 'id', get_current_user_id() );

		return $this->get_item( $request );
	}

	/**
	 * Update logged in user. Handle `users/me` endpoint.
	 *
	 * @since 1.6.8
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_logged_in_user( $request ) {
		$user = wp_get_current_user();

		if ( isset( $user->user_email, $request['email'] ) && $user->user_email !== $request['email'] ) {
			$token = wp_hash( $user->ID . $request['email'] . time() );
			update_user_meta( $user->ID, '_email_change_token', $token );
			update_user_meta( $user->ID, '_pending_new_email', $request['email'] );
			$account_page_permalink = masteriyo_get_page_permalink( 'account' ) . '/#/dashboard';
			$confirmation_link      = add_query_arg(
				array(
					'token' => $token,
				),
				$account_page_permalink
			);

			$subject = apply_filters( 'masteriyo_email_change_subject', __( 'Confirm Your Email Change', 'learning-management-system' ), $user, $request['email'] );

			$message = sprintf(
				/* translators: %s: user email */
				__( 'You requested to change your email to <strong>%s</strong>. Please confirm this change by clicking the link below:', 'learning-management-system' ),
				$request['email']
			);
			$message  = apply_filters( 'masteriyo_email_change_message', $message, $user, $request['email'], $confirmation_link );
			$message .= "<br><br><a href='{$confirmation_link}'>Confirm Email Change</a>";
			$headers  = apply_filters( 'masteriyo_email_change_headers', array( 'Content-Type: text/html; charset=UTF-8' ), $user, $request['email'] );
			$mail     = wp_mail( $user->user_email, $subject, $message, $headers );
			if ( $mail ) {
				return new WP_Error(
					'masteriyo_rest_user_email_exists',
					sprintf(
							/* translators: %s: user email */
						esc_html__( 'Your email address has not been updated yet. Please check your inbox at %s for a confirmation email.', 'learning-management-system' ),
						$user->user_email
					),
					array(
						'status' => 400,
					)
				);
			} else {
				return new WP_Error(
					'masteriyo_rest_user_email_exists',
					__( 'There was an error sending the confirmation email. Please try again.', 'learning-management-system' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( isset( $request['old_password'], $request['password'] ) && wp_check_password( $request['password'], $user->user_pass, $user->ID ) ) {
			return new WP_Error(
				'masteriyo_rest_user_old_password_same_as_new_password',
				__( 'New password cannot be the same as the old password.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['old_password'] ) && ! wp_check_password( $request['old_password'], $user->user_pass, $user->ID ) ) {
			return new WP_Error(
				'masteriyo_rest_user_old_password_does_not_match',
				__( 'Old password does not match. Please verify your current password and try again.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['password'] ) && ( ! isset( $request['confirm_password'] ) || $request['password'] !== $request['confirm_password'] ) ) {
			return new WP_Error(
				'masteriyo_rest_new_password_mismatch',
				__( 'Old password does not match. Please verify your current password and try again.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		$request->set_param( 'id', $user->ID );

		// It denies the updating of the role of from account profile update page (frontend).
		if ( ! current_user_can( 'manage_options' ) ) {
			$request->offsetUnset( 'role' );
			$request->offsetUnset( 'roles' );
		}

		return $this->update_item( $request );
	}

	/**
	 * Retrieves the login QR code URL for the current user.
	 *
	 * @since 1.9.0
	 *
	 * @return WP_REST_Response|WP_Error Returns the QR code URL wrapped in a REST response or WP_Error if user is not authenticated.
	 */
	public function get_login_qr_code() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'authentication_required', 'User must be logged in to retrieve QR code', array( 'status' => 401 ) );
		}

		$token = get_user_meta( $user_id, 'masteriyo_login_via_qr_token', true );
		if ( empty( $token ) ) {
			return rest_ensure_response( array( 'message' => __( 'No QR code available. Please generate one first.', 'learning-management-system' ) ) );
		}

		$login_url = $this->construct_login_url_with_qr_token( $user_id, $token );

		$qr_code     = new QRCode();
		$qr_code_url = $qr_code->render( $login_url );

		return rest_ensure_response(
			array(
				'qr_img_url' => $qr_code_url,
				'login_url'  => $login_url,
			)
		);
	}

	/**
	 * Retrieves and serves the login QR code URL for the current user.
	 *
	 * @since 1.9.0
	 *
	 * @return WP_REST_Response Returns the QR code URL wrapped in a REST response.
	 */
	public function generate_login_qr_code() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error( 'authentication_required', 'User must be logged in to generate QR code', array( 'status' => 401 ) );
		}

		$qr_code = new QRCode();

		$token = $this->generate_login_token_for_user( $user_id );

		$login_url = $this->construct_login_url_with_qr_token( $user_id, $token );

		$qr_code_url = $qr_code->render( $login_url );

		return rest_ensure_response(
			array(
				'qr_img_url' => $qr_code_url,
				'login_url'  => $login_url,
			)
		);
	}

	/**
	 * Deletes or invalidates the login QR code token for the current user.
	 *
	 * @since 1.9.0
	 *
	 * @return WP_REST_Response|WP_Error Returns success message or WP_Error if an error occurred.
	 */
	public function delete_login_qr_code() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error( 'authentication_required', 'User must be logged in to delete QR code', array( 'status' => 401 ) );
		}

		$deleted = delete_user_meta( $user_id, 'masteriyo_login_via_qr_token' );

		if ( $deleted ) {
			return rest_ensure_response( array( 'message' => 'QR login link successfully deleted.' ) );
		} else {
			return new WP_Error( 'deletion_failed', 'Failed to delete QR login link.', array( 'status' => 500 ) );
		}
	}

	/**
	 * Generates a unique login token for a user and stores it as user meta.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id The user ID for which to generate the login token.
	 *
	 * @return string The generated token.
	 */
	private function generate_login_token_for_user( $user_id ) {
		$time         = time();
		$action       = 'masteriyo_login_via_qr';
		$key          = wp_generate_password( 100, false );
		$token_string = $key . '|' . $action . '|' . $time;
		$token        = wp_hash( $token_string );

		update_user_meta( $user_id, $action . '_token', $token );

		return $token;
	}

	/**
	 * Constructs a URL for QR-based user login including the generated token and nonce.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id The user ID for which the login URL is generated.
	 * @param string $token The unique login token for the user.
	 *
	 * @return string The constructed login URL.
	 */
	private function construct_login_url_with_qr_token( $user_id, $token ) {
		$url_params = array(
			'uid'            => $user_id,
			'qr_login_token' => $token,
		);

		$login_url = add_query_arg( $url_params, masteriyo_get_page_permalink( 'account' ) );

		return $login_url;
	}
}
