<?php
/**
 * Setting rest controller.
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Exporter\SettingExporter;
use Masteriyo\Helper\Permission;
use Masteriyo\Models\Setting;
use WP_Error;

class SettingsController extends CrudController {

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
	protected $rest_base = 'settings';

	/**
	 * Object type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $object_type = 'setting';

	/**
	 * If object is hierarchical.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Permission class.
	 *
	 * @since 1.0.0
	 *
	 * @var Masteriyo\Helper\Permission
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
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => array( $this, 'export_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import' ),
				'permission_callback' => array( $this, 'export_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/setup-wizard/email-collection-data-sharing-content',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'collect_email_and_data_sharing_consent' ),
				'permission_callback' => array( $this, 'collect_email_and_data_sharing_consent_permissions_check' ),
				'args'                => array(
					'allow_usage'       => array(
						'required'    => true,
						'type'        => 'boolean',
						'description' => __( 'User consent for sharing diagnostic data.', 'learning-management-system' ),
					),
					'subscribe_updates' => array(
						'required'    => true,
						'type'        => 'boolean',
						'description' => __( 'User consent for receiving updates and offers.', 'learning-management-system' ),
					),
					'email'             => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'User email for communications.', 'learning-management-system' ),
					),
				),
			)
		);
	}

	/**
	 * Export items.
	 *
	 * @since 1.6.14
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function export( \WP_REST_Request $request ) {
		$exporter = new SettingExporter();
		$data     = $exporter->export();
		return rest_ensure_response( $data );
	}

	/**
	 * Parse Import file.
	 *
	 * @since 1.6.14
	 * @param array $files $_FILES array for a given file.
	 * @return string|\WP_Error File path on success and WP_Error on failure.
	 */
	protected function get_import_file( $files ) {
		if ( ! isset( $files['file']['tmp_name'] ) ) {
			return new \WP_Error(
				'rest_upload_no_data',
				__( 'No data supplied.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		if (
			! isset( $files['file']['name'] ) ||
			'json' !== pathinfo( $files['file']['name'], PATHINFO_EXTENSION )
		) {
			return new \WP_Error(
				'invalid_file_ext',
				__( 'Invalid file type for import.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return $files['file']['tmp_name'];
	}

	/**
	 * Import items.
	 *
	 * @since 1.6.14
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import( \WP_REST_Request $request ) {
		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$file_system = masteriyo_get_filesystem();

		$file_contents = json_decode( $file_system->get_contents( $file ), true );

		if ( isset( $file_contents['manifest'] ) ) {
			unset( $file_contents['manifest'] );
		}

		if ( $file_contents ) {
			update_option( 'masteriyo_settings', $file_contents );
		}

		return new \WP_REST_Response(
			array(
				'message' => __( 'Import successful.', 'learning-management-system' ),
			)
		);
	}


	/**
	 * Check if a given request has access to import/export items.
	 *
	 * @since 1.6.14
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function export_permission_check( $request ) {

		return current_user_can( 'manage_masteriyo_settings' );

	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.0.0
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

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Check if a given request has access to update an item.
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

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Check if a given request has access to update an data sharing consent.
	 *
	 * @since 1.14.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function collect_email_and_data_sharing_consent_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to submit setup preferences.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		/**
		 * Added name parameter to get the group or single setting.
		 *
		 * @since 1.3.13
		 */
		$params['name'] = array(
			'description'       => __( 'Group setting or single setting.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * The dynamic part of the filter `$this->object_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->object_type}_query` filter to set WP_Query parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 * @param WP_object_type $object_type    Post type object.
		 */
		return apply_filters( "rest_{$this->object_type}_collection_params", $params, $this->object_type );
	}

	/**
	 * Get the settings' schema, conforming to JSON Schema.
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
				'general'        => array(
					'description' => __( 'General Settings', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'         => 'object',
						'styling'      => array(
							'description' => __( 'Styling', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'          => 'object',
								'primary_color' => array(
									'description' => __( 'Primary color', 'learning-management-system' ),
									'type'        => 'string',
									'format'      => 'hex-color',
									'context'     => array( 'view', 'edit' ),
								),
								'theme'         => array(
									'description' => __( 'Theme', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'widgets_css'  => array(
							'description' => __( 'Widgets CSS', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'pages'        => array(
							'description' => __( 'Pages Setting', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                 => 'object',
								'account_page_id'      => array(
									'description' => __( 'Account page ID', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'courses_page_id'      => array(
									'description' => __( 'Archive course page ID', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'checkout_page_id'     => array(
									'description' => __( 'Checkout page ID', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'course_thankyou_page' => array(
									'description' => __( 'Course Thankyou page ID', 'learning-management-system' ),
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
									'items'       => array(
										'display_type' => 'string',
										'page_id'      => 'integer',
										'wp_page_url'  => 'string',
										'custom_url'   => 'string',
									),
								),
								'after_checkout_page'  => array(
									'description' => __( 'Landing Page ID', 'learning-management-system' ),
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
									'items'       => array(
										'display_type' => 'string',
										'page_id'      => 'integer',
										'wp_page_url'  => 'string',
										'custom_url'   => 'string',
									),
								),
							),
						),
						'registration' => array(
							'description' => __( 'Registration related setting.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                  => 'object',
								'enable_student_registration' => array(
									'description' => __( 'Enable or disable student registration.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_instructor_registration' => array(
									'description' => __( 'Enable or disable instructor registration.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_guest_checkout' => array(
									'description' => __( 'Allow non-registered users to checkout and create an account during the process.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'editor'       => array(
							'description' => __( 'Editor', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'default_editor' => 'string',
							),
						),
					),
				),
				'course_archive' => array(
					'description' => __( 'Courses Settings', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => __( 'Styling', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'           => 'object',
								'view_mode'      => array(
									'description' => __( 'Select between list and grid view for courses.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_search'  => array(
									'description' => __( 'Enable course search.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'per_page'       => array(
									'description' => __( 'Courses per page.', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'per_row'        => array(
									'description' => __( 'Courses per row.', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'thumbnail_size' => array(
									'description' => __( 'Course thumbnail size.', 'learning-management-system' ),
									'type'        => 'string',
									'enum'        => get_intermediate_image_sizes(),
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'single_course'  => array(
					'description' => __( 'Single course settings.', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => __( 'Single course display settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'enable_review'        => array(
									'description' => __( 'Enable course review.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_review_enrolled_users_only' => array(
									'description' => __( 'Enable course review for enrolled users only.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_approve_reviews' => array(
									'description' => __( 'Automatically approve new reviews.', 'learning-management-system' ),
									'type'        => 'boolean',
									'default'     => true,
									'context'     => array( 'view', 'edit' ),
								),
								'course_visibility'    => array(
									'description' => __( 'Students must be logged in to view course.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'quiz'           => array(
					'description' => __( 'Quiz Setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => __( 'Quiz display settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                   => 'object',
								'questions_display_per_page' => array(
									'description' => __( 'Quiz questions per page.', 'learning-management-system' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'quiz_completion_button' => array(
									'description' => __( 'Quiz complete button visibility', 'learning-management-system' ),
									'type'        => 'boolean',
									'content'     => array( 'view', 'edit' ),
								),
								'quiz_review_visibility' => array(
									'description' => __( 'Quiz Review button visibility', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'quiz_previous_page'     => array(
									'description' => __( 'Show quiz previous page', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'general' => array(
							'description' => __( 'Quiz general settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'        => 'object',
								'quiz_access' => array(
									'description' => __( 'Quiz access for guest users.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'automatically_submit_quiz' => array(
									'description' => __( 'Automatically submit the quiz.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'learn_page'     => array(
					'description' => __( 'Learn page settings', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'general' => array(
							'description' => __( 'Learn page general settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                   => 'object',
								'logo_id'                => array(
									'description' => __( 'Learn page logo id.', 'learning-management-system' ),
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_load_next_content' => array(
									'description' => __( "When enabled, the page will automatically navigate to the next content as we press 'Mark as complete.'", 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'lesson_video_url_type'  => array(
									'description' => __( "Lesson video URL type.'", 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'display' => array(
							'description' => __( 'Learn page display settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                     => 'object',
								'enable_questions_answers' => array(
									'description' => __( 'Enable questions answers in learn page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_focus_mode'        => array(
									'description' => __( 'Enable or disable focus mode.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'show_sidebar'             => array(
									'description' => __( 'Show or hide sidebar in focus mode.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'show_header'              => array(
									'description' => __( 'Show or hide header in focus mode.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_lesson_comment'    => array(
									'description' => __( 'Show or hide lesson comments in learn page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_approve_comments'    => array(
									'description' => __( 'Show or hide lesson comments in learn page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'accounts_page'  => array(
					'description' => __( 'Account page settings', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => __( 'Account page display settings.', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                    => 'object',
								'enable_history_page'     => array(
									'description' => __( 'Enable history in accounts page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_invoice'          => array(
									'description' => __( 'Enable invoice in orders table.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_profile_page'     => array(
									'description' => __( 'Enable profile page in accounts page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_instructor_apply' => array(
									'description' => __( 'Enable apply for instructor button for profile page in accounts page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_edit_profile'     => array(
									'description' => __( 'Enable edit profile button for profile page in accounts page.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'payments'       => array(
					'description' => __( 'Payments Settings', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'            => 'object',
						'store'           => array(
							'description' => __( 'General Settings', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'          => 'object',
								'address_line1' => array(
									'description' => __( 'Address Line 1', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'address_line2' => array(
									'description' => __( 'Address Line 2', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'city'          => array(
									'description' => __( 'City Name', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'country'       => array(
									'description' => __( 'Country Name', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'postcode'      => array(
									'description' => __( 'Postal Code', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'currency'        => array(
							'description' => __( 'Currency Settings', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'               => 'object',
								'currency'           => array(
									'description' => __( 'Currency Code', 'learning-management-system' ),
									'type'        => 'string',
									'default'     => 'USD',
									'enum'        => masteriyo_get_currency_codes(),
									'context'     => array( 'view', 'edit' ),
								),
								'currency_position'  => array(
									'description' => __( 'Position of Currency', 'learning-management-system' ),
									'type'        => 'string',
									'default'     => 'left',
									'enum'        => array( 'left', 'right', 'left_space', 'right_space' ),
									'context'     => array( 'view', 'edit' ),
								),
								'thousand_separator' => array(
									'description' => __( 'Thousand Separator', 'learning-management-system' ),
									'type'        => 'string',
									'default'     => ',',
									'context'     => array( 'view', 'edit' ),
								),
								'decimal_separator'  => array(
									'description' => __( 'Decimal Separator', 'learning-management-system' ),
									'type'        => 'string',
									'default'     => '.',
									'context'     => array( 'view', 'edit' ),
								),
								'number_of_decimals' => array(
									'description' => __( 'Number of Decimals', 'learning-management-system' ),
									'type'        => 'integer',
									'default'     => 3,
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'offline'         => array(
							'enable'       => array(
								'description' => __( 'Enable offline payment.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'title'        => array(
								'description' => __( 'Offline payment title which the user sees during checkout.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'description'  => array(
								'description' => __( 'Offline payment description which the user sees during checkout.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'instructions' => array(
								'description' => __( 'Offline payment instructions.', 'learning-management-system' ),
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
						),
						'paypal'          => array(
							'enable'                  => array(
								'description' => __( 'Enable standard paypal.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'title'                   => array(
								'description' => __( 'Paypal title which the user sees during checkout.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'description'             => array(
								'description' => __( 'Paypal description which the user sees during checkout.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'ipn_email_notifications' => array(
								'description' => __( 'Enable IPN email notifications.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox'                 => array(
								'description' => __( 'Enable sandbox/sandbox mode on paypal.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'email'                   => array(
								'description' => __( 'Paypal email.', 'learning-management-system' ),
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'receiver_email'          => array(
								'description' => __( 'Paypal receiver email.', 'learning-management-system' ),
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'identity_token'          => array(
								'description' => __( 'Paypal identity token.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'invoice_prefix'          => array(
								'description' => __( 'Paypal invoice prefix.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => 'masteriyo-',
								'context'     => array( 'view', 'edit' ),
							),
							'payment_action'          => array(
								'description' => __( 'Paypal payment action.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => 'capture',
								'enum'        => array( 'capture', 'authorize' ),
								'context'     => array( 'view', 'edit' ),
							),
							'image_url'               => array(
								'description' => __( 'Paypal image url.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'debug'                   => array(
								'description' => __( 'Enable log.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_username'    => array(
								'description' => __( 'Paypal sandbox API username.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_password'    => array(
								'description' => __( 'Paypal sandbox API password.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_signature'   => array(
								'description' => __( 'Paypal sandbox API signature.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_username'       => array(
								'description' => __( 'Paypal live API username.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_password'       => array(
								'description' => __( 'Paypal live API password.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_signature'      => array(
								'description' => __( 'Paypal live API signature.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'checkout_fields' => array(
							'address_1'         => array(
								'description' => __( 'Address line One ', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'address_2'         => array(
								'description' => __( 'Address line two', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'company'           => array(
								'description' => __( 'Company Name', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'country'           => array(
								'description' => __( 'Country', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'customer_note'     => array(
								'description' => __( 'Customer Note', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'attachment_upload' => array(
								'description' => __( 'Attachment Upload', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'phone'             => array(
								'description' => __( 'Phone Number', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'postcode'          => array(
								'description' => __( 'Postal / Zip Code', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'state'             => array(
								'description' => __( 'State', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'city'              => array(
								'description' => __( 'Town/City', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'emails'         => array(
					'description' => __( 'Email Setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'general'              => array(
							'from_name'       => array(
								'description' => __( 'Email send from.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'from_email'      => array(
								'description' => __( 'Email address to send email.', 'learning-management-system' ),
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'default_content' => array(
								'description' => __( 'Default content for email.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'header_image'    => array(
								'description' => __( 'Email header image.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'footer_text'     => array(
								'description' => __( 'Email footer text.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'new_order'            => array(
							'enable'     => array(
								'description' => __( 'Enable new order.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'recipients' => array(
								'description' => __( 'Recipients email address.', 'learning-management-system' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type' => 'email',
								),
							),
							'subject'    => array(
								'description' => __( 'New order email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading'    => array(
								'description' => __( 'New order email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content'    => array(
								'description' => __( 'New order email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'processing_order'     => array(
							'enable'  => array(
								'description' => __( 'Enable processing order.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'Processing order email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'Processing order email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Processing order email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'completed_order'      => array(
							'enable'  => array(
								'description' => __( 'Enable completed order.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'Completed order email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'Completed order email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Completed order email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'onhold_order'         => array(
							'enable'  => array(
								'description' => __( 'Enable on hold order.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'On hold order email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'On hold order email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'On hold order email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'cancelled_order'      => array(
							'enable'     => array(
								'description' => __( 'Enable cancelled order.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'recipients' => array(
								'description' => __( 'Recipients email address.', 'learning-management-system' ),
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type' => 'email',
								),
							),
							'subject'    => array(
								'description' => __( 'Cancelled order email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading'    => array(
								'description' => __( 'Cancelled order email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content'    => array(
								'description' => __( 'Cancelled order email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'enrolled_course'      => array(
							'enable'  => array(
								'description' => __( 'Enable enrolled course.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'Enrolled course email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'Enrolled course email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Enrolled course email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'completed_course'     => array(
							'enable'  => array(
								'description' => __( 'Enable completed course.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'Completed course email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'Completed course email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Completed course email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'become_an_instructor' => array(
							'enable'  => array(
								'description' => __( 'Enable become an instructor.', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => __( 'Become an instructor email subject.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => __( 'Become an instructor email heading.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Become an instructor email content.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'advance'        => array(
					'description' => __( 'Advance setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'checkout'   => array(
							'description' => __( 'Checkout endpoints', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'pay'                   => array(
									'description' => __( 'Pay endpoint', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'order_received'        => array(
									'description' => __( 'Order received endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'add_payment_method'    => array(
									'description' => __( 'Add payment method endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'delete_payment_method' => array(
									'description' => __( 'Delete payment method endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'set_default_payment_method' => array(
									'description' => __( 'Set default payment method endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'account'    => array(
							'description' => __( 'Account endpoints', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'orders'          => array(
									'description' => __( 'Orders endpoint', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'view_order'      => array(
									'description' => __( 'View order endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'my_courses'      => array(
									'description' => __( 'My courses endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'edit_account'    => array(
									'description' => __( 'Edit account endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'payment_methods' => array(
									'description' => __( 'Payment methods endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'lost_password'   => array(
									'description' => __( 'Lost password endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'logout'          => array(
									'description' => __( 'Logout endpoint.', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'permalinks' => array(
							'description' => __( 'Permalinks', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                     => 'object',
								'category_base'            => array(
									'description' => __( 'Course category base', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'tag_base'                 => array(
									'description' => __( 'Course tag base', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'difficulty_base'          => array(
									'description' => __( 'Course difficulty base', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_course_permalink'  => array(
									'description' => __( 'Single course permalink', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_lesson_permalink'  => array(
									'description' => __( 'Course lessons permalink', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_quiz_permalink'    => array(
									'description' => __( 'Course quizzes permalink', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_section_permalink' => array(
									'description' => __( 'Course sections permalink', 'learning-management-system' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'debug'      => array(
							'description' => __( 'Debug', 'learning-management-system' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'           => 'object',
								'template_debug' => array(
									'description' => __( 'Enable template debug.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'debug'          => array(
									'description' => __( 'Enable debug.', 'learning-management-system' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),

			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = array(
			'offset'   => $request['offset'],
			'paged'    => $request['page'],
			'per_page' => $request['per_page'],
			's'        => $request['search'],
		);

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
	 * Get a collection of posts.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$setting      = masteriyo( 'setting' );
		$setting_repo = masteriyo( 'setting.store' );
		$setting_repo->read( $setting );

		return $this->prepare_object_for_response( $setting, $request );
	}

	/**
	 * Check permissions for an item.
	 *
	 * @since 1.0.0
	 * @param string $object_type Object type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	protected function check_item_permission( $object_type, $context = 'read', $object_id = 0 ) {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $option Option.
	 * @return Setting
	 */
	public function get_object( $option ) {
		try {
			$setting      = masteriyo( 'setting' );
			$setting_repo = masteriyo( 'setting.store' );
			$setting_repo->read( $setting );
		} catch ( \Exception $e ) {
			return false;
		}

		return $setting;
	}

	/**
	 * Reset the default value to settings.
	 *
	 * @since 1.0.0
	 * @return Setting
	 */
	public function delete_items( $request ) {
		$setting = masteriyo( 'setting' );
		$setting->delete( $setting );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $setting, $request );

		return $response;
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
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$name    = ! empty( $request['name'] ) ? $request['name'] : '';

		$data = $this->get_setting_data( $object, $context, $name );

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
	 * Get settings data.
	 *
	 * @since 1.0.0
	 * @since 1.3.13 Added 'name' parameter to fetch group or individual setting.
	 *
	 * @param object $setting Setting instance.
	 * @param string $context Request context. Options: 'view' and 'edit'.
	 * @param string $name Setting name.
	 *
	 * @return array
	 */
	protected function get_setting_data( $setting, $context = 'view', $name = '' ) {
		if ( empty( $name ) ) {
			$data = $setting->get_data();
		} else {
			$data = $setting->get( $name, $context );
		}

		/**
		 * Filter global setting  rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Setting data.
		 * @param Masteriyo\Models\Setting $setting Setting object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\SettingsController $controller REST settings controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $setting, $context, $this );
	}

	/**
	 * Prepare a single settings for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$setting      = masteriyo( 'setting' );
		$setting_repo = masteriyo( 'setting.store' );
		$setting_repo->read( $setting );

		$setting->set_data( $request->get_params() );

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Database\Model $setting Setting object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $setting, $request, $creating );
	}

	/**
	 * Return settings as object.
	 *
	 * @since 1.0.0
	 */
	protected function process_objects_collection( $settings ) {
		return array_shift( $settings );
	}

	/**
	 * Collect email and data sharing consent.
	 *
	 * @since 1.14.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function collect_email_and_data_sharing_consent( \WP_REST_Request $request ) {
		try {
			$allow_usage       = isset( $request['allow_usage'] ) ? masteriyo_string_to_bool( $request['allow_usage'] ) : false;
			$subscribe_updates = isset( $request['subscribe_updates'] ) ? masteriyo_string_to_bool( $request['subscribe_updates'] ) : false;
			$email             = isset( $request['email'] ) ? sanitize_email( $request['email'] ) : '';

			if ( ! is_email( $email ) ) {
				return new WP_Error(
					'masteriyo_invalid_email',
					__( 'Please provide a valid email address.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}

			$old_email = masteriyo_get_setting( 'advance.tracking.email' );

			masteriyo_set_setting( 'advance.tracking.allow_usage', $allow_usage );
			masteriyo_set_setting( 'advance.tracking.subscribe_updates', $subscribe_updates );
			masteriyo_set_setting( 'advance.tracking.email', $email );

			if ( $old_email && $old_email === $email ) {
				return rest_ensure_response(
					array(
						'status'  => 'success',
						'message' => __( 'Email has been collected already.', 'learning-management-system' ),
					)
				);
			}

			if ( $subscribe_updates ) {
				$this->send_email_to_tracking_server( $email );
			}

			return rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => __( 'Email and data sharing consent collected.', 'learning-management-system' ),
				)
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'masteriyo_internal_error',
				__( 'An unexpected error occurred.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Send email to tracking server.
	 *
	 * @since 1.14.0
	 *
	 * @param string $email Email address.
	 *
	 * @return void
	 */
	private function send_email_to_tracking_server( $email ) {
		wp_remote_post(
			'https://stats.wpeverest.com/wp-json/tgreporting/v1/process-email/',
			array(
				'method'      => 'POST',
				'timeout'     => 10,
				'redirection' => 5,
				'httpversion' => '1.0',
				'headers'     => array(
					'user-agent' => 'Masteriyo/' . masteriyo_get_version() . '; ' . get_bloginfo( 'url' ),
				),
				'body'        => array(
					'data' => array(
						'email'       => $email,
						'website_url' => get_bloginfo( 'url' ),
						'plugin_name' => is_plugin_active( 'learning-management-system-pro/lms.php' ) ? 'Masteriyo PRO' : 'Masteriyo',
						'plugin_slug' => plugin_basename( MASTERIYO_PLUGIN_FILE ),
					),
				),
			)
		);
	}
}
