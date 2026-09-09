<?php
/**
 * Courses controller class.
 *
 * @since 1.0.0
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Utils;
use Masteriyo\Enums\CourseFlow;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\VideoSource;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseBillingPeriod;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\RestApi\Controllers\Version1\PostsController;
use Masteriyo\Jobs\CheckCourseEndDateJob;
use Masteriyo\AddonsFramework\Addons;
use DateTime;
use DateTimeZone;


class CoursesController extends PostsController {
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
	protected $rest_base = 'courses';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $object_type = 'course';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mto-course';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

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
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;

		add_action( 'masteriyo_after_trash_course', array( $this, 'update_enrollments_status_for_courses_deletion' ), 10, 2 );
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
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/preview-link',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preview_link' ),
					'permission_callback' => array( $this, 'get_preview_link_permissions_check' ),
					'args'                => array(
						'email' => array(
							'description' => __( 'Email of the registered user to preview as. Omit to use the demo student.', 'learning-management-system' ),
							'type'        => 'string',
							'format'      => 'email',
							'required'    => false,
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
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids'      => array(
							'required'    => true,
							'description' => __( 'Course IDs.', 'learning-management-system' ),
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
							'description' => __( 'Course Ids', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);

		// @since 2.5.7 Added clone endpoint to lessons REST API.
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
					'permission_callback' => array( $this, 'clone_item_permissions_check' ),
				),
			)
		);

		/**
		 * Registers the REST API routes for the Courses controller.
		 *
		 * This action hook allows other plugins or modules to register additional routes
		 * for the Courses controller.
		 *
		 * @since 1.11.0 [free]
		 *
		 * @param string $namespace The API namespace.
		 * @param string $rest_base The REST base.
		 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller The Courses controller instance.
		 */
		do_action( 'masteriyo_rest_api_register_course_routes', $this->namespace, $this->rest_base, $this );
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

		$params['slug'] = array(
			'description'       => __( 'Limit result set to courses with a specific slug.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to courses assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future', 'trash' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['featured'] = array(
			'description'       => __( 'Limit result set to featured courses.', 'learning-management-system' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'masteriyo_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['price'] = array(
			'description'       => __( 'List courses with specific price.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['price_type'] = array(
			'description'       => __( 'List courses with specific price type (free or paid).', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => CoursePriceType::all(),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category'] = array(
			'description'       => __( 'Limit result set to courses assigned a specific category ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tag'] = array(
			'description'       => __( 'Limit result set to courses assigned a specific tag ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['difficulty'] = array(
			'description'       => __( 'Limit result set to courses assigned a specific difficulty ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['access_mode'] = array(
			'description'       => __( 'List courses with specific access mode.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => CourseAccessMode::all(),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param  Model|WP_Post $object Model or WP_Post object.
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Post' ) ? $object->ID : $object->get_id();
			}

			$course = masteriyo( 'course' );
			$course->set_id( $id );
			$course_repo = masteriyo( 'course.store' );
			$course_repo->read( $course );
		} catch ( \Exception $e ) {
			return false;
		}

		return $course;
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
		$data    = $this->get_course_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * @since 1.0.0
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
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
	 * @since 1.0.0
	 *
	 * @param array $objects Courses data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Courses query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'         => $query_results['total'],
				'pages'         => $query_results['pages'],
				'current_page'  => $query_args['paged'],
				'per_page'      => $query_args['posts_per_page'],
				'courses_count' => $this->get_courses_count(),
			),
		);
	}

	/**
	 * Get courses count by status.
	 *
	 * @since 1.4.12
	 *
	 * @return Array
	 */
	protected function get_courses_count() {
		$post_count = parent::get_posts_count();

		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), PostStatus::all() ) );
	}

	/**
	 * Get course description data
	 *
	 * @since 1.7.3
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $context Request context.
	 *
	 * @return object
	 */
	protected function description_data( $course, $context ) {
		if ( 'view' === $context ) {
			return masteriyo_format_content_for_view( wp_kses_post( $course->get_description() ) );
		}
		return $course->get_description( $context );
	}

	/**
	 * Get course data.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Course $course Course instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_course_data( $course, $context = 'view' ) {
		$author = masteriyo_get_user( $course->get_author_id( $context ) );

		if ( is_wp_error( $author ) || is_null( $author ) ) {
			$author = null;
		} else {
			$author = array(
				'id'           => $author->get_id(),
				'display_name' => $author->get_display_name(),
				'avatar_url'   => $author->profile_image_url(),
			);
		}

		/**
		 * Filters short description of course.
		 *
		 * @since 1.0.0
		 *
		 * @param string $short_description Short description of course.
		 */
		$short_description = 'view' === $context ? apply_filters( 'masteriyo_short_description', $course->get_short_description() ) : $course->get_short_description();

		$data = array(
			'id'                                 => $course->get_id(),
			'name'                               => wp_specialchars_decode( $course->get_name( $context ) ),
			'slug'                               => $course->get_slug( $context ),
			'permalink'                          => $course->get_permalink(),
			'preview_permalink'                  => $course->get_preview_link(),
			'page_preview_permalink'             => (string) $course->get_preview_course_link(),
			'student_preview_permalink'          => masteriyo_current_user_can_student_preview()
				? $course->get_student_preview_link()
				: '',
			'student_preview_email'              => masteriyo_current_user_can_student_preview()
				? masteriyo_get_demo_student_email()
				: '',
			'status'                             => $course->get_status( $context ),
			'description'                        => $this->description_data( $course, $context ),
			'short_description'                  => $short_description,
			'reviews_allowed'                    => $course->get_reviews_allowed( $context ),
			'parent_id'                          => $course->get_parent_id( $context ),
			'menu_order'                         => $course->get_menu_order( $context ),
			'author'                             => $author,
			'date_created'                       => masteriyo_rest_prepare_date_response( $course->get_date_created( $context ) ),
			'date_modified'                      => masteriyo_rest_prepare_date_response( $course->get_date_modified( $context ) ),
			'featured'                           => $course->get_featured( $context ),
			'price'                              => $course->get_price( $context ),
			'formatted_price'                    => $course->get_rest_formatted_price( $context ),
			'formatted_regular_price'            => $course->get_rest_formatted_regular_price( $context ),
			'regular_price'                      => $course->get_regular_price( $context ),
			'sale_price'                         => $course->get_sale_price( $context ),
			'price_type'                         => $course->get_price_type( $context ),
			'featured_image'                     => $course->get_featured_image( $context ),
			'students_count'                     => masteriyo_count_enrolled_users( $course->get_id() ),
			'enrollment_limit'                   => $course->get_enrollment_limit( $context ),
			'duration'                           => $course->get_duration( $context ),
			'access_mode'                        => $course->get_access_mode( $context ),
			'show_curriculum'                    => $course->get_show_curriculum( $context ),
			'highlights'                         => $course->get_highlights( $context ),
			'edit_post_link'                     => $course->get_edit_post_link(),
			'categories'                         => $this->get_taxonomy_terms( $course, 'cat' ),
			'tags'                               => $this->get_taxonomy_terms( $course, 'tag' ),
			'difficulty'                         => $this->get_taxonomy_terms( $course, 'difficulty' ),
			'is_ai_created'                      => $course->get_is_ai_created( $context ),
			'is_creating'                        => $course->get_is_creating( $context ),
			'end_date'                           => ! empty( $course->get_end_date( $context ) ) ? masteriyo_rest_prepare_date_response( $course->get_end_date( $context ) ) : null,
			'course_start_date'                  => ! empty( $course->get_course_start_date( $context ) ) ? masteriyo_rest_prepare_date_response( $course->get_course_start_date( $context ) ) : null,
			'enrollment_opens_on'                => ! empty( $course->get_enrollment_opens_on( $context ) ) ? masteriyo_rest_prepare_date_response( $course->get_enrollment_opens_on( $context ) ) : null,
			'enrollment_closes_on'               => ! empty( $course->get_enrollment_closes_on( $context ) ) ? masteriyo_rest_prepare_date_response( $course->get_enrollment_closes_on( $context ) ) : null,
			'enable_course_retake'               => $course->get_enable_course_retake( $context ),
			'enable_cohort_mode'                 => $course->get_enable_cohort_mode( $context ),
			'enable_end_date'                    => $course->get_enable_end_date( $context ),

			// PRO: Subscription fields (when access_mode is recurring)
			'billing_period'                     => $course->get_billing_period( $context ),
			'billing_interval'                   => $course->get_billing_interval( $context ),
			'billing_expire_after'               => $course->get_billing_expire_after( $context ),

			// Pro.
			'featured_video_source'              => $course->get_featured_video_source( $context ),
			'featured_video_url'                 => $course->get_featured_video_url( $context ),
			'flow'                               => $course->get_flow( $context ),
			'enrollment_expiration_enabled'      => $course->get_enrollment_expiration_enabled( $context ),
			'enrollment_expiration_duration'     => $course->get_enrollment_expiration_duration( $context ),

			'review_after_course_completion'     => $course->get_review_after_course_completion( $context ),
			'disable_course_content'             => $course->get_disable_course_content( $context ),
			'welcome_message_to_first_time_user' => $course->get_welcome_message_to_first_time_user( $context ),
			'fake_enrolled_count'                => $course->get_fake_enrolled_count( $context ),
			'course_badge'                       => $course->get_course_badge( $context ),
			'custom_fields'                      => $course->get_custom_fields( $context ),

			// PDF download URL.
			'pdf_download_url'                   => masteriyo_generate_course_pdf_download_url( $course ),

			// Preview mode helpers
			'is_current_user_course_author'      => masteriyo_bool_to_string( masteriyo_is_current_user_post_author( $course->get_id() ) ),
		);

		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' ) || user_can( get_current_user_id(), 'edit_course', $course->get_id() ) ) {
			$data['post_password'] = $course->get_post_password( $context );
		}

		/**
		 * Filter course rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Course data.
		 * @param Masteriyo\Models\Course $course Course object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $course, $context, $this );
	}

	/**
	 * Get taxonomy terms.
	 *
	 * @since 1.0.0
	 *
	 * @param Course $course Course object.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array|null A list of terms for every taxonomy except 'difficulty', which yields a
	 *                    SINGLE term — or null when the course carries no difficulty term,
	 *                    because array_shift() on an empty array returns null.
	 */
	protected function get_taxonomy_terms( $course, $taxonomy = 'cat' ) {
		$terms = Utils::get_object_terms( $course->get_id(), 'course_' . $taxonomy );

		$terms = array_map(
			function ( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => wp_specialchars_decode( $term->name ),
					'slug' => $term->slug,
				);
			},
			$terms
		);

		$terms = 'difficulty' === $taxonomy ? array_shift( $terms ) : $terms;

		return $terms;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		// Taxonomy query to filter courses by category, tag and difficult
		$tax_query = array();

		// Map between taxonomy name and arg's key.
		$taxonomies = array(
			'course_cat'        => 'category',
			'course_tag'        => 'tag',
			'course_difficulty' => 'difficulty',
		);

		// Set tax_query for each passed arg.
		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $request[ $key ],
				);
			}
		}

		// Filter featured.
		if ( is_bool( $request['featured'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'course_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => true === $request['featured'] ? 'IN' : 'NOT IN',
			);
		}

		if ( $request['price_type'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'course_visibility',
				'field'    => 'name',
				'terms'    => $request['price_type'],
			);
		}

		// Build tax_query if taxonomies are set.
		if ( ! empty( $tax_query ) ) {
			if ( ! empty( $args['tax_query'] ) ) {
				$args['tax_query'] = array_merge( $tax_query, $args['tax_query'] ); // WPCS: slow query ok.
			} else {
				$args['tax_query'] = $tax_query; // WPCS: slow query ok.
			}
		}

		if ( isset( $request['price'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_price',
					'value'   => abs( $request['price'] ),
					'compare' => '=',
				),
			);
		}

		if ( $request['access_mode'] ) {
			$args['meta_query'][] = array(
				'key'     => '_access_mode',
				'value'   => $request['access_mode'],
				'compare' => '=',
			);
		}

		if ( masteriyo_is_current_user_admin() ) {
			unset( $args['author'] );
		}

		return $args;
	}

	/**
	 * Get the courses'schema, conforming to JSON Schema.
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
				'id'                             => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                           => array(
					'description' => __( 'Course name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'                           => array(
					'description' => __( 'Course slug', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'                      => array(
					'description' => __( 'Course URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'preview_permalink'              => array(
					'description' => __( 'Course Preview URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'page_preview_permalink'         => array(
					'description' => __( 'Preview URL for the single course page; resolves for every status except trash.', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'student_preview_permalink'      => array(
					'description' => __( 'Student preview magic link (admin/instructor only).', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'student_preview_email'          => array(
					'description' => __( 'Email of the demo student used for preview (admin/instructor only).', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'                   => array(
					'description' => __( "The date the course was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'               => array(
					'description' => __( 'The date the course was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'                  => array(
					'description' => __( "The date the course was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'              => array(
					'description' => __( 'The date the course was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                         => array(
					'description' => __( 'Course status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'featured'                       => array(
					'description' => __( 'Featured course.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'featured_video_source'          => array(
					'description' => __( 'Featured video source', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => VideoSource::SELF_HOSTED,
					'enum'        => VideoSource::all(),
				),
				'featured_video_url'             => array(
					'description' => __( 'Featured video URL or id if self hosted', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'catalog_visibility'             => array(
					'description' => __( 'Catalog visibility', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'                    => array(
					'description' => __( 'Course description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'short_description'              => array(
					'description' => __( 'Course short description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price'                          => array(
					'description' => __( 'Current course price.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'regular_price'                  => array(
					'description' => __( 'Course regular price.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sale_price'                     => array(
					'description' => __( 'Course sale price.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'price_type'                     => array(
					'description' => __( 'Course price type.', 'learning-management-system' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'reviews_allowed'                => array(
					'description' => __( 'Allow reviews.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'average_rating'                 => array(
					'description' => __( 'Reviews average rating.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'rating_count'                   => array(
					'description' => __( 'Amount of reviews that the course have.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'                      => array(
					'description' => __( 'Course parent ID.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'featured_image'                 => array(
					'description'       => __( 'Course featured image.', 'learning-management-system' ),
					'type'              => 'integer',
					'default'           => null,
					'validate_callback' => array( $this, 'validate_featured_image' ),
					'context'           => array( 'view', 'edit' ),
				),
				'categories'                     => array(
					'description' => __( 'List of categories.', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Category ID', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Category name', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Category slug', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tags'                           => array(
					'description' => __( 'List of tags', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Tag ID', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Tag name', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Tag slug', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'difficulty'                     => array(
					'description' => __( 'Course difficulty', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Difficulty ID.', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Difficulty name.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Difficulty slug.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'menu_order'                     => array(
					'description' => __( 'Menu order, used to custom sort courses.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'enrollment_limit'               => array(
					'description' => __( 'Course enrollment limit. Default unlimited.', 'learning-management-system' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'duration'                       => array(
					'description' => __( 'Course duration (minutes).', 'learning-management-system' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'access_mode'                    => array(
					'description' => __( 'Course access mode', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => CourseAccessMode::OPEN,
					'enum'        => CourseAccessMode::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'billing_cycle'                  => array(
					'description' => __( 'Course billing cycle (1d, 2w, 3m, 4y).', 'learning-management-system' ),
				),
				'billing_interval'               => array(
					'description' => __( 'Course billing interval.', 'learning-management-system' ),
					'type'        => 'integer',
					'default'     => 1,
					'min'         => 1,
					'max'         => 6,
					'context'     => array( 'view', 'edit' ),
				),
				'billing_period'                 => array(
					'description' => __( 'Course billing period.', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => CourseBillingPeriod::YEAR,
					'enum'        => CourseBillingPeriod::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'billing_expire_after'           => array(
					'description' => __( 'Course billing expire after.', 'learning-management-system' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'show_curriculum'                => array(
					'description' => __( 'Course show curriculum. ( True = Visible to all, False = Visible to only enrollees)', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),

				),
				'review_after_course_completion' => array(
					'description' => __( 'Course review after completion. ( True = Visible for user to review, False =Invisible for user to review)', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'highlights'                     => array(
					'description'       => __( 'Course highlights', 'learning-management-system' ),
					'type'              => 'string',
					'context'           => array( 'view', 'edit' ),
					'sanitize_callback' => 'wp_kses_post',
				),
				'wp_edit_link'                   => array(
					'description' => __( 'Course WordPress edit link.', 'learning-management-system' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'flow'                           => array(
					'description' => __( 'Course flow', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => CourseFlow::FREE_FLOW,
					'enum'        => CourseFlow::all(),
				),
				'students_count'                 => array(
					'description' => __( 'Enrolled students count', 'learning-management-system' ),
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'enrollment_expiration_enabled'  => array(
					'description' => __( 'Indicates if enrollment expiration is enabled for the course', 'learning-management-system' ),
					'type'        => 'boolean',
					'readonly'    => false,
					'context'     => array( 'view', 'edit' ),
				),
				'enrollment_expiration_duration' => array(
					'description' => __( 'Duration for which the enrollment is valid (in days)', 'learning-management-system' ),
					'type'        => 'integer',
					'readonly'    => false,
					'context'     => array( 'view', 'edit' ),
				),
				'end_date'                       => array(
					'description' => __( 'Course end date', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'course_start_date'              => array(
					'description' => __( 'Course start date', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'enrollment_opens_on'            => array(
					'description' => __( 'Enrollment opens on', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'enrollment_closes_on'           => array(
					'description' => __( 'Enrollment closes on', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'course_badge'                   => array(
					'description' => __( 'Course Badge', 'learning-management-system' ),
					'type'        => 'string',
				),
				'fake_enrolled_count'            => array(
					'description' => __( 'Fake enrolled Count. Default zero.', 'learning-management-system' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'custom_fields'                  => array(
					'description' => __( 'Custom fields', 'learning-management-system' ),
					'type'        => 'object',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'                      => array(
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
				'is_current_user_course_author'  => array(
					'description' => __( 'Whether the current user is the course author', 'learning-management-system' ),
					'type'        => 'string',
					'enum'        => array( 'yes', 'no' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single course for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Models\Course
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id     = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$course = masteriyo( 'course' );

		if ( 0 !== $id ) {
			$course->set_id( $id );
			$course_repo = masteriyo( \Masteriyo\Repository\CourseRepository::class );
			$course_repo->read( $course );
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			$course->set_name( sanitize_text_field( $request['name'] ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$course->set_description( wp_slash( wp_kses_post( $request['description'] ) ) );
		}

		// Post excerpt.
		if ( isset( $request['short_description'] ) ) {
			$course->set_short_description( wp_kses_post( $request['short_description'] ) );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$new_status     = get_post_status_object( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'draft';
			$current_status = $course->get_status();

			// Update  all the enrollments related with this course.
			$this->update_enrollments_status( $course, $current_status, $new_status );
			$course->set_status( $new_status );
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$course->set_slug( sanitize_title( $request['slug'] ) );
		}

		// Author/Instructor.
		if ( isset( $request['author_id'] ) ) {
			$course->set_author_id( $request['author_id'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$course->set_menu_order( $request['menu_order'] );
		}

		// Comment status.
		if ( isset( $request['reviews_allowed'] ) ) {
			$course->set_reviews_allowed( $request['reviews_allowed'] );
		}

		// Featured Course.
		if ( isset( $request['featured'] ) ) {
			$course->set_featured( $request['featured'] );
		}

		// Featured video source.
		if ( isset( $request['featured_video_source'] ) ) {
			$course->set_featured_video_source( $request['featured_video_source'] );
		}

		// Featured video source url.
		if ( isset( $request['featured_video_url'] ) ) {
			$course->set_featured_video_url( $request['featured_video_url'] );
		}

		// Regular Price.
		if ( isset( $request['regular_price'] ) ) {
			// An empty price must persist as '0', not ''. masteriyo_format_decimal( '' ) is '',
			// and an empty WC regular price means "not purchasable" rather than "free".
			$course->set_regular_price( '' === $request['regular_price'] ? '0' : $request['regular_price'] );
		}

		// Sale Price.
		if ( isset( $request['sale_price'] ) ) {
			$course->set_sale_price( $request['sale_price'] );
		}

		// Course parent ID.
		if ( isset( $request['parent_id'] ) ) {
			$course->set_parent_id( $request['parent_id'] );
		}

		// Course featured image.
		if ( isset( $request['featured_image'] ) ) {
			$course->set_featured_image( $request['featured_image'] );
		}

		// Course enrollment limit.
		if ( isset( $request['enrollment_limit'] ) ) {
			$course->set_enrollment_limit( $request['enrollment_limit'] );
		}

		// Course duration.
		if ( isset( $request['duration'] ) ) {
			$course->set_duration( $request['duration'] );
		}

		// Course access mode.
		if ( isset( $request['access_mode'] ) ) {
			$course->set_access_mode( $request['access_mode'] );
		}

		// Course billing cycle.
		if ( isset( $request['billing_period'] ) ) {
			$course->set_billing_period( $request['billing_period'] );
		}

		// Course billing interval.
		if ( isset( $request['billing_interval'] ) ) {
			$course->set_billing_interval( $request['billing_interval'] );
		}

		// Course billing expire after.
		if ( isset( $request['billing_expire_after'] ) ) {
			$course->set_billing_expire_after( $request['billing_expire_after'] );
		}

		// Course show curriculum.
		if ( isset( $request['show_curriculum'] ) ) {
			$course->set_show_curriculum( $request['show_curriculum'] );
		}

		// Review after completion enable/disable.
		if ( isset( $request['review_after_course_completion'] ) ) {
			$course->set_review_after_course_completion( $request['review_after_course_completion'] );
		}

		if ( isset( $request['disable_course_content'] ) ) {
			$course->set_disable_course_content( $request['disable_course_content'] );
		}

		// Course highlights.
		if ( isset( $request['highlights'] ) ) {
			$course->set_highlights( $request['highlights'] );
		}

		// Course enable_course_retake.
		if ( isset( $request['enable_course_retake'] ) ) {
			$course->set_enable_course_retake( $request['enable_course_retake'] );
		}

		// Course categories.
		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			$course = $this->save_taxonomy_terms( $course, $request['categories'] );
		}

		// Course tags.
		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			$course = $this->save_taxonomy_terms( $course, $request['tags'], 'tag' );
		}

		// Course difficulties.
		if ( isset( $request['difficulty'] ) && is_array( $request['difficulty'] ) ) {
			$course = $this->save_taxonomy_terms( $course, $request['difficulty'], 'difficulty' );
		}

		// Course end date.
		if ( isset( $request['end_date'] ) ) {
			$raw_end_date = $request['end_date'];
			$end_date     = ! empty( $raw_end_date )
			? ( new DateTime( $raw_end_date, new \DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:i:s\Z' )
			: '';

			$course->set_end_date( $end_date );

			if ( ! empty( $end_date ) ) {
				try {
					$dt        = new \DateTime( $end_date, new \DateTimeZone( 'UTC' ) );
					$timestamp = $dt->getTimestamp();

					as_schedule_single_action(
						$timestamp,
						CheckCourseEndDateJob::NAME,
						array( $course->get_id() ),
						'learning-management-system'
					);
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}
		}

		// Course start date.
		if ( isset( $request['course_start_date'] ) ) {
			$course_start_date = $request['course_start_date']
			? ( new \DateTime( $request['course_start_date'], new \DateTimeZone( 'UTC' ) ) )
			->format( 'Y-m-d\TH:i:s\Z' )
			: null;

			$course->set_course_start_date( $course_start_date );
		}

		// Enrollment start date and close date.
		if ( isset( $request['enrollment_opens_on'] ) ) {
			$enrollment_opens_on = $request['enrollment_opens_on']
			? ( new \DateTime( $request['enrollment_opens_on'], new \DateTimeZone( 'UTC' ) ) )
			->format( 'Y-m-d\TH:i:s\Z' )
			: null;

			$course->set_enrollment_opens_on( $enrollment_opens_on );
		}

		if ( isset( $request['enrollment_closes_on'] ) ) {
			$enrollment_closes_on = $request['enrollment_closes_on'] ?? null;
			$enrollment_closes_on = $enrollment_closes_on
			? ( new \DateTime( $enrollment_closes_on, new \DateTimeZone( 'UTC' ) ) )
			->format( 'Y-m-d\TH:i:s\Z' )
			: null;
			$course->set_enrollment_closes_on( $enrollment_closes_on );
		}

		// Course badge.
		if ( isset( $request['course_badge'] ) ) {
			$course->set_course_badge( sanitize_text_field( $request['course_badge'] ) );
		}

		// Set Post Password.
		if ( isset( $request['post_password'] ) ) {
			$course->set_post_password( sanitize_key( $request['post_password'] ) );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && $request['meta_data'] ) {
			foreach ( $request['meta_data'] as $meta ) {
				$course->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		// Course flow.
		if ( isset( $request['flow'] ) ) {
			$course->set_flow( $request['flow'] );
		}

		// Check if 'enrollment_expiration_enabled' is set in the request and update the course object accordingly.
		if ( isset( $request['enrollment_expiration_enabled'] ) ) {
			$course->set_enrollment_expiration_enabled( $request['enrollment_expiration_enabled'] );
		}

		// Check if 'enrollment_expiration_duration' is set in the request and update the course object accordingly.
		if ( isset( $request['enrollment_expiration_duration'] ) ) {
			$course->set_enrollment_expiration_duration( $request['enrollment_expiration_duration'] );
		}

		// Allow set welcome_message_to_first_time_user.
		if ( isset( $request['welcome_message_to_first_time_user'] ) ) {
			$course->set_welcome_message_to_first_time_user( $request['welcome_message_to_first_time_user'] );
		}

		// Course fake enrolled count.
		if ( isset( $request['fake_enrolled_count'] ) ) {
			$course->set_fake_enrolled_count( $request['fake_enrolled_count'] );
		}

		// Custom field values.
		if ( isset( $request['custom_fields'] ) ) {
			$course->set_custom_fields( $request['custom_fields'] );
		}

		if ( isset( $request['enable_cohort_mode'] ) ) {
			$course->set_enable_cohort_mode( $request['enable_cohort_mode'] );
		}

		if ( isset( $request['enable_end_date'] ) ) {
			$course->set_enable_end_date( $request['enable_end_date'] );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Models\Course $course  Course object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $course, $request, $creating );
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @since 1.0.0
	 *
	 * @param Course $course  Course instance.
	 * @param array  $terms    Terms data.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return Course
	 */
	protected function save_taxonomy_terms( $course, $terms, $taxonomy = 'cat' ) {
		$term_ids = 'difficulty' === $taxonomy ? array_values( $terms ) : wp_list_pluck( $terms, 'id' );

		if ( 'cat' === $taxonomy ) {
			$course->set_category_ids( $term_ids );
		} elseif ( 'tag' === $taxonomy ) {
			$course->set_tag_ids( $term_ids );
		} elseif ( 'difficulty' === $taxonomy ) {
			$course->set_difficulty_id( array_shift( $term_ids ) );
		}

		return $course;
	}

	/**
	 * Validate the existence of featured image.
	 * Since the featured image will use the WordPress attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param int $featured_image_id Featured image id.
	 * @return bool|WP_Error
	 */
	public function validate_featured_image( $featured_image_id ) {
		if ( ! is_numeric( $featured_image_id ) ) {
			return new \WP_Error( 'rest_invalid_type', 'featured image is not of type integer' );
		}

		$featured_image = get_post( absint( $featured_image_id ) );

		if ( $featured_image && 'attachment' !== $featured_image->post_type ) {
			return new \WP_Error( 'rest_invalid_featured_image', 'invalid featured image id' );
		}

		return true;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.0.0
	 *
	 * @param Model           $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = parent::prepare_links( $object, $request );

		$query = new \WP_Query(
			array(
				'post_type'      => array( 'mto-lesson', 'mto-quiz' ),
				'post_status'    => PostStatus::PUBLISH,
				'posts_per_page' => 1,
				'meta_key'       => '_course_id',
				'meta_compare'   => '=',
				'meta_value'     => $object->get_id(),
				'orderby'        => array(
					'parent'     => 'ASC',
					'menu_order' => 'ASC',
				),
			)
		);

		$links['first'] = array(
			'href' => ( 1 === $query->post_count ) ? $this->get_navigation_link( $query->posts[0] ) : '',
		);

		return $links;
	}

	/**
	 * Restore course.
	 *
	 * @since 1.4.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	/**
	 * Check permissions for GET /courses/{id}/preview-link.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function get_preview_link_permissions_check( $request ): bool {
		if ( ! masteriyo_current_user_can_student_preview() ) {
			return false;
		}
		// Instructors may only preview courses they authored.
		if ( masteriyo_is_current_user_instructor() && ! masteriyo_is_current_user_admin() ) {
			$course_id = (int) $request['id'];
			return (int) get_post_field( 'post_author', $course_id ) === get_current_user_id();
		}
		return true;
	}

	/**
	 * Generate a preview magic link, optionally for a specific user by email.
	 *
	 * GET /masteriyo/v1/courses/{id}/preview-link
	 * GET /masteriyo/v1/courses/{id}/preview-link?email=someone@example.com
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_preview_link( $request ) {
		$course_id = (int) $request['id'];
		$admin_id  = get_current_user_id();
		$email     = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( $email ) {
			$result = masteriyo_generate_preview_link_for_email( $course_id, $admin_id, $email );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} else {
			$course = masteriyo_get_course( $course_id );
			if ( ! $course ) {
				return new \WP_Error( 'masteriyo_rest_course_invalid_id', __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
			}
			$token  = masteriyo_generate_student_preview_token( $course_id, $admin_id );
			$result = array(
				'preview_url'   => add_query_arg( 'mto-student-preview', $token, $course->get_permalink() ),
				'preview_email' => masteriyo_get_demo_student_email(),
			);
		}

		return rest_ensure_response( $result );
	}

	public function restore_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		wp_untrash_post( $object->get_id() );
		$object->set_status( PostStatus::DRAFT );
		$object->save();

		do_action( 'masteriyo_course_restore', $request['id'], $object );

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
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 1.5.24
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

		$course = masteriyo_get_course( $request['id'] );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid Course ID.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( ( new Addons() )->is_active( 'multiple-instructors' ) ) {
			if ( masteriyo_is_instructor_or_additional_instructor( $request['id'] ) ) {
				return true;
			}
		}
		if ( ! user_can( get_current_user_id(), 'edit_course', $course->get_id() ) &&
		! in_array( $course->get_status(), array( PostStatus::PUBLISH, PostStatus::PVT ), true ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/*
		 * A private course is readable only by someone who may edit it or who is actually enrolled.
		 *
		 * The status guard above admits PostStatus::PVT because a private course is still a real
		 * course for its own students; the eligibility half of that has to live here, on its own,
		 * for two reasons. The open-access short-circuit immediately below returns true before any
		 * enrolment is consulted, and masteriyo_can_start_course() cannot supply the missing test
		 * either — it is unconditionally true for an open-access course
		 * (includes/Helper/course.php), before the user is looked at at all.
		 *
		 * This is the same policy the two other private-course gates enforce: the course page
		 * (includes/Masteriyo.php — access_denied when anonymous, not_enrolled when there is no
		 * UserCourse row and the user is not an admin or instructor) and the main query
		 * (FrontendQuery::include_private_courses_for_eligible_students).
		 */
		if ( PostStatus::PVT === $course->get_status()
			&& ! user_can( get_current_user_id(), 'edit_course', $course->get_id() )
			&& ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( CourseAccessMode::OPEN === $course->get_access_mode() ) {
			return true;
		}

		if ( masteriyo_course_has_previewable_lessons( $request['id'] ) ) {
			return true;
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
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
	 * Clone one item/post from the collection.
	 *
	 * @since 2.5.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function clone_item( $request ) {
		$response = parent::clone_item( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$new_course = get_post( masteriyo_array_get( $response->get_data(), 'id' ) );

		$new_course_id = $new_course->ID ?? 0;
		$sections      = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => PostType::SECTION,
				'post_status'    => PostStatus::all(),
				'post_parent'    => $request['id'],
			)
		);

		foreach ( $sections as $section ) {
			/**
			 * Fires before cloning course section.
			 *
			 * @since 2.5.7
			 *
			 * @param \WP_Post $child Section child.
			 * @param \WP_Request $request WP Request object.
			 * @param \WP_Response $response WP Response object.
			 */
			do_action( 'masteriyo_rest_pro_before_course_section_clone', $section, $request, $response );

			$new_section = $this->clone( $section->ID, array( 'post_parent' => $new_course->ID ) );

			// Update course ID of the new section to new course ID.
			update_post_meta( $new_section->ID, '_course_id', $new_course->ID );

			if ( PostType::SECTION === $new_section->post_type ) {
				$this->clone_section_children( $section, $new_section, $new_course );
			}

			/**
			 * Fires after cloning section children.
			 *
			 * @since 2.5.7
			 *
			 * @param \WP_Post|null $new_section
			 * @param \WP_Post $section Section child.
			 * @param \WP_Request $request WP Request object.
			 * @param \WP_Response $response WP Response object.
			 */
			do_action( 'masteriyo_rest_pro_after_section_children_clone', $new_section, $section, $request, $response );
		}

		do_action( 'masteriyo_rest_pro_before_course_clone_response', $new_course_id, $response );

		$new_course_object = masteriyo_get_course( $new_course_id );
		if ( $new_course_object ) {
			/**
			 * Fires after a course is duplicated.
			 *
			 * @param integer $new_course_id The new duplicated course ID.
			 * @param \Masteriyo\Models\Course $new_course_object The new duplicated course object.
			 */
			do_action( 'masteriyo_new_course', $new_course_id, $new_course_object );
		}

		return $response;
	}

	/**
	 * Clone section's children.
	 *
	 * @since 2.5.7
	 *
	 * @param \WP_Post $old_section Old section post
	 * @param \WP_Post $new_section New cloned section post.
	 * @param \WP_Post $new_course_id New course ID.
	 */
	protected function clone_section_children( $old_section, $new_section, $new_course ) {
		$children = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => SectionChildrenPostType::all(),
				'post_status'    => PostStatus::all(),
				'post_parent'    => $old_section->ID,
			)
		);

		foreach ( $children as $child ) {
			/**
			 * Fires before cloning section children.
			 *
			 * @since 2.5.7
			 *
			 * @param \WP_Post $child Section child.
			 */
			do_action( 'masteriyo_rest_pro_before_section_children_clone', $child );

			$new_child = $this->clone( $child->ID, array( 'post_parent' => $new_section->ID ) );

			// Update course ID of the new section to new course ID.
			update_post_meta( $new_child->ID, '_course_id', $new_course->ID );

			if ( PostType::QUIZ === $child->post_type ) {
				$this->clone_questions( $child, $new_child, $new_course );
			}

			/**
			 * Fires after cloning section children.
			 *
			 * @since 2.5.7
			 *
			 * @param \WP_Post|null $new_child
			 * @param \WP_Post $child Section child.
			 */
			do_action( 'masteriyo_rest_pro_after_section_children_clone', $new_child, $child );
		}
	}

	/**
	 * Clone questions.
	 *
	 * @since 2.5.7
	 *
	 * @param \WP_Post $old_quiz Old Quiz post
	 * @param \WP_Post $new_quiz New cloned quiz post.
	 * @param \WP_Post $new_course New cloned course.
	 */
	protected function clone_questions( $old_quiz, $new_quiz, $new_course ) {
		$questions = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => PostType::QUESTION,
				'post_status'    => PostStatus::all(),
				'post_parent'    => $old_quiz->ID,
			)
		);

		foreach ( $questions as $question ) {
			$new_question = $this->clone( $question->ID, array( 'post_parent' => $new_quiz->ID ) );

			// Update course ID of the new section to new course ID.
			update_post_meta( $new_question->ID, '_course_id', $new_course->ID );
		}
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.7.3
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param $current_status Current status.
	 * @param $new_status New status.
	 */
	private function update_enrollments_status( $course, $current_status, $new_status ) {
		global $wpdb;

		if ( ! $wpdb || ! $course ) {
			return;
		}

		if ( $current_status === $new_status ) {
			return;
		}

		// Determine enrollment status based on course status.
		// Keep enrollments active for both 'publish' and 'private' courses.
		$enrollment_status = ( PostStatus::PUBLISH === $new_status || 'private' === $new_status ) ? 'active' : 'inactive';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_user_items
            SET status = %s
            WHERE item_id = %d
            AND item_type = 'user_course'",
				$enrollment_status,
				$course->get_id()
			)
		); // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.7.3
	 *
	 * @param integer $id The course ID.
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	public function update_enrollments_status_for_courses_deletion( $id, $course ) {
		global $wpdb;

		if ( ! $wpdb || ! $id ) {
			return;
		}

		$new_status = 'inactive';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_user_items
            SET status = %s
            WHERE item_id = %d
            AND item_type = 'user_course'",
				$new_status,
				$id
			)
		); // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	}
}
