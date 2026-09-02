<?php
/**
 * Certificate rest controller.
 *
 * @since 2.3.7
 *
 * @package Masteriyo\Addons
 * @subpackage Masteriyo\Addons\Certificate
 */

namespace Masteriyo\Addons\Certificate\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Constants;
use Masteriyo\Helper\Utils;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Addons\Certificate\Models\Setting;
use Masteriyo\RestApi\Controllers\Version1\PostsController;

class CertificatesController extends PostsController {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/pro/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'certificates';

	/** Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'certificate';

	/** Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mto-certificate';

	/**
	 * Content format to scope the current collection query and counts by.
	 *
	 * @var string
	 */
	protected $content_format_filter = '';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Permission class.
	 *
	 * @since 2.3.7
	 *
	 * @var \Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 2.3.7
	 *
	 * @param \Masteriyo\Helper\Permission $permission
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 2.3.7
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
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids'      => array(
							'required'    => true,
							'description' => __( 'Certificate IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force'    => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
						'children' => array(
							'default'     => false,
							'description' => __( 'Whether to delete the children under the certificate.', 'learning-management-system' ),
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
							'description' => __( 'Certificate Ids', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
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
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)/clone',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'name' => array(
							'description' => __( 'Certificate name', 'learning-management-system' ),
							'required'    => false,
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clone-template',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_template' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'template_id' => array(
							'description' => __( 'Template ID.', 'learning-management-system' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/samples',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_samples' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/mine',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_certificates' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		/**
		 * Added certificate setting api.
		 *
		 * @since 2.11.0
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_certificate_setting' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_certificate_setting' ),
					'permission_callback' => array( $this, 'update_certificate_setting_permission_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);

		/**
		 * Added route to download remaining certificate fonts.
		 *
		 * @since 2.13.0
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import-certificate-fonts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_certificate_additional_font_setting' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_additional_fonts' ),
					'permission_callback' => array( $this, 'update_certificate_setting_permission_check' ),
				),
			)
		);
		/*
		 * `certificates/import-custom-fonts` is deliberately absent here. Uploading
		 * a font of your own is pro's — free 2.3.2 has no such route — so the pro
		 * half of this addon registers it, on the same namespace and base, and the
		 * URL is unchanged in the pro product. The route above, which installs the
		 * *predefined* font list, ships to both products and stays.
		 */

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/pdfdraft-inline-images',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'inline_pdfdraft_images_for_preview' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/pdfdraft-preview-data',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Certificate ID.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pdfdraft_preview_data' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/pdfdraft-preview',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Certificate ID.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_pdfdraft_preview' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'rendered_html' => array(
							'description' => __( 'Rendered HTML snapshot from the designer canvas.', 'learning-management-system' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Return resolved field values for the PDFDraft certificate preview.
	 *
	 * Finds the course linked to this certificate and returns real data
	 * (course title, instructor, duration) alongside date/site values so the
	 * JS editor can show meaningful placeholder data in the PDF preview.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_pdfdraft_preview_data( $request ) {
		$certificate_id = absint( $request['id'] );
		$date_format    = get_option( 'date_format', 'F j, Y' );
		$time_format    = get_option( 'time_format', 'g:i a' );

		$data = array(
			'site_name'         => get_bloginfo( 'name' ),
			'current_date'      => date_i18n( $date_format ),
			'current_time'      => date_i18n( $time_format ),
			'current_timestamp' => date_i18n( $date_format . ' ' . $time_format ),
			'course_title'      => '',
			'instructor_name'   => '',
			'course_duration'   => '',
			'date_format'       => $date_format,
			'time_format'       => $time_format,
		);

		// Find the course that has this certificate assigned.
		$course_posts = get_posts(
			array(
				'post_type'      => 'mto-course',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_masteriyo_certificate_id',
						'value'   => $certificate_id,
						'compare' => '=',
					),
				),
			)
		);

		if ( ! empty( $course_posts ) ) {
			$course = masteriyo_get_course( $course_posts[0] );

			if ( $course && ! is_wp_error( $course ) ) {
				$data['course_title']    = $course->get_name();
				$data['course_duration'] = masteriyo_minutes_to_time_length_string( $course->get_duration() );

				$instructor = masteriyo_get_user( $course->get_author_id() );
				if ( $instructor && ! is_wp_error( $instructor ) ) {
					$data['instructor_name'] = $instructor->get_display_name();
				}
			}
		}

		// Fall back to the current admin user as instructor.
		if ( empty( $data['instructor_name'] ) ) {
			$current_user            = wp_get_current_user();
			$data['instructor_name'] = $current_user->display_name ?? '';
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Generate a temporary preview URL for a pdfdraft certificate.
	 * Stores the rendered HTML in a transient and returns a one-time preview URL.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_pdfdraft_preview( $request ) {
		$certificate_id = absint( $request['id'] );
		$rendered_html  = $request->get_param( 'rendered_html' );

		if ( empty( $rendered_html ) ) {
			return new \WP_Error(
				'masteriyo_empty_rendered_html',
				__( 'rendered_html is required.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$token = wp_generate_uuid4();
		set_transient(
			'masteriyo_pdfdraft_preview_' . $token,
			array(
				'certificate_id' => $certificate_id,
				'rendered_html'  => $rendered_html,
			),
			15 * MINUTE_IN_SECONDS
		);

		$preview_url = add_query_arg(
			array( 'masteriyo_pdfdraft_preview' => $token ),
			get_home_url()
		);

		return rest_ensure_response( array( 'url' => $preview_url ) );
	}

	/**
	 * Return the design with remote image URLs inlined as base64 data URIs, so the
	 * editor's client-side Preview/Export (PDFExporter) renders cross-origin/CDN
	 * images without canvas tainting.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function inline_pdfdraft_images_for_preview( $request ) {
		$params   = $request->get_json_params();
		$pages    = isset( $params['pages'] ) && is_array( $params['pages'] ) ? $params['pages'] : array();
		$settings = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : array();

		$certificate_pdf = new \Masteriyo\Addons\Certificate\PDF\CertificatePDF( 0, get_current_user_id(), '' );
		$design          = $certificate_pdf->inline_pdfdraft_remote_images(
			array(
				'pages'    => $pages,
				'settings' => $settings,
			)
		);

		return rest_ensure_response(
			array(
				'pages'    => isset( $design['pages'] ) ? $design['pages'] : $pages,
				'settings' => isset( $design['settings'] ) ? $design['settings'] : $settings,
			)
		);
	}

	/**
	 * Checks if a given request has access to update items.
	 *
	 * @since 2.11.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function update_certificate_setting_permission_check( $request ) {
		if ( ! current_user_can( 'manage_masteriyo_settings' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Provides the certificate setting data.
	 *
	 * @since 2.11.0
	 *
	 * @return WP_Error|array
	 */
	public function get_certificate_setting() {
		$data = Setting::all();

		return rest_ensure_response( $data );
	}

	/**
	 * Checks if certificate additional fonts are installed.
	 *
	 * @since 2.13.0
	 *
	 * @return true|false
	 */
	public function get_certificate_additional_font_setting() {
		$now = get_option( '_masteriyo_additional_certificate_fonts_downloaded', false );

		$now = masteriyo_string_to_bool( $now );

		return rest_ensure_response( $now );
	}

	/**
	 * Save certificate setting data.
	 *
	 * @since 2.11.0
	 *
	 * @param  $request $request Full details about the request.
	 *
	 * @return \WP_Error|array
	 */
	public function save_certificate_setting( $request ) {
		$use_absolute_img_path = isset( $request['use_absolute_img_path'] ) ? masteriyo_string_to_bool( $request['use_absolute_img_path'] ) : true;
		$use_ssl_verified      = isset( $request['use_ssl_verified'] ) ? masteriyo_string_to_bool( $request['use_ssl_verified'] ) : true;

		Setting::set( 'use_absolute_img_path', $use_absolute_img_path );
		Setting::set( 'use_ssl_verified', $use_ssl_verified );
		Setting::save();

		return rest_ensure_response( Setting::all() );
	}

	/**
	 * Get the query params for collections of download_materials.
	 *
	 * @since 2.3.7
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['slug']           = array(
			'description'       => __( 'Limit result set to certificates with a specific slug.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']         = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to certificates assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future', 'trash' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['content_format'] = array(
			'description'       => __( 'Limit result set to certificates with a specific content format.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array( 'gutenberg', 'pdfdraft' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 2.3.7
	 *
	 * @param int|\WP_Post|\Masteriyo\Addons\Certificate\Models\Certificate $object Object ID or WP_Post or Model.
	 *
	 * @return \Masteriyo\Addons\Certificate\Models\Certificate|false Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Post' ) ? $object->ID : $object->get_id();
			}
			$certificate = masteriyo( 'certificate' );
			$certificate->set_id( $id );
			$certificate_repo = masteriyo( 'certificate.store' );
			$certificate_repo->read( $certificate );
		} catch ( \Exception $e ) {
			return false;
		}

		return $certificate;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 2.3.7
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $object  Model object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_certificate_data( $object, $context );

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
		 * @since 2.3.7
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Model          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get certificate data.
	 *
	 * @since 2.3.7
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_certificate_data( $certificate, $context = 'view' ) {
		$author = masteriyo_get_user( $certificate->get_author_id( $context ) );

		if ( ! is_wp_error( $author ) ) {
			$author = array(
				'id'           => $author->get_id(),
				'display_name' => $author->get_display_name(),
				'avatar_url'   => $author->get_avatar_url(),
			);
		} else {
			$author = null;
		}

		$data = array(
			'id'             => $certificate->get_id(),
			'name'           => wp_specialchars_decode( $certificate->get_name( $context ) ),
			'slug'           => $certificate->get_slug( $context ),
			'permalink'      => $certificate->get_permalink(),
			'preview_link'   => $certificate->get_post_preview_link(),
			'edit_post_link' => $certificate->get_edit_post_link(),
			'status'         => $certificate->get_status( $context ),
			'html_content'   => ( 'view' === $context && 'pdfdraft' !== $certificate->get_content_format() ) ? wpautop( do_shortcode( $certificate->get_html_content() ) ) : $certificate->get_html_content( $context ),
			'parent_id'      => $certificate->get_parent_id( $context ),
			'author'         => $author,
			'date_created'   => masteriyo_rest_prepare_date_response( $certificate->get_date_created( $context ) ),
			'date_modified'  => masteriyo_rest_prepare_date_response( $certificate->get_date_modified( $context ) ),
			'content_format' => $certificate->get_content_format( $context ),
			'rendered_html'  => $certificate->get_rendered_html( $context ),
		);

		return $data;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since 2.3.7
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		if ( ! masteriyo_is_current_user_admin() ) {
			$args['author__in'] = array( 'author' => get_current_user_id() );
		}

		if ( ! empty( $request['content_format'] ) ) {
			$this->content_format_filter = sanitize_key( $request['content_format'] );
			$args['meta_query']          = $this->get_content_format_meta_query( $this->content_format_filter ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return $args;
	}

	/**
	 * Build the meta query that scopes certificates to a content format.
	 *
	 * Legacy certificates created before the pdfdraft builder may not have the
	 * `_masteriyo_content_format` meta set, so the gutenberg filter also matches
	 * certificates where that meta is missing.
	 *
	 * @param string $content_format 'gutenberg' or 'pdfdraft'.
	 *
	 * @return array
	 */
	protected function get_content_format_meta_query( $content_format ) {
		if ( 'pdfdraft' === $content_format ) {
			return array(
				array(
					'key'   => '_masteriyo_content_format',
					'value' => 'pdfdraft',
				),
			);
		}

		return array(
			'relation' => 'OR',
			array(
				'key'     => '_masteriyo_content_format',
				'value'   => 'pdfdraft',
				'compare' => '!=',
			),
			array(
				'key'     => '_masteriyo_content_format',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Get the certificates'schema, conforming to JSON Schema.
	 *
	 * @since 2.3.7
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
				'name'              => array(
					'description' => __( 'Certificate name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'              => array(
					'description' => __( 'Certificate slug', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'         => array(
					'description' => __( 'Certificate URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'      => array(
					'description' => __( "The date the certificate was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the certificate was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'     => array(
					'description' => __( "The date the certificate was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the certificate was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'Certificate status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => 'draft',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'html_content'      => array(
					'description' => __( 'Certificate html content', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'content_format'    => array(
					'description' => __( 'Certificate builder format (gutenberg or pdfdraft).', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => array( 'gutenberg', 'pdfdraft' ),
					'default'     => 'gutenberg',
					'context'     => array( 'view', 'edit' ),
				),
				'rendered_html'     => array(
					'description' => __( 'Pre-rendered HTML snapshot for PDF generation (pdfdraft format only).', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'         => array(
					'description' => __( 'Certificate parent ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => false,
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

		return $schema;
	}

	/**
	 * Prepare a single certificate for create or update.
	 *
	 * @since 2.3.7
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param boolean $creating If is creating a new object.
	 *
	 * @return \WP_Error|\Masteriyo\Addons\Certificate\Models\Certificate
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id          = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$certificate = masteriyo_create_certificate_object();

		if ( 0 !== $id ) {
			$certificate->set_id( $id );
			$certificate_repo = masteriyo( 'certificate.store' );
			$certificate_repo->read( $certificate );
		}

		$status = get_post_status( $id );

		$content_format = 'gutenberg';
		if ( 0 !== $id ) {
			$content_format = $certificate->get_content_format() ? $certificate->get_content_format() : 'gutenberg';
		}
		if ( isset( $request['content_format'] ) ) {
			$content_format = sanitize_key( $request['content_format'] );
		}

		/**
		 * Filters the number of certificate templates that may be published per format.
		 *
		 * Two is the free product's allowance, and this controller ships to both
		 * products, so the limit lives here rather than in a pro-only file. Pro
		 * lifts it by returning 0, which means unlimited.
		 *
		 * @param int    $max_published  Maximum published templates, 0 for unlimited.
		 * @param string $content_format The format being counted, 'gutenberg' or 'pdfdraft'.
		 */
		$max_published = (int) apply_filters( 'masteriyo_certificate_max_published_templates', 2, $content_format );

		if ( $max_published > 0 && PostStatus::PUBLISH !== $status ) {
			$published_count = $this->count_published_by_format( $content_format );

			if ( ! $id && isset( $request['status'] ) && PostStatus::PUBLISH === $request['status'] && $max_published <= $published_count ) {
				return new \WP_Error(
					"masteriyo_rest_{$this->post_type}_upgrade_required",
					__( 'You cannot create more than two published templates in the free version. Please upgrade to Pro.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}

			if ( $id && isset( $request['status'] ) && PostStatus::PUBLISH === $request['status'] && $max_published <= $published_count ) {
				return new \WP_Error(
					"masteriyo_rest_{$this->post_type}_upgrade_required",
					__( 'You cannot have more than two published templates in the free version. Please upgrade to Pro.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			$certificate->set_name( sanitize_text_field( $request['name'] ) );
		}

		// HTML content.
		if ( isset( $request['html_content'] ) ) {
			$certificate->set_html_content( $request['html_content'] );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$certificate->set_status( get_post_status_object( $request['status'] ) ? $request['status'] : 'draft' );
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$certificate->set_slug( $request['slug'] );
		}

		// Certificate parent ID.
		if ( isset( $request['parent_id'] ) ) {
			$certificate->set_parent_id( $request['parent_id'] );
		}

		// Certificate author ID.
		if ( isset( $request['author_id'] ) ) {
			$certificate->set_author_id( $request['author_id'] );
		}

		// Content format (gutenberg or pdfdraft).
		if ( isset( $request['content_format'] ) ) {
			$certificate->set_content_format( $request['content_format'] );
		}

		// Rendered HTML snapshot (pdfdraft format only).
		if ( isset( $request['rendered_html'] ) ) {
			$certificate->set_rendered_html( $request['rendered_html'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$certificate->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 2.3.7
		 *
		 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate  Object object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param boolean $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $certificate, $request, $creating );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 2.3.7
	 *
	 * @param array $objects Certificates data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Certificates query result data.
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
				'counts'       => $this->get_certificate_counts(),
			),
		);
	}

	/**
	 * Get certificates count by status.
	 *
	 * @since 2.3.7
	 *
	 * @return array
	 */
	protected function get_certificate_counts() {
		$post_count = $this->get_posts_count();
		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), PostStatus::all() ) );
	}

	/**
	 * Get posts count by status.
	 *
	 * @since 2.3.7
	 *
	 * @return array
	 */
	protected function get_posts_count() {
		$post_count        = empty( $this->content_format_filter ) ? parent::get_posts_count() : $this->get_content_format_posts_count( $this->content_format_filter );
		$post_count['any'] = array_sum( array( $post_count[ PostStatus::PUBLISH ], $post_count[ PostStatus::DRAFT ] ) );

		/**
		 * Filters the post counts.
		 *
		 * @since 2.3.7
		 *
		 * @param array $post_count Posts count.
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller Posts Controller.
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_count", $post_count, $this );
	}

	/**
	 * Count certificates per post status, scoped to a content format.
	 *
	 * Mirrors the status keys returned by wp_count_posts() so callers can treat
	 * the result identically. Non-admins/non-managers are limited to their own
	 * certificates, matching the parent count behaviour.
	 *
	 * @param string $content_format 'gutenberg' or 'pdfdraft'.
	 *
	 * @return array
	 */
	protected function get_content_format_posts_count( $content_format ) {
		global $wpdb;

		// Seed every known status with zero so missing rows don't trigger notices.
		$counts = array();
		foreach ( get_post_stati() as $status ) {
			$counts[ $status ] = 0;
		}

		$author_where = '';
		$author_args  = array();
		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() ) {
			$author_where = ' AND p.post_author = %d';
			$author_args  = array( get_current_user_id() );
		}

		if ( 'pdfdraft' === $content_format ) {
			$sql  = "SELECT p.post_status, COUNT( * ) AS num_posts
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %s )
				WHERE p.post_type = %s{$author_where}
				GROUP BY p.post_status";
			$args = array_merge( array( '_masteriyo_content_format', 'pdfdraft', $this->post_type ), $author_args );
		} else {
			$sql  = "SELECT p.post_status, COUNT( * ) AS num_posts
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = %s )
				WHERE p.post_type = %s AND ( pm.meta_value IS NULL OR pm.meta_value <> %s ){$author_where}
				GROUP BY p.post_status";
			$args = array_merge( array( '_masteriyo_content_format', $this->post_type, 'pdfdraft' ), $author_args );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( (array) $results as $row ) {
			$counts[ $row->post_status ] = absint( $row->num_posts );
		}

		return $counts;
	}

	/**
	 * Return certificates samples.
	 *
	 * @since 2.3.7
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_samples() {
		$data = masteriyo_get_certificate_templates();

		// Bail early if there is error in data.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Clone a certificate post.
	 *
	 * @param int   $post_id Source post ID.
	 * @param array $args    Override args for the new post.
	 * @return \WP_Post|null
	 */
	public function clone( $post_id, $args = '' ) {
		global $wpdb;

		$content_format   = get_post_meta( absint( $post_id ), '_masteriyo_content_format', true );
		$original_content = get_post_field( 'post_content', absint( $post_id ), 'raw' );

		$new_post = parent::clone( $post_id, $args );

		if ( $new_post && 'pdfdraft' === $content_format ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $original_content ),
				array( 'ID' => $new_post->ID )
			);
			clean_post_cache( $new_post->ID );
		}

		return $new_post;
	}

	/**
	 * Create a new certificate from a template.
	 *
	 * @since 2.3.7
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function clone_template( $request ) {
		$template_id = sanitize_text_field( $request['template_id'] );
		$templates   = masteriyo_get_certificate_templates();
		$template    = null;

		if ( 'blank' === $template_id ) {
			$template = array(
				'id'                       => 'blank',
				'content'                  => '',
				'content_format'           => 'gutenberg',
				'title'                    => __( 'Blank Slate', 'learning-management-system' ),
				'backgroundImageLandscape' => '',
				'backgroundImagePortrait'  => '',
			);
		} elseif ( is_array( $templates ) ) {
			foreach ( $templates as $item ) {
				if ( $item['id'] === $template_id ) {
					$template = array(
						'title'          => $item['title'],
						'content'        => masteriyo_process_content_for_import( $item['content'] ),
						'content_format' => 'gutenberg',
					);
					break;
				}
			}
		}

		if ( empty( $template ) ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$certificate = masteriyo_create_certificate_object();

		$certificate->set_name( $template['title'] );
		$certificate->set_html_content( $template['content'] );
		$certificate->set_content_format( $template['content_format'] ?? 'gutenberg' );
		$certificate->save();

		if ( ! $certificate->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_cannot_clone", __( 'Unable to clone', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		// Read the new object.
		$object = $this->get_object( $certificate->get_id() );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		/**
		 * Filter the data for a response.
		 *
		 * @since 2.3.7
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'masteriyo_rest_create_certificate_from_template', $response, $object, $request );
	}

	/**
	 * Restore certificate.
	 *
	 * @since 2.3.7
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

		$object   = $this->get_object( (int) $request['id'] );
		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Get a collection of user's certificates.
	 *
	 * @since 2.3.7
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_user_certificates( $request ) {

		$per_page    = intval( preg_replace( '/[^0-9]/', '', $_GET['per_page'] ?? 5 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_number = intval( preg_replace( '/[^0-9]/', '', $_GET['page'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$context = 'view';
		$query   = new CourseProgressQuery(
			array(
				'user_id'  => get_current_user_id(),
				'status'   => CourseProgressStatus::COMPLETED,
				'per_page' => $per_page,
				'page'     => $page_number,
			)
		);

		$progresses   = $query->get_course_progress();
		$certificates = array();

		foreach ( $progresses as $progress ) {
			$course = masteriyo_get_course( $progress->get_course_id( $context ) );

			if ( is_null( $course ) ) {
				continue;
			}

			$certificate_id = get_post_meta( $course->get_id(), '_certificate_id', true );

			if ( ! masteriyo_string_to_bool( get_post_meta( $course->get_id(), '_certificate_enabled', true ) ) || ! $certificate_id ) {
				continue;
			}

			$course_author = masteriyo_get_user( $course->get_author_id( $context ) );
			$certificate   = array(
				'id'           => $progress->get_id(),
				'download_url' => masteriyo_generate_certificate_download_url( $course ),
				'view_url'     => masteriyo_get_certificate_addon_view_url( $course, get_current_user_id(), $certificate_id ),
				'course'       => array(
					'id'                 => $course->get_id(),
					'name'               => wp_specialchars_decode( $course->get_name( $context ) ),
					'slug'               => $course->get_slug( $context ),
					'featured_image_url' => $course->get_featured_image_url( 'masteriyo_thumbnail' ),
					'permalink'          => $course->get_permalink(),
					'preview_permalink'  => $course->get_preview_link(),
					'status'             => $course->get_status( $context ),
					'author'             => null,
					'date_created'       => masteriyo_rest_prepare_date_response( $course->get_date_created( $context ) ),
					'date_modified'      => $course->get_date_modified( $context ),
					'featured'           => $course->get_featured( $context ),
					'price'              => $course->get_price( $context ),
					'price_label'        => wp_kses_post( masteriyo_price( $course->get_price() ) ),
					'regular_price'      => $course->get_regular_price( $context ),
					'sale_price'         => $course->get_sale_price( $context ),
					'price_type'         => $course->get_price_type( $context ),
					'featured_image'     => $course->get_featured_image( $context ),
					'average_rating'     => $course->get_average_rating( $context ),
					'review_count'       => $course->get_review_count( $context ),
					'categories'         => $this->get_course_taxonomy_terms( $course, 'cat' ),
					'difficulty'         => $this->get_course_taxonomy_terms( $course, 'difficulty' ),
					'started_at'         => masteriyo_rest_prepare_date_response( $progress->get_started_at( $context ), 'Y-m-d' ),
				),
			);

			if ( ! is_wp_error( $course_author ) ) {
				$certificate['course']['author'] = array(
					'id'           => $course_author->get_id(),
					'display_name' => $course_author->get_display_name( $context ),
					'avatar_url'   => $course_author->get_avatar_url(),
				);
			}

			$certificates[] = $certificate;
		}

		$certificates = apply_filters( 'masteriyo_user_certificates_response_data', $certificates );
		$response     = rest_ensure_response( $certificates );

		return $response;
	}

	/**
	 * Get taxonomy terms of a course.
	 *
	 * @since 2.3.7
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected function get_course_taxonomy_terms( $course, $taxonomy = 'cat' ) {
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
	 * Import additional certificate fonts.
	 *
	 * @since 2.13.0
	 * @param object $request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_additional_fonts( $request ) {
		try {

			if ( ! $request ) {
				return new \WP_Error(
					'import_fonts_error',
					__( 'Request not found.', 'learning-management-system' ),
					array( 'status' => 500 )
				);
			}

			$filesystem = masteriyo_get_filesystem();
			if ( ! $filesystem || ! class_exists( \ZipArchive::class ) ) {
				throw new \Exception( __( 'Filesystem or ZipArchive class not available.', 'learning-management-system' ) );
			}

			$destination = wp_upload_dir()['basedir'] . '/masteriyo/certificate-fonts';
			$api         = 'https://d1sb0nhp4t2db4.cloudfront.net/resources/masteriyo/certificate/fonts-old.zip';

			$response = wp_remote_get(
				$api,
				array(
					'timeout' => 100,
					'stream'  => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new \Exception( __( 'Failed to download fonts.', 'learning-management-system' ) );
			}

			$temp_file = $response['filename'];

			if ( empty( $temp_file ) || ! file_exists( $temp_file ) ) {
				throw new \Exception( __( 'Temporary file does not exist.', 'learning-management-system' ) );
			}

			if ( ! $filesystem->is_dir( $destination ) ) {
				$filesystem->mkdir( $destination );
			}

			$zip = new \ZipArchive();

			if ( $zip->open( $temp_file ) === true ) {
				$font_exts = array( 'ttf', 'otf' );

				for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$filename  = $zip->getNameIndex( $i );
					$file_info = pathinfo( $filename );

					if ( '.' !== $file_info['basename'] && '..' !== $file_info['basename'] && isset( $file_info['extension'] ) && in_array( $file_info['extension'], $font_exts, true ) && ! $filesystem->exists( $destination . '/' . $file_info['basename'] ) ) {
						$font = $destination . '/' . $file_info['basename'];
						$filesystem->copy( "zip://{$temp_file}#{$filename}", $font );
					}
				}
				$zip->close();
			} else {
				wp_delete_file( $temp_file );

				throw new \Exception( __( 'Failed to open the ZIP file.', 'learning-management-system' ) );
			}

			wp_delete_file( $temp_file );

			update_option( '_masteriyo_additional_certificate_fonts_downloaded', true );
			delete_transient( 'masteriyo_certificate_font_urls' );
			delete_transient( 'masteriyo_font_configurations' );

			return new \WP_REST_Response(
				array(
					'message' => __( 'Certificate fonts installed.', 'learning-management-system' ),
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'import_fonts_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Count published certificates of a specific content format.
	 *
	 * @param string $format 'pdfdraft' or 'gutenberg'.
	 *
	 * @return int
	 */
	protected function count_published_by_format( $format ) {
		global $wpdb;

		if ( 'pdfdraft' === $format ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_masteriyo_content_format' AND pm.meta_value = %s
					WHERE p.post_type = %s AND p.post_status = 'publish'",
					'pdfdraft',
					$this->post_type
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_masteriyo_content_format'
				WHERE p.post_type = %s AND p.post_status = 'publish'
				AND ( pm.meta_value IS NULL OR pm.meta_value != 'pdfdraft' )",
				$this->post_type
			)
		);
	}
}
