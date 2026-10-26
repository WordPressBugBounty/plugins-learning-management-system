<?php
/**
 * Setting rest controller.
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Permission;
use Masteriyo\Models\Setting;
use Masteriyo\Enums\QuizGradingType;
use Masteriyo\Exporter\SettingExporter;
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
					'allow_usage' => array(
						'required'    => true,
						'type'        => 'boolean',
						'description' => 'User consent for sharing diagnostic data.',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/individual-email-reset',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'reset_individual_email' ),
				'permission_callback' => array( $this, 'reset_individual_email_permissions_check' ),
				'args'                => array(
					'email_id' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => 'Individual email ID.',
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
		$manifest      = is_array( $file_contents ) ? ( $file_contents['manifest'] ?? array() ) : array();
		$unresolved    = array();

		if ( isset( $file_contents['manifest'] ) ) {
			unset( $file_contents['manifest'] );
		}

		if ( isset( $file_contents['general']['pages'] ) && is_array( $file_contents['general']['pages'] ) ) {
			$file_contents['general']['pages'] = $this->resolve_pages(
				$file_contents['general']['pages'],
				is_array( $manifest['pages'] ?? null ) ? $manifest['pages'] : null,
				$unresolved
			);
		}

		if ( $file_contents ) {
			update_option( 'masteriyo_settings', $file_contents );
		}

		if ( $unresolved ) {
			return new \WP_REST_Response(
				array(
					'status'  => 'partial',
					'message' => sprintf(
						/* translators: %s: the page settings, e.g. "Account, Checkout" */
						__( 'Some pages were not found on this site, so its current choices were kept. Review Settings → General → Pages: %s.', 'learning-management-system' ),
						implode( ', ', $unresolved )
					),
				)
			);
		}

		return new \WP_REST_Response(
			array(
				'status'  => 'completed',
				'message' => __( 'Import successful.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Replace the exporting site's page IDs with this site's pages at the same path.
	 *
	 * A page with no match keeps this site's current choice and is named in
	 * `$unresolved`, unless the exporting site had no page there.
	 *
	 * @param array<string, mixed>       $pages      The imported general.pages setting.
	 * @param array<string, string>|null $slugs      Page URI paths keyed by setting path, from the export manifest; null for exports made before they were recorded.
	 * @param list<string>          $unresolved Filled with the labels of pages that could not be resolved.
	 *
	 * @return array<string, mixed>
	 */
	private function resolve_pages( array $pages, ?array $slugs, array &$unresolved ): array {
		foreach ( SettingExporter::get_page_setting_paths( $pages ) as $path => $imported_id ) {
			$slug    = $slugs[ $path ] ?? '';
			$page    = $slug ? get_page_by_path( $slug, 'OBJECT', 'page' ) : null;
			$page_id = $page ? $page->ID : absint( masteriyo_get_setting( 'general.pages.' . $path ) );

			// A source with no page there asks for nothing; an older export cannot say, so any ID counts.
			if ( ! $page && ( null === $slugs ? $imported_id : $slug ) ) {
				$unresolved[] = self::page_setting_label( $path );
			}

			list( $key, $child ) = array_pad( explode( '.', $path, 2 ), 2, null );

			if ( $child ) {
				$pages[ $key ][ $child ] = $page_id;
			} else {
				$pages[ $key ] = $page_id;
			}
		}

		return $pages;
	}

	/**
	 * Human name of a page setting: `account_page_id` → "Account", `course_thankyou_page.page_id` → "Course Thankyou".
	 *
	 * @param string $path Dotted setting path under general.pages.
	 *
	 * @return string
	 */
	private static function page_setting_label( string $path ): string {
		return ucwords( str_replace( '_', ' ', preg_replace( '/(_page_id|_page\.page_id)$/', '', $path ) ) );
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
	 * @since 1.14.0 [Free]
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
	 * Check if a given request has access to reset individual email content.
	 *
	 * @since 2.16.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function reset_individual_email_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to reset email content.', 'learning-management-system' ),
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
			'description'       => 'Group setting or single setting.',
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
					'description' => 'General Settings',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'          => 'object',
						'button_labels' => array(
							'description' => 'Custom labels for the course call-to-action buttons. Empty means the built-in label.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'         => 'object',
								'start_course' => array(
									'description' => 'Start course button label.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'continue'     => array(
									'description' => 'Continue course button label.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'enroll'       => array(
									'description' => 'Enroll button label.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'buy_now'      => array(
									'description' => 'Buy now button label.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'styling'       => array(
							'description' => 'Styling',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'               => 'object',
								'primary_color'      => array(
									'description' => 'Primary color',
									'type'        => 'string',
									'format'      => 'hex-color',
									'context'     => array( 'view', 'edit' ),
								),
								'theme'              => array(
									'description' => 'Theme',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'primary_color_for_learn_page' => array(
									'description' => 'Primary color for learn page',
									'type'        => 'string',
									'format'      => 'hex-color',
									'context'     => array( 'view', 'edit' ),
								),
								'button_color'       => array(
									'description' => 'Button color',
									'type'        => 'string',
									'format'      => 'hex-color',
									'context'     => array( 'view', 'edit' ),
								),
								'button_hover_color' => array(
									'description' => 'Button hover color',
									'type'        => 'string',
									'format'      => 'hex-color',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'widgets_css'  => array(
							'description' => 'Widgets CSS',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'pages'        => array(
							'description' => 'Pages Setting',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                 => 'object',
								'account_page_id'      => array(
									'description' => 'Account page ID',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'courses_page_id'      => array(
									'description' => 'Archive course page ID',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'checkout_page_id'     => array(
									'description' => 'Checkout page ID',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'course_thankyou_page' => array(
									'description' => 'Course Thankyou page ID',
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
									'description' => 'Landing Page ID',
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
							'description' => 'Registration related setting.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                  => 'object',
								'enable_student_registration' => array(
									'description' => 'Enable or disable student registration.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_instructor_registration' => array(
									'description' => 'Enable or disable instructor registration.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_guest_checkout' => array(
									'description' => 'Allow non-registered users to checkout and create an account during the process.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'editor'                => array(
									'description' => 'Editor',
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
									'items'       => array(
										'default_editor' => 'string',
										'course_overview_editor' => 'string',
									),
								),
							),
						),
					),
				),
				'course_archive' => array(
					'description' => 'Courses Settings',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => 'Styling',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'           => 'object',
								'view_mode'      => array(
									'description' => 'Select between list and grid view for courses.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_search'  => array(
									'description' => 'Enable course search.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'per_page'       => array(
									'description' => 'Courses per page.',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'per_row'        => array(
									'description' => 'Courses per row.',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'thumbnail_size' => array(
									'description' => 'Course thumbnail size.',
									'type'        => 'string',
									'enum'        => get_intermediate_image_sizes(),
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'single_course'  => array(
					'description' => 'Single course settings.',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => 'Single course display settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'enable_review'        => array(
									'description' => 'Enable course review.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_review_visibility_control' => array(
									'description' => 'Enable course Review Visibility Control.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_review_enrolled_users_only' => array(
									'description' => 'Enable course review for enrolled users only.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_approve_reviews' => array(
									'description' => 'Automatically approve new reviews.',
									'type'        => 'boolean',
									'default'     => true,
									'context'     => array( 'view', 'edit' ),
								),
								'course_visibility'    => array(
									'description' => 'Students must be logged in to view course.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'show_curriculum'      => array(
									'description' => 'Show the curriculum tab to everyone, not only enrolled students.',
									'type'        => 'boolean',
									'default'     => true,
									'context'     => array( 'view', 'edit' ),
								),
								'review_after_course_completion' => array(
									'description' => 'Ask the student for a review after the student completes a course.',
									'type'        => 'boolean',
									'default'     => false,
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'quiz'           => array(
					'description' => 'Quiz Setting',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => 'Quiz display settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                   => 'object',
								'questions_display_per_page' => array(
									'description' => 'Quiz questions per page.',
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'quiz_completion_button' => array(
									'description' => 'Quiz complete button visibility',
									'type'        => 'boolean',
									'content'     => array( 'view', 'edit' ),
								),
								'quiz_review_visibility' => array(
									'description' => 'Quiz Review button visibility',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'quiz_previous_page'     => array(
									'description' => 'Show quiz previous page',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'general' => array(
							'description' => 'Quiz general settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'        => 'object',
								'grading'     => array(
									'description' => 'Quiz grading calculation type',
									'type'        => 'string',
									'default'     => QuizGradingType::LAST_ATTEMPT,
									'enum'        => QuizGradingType::all(),
									'context'     => array( 'view', 'edit' ),
								),
								'quiz_access' => array(
									'description' => 'Quiz access for guest users.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'automatically_submit_quiz' => array(
									'description' => 'Automatically submit the quiz.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'learn_page'     => array(
					'description' => 'Learn page settings',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'general' => array(
							'description' => 'Learn page general settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                   => 'object',
								'logo_id'                => array(
									'description' => 'Learn page logo id.',
									'type'        => 'number',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_load_next_content' => array(
									'description' => "When enabled, the page will automatically navigate to the next content as we press 'Mark as complete.'",
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_content_protection' => array(
									'description' => 'Content protection for course content',
									'type'        => 'boolean',
									'content'     => array( 'view', 'edit' ),
								),
								'lesson_video_url_type'  => array(
									'description' => "Lesson video URL type.'",
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'display' => array(
							'description' => 'Learn page display settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                     => 'object',
								'enable_questions_answers' => array(
									'description' => 'Enable questions answers in learn page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_focus_mode'        => array(
									'description' => 'Enable or disable focus mode.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'show_sidebar'             => array(
									'description' => 'Show or hide sidebar in focus mode.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'show_header'              => array(
									'description' => 'Show or hide header in focus mode.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_lesson_comment'    => array(
									'description' => 'Show or hide lesson comments in learn page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'auto_approve_comments'    => array(
									'description' => 'Show or hide lesson comments in learn page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'payments'       => array(
					'description' => 'Payments Settings',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'            => 'object',
						'enabled'         => array(
							// Tri-state string, matching the `'yes' === ...` check in masteriyo_commerce_enabled(). Not boolean: WP REST would coerce a 'boolean' schema type to PHP true/false, which that comparison would never match.
							'description' => 'Whether commerce (payments) is explicitly enabled. Empty string falls back to evidence-based detection.',
							'type'        => 'string',
							'enum'        => array( '', 'yes', 'no' ),
							'context'     => array( 'view', 'edit' ),
						),
						'store'           => array(
							'description' => 'General Settings',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'          => 'object',
								'address_line1' => array(
									'description' => 'Address Line 1',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'address_line2' => array(
									'description' => 'Address Line 2',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'city'          => array(
									'description' => 'City Name',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'country'       => array(
									'description' => 'Country Name',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'postcode'      => array(
									'description' => 'Postal Code',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'currency'        => array(
							'description' => 'Currency Settings',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'               => 'object',
								'currency'           => array(
									'description' => 'Currency Code',
									'type'        => 'string',
									'default'     => 'USD',
									'enum'        => masteriyo_get_currency_codes(),
									'context'     => array( 'view', 'edit' ),
								),
								'currency_position'  => array(
									'description' => 'Position of Currency',
									'type'        => 'string',
									'default'     => 'left',
									'enum'        => array( 'left', 'right', 'left_space', 'right_space' ),
									'context'     => array( 'view', 'edit' ),
								),
								'thousand_separator' => array(
									'description' => 'Thousand Separator',
									'type'        => 'string',
									'default'     => ',',
									'context'     => array( 'view', 'edit' ),
								),
								'decimal_separator'  => array(
									'description' => 'Decimal Separator',
									'type'        => 'string',
									'default'     => '.',
									'context'     => array( 'view', 'edit' ),
								),
								'number_of_decimals' => array(
									'description' => 'Number of Decimals',
									'type'        => 'integer',
									'default'     => 3,
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'offline'         => array(
							'enable'       => array(
								'description' => 'Enable offline payment.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'title'        => array(
								'description' => 'Offline payment title which the user sees during checkout.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'description'  => array(
								'description' => 'Offline payment description which the user sees during checkout.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'instructions' => array(
								'description' => 'Offline payment instructions.',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
						),
						'paypal'          => array(
							'enable'                  => array(
								'description' => 'Enable standard paypal.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'title'                   => array(
								'description' => 'Paypal title which the user sees during checkout.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'description'             => array(
								'description' => 'Paypal description which the user sees during checkout.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'ipn_email_notifications' => array(
								'description' => 'Enable IPN email notifications.',
								'type'        => 'boolean',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox'                 => array(
								'description' => 'Enable sandbox/sandbox mode on paypal.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'email'                   => array(
								'description' => 'Paypal email.',
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'receiver_email'          => array(
								'description' => 'Paypal receiver email.',
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'identity_token'          => array(
								'description' => 'Paypal identity token.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'invoice_prefix'          => array(
								'description' => 'Paypal invoice prefix.',
								'type'        => 'string',
								'default'     => 'masteriyo-',
								'context'     => array( 'view', 'edit' ),
							),
							'payment_action'          => array(
								'description' => 'Paypal payment action.',
								'type'        => 'string',
								'default'     => 'capture',
								'enum'        => array( 'capture', 'authorize' ),
								'context'     => array( 'view', 'edit' ),
							),
							'image_url'               => array(
								'description' => 'Paypal image url.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'debug'                   => array(
								'description' => 'Enable log.',
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_username'    => array(
								'description' => 'Paypal sandbox API username.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_password'    => array(
								'description' => 'Paypal sandbox API password.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sandbox_api_signature'   => array(
								'description' => 'Paypal sandbox API signature.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_username'       => array(
								'description' => 'Paypal live API username.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_password'       => array(
								'description' => 'Paypal live API password.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'live_api_signature'      => array(
								'description' => 'Paypal live API signature.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'checkout_fields' => array(
							'address_1'         => array(
								'description' => 'Address line One ',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'address_2'         => array(
								'description' => 'Address line two',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'company'           => array(
								'description' => 'Company Name',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'country'           => array(
								'description' => 'Country',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'customer_note'     => array(
								'description' => 'Customer Note',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'attachment_upload' => array(
								'description' => 'Customer Note',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'phone'             => array(
								'description' => 'Phone Number',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'postcode'          => array(
								'description' => 'Postal / Zip Code',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'state'             => array(
								'description' => 'State',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'city'              => array(
								'description' => 'Town/City',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'taxes'           => array(
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'description' => 'Taxes settings',
							'items'       => array(
								'calculation_method' => array(
									'description' => 'Tax calculation method',
									'type'        => 'string',
									'enum'        => array( 'inclusive', 'exclusive' ),
									'context'     => array( 'view', 'edit' ),
								),
								'display_inclusive'  => array(
									'description' => 'Display inclusive tax',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'regions'            => array(
									'description' => 'Tax regions',
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'invoice'         => array(
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'description' => 'Invoice',
							'items'       => array(
								'enable' => array(
									'description' => 'Enable Invoices',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'emails'         => array(
					'description' => 'Email Setting',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'general'              => array(
							'from_name'         => array(
								'description' => 'Email send from.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'from_email'        => array(
								'description' => 'Email address to send email.',
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
							),
							'default_content'   => array(
								'description' => 'Default content for email.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'header_image'      => array(
								'description' => 'Email header image.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'footer_text'       => array(
								'description' => 'Email footer text.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'body_bg_color'     => array(
								'description' => 'Email template body background color.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'body_text_color'   => array(
								'description' => 'Email template body text color.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'header_bg_color'   => array(
								'description' => 'Email template header background color.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'button_bg_color'   => array(
								'description' => 'Email template button background color.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'button_text_color' => array(
								'description' => 'Email template button text color.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'new_order'            => array(
							'enable'     => array(
								'description' => 'Enable new order.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'recipients' => array(
								'description' => 'Recipients email address.',
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type' => 'email',
								),
							),
							'subject'    => array(
								'description' => 'New order email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading'    => array(
								'description' => 'New order email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content'    => array(
								'description' => 'New order email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'processing_order'     => array(
							'enable'  => array(
								'description' => 'Enable processing order.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'Processing order email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'Processing order email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'Processing order email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'completed_order'      => array(
							'enable'  => array(
								'description' => 'Enable completed order.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'Completed order email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'Completed order email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'Completed order email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'onhold_order'         => array(
							'enable'  => array(
								'description' => 'Enable on hold order.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'On hold order email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'On hold order email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'On hold order email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'cancelled_order'      => array(
							'enable'     => array(
								'description' => 'Enable cancelled order.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'recipients' => array(
								'description' => 'Recipients email address.',
								'type'        => 'email',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type' => 'email',
								),
							),
							'subject'    => array(
								'description' => 'Cancelled order email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading'    => array(
								'description' => 'Cancelled order email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content'    => array(
								'description' => 'Cancelled order email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'enrolled_course'      => array(
							'enable'  => array(
								'description' => 'Enable enrolled course.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'Enrolled course email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'Enrolled course email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'Enrolled course email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'completed_course'     => array(
							'enable'  => array(
								'description' => 'Enable completed course.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'Completed course email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'Completed course email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'Completed course email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
						'become_an_instructor' => array(
							'enable'  => array(
								'description' => 'Enable become an instructor.',
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'subject' => array(
								'description' => 'Become an instructor email subject.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'heading' => array(
								'description' => 'Become an instructor email heading.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => 'Become an instructor email content.',
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'advance'        => array(
					'description' => 'Advance setting',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'checkout'              => array(
							'description' => 'Checkout endpoints',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'pay'                   => array(
									'description' => 'Pay endpoint',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'order_received'        => array(
									'description' => 'Order received endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'add_payment_method'    => array(
									'description' => 'Add payment method endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'delete_payment_method' => array(
									'description' => 'Delete payment method endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'set_default_payment_method' => array(
									'description' => 'Set default payment method endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'account'               => array(
							'description' => 'Account endpoints',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'orders'          => array(
									'description' => 'Orders endpoint',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'view_order'      => array(
									'description' => 'View order endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'my_courses'      => array(
									'description' => 'My courses endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'edit_account'    => array(
									'description' => 'Edit account endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'payment_methods' => array(
									'description' => 'Payment methods endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'lost_password'   => array(
									'description' => 'Lost password endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'logout'          => array(
									'description' => 'Logout endpoint.',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'permalinks'            => array(
							'description' => 'Permalinks',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                     => 'object',
								'category_base'            => array(
									'description' => 'Course category base',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'tag_base'                 => array(
									'description' => 'Course tag base',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'difficulty_base'          => array(
									'description' => 'Course difficulty base',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_course_permalink'  => array(
									'description' => 'Single course permalink',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_lesson_permalink'  => array(
									'description' => 'Course lessons permalink',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_quiz_permalink'    => array(
									'description' => 'Course quizzes permalink',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'single_section_permalink' => array(
									'description' => 'Course sections permalink',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'debug'                 => array(
							'description' => 'Debug',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'          => 'object',
								'enable_logger' => array(
									'description' => 'Enable logger.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
						'mailchimp_integration' => array(
							'description' => 'Set the client secrets for the data and content message with default list and group.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'          => 'object',
								'client_id'     => array(
									'description' => 'Client Id created from mailchimp',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'client_secret' => array(
									'description' => 'Client Secret created from mailchimp',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'subscriber_consent_message' => array(
									'description' => 'Subscriber Consent Message created from mailchimp',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'default_group' => array(
									'description' => 'Default Group created for mailchimp',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'default_list'  => array(
									'description' => 'Default List created for mailchimp',
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
				'accounts_page'  => array(
					'description' => 'Account page settings',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
						'display' => array(
							'description' => 'Account page display settings.',
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'                    => 'object',
								'enable_assignments_page' => array(
									'description' => 'Enable assignment in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_certificate_page' => array(
									'description' => 'Enable certificate in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_gradebook_page'   => array(
									'description' => 'Enable gradebook in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_history_page'     => array(
									'description' => 'Enable history in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_invoice'          => array(
									'description' => 'Enable invoice in orders table.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_quiz_attempts_page' => array(
									'description' => 'Enable quiz attempts in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_subscriptions_page' => array(
									'description' => 'Enable subscription in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_instructor_apply' => array(
									'description' => 'Enable instructor apply button in accounts page in profile tab.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'instructor_max_attempts' => array(
									'description' => 'Maximum number of times a user can apply for instructor status.',
									'type'        => 'integer',
									'default'     => 3,
									'minimum'     => 1,
									'maximum'     => 10,
									'context'     => array( 'view', 'edit' ),
								),
								'enable_profile_page'     => array(
									'description' => 'Enable profile page tab in accounts page.',
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
								),
								'enable_edit_profile'     => array(
									'description' => 'Enable edit profile button for accounts page in profile tab.',
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
	 * Return the Elearning palette "colors" array from theme_mods_elearning.
	 * Empty array if Elearning isn't active or keys/option are missing.
	 *
	 * @since 3.0.2
	 * @return array
	 */
	protected function get_elearning_colors_only() {
		$theme = wp_get_theme();

		$is_elearning_active = (
		$theme && (
			$theme->get_stylesheet() === 'elearning' ||
			$theme->get_template() === 'elearning'
		)
		);

		if ( ! $is_elearning_active ) {
			return array();
		}

		$mods = get_option( 'theme_mods_elearning' );
		if ( ! is_array( $mods ) ) {
			return array();
		}

		$palette = isset( $mods['elearning_color_palette'] ) && is_array( $mods['elearning_color_palette'] )
		? $mods['elearning_color_palette']
		: array();

		$colors = isset( $palette['colors'] ) && is_array( $palette['colors'] )
		? $palette['colors']
		: array();

		if ( empty( $colors ) ) {
			return array();
		}

		$allowed_keys = array(
			'elearning-color-1',
			'elearning-color-2',
			'elearning-color-3',
			'elearning-color-4',
			'elearning-color-5',
			'elearning-color-6',
			'elearning-color-7',
			'elearning-color-8',
			'elearning-color-9',
		);

		return array_intersect_key( $colors, array_flip( $allowed_keys ) );
	}


	/**
	 * Converted the settings data to a simplified structure for search terms.
	 *
	 * @since 2.14.0
	 *
	 * @param array $data
	 * @return array
	 */
	protected function simplify_structure_for_search_term( $data ) {
		$simplified = array();

		foreach ( $data as $key => $section ) {
			$section_name                = ucwords( str_replace( '_', ' ', $key ) );
			$simplified[ $section_name ] = array();

			foreach ( $section as $sub_key => $sub_section ) {
				$sub_section_name                                 = ucwords( str_replace( '_', ' ', $sub_key ) );
				$simplified[ $section_name ][ $sub_section_name ] = array();

				if ( is_array( $sub_section ) ) {
					foreach ( $sub_section as $item_key => $value ) {
							$item_name = ucwords( str_replace( '_', ' ', $item_key ) );
							$simplified[ $section_name ][ $sub_section_name ][] = $item_name;
					}
				} else {
					$item_name = ucwords( str_replace( '_', ' ', $sub_key ) );
					$simplified[ $section_name ][ $sub_section_name ][] = $item_name;
				}
			}
		}

		return $simplified;
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
		$data = apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $setting, $context, $this );
		$data['general']['styling']['elearning_colors'] = $this->get_elearning_colors_only();
		$data['searchTerms']                            = $this->simplify_structure_for_search_term( $data );
		// Response-only, like searchTerms. The resolved commerce visibility ('' defers to the evidence rule, so the client can't compute it) — the save flow compares it against the page-load value to know the admin menus went stale.
		$data['commerce_visible'] = masteriyo_commerce_enabled();

		return $data;
	}

	/**
	* Prepare a single settings for create or update.
	*
	* @since 1.0.0
	*
	* @param \WP_REST_Request $request Request object.
	* @param bool            $creating If is creating a new object.
	*
	* @return WP_Error|Masteriyo\Database\Model
	*/
	protected function prepare_object_for_database( $request, $creating = false ) {
		$regions = $request['payments']['taxes']['regions'] ?? array();

		if ( ! empty( $regions ) ) {
			$regions = masteriyo_sanitize_nested_tax_regions( $regions );

			if ( isset( $data['payments']['taxes']['regions'] ) ) {
				unset( $data['payments']['taxes']['regions'] );
			}

			if ( ! empty( $regions ) ) {
				masteriyo_set_setting( 'payments.taxes.regions', maybe_serialize( $regions ) );
			}
		}

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
	 * @since 1.14.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function collect_email_and_data_sharing_consent( \WP_REST_Request $request ) {
		try {
			$allow_usage = isset( $request['allow_usage'] ) ? masteriyo_string_to_bool( $request['allow_usage'] ) : false;

			masteriyo_set_setting( 'advance.tracking.allow_usage', $allow_usage );

			return rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => __( 'Data sharing consent collected.', 'learning-management-system' ),
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
	 * Reset individual email settings to default values.
	 *
	 * @since 2.16.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function reset_individual_email( \WP_REST_Request $request ) {
		try {
			$email_id = $request->get_param( 'email_id' );

			$validation_result = $this->validate_email_id( $email_id );

			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
			}

			list( $second_key, $third_key ) = $validation_result;

			$default_email_contents = masteriyo_get_default_email_contents();
			$email_data             = $default_email_contents[ $second_key ][ $third_key ] ?? array();

			if ( empty( $email_data ) ) {
					return new WP_Error(
						'masteriyo_invalid_email_data',
						__( 'No default email data found for the given ID.', 'learning-management-system' ),
						array( 'status' => 400 )
					);
			}

			$email_data = $this->populate_email_defaults( $email_id, $email_data );

			masteriyo_set_setting( $email_id, $email_data );

			return rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => __( 'Email reset successfully.', 'learning-management-system' ),
					'data'    => array( $email_id => $email_data ),
				)
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'masteriyo_email_reset_error',
				__( 'An unexpected error occurred while resetting email.', 'learning-management-system' ),
				array(
					'status'  => 500,
					'details' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Validates the email ID and extracts keys.
	 *
	 * @param string|null $email_id Email ID to validate.
	 *
	 * @return array|WP_Error List of keys on success, or WP_Error on failure.
	 *
	 * @throws WP_Error If the email ID is invalid.
	 */
	private function validate_email_id( $email_id ) {
		if ( empty( $email_id ) ) {
			return new WP_Error(
				'masteriyo_email_id_missing',
				__( 'Email ID is missing.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$keys = explode( '.', $email_id );

		if ( count( $keys ) !== 3 ) {
			return new WP_Error(
				'masteriyo_invalid_email_id',
				__( 'Invalid email ID format.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		array_shift( $keys );

		return $keys;
	}


	/**
	 * Populates default email settings with fallback values if not present.
	 *
	 * @since 2.16.0
	 *
	 * @param string $email_id  The email ID.
	 * @param array  $email_data The email data to populate.
	 *
	 * @return array The populated email data.
	 */
	private function populate_email_defaults( $email_id, $email_data ) {
		$email_data['from_address']     = ! empty( $email_data['from_address'] ) ? $email_data['from_address'] : masteriyo_get_setting( 'emails.general.from_email' ) ?? get_bloginfo( 'admin_email' );
		$email_data['from_name']        = ! empty( $email_data['from_name'] ) ? $email_data['from_name'] : masteriyo_get_setting( 'emails.general.from_name' ) ?? get_bloginfo( 'name' );
		$email_data['reply_to_address'] = ! empty( $email_data['reply_to_address'] ) ? $email_data['reply_to_address'] : masteriyo_get_setting( 'emails.general.reply_to_address' ) ?? get_bloginfo( 'admin_email' );
		$email_data['reply_to_name']    = ! empty( $email_data['reply_to_name'] ) ? $email_data['reply_to_name'] : masteriyo_get_setting( 'emails.general.reply_to_name' ) ?? get_bloginfo( 'name' );

		return $email_data;
	}
}
