<?php

/**
 * Analytics controller.
 *
 * @since 1.6.7
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\DateTime;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\CourseProgressItemType;
use Masteriyo\Roles;
use WP_Error;

class AnalyticsController extends CrudController {


	/**
	 * Route base.
	 *
	 * @since 1.6.7
	 *
	 * @var string
	 */
	protected $rest_base = 'analytics';

	/**
	 * Permission class.
	 *
	 * @since 1.6.7
	 *
	 * @var Permission
	 */
	protected $permission;

	/**
	 * Object type.
	 *
	 * @since 1.6.7
	 *
	 * @var string
	 */
	protected $object_type = 'analytics';

	/**
	 * Constructor.
	 *
	 * @since 1.6.7
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;

	}

	/**
	 * Deletes sales-related cache keys.
	 *
	 * This function deletes the sales-related cache keys by clearing the caches
	 * for the 'analytics_sales_group' transient. It takes two parameters:
	 * - $id: The ID of the sales-related cache key.
	 * - $order: An instance of the \Masteriyo\Models\Order\Order class.
	 *
	 * @since 2.14.0
	 *
	 * @param mixed $id The ID of the sales-related cache key.
	 * @param \Masteriyo\Models\Order\Order $order An instance of the \Masteriyo\Models\Order\Order class.
	 *
	 * @return void
	 */
	public function delete_sales_related_cache_keys( $id, $order ) {
		if ( ! $id || ! ( $order instanceof \Masteriyo\Models\Order\Order ) ) {
			return;
		}

		masteriyo_transient_cache()->clear_caches( 'analytics_sales_group' );
	}

	/**
	 * Clear sales-related analytics caches when an order's status changes.
	 *
	 * Gateway-driven transitions (e.g. an offline order auto-set to on-hold) may
	 * not re-fire masteriyo_update_order with items attached, leaving the cached
	 * order counts stale. Clearing here keeps Total Orders in sync.
	 *
	 * @param int    $id    Order ID.
	 * @param string $from  Previous status.
	 * @param string $to    New status.
	 * @param mixed  $order Order object.
	 *
	 * @return void
	 */
	public function delete_sales_related_cache_keys_on_status( $id, $from, $to, $order ) {
		masteriyo_transient_cache()->clear_caches( 'analytics_sales_group' );
	}

	/**
	 * Deletes user courses related cache keys.
	 *
	 * This function deletes the user courses related cache keys by clearing the caches
	 * for the 'analytics_user_courses_group' transient. It takes two parameters:
	 * - $id: The ID of the user courses related cache key.
	 * - $user_course: An instance of the \Masteriyo\Models\UserCourse class.
	 *
	 * @since 2.14.0
	 *
	 * @param mixed $id The ID of the user courses related cache key.
	 * @param \Masteriyo\Models\UserCourse $user_course An instance of the \Masteriyo\Models\UserCourse class.
	 *
	 * @return void
	 */
	/**
	 * Clears the cached course ID list used by analytics queries.
	 * Fires when any course is created, updated, or trashed.
	 */
	public function delete_courses_list_cache() {
		masteriyo_transient_cache()->clear_caches( 'analytics_courses_list_group' );
	}

	/**
	 * Clear the analytics courses-list cache when a course post is permanently
	 * deleted or restored via any path (including wp_delete_post used by bulk
	 * "delete permanently" and sample-course cleanup).
	 *
	 * @param int          $post_id Post ID.
	 * @param \WP_Post|null $post   Post object (passed by before_delete_post).
	 *
	 * @return void
	 */
	public function maybe_delete_courses_list_cache( $post_id, $post = null ) {
		$type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );

		if ( PostType::COURSE === $type ) {
			$this->delete_courses_list_cache();
		}
	}

	public function delete_user_courses_related_cache_keys( $id, $user_course ) {
		if ( ! $id || ! ( $user_course instanceof \Masteriyo\Models\UserCourse ) ) {
			return;
		}

		masteriyo_transient_cache()->clear_caches( 'analytics_user_courses_group' );
	}

	/**
	 * Generates an array of cache keys for analytics data based on the provided user ID, start date, and end date.
	 *
	 * @since 2.14.0
	 *
	 * @param int|null $user_id The ID of the user (default: null)
	 * @param string|null $start_date The start date for the analytics data (default: null)
	 * @param string|null $end_date The end date for the analytics data (default: null)
	 *
	 * @return array An array of cache keys for analytics data
	 */
	private function analytics_cache_keys( $user_id = null, $start_date = null, $end_date = null, $course_ids = array(), $scope_key = '' ) {
		$is_admin_or_manager  = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$current_user_id      = $user_id ? $user_id : get_current_user_id();
		$start_date_formatted = $start_date ? gmdate( 'Y-m-d', strtotime( $start_date ) ) : 'no_start_date';
		$end_date_formatted   = $end_date ? gmdate( 'Y-m-d', strtotime( $end_date ) ) : 'no_end_date';
		$course_ids           = array_filter( array_map( 'absint', (array) $course_ids ) );
		sort( $course_ids );
		$course_scope = empty( $course_ids ) ? '' : '_courses_' . md5( implode( ',', $course_ids ) );
		$scope_key    = $scope_key ? '_' . sanitize_key( $scope_key ) : '';
		$version      = '_v4';

		return array(
			'sales_data'                => 'analytics_sales_data_' . ( $is_admin_or_manager ? 'all_' : $current_user_id . '_' ) . $start_date_formatted . '_' . $end_date_formatted . $course_scope . $scope_key . $version,
			'enrolled_courses_data'     => 'analytics_enrolled_courses_data_' . ( $is_admin_or_manager ? 'all_' : $current_user_id . '_' ) . $start_date_formatted . '_' . $end_date_formatted . $course_scope . $scope_key . $version,
			'total_earnings'            => 'analytics_total_earnings_' . ( $is_admin_or_manager ? 'all' : $current_user_id ) . $course_scope . $scope_key . $version,
			'total_refunds'             => 'analytics_total_refunds_' . ( $is_admin_or_manager ? 'all' : $current_user_id ) . $course_scope . $scope_key . $version,
			'total_discounts'           => 'analytics_total_discounts_' . ( $is_admin_or_manager ? 'all' : $current_user_id ) . $course_scope . $scope_key . $version,
			'total_discounts_completed' => 'analytics_total_discounts_completed_' . ( $is_admin_or_manager ? 'all' : $current_user_id ) . $course_scope . $scope_key . $version,
			'total_discounts_refunded'  => 'analytics_total_discounts_refunded_' . ( $is_admin_or_manager ? 'all' : $current_user_id ) . $course_scope . $scope_key . $version,
		);
	}

	/**
	 * Build a SQL IN constraint for a course-ID column.
	 *
	 * For small lists (≤200 IDs) this returns a literal IN(%d,%d,...) clause,
	 * which is fast and simple. For large lists it returns a correlated subquery
	 * against wp_posts so MySQL can plan the join without a 200+ literal list
	 * (avoids the query-planner's range-scan pessimism and reduces PHP memory).
	 *
	 * Usage:
	 *   [ 'sql' => ' AND item_id IN (...)', 'params' => [...] ]
	 *   — append sql to the query string, merge params into prepare() args.
	 *
	 * Public because a listener on `masteriyo_analytics_timeseries_data` builds a
	 * series of its own and must scope it exactly as this controller does; the
	 * alternative is a second copy of the 200-ID subquery heuristic, which would
	 * diverge. Generic infrastructure, not a capability — same reason
	 * `get_total_amount()` is public for `CourseBundleAddon`.
	 *
	 * @param array  $course_ids  Sanitised list of course post IDs.
	 * @param string $column      Column name to constrain (default 'item_id').
	 *
	 * @return array{ sql: string, params: array }
	 */
	public function build_course_ids_sql( array $course_ids, string $column = 'item_id' ): array {
		if ( empty( $course_ids ) ) {
			return array(
				'sql'    => '',
				'params' => array(),
			);
		}

		if ( count( $course_ids ) <= 200 ) {
			$placeholders = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			return array(
				'sql'    => " AND {$column} IN ({$placeholders})",
				'params' => array_values( $course_ids ),
			);
		}

		// Large site: use a subquery — MySQL converts this to a semi-join and can
		// leverage the wp_posts (post_type, post_status[, post_author]) indexes.
		global $wpdb;
		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();

		if ( $is_admin_or_manager ) {
			return array(
				'sql'    => " AND {$column} IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s)",
				'params' => array( PostType::COURSE, PostStatus::PUBLISH ),
			);
		}

		return array(
			'sql'    => " AND {$column} IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_author = %d)",
			'params' => array( PostType::COURSE, PostStatus::PUBLISH, get_current_user_id() ),
		);
	}

	/**
	 * Register routes.
	 *
	 * @since 1.6.7
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
					'args'                => array(
						'start_date'      => array(
							'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'learning-management-system' ),
							'type'              => 'string',
							'format'            => 'date-time',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'end_date'        => array(
							'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'learning-management-system' ),
							'type'              => 'string',
							'format'            => 'date-time',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'course_id'       => array(
							'description'       => __( 'Filter analytics data by a specific course ID.', 'learning-management-system' ),
							'type'              => 'integer',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'absint',
						),
						'bundle_id'       => array(
							'description'       => __( 'Filter analytics data by a specific course bundle ID.', 'learning-management-system' ),
							'type'              => 'integer',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'absint',
						),
						'analytics_scope' => array(
							'description'       => __( 'Filter analytics data by all, courses, or bundles.', 'learning-management-system' ),
							'type'              => 'string',
							'enum'              => array( 'all', 'courses', 'bundles' ),
							'default'           => 'all',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		/**
		 * Register analytics preferences route.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preferences',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preferences' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_preferences' ),
					'permission_callback' => array( $this, 'save_preferences_permissions_check' ),
					'args'                => array(
						'summary'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'visualization' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'tables'        => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
			)
		);

		// ── Section endpoints ────────────────────────────────────────────────
		// Clients should fire all three in parallel for progressive rendering.
		// /counts   → scope-only counts + prefs  (~50 ms, no date params needed)
		// /summary  → date-scoped metric-card comparisons + sales/enrollment (~200 ms)
		// /timeseries → all chart series + tables (~600 ms)
		// The legacy GET /analytics endpoint remains for backward compatibility.

		$scope_args = array(
			'course_id'       => array(
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'absint',
			),
			'bundle_id'       => array(
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'absint',
			),
			'analytics_scope' => array(
				'type'              => 'string',
				'enum'              => array( 'all', 'courses', 'bundles' ),
				'default'           => 'all',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_key',
			),
		);

		$date_args = array_merge(
			$scope_args,
			array(
				'start_date' => array(
					'type'              => 'string',
					'format'            => 'date-time',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'end_date'   => array(
					'type'              => 'string',
					'format'            => 'date-time',
					'validate_callback' => 'rest_validate_request_arg',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/counts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_counts' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $scope_args,
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/summary',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_summary_section' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $date_args,
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/timeseries',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_timeseries_section' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $date_args,
				),
			)
		);

		/**
		 *
		 * Register courses analytics route.
		 *
		 * @since 2.14.4
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/courses_analytics',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_courses_analytics' ),
					'permission_callback' => array( $this, 'get_courses_analytics_permissions_check' ),
					'args'                => parent::get_collection_params(),
				),
			)
		);
	}

	/**
	 * Prepare courses analytics data.
	 *
	 * @since 2.14.4
	 *
	 * @param object $request.
	 *
	 * @return array
	 */
	public function get_courses_analytics( $request ) {
		$query_args = parent::prepare_objects_query( $request );

		$query_args['post_type']           = PostType::COURSE;
		$query_args['post_status']         = PostStatus::PUBLISH;
		$query_args['orderby']             = 'date';
		$query_args['post_parent__in']     = array();
		$query_args['post_parent__not_in'] = array();

		$courses_data = $this->get_courses_data();

		$total_posts = $courses_data['total'];
		$course_ids  = $courses_data['ids'];

		$posts_per_page = (int) $query_args['posts_per_page'];
		$current_page   = (int) $query_args['paged'];
		$pages          = (int) ceil( $total_posts / $posts_per_page );

		$offset = ( $current_page - 1 ) * $posts_per_page;

		$limited_course_ids = array_slice( $course_ids, $offset, $posts_per_page );

		$data = array();

		foreach ( $limited_course_ids as $course_id ) {
			$course_data = $this->get_courses_analytics_data( $course_id, $request );
			if ( null !== $course_data ) { // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
					$data[] = $course_data;
			}
		}

		return array(
			'data' => $data,
			'meta' => array(
				'total'        => $total_posts,
				'pages'        => $pages,
				'current_page' => $current_page,
				'per_page'     => $posts_per_page,
			),
		);
	}

	/**
	 * Prepare course analytics data according to course id.
	 *
	 * @since 2.14.4
	 *
	 * @param int $course_id.
	 * @param WP_REST_Request $request.
	 *
	 * @return array
	 */
	public function get_courses_analytics_data( $course_id, $request ) {

		$course = masteriyo_get_course( $course_id );
		$search = isset( $request['search'] ) ? esc_attr( $request['search'] ) : '';

		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return new WP_Error( 'invalid_course', __( 'Invalid course data.', 'learning-management-system' ) );
		}

		$course_name = $course->get_title();

		if ( ! empty( $search ) && stripos( $course_name, $search ) === false ) {
			return null;
		}

		$context = 'view';

		$sections_count = $this->get_sections_count( $course->get_id() );
		$quizzes_count  = $this->get_quizzes_data( $course->get_id() );
		$lessons_count  = $this->get_lessons_data( $course->get_id() );
		$earnings       = $this->get_total_earnings_data( $course->get_id() );
		$sections_count = $sections_count['total'];
		$quizzes_count  = $quizzes_count['total'];
		$lessons_count  = $lessons_count['total'];
		$author         = masteriyo_get_user( $course->get_author_id( $context ) );

		$featured       = $course->get_featured( $context );
		$students_count = masteriyo_count_enrolled_users( $course->get_id() );

		if ( is_wp_error( $author ) || is_null( $author ) ) {
			$author = null;
		} else {
			$author = array(
				'id'           => $author->get_id(),
				'display_name' => $author->get_display_name(),
				'avatar_url'   => $author->profile_image_url(),
			);
		}

		$data = array(
			'id'             => $course->get_id(),
			'name'           => $course->get_title(),
			'sections_count' => $sections_count,
			'lessons_count'  => $lessons_count,
			'quizzes_count'  => $quizzes_count,
			'earnings'       => $earnings,
			'author'         => $author,
			'featured'       => $featured,
			'students_count' => $students_count,
		);

		/**
		 * Filter course rest response data.
		 *
		 * @since 2.14.4
		 *
		 * @param array $data Course data.
		 * @param Masteriyo\Models\Course $course Course object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\AnalyticsController $controller REST courses controller object.
		 */
		return apply_filters( 'masteriyo_courses_analytics_data', $data, $course, $context, $this );
	}

	/**
	 * Get a collection of courses data.
	 *
	 * @since 1.6.7
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$items    = $this->prepare_items_for_response( $request );
		$response = rest_ensure_response( $items );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.6.7
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param array             $items Analytics data.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $items, $request );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.6.7
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' ) || current_user_can( 'edit_courses' );
	}

	/**
	 * Check if a given request has access to save analytics dashboard preferences.
	 *
	 * @param  \WP_REST_Request $_request Full details about the request.
	 * @return true|\WP_Error
	 */
	public function save_preferences_permissions_check( \WP_REST_Request $_request ) {
		return current_user_can( 'edit_masteriyo_courses' ) || current_user_can( 'manage_masteriyo_settings' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Check if a given request has access to read items for courses analytics.
	 *
	 * @since 2.14.4
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_courses_analytics_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' ) || current_user_can( 'edit_courses' );
	}

	// ── Section endpoint callbacks ───────────────────────────────────────────

	/**
	 * GET /analytics/counts
	 * Scope-aware static counts + user preferences. No date params needed.
	 * Typically resolves in ~50 ms — clients should render metric cards immediately.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_counts( \WP_REST_Request $request ) {
		$s               = $this->resolve_scope( $request );
		$course_ids      = $s['course_ids'];
		$is_course_scope = $s['is_course_scope'];
		$is_bundle_scope = $s['is_bundle_scope'];

		if ( $s['is_bundle_scope'] ) {
			$courses_total = 0;
		} else {
			// resolve_scope() already called get_courses_data() (cached), so count() is free.
			$courses_total = count( $course_ids );
		}

		$items = array(
			'courses'             => array( 'total' => $courses_total ),
			'lessons'             => $this->get_lessons_data( $course_ids ),
			'quizzes'             => $this->get_quizzes_data( $course_ids ),
			'questions'           => $this->get_questions_data( $course_ids ),
			'questions_answers'   => $this->get_questions_answers_data( $course_ids ),
			'reviews'             => $this->get_reviews_data( $course_ids ),
			'instructors'         => $this->get_instructors_data(),
			'total_students'      => $is_bundle_scope ? array( 'total' => 0 ) : $this->get_students_data( $is_course_scope ? $course_ids : array() ),
			'total_orders'        => $is_bundle_scope ? array( 'total' => 0 ) : array( 'total' => $this->get_total_orders_count( $course_ids, $is_course_scope ) ),
			'net_revenue'         => $is_bundle_scope ? array( 'total' => 0 ) : array( 'total' => $this->get_net_revenue( $course_ids, $is_course_scope ) ),
			'average_order_value' => $is_bundle_scope ? array( 'total' => 0 ) : array( 'total' => $this->get_average_order_value_metric( $course_ids, $is_course_scope ) ),
			'total_sections'      => array( 'total' => $this->get_sections_count( $course_ids ) ),
			'is_admin'            => masteriyo_is_current_user_admin(),
		);

		$user_id              = get_current_user_id();
		$saved_prefs          = get_user_meta( $user_id, 'masteriyo_analytics_dashboard_preferences', true );
		$items['preferences'] = is_array( $saved_prefs ) ? $saved_prefs : $this->get_default_preferences();

		/**
		 * Filters analytics counts section data.
		 *
		 * @param array            $items      Counts data.
		 * @param \WP_REST_Request $request    Request object.
		 * @param array            $course_ids Scoped course IDs.
		 */
		$items = apply_filters( 'masteriyo_analytics_counts_data', $items, $request, $course_ids );

		return rest_ensure_response( $items );
	}

	/**
	 * GET /analytics/summary
	 * Date-scoped metric-card comparison data (earnings, orders, enrollments)
	 * plus the enrollment and sales time-series used by the primary charts.
	 * Typically resolves in ~200 ms.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_summary_section( \WP_REST_Request $request ) {
		$s               = $this->resolve_scope( $request );
		$course_ids      = $s['course_ids'];
		$start_date      = $s['start_date'];
		$end_date        = $s['end_date'];
		$is_course_scope = $s['is_course_scope'];
		$is_bundle_scope = $s['is_bundle_scope'];
		$sales_ids       = $is_bundle_scope ? array() : $course_ids;

		$items = array(
			'user_courses' => $this->get_enrolled_courses_data( $course_ids, $start_date, $end_date ),
			'sales'        => $this->get_sales_data( $sales_ids, $start_date, $end_date, $is_course_scope || $is_bundle_scope, $request ),
		);

		if ( ! $is_bundle_scope ) {
			$prev             = $this->get_previous_period_dates( $start_date, $end_date );
			$items['summary'] = array(
				'earnings'  => array(
					'total'    => $this->get_total_amount_for_range( $course_ids, OrderStatus::COMPLETED, $start_date, $end_date, '_conversion_total', '_total', $is_course_scope ),
					'previous' => $this->get_total_amount_for_range( $course_ids, OrderStatus::COMPLETED, $prev['start'], $prev['end'], '_conversion_total', '_total', $is_course_scope ),
				),
				'refunds'   => array(
					'total'    => $this->get_total_amount_for_range( $course_ids, OrderStatus::REFUNDED, $start_date, $end_date, '_conversion_total', '_total', $is_course_scope ),
					'previous' => $this->get_total_amount_for_range( $course_ids, OrderStatus::REFUNDED, $prev['start'], $prev['end'], '_conversion_total', '_total', $is_course_scope ),
				),
				'discounts' => array(
					'total'    => $this->get_discounts_for_range( $course_ids, $start_date, $end_date, $is_course_scope ),
					'previous' => $this->get_discounts_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope ),
				),
				'orders'    => array(
					'total'              => $this->get_total_orders_for_range( $course_ids, $start_date, $end_date, $is_course_scope ),
					'previous'           => $this->get_total_orders_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope ),
					'completed'          => $this->get_total_orders_for_range( $course_ids, $start_date, $end_date, $is_course_scope, array( OrderStatus::COMPLETED ) ),
					'completed_previous' => $this->get_total_orders_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope, array( OrderStatus::COMPLETED ) ),
				),
				'enrolled'  => array(
					'total'    => $this->get_enrolled_count_for_range( $course_ids, $start_date, $end_date ),
					'previous' => $this->get_enrolled_count_for_range( $course_ids, $prev['start'], $prev['end'] ),
				),
			);
		} else {
			$items['summary'] = array();
		}

		/**
		 * Filters analytics summary section data.
		 *
		 * @param array            $items      Summary data.
		 * @param \WP_REST_Request $request    Request object.
		 * @param array            $course_ids Scoped course IDs.
		 */
		$items = apply_filters( 'masteriyo_analytics_summary_section_data', $items, $request, $course_ids );

		return rest_ensure_response( $items );
	}

	/**
	 * GET /analytics/timeseries
	 * All activity/completion chart series plus table data (popular courses,
	 * recent reviews, new students). Slowest section — ~400–800 ms uncached.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_timeseries_section( \WP_REST_Request $request ) {
		$s          = $this->resolve_scope( $request );
		$course_ids = $s['course_ids'];
		$start_date = $s['start_date'];
		$end_date   = $s['end_date'];

		// `reviews_time_series`, `qa_time_series` and `popular_courses` are pro's —
		// the Engagement and Popular Courses charts. Core emits the payload without
		// them; `pro/Analytics/EngagementPopularSeries.php` adds them back through
		// the timeseries filter below, so the free product draws a locked upsell in
		// each chart's place as free 2.3.2 does. The queries stay public on this
		// controller and are called back from there — see that class for why.
		$items = array(
			'quiz_attempts'   => array( 'data' => $this->get_quiz_attempts_data( $course_ids, $start_date, $end_date ) ),
			'recent_reviews'  => $this->get_recent_reviews_data( 5, $course_ids ),
			'new_students'    => $this->get_newly_registered_students( 5, $course_ids ),
			'new_instructors' => $this->get_newly_registered_instructors( 5, $course_ids ),
		);

		$subs = $this->get_subscriptions_time_series_data( $course_ids, $start_date, $end_date );
		if ( $subs ) {
			$items['subscriptions'] = $subs;
		}

		/**
		 * Filters analytics timeseries section data.
		 *
		 * Carries the resolved scope and the controller itself, so a listener can
		 * compute a series of its own without re-deriving the date range or
		 * duplicating `format_series_data()` / `build_course_ids_sql()`. This is the
		 * shape `masteriyo_sales_data` already has, and it is the seam through which
		 * the completion series reach this payload — they are pro's, and a shared
		 * file may not compute them.
		 *
		 * @param array            $items      Timeseries data.
		 * @param \WP_REST_Request $request    Request object.
		 * @param array            $course_ids Scoped course IDs.
		 * @param string|null      $start_date Range start, Y-m-d.
		 * @param string|null      $end_date   Range end, Y-m-d.
		 * @param AnalyticsController $controller This controller.
		 */
		$items = apply_filters( 'masteriyo_analytics_timeseries_data', $items, $request, $course_ids, $start_date, $end_date, $this );

		return rest_ensure_response( $items );
	}

	// ── End section endpoint callbacks ───────────────────────────────────────

	/**
	 * Extract scope params from a request — course_ids, flags, normalised dates.
	 * Used by all three section endpoints to avoid repeating this logic.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array{
	 *   start_date: string|null,
	 *   end_date: string|null,
	 *   course_ids: array,
	 *   is_course_scope: bool,
	 *   is_bundle_scope: bool
	 * }
	 */
	private function resolve_scope( \WP_REST_Request $request ): array {
		$start_date      = masteriyo_analytics_normalize_datetime( $request->get_param( 'start_date' ), 'start' );
		$end_date        = masteriyo_analytics_normalize_datetime( $request->get_param( 'end_date' ), 'end' );
		$course_id       = $request->get_param( 'course_id' );
		$bundle_id       = $request->get_param( 'bundle_id' );
		$analytics_scope = $request->get_param( 'analytics_scope' ) ? $request->get_param( 'analytics_scope' ) : 'all';
		$is_course_scope = ! empty( $course_id ) || ( 'courses' === $analytics_scope && empty( $bundle_id ) );
		$is_bundle_scope = ! empty( $bundle_id ) || ( 'bundles' === $analytics_scope && empty( $course_id ) );

		if ( $course_id ) {
			$course_ids = array( absint( $course_id ) );
		} elseif ( $is_bundle_scope ) {
			$course_ids = array();
		} else {
			$course_ids = $this->get_courses_data()['ids'];
		}

		return compact( 'start_date', 'end_date', 'course_ids', 'is_course_scope', 'is_bundle_scope' );
	}

	protected function prepare_items_for_response( \WP_REST_Request $request ) {
		$start_date      = masteriyo_analytics_normalize_datetime( $request->get_param( 'start_date' ), 'start' );
		$end_date        = masteriyo_analytics_normalize_datetime( $request->get_param( 'end_date' ), 'end' );
		$course_id       = $request->get_param( 'course_id' );
		$bundle_id       = $request->get_param( 'bundle_id' );
		$analytics_scope = $request->get_param( 'analytics_scope' ) ? $request->get_param( 'analytics_scope' ) : 'all';
		$is_course_scope = ! empty( $course_id ) || ( 'courses' === $analytics_scope && empty( $bundle_id ) );
		$is_bundle_scope = ! empty( $bundle_id ) || ( 'bundles' === $analytics_scope && empty( $course_id ) );
		$items           = array();

		if ( $course_id ) {
			$course_ids       = array( absint( $course_id ) );
			$items['courses'] = array( 'total' => 1 );
		} elseif ( $is_bundle_scope ) {
			$course_ids       = array();
			$items['courses'] = array( 'total' => 0 );
		} else {
			$courses_data     = $this->get_courses_data();
			$course_ids       = $courses_data['ids'];
			$items['courses'] = array( 'total' => $this->get_courses_count( $course_ids, $start_date, $end_date ) );
		}

		// These six are scoped to the requested range, as they were before the
		// analytics rewrite. Without the dates the date picker moved the sales and
		// enrolment tiles while the content tiles silently stayed all-time.
		$items['lessons']           = $this->get_lessons_data( $course_ids, $start_date, $end_date );
		$items['quizzes']           = $this->get_quizzes_data( $course_ids, $start_date, $end_date );
		$items['questions']         = $this->get_questions_data( $course_ids, $start_date, $end_date );
		$items['questions_answers'] = $this->get_questions_answers_data( $course_ids, $start_date, $end_date );
		$items['reviews']           = $this->get_reviews_data( $course_ids, $start_date, $end_date );
		$items['instructors']       = $this->get_instructors_data( $start_date, $end_date );
		$items['total_students']    = $is_bundle_scope ? array( 'total' => 0 ) : $this->get_students_data( $is_course_scope ? $course_ids : array() );
		$items['user_courses']      = $this->get_enrolled_courses_data( $course_ids, $start_date, $end_date );
		$sales_course_ids           = $is_bundle_scope ? array() : $course_ids;
		$items['sales']             = $this->get_sales_data( $sales_course_ids, $start_date, $end_date, $is_course_scope || $is_bundle_scope, $request );
		// `popular_courses` is pro's — added by EngagementPopularSeries through the
		// masteriyo_analytics_summary_data filter below, so free omits it.
		$items['recent_reviews']  = $this->get_recent_reviews_data( 5, $course_ids );
		$items['new_students']    = $this->get_newly_registered_students( 5, $course_ids );
		$items['new_instructors'] = $this->get_newly_registered_instructors( 5, $course_ids );
		$items['is_admin']        = masteriyo_is_current_user_admin();

		// Previous-period comparison summary for metric cards. Skipped in bundle scope —
		// the masteriyo_analytics_summary_data filter (CourseBundleAddon) populates it.
		if ( ! $is_bundle_scope ) {
			$prev             = $this->get_previous_period_dates( $start_date, $end_date );
			$items['summary'] = array(
				'earnings'  => array(
					'total'    => $this->get_total_amount_for_range( $course_ids, OrderStatus::COMPLETED, $start_date, $end_date, '_conversion_total', '_total', $is_course_scope ),
					'previous' => $this->get_total_amount_for_range( $course_ids, OrderStatus::COMPLETED, $prev['start'], $prev['end'], '_conversion_total', '_total', $is_course_scope ),
				),
				'refunds'   => array(
					'total'    => $this->get_total_amount_for_range( $course_ids, OrderStatus::REFUNDED, $start_date, $end_date, '_conversion_total', '_total', $is_course_scope ),
					'previous' => $this->get_total_amount_for_range( $course_ids, OrderStatus::REFUNDED, $prev['start'], $prev['end'], '_conversion_total', '_total', $is_course_scope ),
				),
				'discounts' => array(
					'total'    => $this->get_discounts_for_range( $course_ids, $start_date, $end_date, $is_course_scope ),
					'previous' => $this->get_discounts_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope ),
				),
				'orders'    => array(
					'total'              => $this->get_total_orders_for_range( $course_ids, $start_date, $end_date, $is_course_scope ),
					'previous'           => $this->get_total_orders_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope ),
					'completed'          => $this->get_total_orders_for_range( $course_ids, $start_date, $end_date, $is_course_scope, array( OrderStatus::COMPLETED ) ),
					'completed_previous' => $this->get_total_orders_for_range( $course_ids, $prev['start'], $prev['end'], $is_course_scope, array( OrderStatus::COMPLETED ) ),
				),
				'enrolled'  => array(
					'total'    => $this->get_enrolled_count_for_range( $course_ids, $start_date, $end_date ),
					'previous' => $this->get_enrolled_count_for_range( $course_ids, $prev['start'], $prev['end'] ),
				),
			);
		} else {
			$items['summary'] = array();
		}

		// New static count metrics.
		$items['total_orders']        = array( 'total' => $this->get_total_orders_count( $course_ids, $is_course_scope ) );
		$items['net_revenue']         = array( 'total' => $this->get_net_revenue( $course_ids, $is_course_scope ) );
		$items['average_order_value'] = array( 'total' => $this->get_average_order_value_metric( $course_ids, $is_course_scope ) );
		$items['total_sections']      = array( 'total' => $this->get_sections_count( $course_ids ) );

		// Time-series data for expanded chart groups. `reviews_time_series` and
		// `qa_time_series` (the Engagement chart) are pro's — EngagementPopularSeries
		// adds them through the masteriyo_analytics_summary_data filter below.
		$items['quiz_attempts'] = array( 'data' => $this->get_quiz_attempts_data( $course_ids, $start_date, $end_date ) );

		$subs = $this->get_subscriptions_time_series_data( $course_ids, $start_date, $end_date );
		if ( $subs ) {
			$items['subscriptions'] = $subs;
		}

		/**
		 * Filters analytics items allowing addons to inject their own metric data.
		 *
		 * Carries the resolved scope and the controller itself, for the reason given
		 * on `masteriyo_analytics_timeseries_data` above — this endpoint is the
		 * single-request form of the same payload, so a listener that fills one must
		 * be able to fill the other from the same arguments.
		 *
		 * @param array            $items      Analytics items data.
		 * @param \WP_REST_Request $request    Request object.
		 * @param array            $course_ids Scoped course IDs.
		 * @param string|null      $start_date Range start, Y-m-d.
		 * @param string|null      $end_date   Range end, Y-m-d.
		 * @param AnalyticsController $controller This controller.
		 */
		$items = apply_filters( 'masteriyo_analytics_summary_data', $items, $request, $course_ids, $start_date, $end_date, $this );

		$user_id              = get_current_user_id();
		$saved_prefs          = get_user_meta( $user_id, 'masteriyo_analytics_dashboard_preferences', true );
		$items['preferences'] = is_array( $saved_prefs ) ? $saved_prefs : $this->get_default_preferences();

		/**
		 * Filters rest prepared analytics items.
		 *
		 * @since 1.6.7
		 *
		 * @param array $items Items data.
		 * @param \WP_REST_Request $request Request.
		 * @param array $course_ids Course IDs used to scope analytics data.
		 */
		return apply_filters( 'masteriyo_rest_prepared_analytics_items', $items, $request, $course_ids );
	}

	/**
	 * Get courses data.
	 *
	 * @since 1.6.7
	 *
	 * @return array
	 */
	protected function get_courses_data() {
		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$cache_key           = 'analytics_course_ids_' . ( $is_admin_or_manager ? 'all' : get_current_user_id() );
		$cache               = masteriyo_transient_cache();
		$cached              = $cache->get_cache( $cache_key, 'analytics_courses_list_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		$query = new \WP_Query(
			array(
				'post_status'    => PostStatus::PUBLISH,
				'post_type'      => PostType::COURSE,
				'posts_per_page' => -1,
				'author'         => $is_admin_or_manager ? null : get_current_user_id(),
				'fields'         => 'ids',
			)
		);

		$result = array(
			'ids'   => $query->posts,
			'total' => $query->post_count,
		);

		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_courses_list_group' );

		return $result;
	}

	/**
	 * Count courses, optionally restricted to a date range.
	 *
	 * With no range this is the size of the already-resolved id list, so the
	 * cached list from get_courses_data() is not re-queried.
	 *
	 * @param array  $course_ids Course IDs in scope.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return int
	 */
	private function get_courses_count( $course_ids, $start_date = null, $end_date = null ) {
		if ( empty( $course_ids ) ) {
			return 0;
		}

		if ( ! $start_date || ! $end_date ) {
			return count( $course_ids );
		}

		$query = new \WP_Query(
			array(
				'post_status'    => PostStatus::PUBLISH,
				'post_type'      => PostType::COURSE,
				'post__in'       => $course_ids,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Build a WP_Query/WP_Comment_Query date_query clause for an analytics range.
	 *
	 * Returns an empty array when either bound is missing, which both query classes
	 * treat as "no date constraint" — so a caller that has no range is unaffected.
	 *
	 * @param string $start  Range start, or empty for none.
	 * @param string $end    Range end, or empty for none.
	 * @param string $column Optional column to filter on, e.g. 'user_registered'.
	 *
	 * @return array
	 */
	private function analytics_date_query( $start, $end, $column = '' ) {
		if ( ! $start || ! $end ) {
			return array();
		}

		$clause = array(
			'after'     => $start,
			'before'    => $end,
			'inclusive' => true,
		);

		if ( $column ) {
			$clause['column'] = $column;
		}

		return array( $clause );
	}

	/**
	 * Get lessons count.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $course_ids Course IDs.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_lessons_data( $course_ids, $start_date = null, $end_date = null ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query         = new \WP_Query(
				array(
					'post_status'    => PostStatus::PUBLISH,
					'post_type'      => PostType::LESSON,
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => '_course_id',
							'value'   => $course_ids,
							'compare' => 'IN',
						),
					),
					'fields'         => 'ids',
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
				)
			);
			$data['total'] = $query->found_posts;
		}

		return $data;
	}

	/**
	 * Get quizzes count.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $course_ids Course IDs.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_quizzes_data( $course_ids, $start_date = null, $end_date = null ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query         = new \WP_Query(
				array(
					'post_status'    => PostStatus::PUBLISH,
					'post_type'      => PostType::QUIZ,
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => '_course_id',
							'value'   => $course_ids,
							'compare' => 'IN',
						),
					),
					'fields'         => 'ids',
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
				)
			);
			$data['total'] = $query->found_posts;
		}

		return $data;
	}

	/**
	 * Get questions count.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $course_ids Course IDs.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_questions_data( $course_ids, $start_date = null, $end_date = null ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query         = new \WP_Query(
				array(
					'post_status'    => PostStatus::PUBLISH,
					'post_type'      => PostType::QUESTION,
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => '_course_id',
							'value'   => $course_ids,
							'compare' => 'IN',
						),
					),
					'fields'         => 'ids',
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
				)
			);
			$data['total'] = $query->found_posts;
		}

		return $data;
	}

	/**
	 * Get instructors count.
	 *
	 * @since 1.6.7
	 *
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_instructors_data( $start_date = null, $end_date = null ) {
		// The range is part of the cache key, or a ranged count would be served
		// from the all-time entry and vice versa.
		$range_key = ( $start_date && $end_date )
			? gmdate( 'Y-m-d', strtotime( $start_date ) ) . '_' . gmdate( 'Y-m-d', strtotime( $end_date ) )
			: 'all';
		$cache_key = 'analytics_instructors_count_' . $range_key;
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_user_courses_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		$query = new \WP_User_Query(
			array(
				'role'        => Roles::INSTRUCTOR,
				'number'      => 1,
				'fields'      => 'ids',
				'count_total' => true,
				'date_query'  => $this->analytics_date_query( $start_date, $end_date, 'user_registered' ),
			)
		);

		$result = array(
			'total' => $query->get_total(),
		);

		$cache->set_cache( $cache_key, $result, HOUR_IN_SECONDS, 'analytics_user_courses_group' );

		return $result;
	}

	/**
	 * Get students count.
	 *
	 * @param array $course_ids Course IDs.
	 *
	 * @return array
	 */
	protected function get_students_data( $course_ids = array() ) {
		$sorted = array_filter( array_map( 'absint', (array) $course_ids ) );
		sort( $sorted );
		$cache_key = 'analytics_students_count_' . ( $sorted ? md5( implode( ',', $sorted ) ) : 'all' );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_user_courses_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		if ( ! empty( $course_ids ) ) {
			$result = array(
				'total' => masteriyo_count_enrolled_users( $course_ids ),
			);
		} else {
			$query = new \WP_User_Query(
				array(
					'role'        => Roles::STUDENT,
					'number'      => 1,
					'fields'      => 'ids',
					'count_total' => true,
				)
			);

			$result = array(
				'total' => $query->get_total(),
			);
		}

		$cache->set_cache( $cache_key, $result, HOUR_IN_SECONDS, 'analytics_user_courses_group' );

		return $result;
	}


	/**
	 * Get reviews count.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $course_ids Course IDs.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_reviews_data( $course_ids, $start_date = null, $end_date = null ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query         = new \WP_Comment_Query(
				array(
					'type'       => CommentType::COURSE_REVIEW,
					'status'     => CommentStatus::APPROVE_STR,
					'post__in'   => $course_ids,
					'count'      => true,
					'number'     => 1,
					'date_query' => $this->analytics_date_query( $start_date, $end_date ),
				)
			);
			$data['total'] = $query->get_comments();
		}

		return $data;
	}

	/**
	 * Get question/answers count.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $course_ids Course IDs.
	 * @param string $start_date Optional range start.
	 * @param string $end_date   Optional range end.
	 *
	 * @return array
	 */
	protected function get_questions_answers_data( $course_ids, $start_date = null, $end_date = null ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query         = new \WP_Comment_Query(
				array(
					'type'       => CommentType::COURSE_QA,
					'status'     => CommentStatus::APPROVE_STR,
					'count'      => true,
					'post__in'   => $course_ids,
					'number'     => 1,
					'date_query' => $this->analytics_date_query( $start_date, $end_date ),
				)
			);
			$data['total'] = $query->get_comments();
		}

		return $data;
	}

	/**
	 * Get enrolled courses data.
	 *
	 * @since 1.6.7
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	protected function get_enrolled_courses_data( $course_ids, $start_date, $end_date ) {
		$cache     = masteriyo_transient_cache();
		$cache_key = $this->analytics_cache_keys( null, $start_date, $end_date, $course_ids )['enrolled_courses_data'];
		$data      = $cache->get_cache( $cache_key, 'analytics_user_courses_group' );

		if ( ! is_null( $data ) ) {
			return $data;
		}

		global $wpdb;

		$data = array();

		$data['total']    = masteriyo_get_user_courses_count_by_course( $course_ids, $start_date, $end_date );
		$data['students'] = masteriyo_count_enrolled_users( $course_ids, $start_date, $end_date );

		if ( $course_ids ) {
			$course_constraint = $this->build_course_ids_sql( $course_ids );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Clamp the series start to the first enrollment so very wide ranges
			// (e.g. "All Time" starting in 2000) don't crush recent data into an
			// invisible sliver — only kicks in when the requested start predates
			// any enrollment, leaving normal ranges untouched.
			$earliest = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(date_start)
					FROM {$wpdb->prefix}masteriyo_user_items
					WHERE 1=1 {$course_constraint['sql']}
					AND status IN (%s, %s)",
					array_merge( $course_constraint['params'], array( UserCourseStatus::ACTIVE, UserCourseStatus::ENROLLED ) )
				)
			);

			if ( $earliest && strtotime( $earliest ) > strtotime( $start_date ) ) {
				$start_date = gmdate( 'Y-m-d 00:00:00', strtotime( $earliest ) );
			}

			$data['data'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(date_start) as date, COUNT(*) as count
					FROM {$wpdb->prefix}masteriyo_user_items
					WHERE 1=1 {$course_constraint['sql']}
					AND status IN (%s, %s)
					AND date_start >= %s AND date_start <= %s
					GROUP BY DATE(date_start)",
					array_merge( $course_constraint['params'], array( UserCourseStatus::ACTIVE, UserCourseStatus::ENROLLED, $start_date, $end_date ) )
				),
				ARRAY_A
			);
			// phpcs:enable
		}

		$data['data'] = $this->format_series_data( array_values( $data['data'] ?? array() ), $start_date, $end_date, '1 day' );

		$cache->set_cache( $cache_key, $data, DAY_IN_SECONDS, 'analytics_user_courses_group' );

		return $data;
	}

	/**
	 * Get sales data.
	 *
	 * @since 1.6.7
	 *
	 * @param array $course_ids Course IDs.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 *
	 * @return array
	 */
	protected function get_sales_data( $course_ids, $start_date, $end_date, $force_course_scope = false, $request = null ) {
		$data = array(
			'total_earnings'  => 0,
			'total_refunds'   => 0,
			'total_discounts' => 0,
			'earnings'        => array( 'data' => array() ),
			'refunds'         => array( 'data' => array() ),
		);

		$cache           = masteriyo_transient_cache();
		$empty_scope_key = $force_course_scope && empty( $course_ids ) ? 'empty_course_scope' : '';
		$cache_key       = $this->analytics_cache_keys( null, $start_date, $end_date, $force_course_scope ? $course_ids : array(), $empty_scope_key )['sales_data'];
		$all_orders      = $cache->get_cache( $cache_key, 'analytics_sales_group' );

		if ( is_null( $all_orders ) ) {
			$all_orders = $this->get_orders_within_range( $course_ids, $start_date, $end_date, $force_course_scope );
			$cache->set_cache( $cache_key, $all_orders, DAY_IN_SECONDS, 'analytics_sales_group' );
		}

		if ( ! empty( $all_orders ) ) {
			$all_orders = array_map(
				function( $all_order ) {
					$all_order['amount'] = masteriyo_format_decimal( $all_order['amount'] );

					return $all_order;
				},
				$all_orders
			);

			foreach ( $all_orders as $result ) {
				if ( OrderStatus::COMPLETED === $result['status'] ) {
					$data['earnings']['data'][] = $result;
				} elseif ( OrderStatus::REFUNDED === $result['status'] ) {
					$data['refunds']['data'][] = $result;
				}
			}

			$data['earnings']['data'] = $this->format_series_data( $data['earnings']['data'], $start_date, $end_date, '1 day' );
			$data['refunds']['data']  = $this->format_series_data( $data['refunds']['data'], $start_date, $end_date, '1 day' );
		} else {
			$data['earnings']['data'] = $this->format_series_data( array(), $start_date, $end_date, '1 day' );
			$data['refunds']['data']  = $this->format_series_data( array(), $start_date, $end_date, '1 day' );
		}

		$data['total_earnings']  = $this->get_total_earnings( $course_ids, $force_course_scope );
		$data['total_refunds']   = $this->get_total_refunds( $course_ids, $force_course_scope );
		$data['total_discounts'] = $this->get_total_discounts( $course_ids, $force_course_scope );
		$data['discounts']       = array( 'data' => $this->get_discounts_time_series( $course_ids, $start_date, $end_date ) );

		/**
		 * Filters the sales data returned by the `get_sales_data()` method.
		 *
		 * @since 2.12.0
		 *
		 * @param array $data The sales data array.
		 * @param string $start_date The start date of the date range.
		 * @param string $end_date The end date of the date range.
		 * @param array $course_ids The course IDs to filter the sales data by.
		 * @param self $this The current instance of this controller.
		 *
		 * self is added since 2.14.0
		 *
		 * @return array The filtered sales data.
		 */
		return apply_filters( 'masteriyo_sales_data', $data, $start_date, $end_date, $course_ids, $this, $request );
	}

	/**
	 * Retrieves orders within the specified date range.
	 *
	 * @since 2.11.0
	 *
	 * @param array $course_ids Course IDs.
	 * @param string $start_date The start date of the date range.
	 * @param string $end_date The end date of the date range.
	 *
	 * @return array An array of orders within the specified date range.
	 */
	private function get_orders_within_range( $course_ids, $start_date, $end_date, $force_course_scope = false ) {
		global $wpdb;

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();

		if ( $is_admin_or_manager && ! $force_course_scope ) {
			$orders_query = $wpdb->prepare(
				"
				SELECT
					DATE(p.post_modified) AS date,
					p.post_status AS status,
					COUNT(*) AS count,
					SUM(
						CASE
							WHEN meta_conversion.meta_value IS NOT NULL AND meta_conversion.meta_value != ''
							THEN CAST(meta_conversion.meta_value AS DECIMAL(10, 2))
							ELSE CAST(pm.meta_value AS DECIMAL(10, 2))
						END
					) AS amount
				FROM {$wpdb->posts} AS p
				LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_total'
				LEFT JOIN {$wpdb->postmeta} AS meta_conversion ON p.ID = meta_conversion.post_id AND meta_conversion.meta_key = '_conversion_total'
				WHERE p.post_type = %s
					AND p.post_status IN (%s, %s)
					AND p.post_modified >= %s
					AND p.post_modified <= %s
				GROUP BY DATE(p.post_modified), p.post_status
				ORDER BY DATE(p.post_modified) ASC, p.post_status ASC
				",
				PostType::ORDER,
				OrderStatus::COMPLETED,
				OrderStatus::REFUNDED,
				$start_date,
				$end_date
			);
		} else {
			if ( empty( $course_ids ) ) {
				return array();
			}

			$course_ids_placeholder = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );

			$query_params = array_merge(
				$course_ids,
				array(
					PostType::ORDER,
					OrderStatus::COMPLETED,
					OrderStatus::REFUNDED,
					$start_date,
					$end_date,
				)
			);

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$orders_query = $wpdb->prepare(
				"
				SELECT
					DATE(p.post_modified) AS date,
					p.post_status AS status,
					COUNT(DISTINCT p.ID) AS count,
					SUM(
						CASE
							WHEN meta_conversion.meta_value IS NOT NULL AND meta_conversion.meta_value != ''
							THEN CAST(meta_conversion.meta_value AS DECIMAL(10, 2))
							ELSE CAST(pm.meta_value AS DECIMAL(10, 2))
						END
					) AS amount
				FROM {$wpdb->posts} AS p
				LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_total'
				LEFT JOIN {$wpdb->postmeta} AS meta_conversion ON p.ID = meta_conversion.post_id AND meta_conversion.meta_key = '_conversion_total'
				INNER JOIN {$wpdb->prefix}masteriyo_order_items oi ON p.ID = oi.order_id
				INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
				WHERE oim.meta_key = 'course_id'
					AND oim.meta_value IN ($course_ids_placeholder)
					AND p.post_type = %s
					AND p.post_status IN (%s, %s)
					AND p.post_modified >= %s
					AND p.post_modified <= %s
				GROUP BY DATE(p.post_modified), p.post_status
				ORDER BY DATE(p.post_modified) ASC, p.post_status ASC
				",
				...$query_params
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$orders_results = $wpdb->get_results( $orders_query, ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $orders_results ) {
			return array();
		}

		$orders_results = array_map(
			function( $orders_result ) {
				$orders_result['amount'] = masteriyo_format_decimal( $orders_result['amount'] );

				return $orders_result;
			},
			$orders_results
		);

		return $orders_results;
	}

	/**
	 * Retrieves the total amount based on the provided course IDs, status, and meta keys.
	 *
	 * @since 2.14.0
	 *
	 * @param array $course_ids The IDs of the courses.
	 * @param string $status The post status (e.g., 'completed', 'refunded').
	 * @param string $meta_key1 The primary meta key to check (e.g., '_conversion_total').
	 * @param string $meta_key2 The fallback meta key (e.g., '_total').
	 * @param bool $is_needs_admin_or_manager Whether to check whether the user is an admin or manager.
	 * @param string $analytics_cache_key The cache key for analytics.
	 * @param string $cache_key_group The cache group for analytics.
	 *
	 * @return string The total amount.
	 */
	public function get_total_amount( $course_ids, $status, $meta_key1, $meta_key2, $cache_key = '', $cache_key_group = '', $order_item_type = 'course', $order_item_meta_key = 'course_id', $is_needs_admin_or_manager = true, $force_course_scope = false ) {
		$cache           = masteriyo_transient_cache();
		$empty_scope_key = $force_course_scope && empty( $course_ids ) ? 'empty_course_scope' : '';
		$cache_key       = $this->analytics_cache_keys( null, null, null, $force_course_scope ? $course_ids : array(), $empty_scope_key )[ $cache_key ];
		$total           = $cache->get_cache( $cache_key, $cache_key_group );

		if ( ! is_null( $total ) ) {
			return $total;
		}

		global $wpdb;

		$total = 0;

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();

		if ( $is_admin_or_manager && $is_needs_admin_or_manager && ! $force_course_scope ) {
			// Query for admins/managers
			$query = "
					SELECT SUM(
							CASE
									WHEN conversion_meta.meta_value IS NOT NULL AND conversion_meta.meta_value != ''
									THEN CAST(conversion_meta.meta_value AS DECIMAL(10, 2))
									ELSE CAST(pm.meta_value AS DECIMAL(10, 2))
							END
					) AS total_amount
					FROM {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->postmeta} conversion_meta
							ON pm.post_id = conversion_meta.post_id AND conversion_meta.meta_key = %s
					JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s
					AND p.post_status = %s
					AND p.post_type = %s
			";

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$prepared_query = $wpdb->prepare( $query, $meta_key1, $meta_key2, $status, PostType::ORDER );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		} else {

			if ( empty( $course_ids ) ) {
				return '0';
			}

			// Query for non-admins
			$course_constraint = $this->build_course_ids_sql( $course_ids, 'itemmeta.meta_value' );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = "
					SELECT
						SUM(
								CASE
										WHEN meta1.meta_value IS NOT NULL AND meta1.meta_value != ''
										THEN CAST(meta1.meta_value AS DECIMAL(10, 2))
										ELSE CAST(meta2.meta_value AS DECIMAL(10, 2))
								END
						) AS total
						FROM
							{$wpdb->prefix}masteriyo_order_items AS items
						INNER JOIN
							{$wpdb->prefix}masteriyo_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id
						LEFT JOIN
							{$wpdb->postmeta} AS meta1 ON items.order_id = meta1.post_id AND meta1.meta_key = %s
						LEFT JOIN
							{$wpdb->postmeta} AS meta2 ON items.order_id = meta2.post_id AND meta2.meta_key = %s
						INNER JOIN
							{$wpdb->posts} AS posts ON items.order_id = posts.ID
						WHERE
							items.order_item_type = %s
							AND itemmeta.meta_key = %s
							{$course_constraint['sql']}
							AND posts.post_type = %s
							AND posts.post_status = %s
					";

			$prepared_query = $wpdb->prepare(
				$query,
				array_merge(
					array( $meta_key1, $meta_key2, $order_item_type, $order_item_meta_key ),
					$course_constraint['params'],
					array( PostType::ORDER, $status )
				)
			);
			// phpcs:enable
		}

		$total = $wpdb->get_var( $prepared_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$cache->set_cache( $cache_key, $total, 0, $cache_key_group );
		return ( is_null( $total ) || '' === $total ) ? '0' : masteriyo_format_decimal( floatval( $total ) );
	}

	/**
	 * Retrieves the total earnings for the specified course IDs.
	 *
	 * @since 2.12.0
	 *
	 * @param array $course_ids The IDs of the courses to retrieve the total earnings for.
	 *
	 * @return string The total earnings for the specified courses.
	 */
	public function get_total_earnings( $course_ids, $force_course_scope = false ) {
		return $this->get_total_amount( $course_ids, OrderStatus::COMPLETED, '_conversion_total', '_total', 'total_earnings', 'analytics_sales_group', 'course', 'course_id', true, $force_course_scope );
	}

	/**
	 * Retrieves the total number of refunds for the given course IDs.
	 *
	 * @since 2.12.0
	 *
	 * @param int[] $course_ids The IDs of the courses to get the total refunds for.
	 *
	 * @return string The total number of refunds for the given course IDs.
	 */
	public function get_total_refunds( $course_ids, $force_course_scope = false ) {
		return $this->get_total_amount( $course_ids, OrderStatus::REFUNDED, '_conversion_total', '_total', 'total_refunds', 'analytics_sales_group', 'course', 'course_id', true, $force_course_scope );
	}

	/**
	 * Retrieves the total discounts for the given course IDs.
	 *
	 * @since 2.12.0
	 *
	 * @param int[] $course_ids The IDs of the courses to get the total discounts for.
	 *
	 * @return string The total discounts for the given course IDs.
	 */
	public function get_total_discounts( $course_ids, $force_course_scope = false ) {
		$completed = floatval( $this->get_total_amount( $course_ids, OrderStatus::COMPLETED, '_conversion_discount_total', '_discount_total', 'total_discounts_completed', 'analytics_sales_group', 'course', 'course_id', true, $force_course_scope ) );
		$refunded  = floatval( $this->get_total_amount( $course_ids, OrderStatus::REFUNDED, '_conversion_discount_total', '_discount_total', 'total_discounts_refunded', 'analytics_sales_group', 'course', 'course_id', true, $force_course_scope ) );
		return masteriyo_format_decimal( $completed + $refunded );
	}

	/**
	 * Retrieves the total earnings for the specified course IDs.
	 *
	 * @since 2.14.4
	 *
	 * @param array $course_ids The IDs of the courses to retrieve the total earnings for.
	 *
	 * @return string The total earnings for the specified courses.
	 */
	private function get_total_earnings_data( $course_id ) {
		global $wpdb;

		// Prepare the query parameters
		$query_params = array(
			$course_id,
			PostType::ORDER,
			OrderStatus::COMPLETED,
		);

		// phpcs:disable
		$orders_query = $wpdb->prepare(
			"
			SELECT
				SUM(
					CASE
						WHEN meta_conversion.meta_value IS NOT NULL AND meta_conversion.meta_value != ''
						THEN meta_conversion.meta_value
						ELSE pm.meta_value
					END
				) AS total_amount
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_total'
			LEFT JOIN {$wpdb->postmeta} AS meta_conversion ON p.ID = meta_conversion.post_id AND meta_conversion.meta_key = '_conversion_total'
			INNER JOIN {$wpdb->prefix}masteriyo_order_items oi ON p.ID = oi.order_id
			INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
			WHERE oim.meta_key = 'course_id'
				AND oim.meta_value = %d
				AND p.post_type = %s
				AND p.post_status = %s
			",
			...$query_params
		);

		$total_amount = $wpdb->get_var($orders_query);

		// Return the total amount as an integer (0 if no results found)
		$total_amount = masteriyo_format_decimal( $total_amount );
		$total_amount = $total_amount ? $total_amount : 0;
		return $total_amount;
	}


	/**
	 * Get sections count.
	 *
	 * @since 2.14.4
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return int
	 */
	protected function get_sections_count( $course_ids ) {
		$data = array(
			'total' => 0,
		);

		if ( $course_ids ) {
			$query = new \WP_Query(
				array(
					'post_status'    => PostStatus::PUBLISH,
					'post_type'      => PostType::SECTION,
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => '_course_id',
							'value'   => $course_ids,
							'compare' => 'IN',
						),
					),
					'fields'         => 'ids',
				)
			);
			$data['total'] = $query->found_posts;
		}

		return $data;
	}

	/**
	 * Format series data.
	 *
	 * Prefills empty data with 0.
	 *
	 * Public for the reason given on `build_course_ids_sql()` — a listener adding
	 * its own series must bucket it identically, or two series on one chart would
	 * carry different x-axes.
	 *
	 * @since 1.6.7
	 *
	 * @param array $data Table name.
	 * @param DateTime $start Start date.
	 * @param DateTime $end End date.
	 * @param string $interval Interval.
	 */
	public function format_series_data( $data, $start, $end, $interval ) {
		// Bucket size is driven by the range so a huge span (e.g. "All Time")
		// produces a few hundred points instead of one per day — see
		// get_analytics_bucket_interval(). The passed $interval is kept for
		// backward compatibility but no longer forces per-day output.
		$bucket = $this->get_analytics_bucket_interval( $start, $end );

		// Fold the sparse daily rows into their bucket (sum count/amount).
		$acc = array();
		foreach ( $data as $row ) {
			if ( ! isset( $row['date'] ) ) {
				continue;
			}
			$key = $this->get_analytics_bucket_key( new \DateTime( $row['date'] ), $bucket );
			if ( ! isset( $acc[ $key ] ) ) {
				$acc[ $key ] = array(
					'count'      => 0,
					'amount'     => 0,
					'has_amount' => false,
					'status'     => $row['status'] ?? null,
				);
			}
			$acc[ $key ]['count'] += (int) ( $row['count'] ?? 0 );
			if ( isset( $row['amount'] ) && null !== $row['amount'] ) {
				$acc[ $key ]['amount']    += (float) $row['amount'];
				$acc[ $key ]['has_amount'] = true;
			}
		}

		// Emit every bucket across the range, prefilling empty buckets with 0.
		$formatted_data = array();
		foreach ( $this->get_analytics_bucket_dates( $start, $end, $bucket ) as $key ) {
			$current          = $acc[ $key ] ?? null;
			$formatted_data[] = array(
				'date'   => $key,
				'count'  => $current['count'] ?? 0,
				'status' => $current['status'] ?? null,
				'amount' => ( $current && $current['has_amount'] ) ? $current['amount'] : null,
			);
		}

		return $formatted_data;
	}

	/**
	 * Pick the time-series bucket size for a date range so large ranges stay bounded.
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 *
	 * @return string One of 'day', 'week', 'month'.
	 */
	protected function get_analytics_bucket_interval( $start, $end ) {
		$s    = new \DateTime( gmdate( 'Y-m-d', strtotime( $start ) ) );
		$e    = new \DateTime( gmdate( 'Y-m-d', strtotime( $end ) ) );
		$days = (int) $s->diff( $e )->days + 1;

		if ( $days <= 92 ) {
			return 'day';
		}
		if ( $days <= 731 ) {
			return 'week';
		}
		return 'month';
	}

	/**
	 * Map a date to its bucket-start key for the given interval.
	 *
	 * @param \DateTime $date     Date to map.
	 * @param string    $interval 'day', 'week' or 'month'.
	 *
	 * @return string Y-m-d bucket-start key.
	 */
	protected function get_analytics_bucket_key( \DateTime $date, $interval ) {
		if ( 'month' === $interval ) {
			return $date->format( 'Y-m-01' );
		}
		if ( 'week' === $interval ) {
			$week = clone $date;
			$week->modify( 'monday this week' );
			return $week->format( 'Y-m-d' );
		}
		return $date->format( 'Y-m-d' );
	}

	/**
	 * Ordered list of bucket-start keys spanning a range at the given interval.
	 *
	 * @param string $start    Start date.
	 * @param string $end      End date.
	 * @param string $interval 'day', 'week' or 'month'.
	 *
	 * @return string[] Y-m-d bucket-start keys.
	 */
	protected function get_analytics_bucket_dates( $start, $end, $interval ) {
		$cursor = new \DateTime( gmdate( 'Y-m-d', strtotime( $start ) ) );
		$end_dt = new \DateTime( gmdate( 'Y-m-d', strtotime( $end ) ) );
		$end_dt->modify( '+1 day' );

		if ( 'month' === $interval ) {
			$cursor->modify( 'first day of this month' );
			$step = new \DateInterval( 'P1M' );
		} elseif ( 'week' === $interval ) {
			$cursor->modify( 'monday this week' );
			$step = new \DateInterval( 'P1W' );
		} else {
			$step = new \DateInterval( 'P1D' );
		}

		$dates = array();
		while ( $cursor < $end_dt ) {
			$dates[] = $this->get_analytics_bucket_key( $cursor, $interval );
			$cursor->add( $step );
		}

		return $dates;
	}

	/**
	 * Fetch the most popular courses based on the number of enrolled users and review counts.
	 *
	 * Public because it backs a pro chart. The Popular Courses chart is pro's, so
	 * core no longer puts this in the payload; `pro/Analytics/EngagementPopularSeries.php`
	 * calls it back and adds the `popular_courses` key on pro installs only. Kept
	 * here rather than moved so the query's `ARRAY_A` stays on the baselined
	 * controller — see that class.
	 *
	 * @since 2.6.11
	 *
	 * @param int $count The number of popular courses to fetch. Default is 5.
	 *
	 * @return array An array of most popular courses.
	 */
	public function get_most_popular_courses_data( $count = 5, $course_ids = array(), $start_date = null, $end_date = null ) {
		$current_user_id = get_current_user_id();

		$cache_key   = 'masteriyo_popular_courses_' . $current_user_id
			. ( $course_ids ? '_' . md5( implode( ',', $course_ids ) ) : '' )
			. ( $start_date ? '_' . md5( $start_date . $end_date ) : '' );
		$cached_data = get_transient( $cache_key );

		if ( $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$role_conditions = '';

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() ) {
			$role_conditions = "AND p.post_author = {$current_user_id}";
		}

		$course_clause = '';
		if ( ! empty( $course_ids ) ) {
			$placeholders  = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			$course_clause = $wpdb->prepare( "AND p.ID IN ({$placeholders})", ...$course_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Scope earnings to the selected date range when provided.
		$date_conditions = '';
		if ( $start_date && $end_date ) {
			$start_sql       = esc_sql( $start_date );
			$end_sql         = esc_sql( $end_date );
			$date_conditions = "AND ord.post_date_gmt >= '{$start_sql}' AND ord.post_date_gmt <= '{$end_sql}'";
		}

		// Scope earnings subquery to the same courses as the main query.
		$earnings_course_clause = '';
		if ( ! empty( $course_ids ) ) {
			$placeholders           = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			$earnings_course_clause = $wpdb->prepare( "AND CAST(oim.meta_value AS UNSIGNED) IN ({$placeholders})", ...$course_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$order_type   = PostType::ORDER;
		$order_status = OrderStatus::COMPLETED;

		// Exclude staff (admin/instructor/manager) users so the enrolled count
		// matches masteriyo_count_enrolled_users() used by the other sections.
		$exclude_users = array_map(
			'absint',
			(array) get_users(
				array(
					'role__in' => array( Roles::ADMIN, Roles::INSTRUCTOR, Roles::MANAGER ),
					'fields'   => 'ID',
				)
			)
		);

		$exclude_clause = '';
		if ( ! empty( $exclude_users ) ) {
			$exclude_placeholders = implode( ',', array_fill( 0, count( $exclude_users ), '%d' ) );
			$exclude_clause       = $wpdb->prepare( "AND ui.user_id NOT IN ({$exclude_placeholders})", ...$exclude_users ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Get enrolled users count.
		$sql_enrolled_users = "
			SELECT ui.item_id, COUNT(*) as enrolled_count
			FROM {$wpdb->prefix}masteriyo_user_items ui
			JOIN {$wpdb->prefix}posts p ON ui.item_id = p.ID
			WHERE ui.status = 'active' AND ui.item_type = 'user_course' AND p.post_status = 'publish' {$role_conditions} {$course_clause} {$exclude_clause}
			GROUP BY ui.item_id
		";

		// Get course review count.
		$sql_course_reviews = "
			SELECT p.ID as item_id, pm.meta_value as review_count
			FROM {$wpdb->prefix}posts p
			JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
			WHERE p.post_status = 'publish' AND pm.meta_key = '_review_count'
		";

		// Get total earnings per course from completed orders within the date range.
		$sql_course_earnings = "
			SELECT CAST(oim.meta_value AS UNSIGNED) as item_id,
				SUM(
					CASE
						WHEN mc.meta_value IS NOT NULL AND mc.meta_value != ''
						THEN mc.meta_value
						ELSE pm.meta_value
					END
				) as total_earnings
			FROM {$wpdb->posts} ord
			LEFT JOIN {$wpdb->postmeta} pm ON ord.ID = pm.post_id AND pm.meta_key = '_total'
			LEFT JOIN {$wpdb->postmeta} mc ON ord.ID = mc.post_id AND mc.meta_key = '_conversion_total'
			INNER JOIN {$wpdb->prefix}masteriyo_order_items oi ON ord.ID = oi.order_id
			INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
			WHERE oim.meta_key = 'course_id'
				AND ord.post_type = '{$order_type}'
				AND ord.post_status = '{$order_status}'
				{$date_conditions}
				{$earnings_course_clause}
			GROUP BY oim.meta_value
		";

		// Get popular courses ordered by earnings, then enrollment, then reviews.
		$sql_popular_courses = "
			SELECT e.item_id, e.enrolled_count, IFNULL(r.review_count, 0) as review_count, IFNULL(earn.total_earnings, 0) as total_earnings
			FROM ({$sql_enrolled_users}) e
			LEFT JOIN ({$sql_course_reviews}) r ON e.item_id = r.item_id
			LEFT JOIN ({$sql_course_earnings}) earn ON e.item_id = earn.item_id
			ORDER BY IFNULL(earn.total_earnings, 0) DESC, e.enrolled_count DESC, IFNULL(r.review_count, 0) DESC
			LIMIT %d
		";

		$popular_courses = $wpdb->get_results( $wpdb->prepare( $sql_popular_courses, $count ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$courses = array_filter(
			array_map(
				function ( $item ) {
					$course = masteriyo_get_course( $item['item_id'] );

					if ( is_null( $course ) || is_wp_error( $course ) ) {
						return null;
					}

					return array(
						'id'             => $course->get_id(),
						'name'           => $course->get_name(),
						'date_created'   => masteriyo_rest_prepare_date_response( $course->get_date_created() ),
						'date_modified'  => masteriyo_rest_prepare_date_response( $course->get_date_modified() ),
						'price'          => $course->get_price(),
						'price_type'     => $course->get_price_type(),
						'access_mode'    => $course->get_access_mode(),
						'edit_link'      => admin_url( "admin.php?page=masteriyo#/courses/{$course->get_id()}/edit" ),
						'enrolled_count' => $item['enrolled_count'],
						'review_count'   => $item['review_count'],
						'earnings'       => masteriyo_format_decimal( $item['total_earnings'] ),
					);
				},
				$popular_courses
			)
		);

		// Save data to cache.
		set_transient( $cache_key, $courses, HOUR_IN_SECONDS );

		return $courses;
	}

	/**
	 * Fetch the most recent course reviews.
	 *
	 * This function queries the WordPress database to retrieve the most recent
	 * reviews for courses, along with associated course and user information.
	 *
	 * @since 2.6.11
	 *
	 * @param int $count The number of recent reviews to fetch. Default is 5.
	 *
	 * @return array An array of associative arrays, each containing details of a review.
	 */
	protected function get_recent_reviews_data( $count = 5, $course_ids = array() ) {
		$current_user_id = get_current_user_id();

		$cache_key   = 'masteriyo_recent_reviews_' . $current_user_id
			. ( $course_ids ? '_' . md5( implode( ',', $course_ids ) ) : '' );
		$cached_data = get_transient( $cache_key );

		if ( $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$role_conditions = '';

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() ) {
			$role_conditions = "AND p.post_author = {$current_user_id}";
		}

		$course_clause = '';
		if ( ! empty( $course_ids ) ) {
			$placeholders  = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			$course_clause = $wpdb->prepare( "AND c.comment_post_ID IN ({$placeholders})", ...$course_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$sql_recent_reviews = "
			SELECT c.comment_ID as review_id, c.comment_post_ID as course_id, p.post_title as course_name,
						c.comment_content as review_content, c.comment_karma as review_rating,
						c.comment_date as review_date, cm.meta_value as review_title, c.user_id, u.display_name as user_name
			FROM {$wpdb->prefix}comments c
			JOIN {$wpdb->prefix}posts p ON c.comment_post_ID = p.ID
			JOIN {$wpdb->prefix}users u ON c.user_id = u.ID
			LEFT JOIN {$wpdb->prefix}commentmeta cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_title'
			WHERE c.comment_type = 'mto_course_review' AND p.post_status = 'publish' AND c.comment_approved = 1
			{$role_conditions} {$course_clause}
			ORDER BY c.comment_date DESC
			LIMIT %d
			";

		$recent_reviews = $wpdb->get_results( $wpdb->prepare( $sql_recent_reviews, $count ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $recent_reviews ) ) {
			return array();
		}

		$reviews = array_map(
			function ( $item ) {
				return array(
					'review_id'      => absint( $item['review_id'] ),
					'course_id'      => absint( $item['course_id'] ),
					'course_name'    => $item['course_name'],
					'review_title'   => $item['review_title'],
					'review_content' => $item['review_content'],
					'review_rating'  => absint( $item['review_rating'] ),
					'review_date'    => masteriyo_rest_prepare_date_response( $item['review_date'] ),
					'user_id'        => $item['user_id'],
					'user_name'      => $item['user_name'],
				);
			},
			$recent_reviews
		);

		set_transient( $cache_key, $reviews, HOUR_IN_SECONDS );

		return $reviews;
	}

	/**
	 * Fetch newly registered users based on their role and provided criteria.
	 *
	 * @since 2.6.11
	 *
	 * @param string $role The role of the users to fetch (e.g., Roles::STUDENT or Roles::INSTRUCTOR).
	 * @param int $count The number of users to fetch. Default is 5.
	 *
	 * @return array An array of newly registered users with their id, username, email, full name, and registered date.
	 */
	protected function get_newly_registered_users_by_role( $role, $count = 5, $course_ids = array() ) {
		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() ) {
			return array();
		}

		$sorted    = array_filter( array_map( 'absint', (array) $course_ids ) );
		sort( $sorted );
		$cache_key = 'analytics_new_users_' . md5( $role . '_' . $count . '_' . implode( ',', $sorted ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_user_courses_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		$args = array(
			'role'    => $role,
			'number'  => $count,
			'orderby' => 'registered',
			'order'   => 'DESC',
			'fields'  => array( 'ID', 'user_login', 'user_email', 'user_registered' ),
		);

		if ( ! empty( $course_ids ) ) {
			global $wpdb;
			$placeholders      = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			$sql               = "SELECT DISTINCT user_id FROM {$wpdb->prefix}masteriyo_user_items WHERE item_id IN ({$placeholders}) AND item_type = 'user_course'";
			$enrolled_user_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$course_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( empty( $enrolled_user_ids ) ) {
				$cache->set_cache( $cache_key, array(), HOUR_IN_SECONDS, 'analytics_user_courses_group' );
				return array();
			}
			$args['include'] = $enrolled_user_ids;
		}

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();

		$newly_registered_users = array();

		foreach ( $users as $user ) {
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$full_name  = trim( "{$first_name} {$last_name}" );

			$newly_registered_users[] = array(
				'id'              => $user->ID,
				'username'        => $user->user_login,
				'email'           => $user->user_email,
				'registered_date' => $user->user_registered,
				'full_name'       => empty( $full_name ) ? _x( 'N/A', 'full name not available', 'learning-management-system' ) : $full_name,
			);
		}

		$cache->set_cache( $cache_key, $newly_registered_users, HOUR_IN_SECONDS, 'analytics_user_courses_group' );

		return $newly_registered_users;
	}

	/**
	 * Fetch newly registered students based on the provided criteria.
	 *
	 * @since 2.6.11
	 *
	 * @param int $count The number of students to fetch. Default is 5.
	 *
	 * @return array An array of newly registered students.
	 */
	protected function get_newly_registered_students( $count = 5, $course_ids = array() ) {
		return $this->get_newly_registered_users_by_role( Roles::STUDENT, $count, $course_ids );
	}

	/**
	 * Fetch newly registered instructors based on the provided criteria.
	 *
	 * @since 2.6.11
	 *
	 * @param int $count The number of instructors to fetch. Default is 5.
	 *
	 * @return array An array of newly registered instructors.
	 */
	protected function get_newly_registered_instructors( $count = 5, $course_ids = array() ) {
		return $this->get_newly_registered_users_by_role( Roles::INSTRUCTOR, $count, $course_ids );
	}

	// -------------------------------------------------------------------------
	// Dashboard preferences
	// -------------------------------------------------------------------------

	/**
	 * Default dashboard slot preferences.
	 *
	 * These are free 2.3.2's own defaults — its `Analytics.tsx` opens the summary
	 * grid on Enrolled / Courses / Students / Lessons and renders one chart. Every
	 * slug named here is unlicensed and un-addon-gated, so the client resolves all
	 * four in either product rather than dropping two into its fallback pool and
	 * picking whatever happens to sit next in `ALL_METRICS`.
	 *
	 * A dashboard that opens on revenue is a pro preference, and therefore pro's to
	 * state — it does so through the filter below. Free's defaults are what a tree
	 * with no `pro/` path produces, with no licence check involved.
	 *
	 * @return array
	 */
	private function get_default_preferences() {
		/**
		 * Filters the analytics dashboard's default slot preferences.
		 *
		 * Applies only when the user has none saved, so a filter added later never
		 * overrides a choice an admin has already made.
		 *
		 * @param array $preferences Default preferences, keyed `summary`,
		 *                           `visualization` and `tables`.
		 */
		return apply_filters(
			'masteriyo_analytics_default_dashboard_preferences',
			array(
				'summary'       => array( 'total-enrolled', 'total-courses', 'total-students', 'total-lessons' ),
				'visualization' => array( 'enrollment-chart' ),
				'tables'        => array(),
			)
		);
	}

	/**
	 * GET /masteriyo/v1/analytics/preferences — return saved prefs or defaults.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_preferences( $request ) {
		$user_id = get_current_user_id();
		$prefs   = get_user_meta( $user_id, 'masteriyo_analytics_dashboard_preferences', true );
		return rest_ensure_response( $prefs ?: $this->get_default_preferences() );
	}

	/**
	 * POST /masteriyo/v1/analytics/preferences — validate and persist prefs.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function save_preferences( $request ) {
		$allowed_summary = array(
			'total-earnings', 'total-refunds', 'total-discounts', 'total-orders',
			'net-revenue', 'average-order-value', 'total-enrolled', 'total-students',
			'total-courses', 'total-lessons', 'total-quizzes',
			'total-questions', 'total-instructors', 'total-qa', 'total-reviews',
			'total-certificates', 'total-assignments', 'total-groups', 'total-bundles-sold',
		);
		$allowed_visualization = array(
			'sales-chart',
			'enrollment-chart',
			'bundle-chart',
			'subscriptions-chart',
			'activity-chart',
			'engagement-chart',
			'instructor-chart',
			'popular-courses-chart',
		);
		$allowed_tables        = array( 'recent-reviews', 'new-students', 'new-instructors' );

		$user_id = get_current_user_id();
		$current = get_user_meta( $user_id, 'masteriyo_analytics_dashboard_preferences', true );

		if ( ! is_array( $current ) ) {
			$current = $this->get_default_preferences();
		}

		$summary       = $request->get_param( 'summary' );
		$visualization = $request->get_param( 'visualization' );
		$tables        = $request->get_param( 'tables' );

		if ( is_array( $summary ) ) {
			$current['summary'] = array_values( array_intersect( $summary, $allowed_summary ) );
		}
		if ( is_array( $visualization ) ) {
			$current['visualization'] = array_values( array_intersect( $visualization, $allowed_visualization ) );
		}
		if ( is_array( $tables ) ) {
			$current['tables'] = array_values( array_intersect( $tables, $allowed_tables ) );
		}

		$current['updated_at'] = gmdate( 'Y-m-d H:i:s' );
		update_user_meta( $user_id, 'masteriyo_analytics_dashboard_preferences', $current );

		return rest_ensure_response( $current );
	}

	// -------------------------------------------------------------------------
	// Previous-period helpers
	// -------------------------------------------------------------------------

	/**
	 * Compute the previous period date range (same length as current, directly before it).
	 *
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array{ start: string, end: string }
	 */
	private function get_previous_period_dates( $start_date, $end_date ) {
		if ( ! $start_date || ! $end_date ) {
			$end   = new \DateTime( 'now' );
			$start = new \DateTime( '30 days ago' );
		} else {
			$start = new \DateTime( $start_date );
			$end   = new \DateTime( $end_date );
		}

		$diff = $start->diff( $end )->days + 1;

		$prev_end   = clone $start;
		$prev_end->modify( '-1 day' );
		$prev_start = clone $prev_end;
		$prev_start->modify( '-' . max( 0, $diff - 1 ) . ' days' );

		return array(
			'start' => $prev_start->format( 'Y-m-d H:i:s' ),
			'end'   => $prev_end->format( 'Y-m-d 23:59:59' ),
		);
	}

	/**
	 * Sum order amounts (earnings or refunds) within a date range.
	 *
	 * Respects admin/instructor scoping. Uses two meta keys: primary (conversion)
	 * with a fallback to the standard key, matching the pattern used across the
	 * rest of this controller.
	 *
	 * @param array       $course_ids
	 * @param string      $status     Order post status (completed / refunded).
	 * @param string|null $start_date
	 * @param string|null $end_date
	 * @param string      $meta_key1  Conversion meta key.
	 * @param string      $meta_key2  Standard meta key (fallback).
	 *
	 * @return string Formatted decimal string.
	 */
	private function get_total_amount_for_range( $course_ids, $status, $start_date, $end_date, $meta_key1 = '_conversion_total', $meta_key2 = '_total', $force_course_scope = false ) {
		if ( ! $start_date || ! $end_date ) {
			return '0';
		}

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$scope_ids           = $is_admin_or_manager && ! $force_course_scope ? array() : $course_ids;
		sort( $scope_ids );
		$cache_key   = 'analytics_amount_' . md5( $status . $start_date . $end_date . $meta_key1 . ( $force_course_scope ? '1' : '0' ) . implode( ',', $scope_ids ) );
		$cache       = masteriyo_transient_cache();
		$cached      = $cache->get_cache( $cache_key, 'analytics_sales_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		if ( $is_admin_or_manager && ! $force_course_scope ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(
						CASE
							WHEN meta_cv.meta_value IS NOT NULL AND meta_cv.meta_value != ''
							THEN CAST(meta_cv.meta_value AS DECIMAL(10, 2))
							ELSE CAST(pm.meta_value AS DECIMAL(10, 2))
						END
					)
					FROM {$wpdb->posts} AS p
					LEFT JOIN {$wpdb->postmeta} AS pm     ON p.ID = pm.post_id     AND pm.meta_key     = %s
					LEFT JOIN {$wpdb->postmeta} AS meta_cv ON p.ID = meta_cv.post_id AND meta_cv.meta_key = %s
					WHERE p.post_type = %s AND p.post_status = %s
					AND p.post_modified >= %s AND p.post_modified <= %s",
					$meta_key2,
					$meta_key1,
					PostType::ORDER,
					$status,
					$start_date,
					$end_date
				)
			);
			// phpcs:enable
		} else {
			if ( empty( $course_ids ) ) {
				$cache->set_cache( $cache_key, '0', DAY_IN_SECONDS, 'analytics_sales_group' );
				return '0';
			}

			$course_constraint = $this->build_course_ids_sql( $course_ids, 'oim.meta_value' );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(
						CASE
							WHEN meta_cv.meta_value IS NOT NULL AND meta_cv.meta_value != ''
							THEN CAST(meta_cv.meta_value AS DECIMAL(10, 2))
							ELSE CAST(pm.meta_value AS DECIMAL(10, 2))
						END
					)
					FROM {$wpdb->posts} AS p
					LEFT JOIN {$wpdb->postmeta} AS pm     ON p.ID = pm.post_id     AND pm.meta_key     = %s
					LEFT JOIN {$wpdb->postmeta} AS meta_cv ON p.ID = meta_cv.post_id AND meta_cv.meta_key = %s
					INNER JOIN {$wpdb->prefix}masteriyo_order_items oi     ON p.ID = oi.order_id
					INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
					WHERE oim.meta_key = 'course_id' {$course_constraint['sql']}
					AND p.post_type = %s AND p.post_status = %s
					AND p.post_modified >= %s AND p.post_modified <= %s",
					array_merge(
						array( $meta_key2, $meta_key1 ),
						$course_constraint['params'],
						array( PostType::ORDER, $status, $start_date, $end_date )
					)
				)
			);
			// phpcs:enable
		}

		$result = is_null( $total ) ? '0' : masteriyo_format_decimal( floatval( $total ) );
		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_sales_group' );

		return $result;
	}

	/**
	 * Total discounts within a date range.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return string
	 */
	private function get_discounts_for_range( $course_ids, $start_date, $end_date, $force_course_scope = false ) {
		$completed = $this->get_total_amount_for_range(
			$course_ids,
			OrderStatus::COMPLETED,
			$start_date,
			$end_date,
			'_conversion_discount_total',
			'_discount_total',
			$force_course_scope
		);
		$refunded  = $this->get_total_amount_for_range(
			$course_ids,
			OrderStatus::REFUNDED,
			$start_date,
			$end_date,
			'_conversion_discount_total',
			'_discount_total',
			$force_course_scope
		);
		return masteriyo_format_decimal( floatval( $completed ) + floatval( $refunded ) );
	}

	/**
	 * Count completed orders within a date range.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return int
	 */
	private function get_total_orders_for_range( $course_ids, $start_date, $end_date, $force_course_scope = false, $statuses = null ) {
		if ( ! $start_date || ! $end_date ) {
			return 0;
		}

		$statuses = $this->resolve_order_count_statuses( $statuses );

		if ( empty( $statuses ) ) {
			return 0;
		}

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$scope_ids           = $is_admin_or_manager && ! $force_course_scope ? array() : $course_ids;
		sort( $scope_ids );
		$cache_key = 'analytics_orders_range_' . md5( $start_date . $end_date . ( $force_course_scope ? '1' : '0' ) . implode( ',', $scope_ids ) . '|' . implode( ',', $statuses ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_sales_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $is_admin_or_manager && ! $force_course_scope ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ($status_placeholders)
					AND post_modified >= %s AND post_modified <= %s",
					array_merge( array( PostType::ORDER ), $statuses, array( $start_date, $end_date ) )
				)
			);
			// phpcs:enable
			$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_sales_group' );
			return $result;
		}

		if ( empty( $course_ids ) ) {
			return 0;
		}

		$course_constraint = $this->build_course_ids_sql( $course_ids, 'oim.meta_value' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}masteriyo_order_items oi     ON p.ID = oi.order_id
				INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
				WHERE oim.meta_key = 'course_id' {$course_constraint['sql']}
				AND p.post_type = %s AND p.post_status IN ($status_placeholders)
				AND p.post_modified >= %s AND p.post_modified <= %s",
				array_merge( $course_constraint['params'], array( PostType::ORDER ), $statuses, array( $start_date, $end_date ) )
			)
		);
		// phpcs:enable

		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_sales_group' );

		return $result;
	}

	/**
	 * Resolve the order statuses used by the Total Orders count.
	 *
	 * Passing null counts every order regardless of status (all generated orders),
	 * excluding only trashed orders. Pass an explicit array to scope the count
	 * (e.g. only completed orders for the average-order-value denominator).
	 *
	 * @param array|string|null $statuses Explicit status list, or null for all statuses.
	 *
	 * @return array
	 */
	private function resolve_order_count_statuses( $statuses = null ) {
		if ( null === $statuses ) {
			$statuses = array_values( array_diff( OrderStatus::all(), array( OrderStatus::TRASH ) ) );
		}

		return array_values( array_filter( (array) $statuses ) );
	}

	/**
	 * Count active enrollments within a date range.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return int
	 */
	private function get_enrolled_count_for_range( $course_ids, $start_date, $end_date ) {
		if ( empty( $course_ids ) || ! $start_date || ! $end_date ) {
			return 0;
		}

		$sorted = $course_ids;
		sort( $sorted );
		$cache_key = 'analytics_enrolled_range_' . md5( $start_date . $end_date . implode( ',', $sorted ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_user_courses_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$course_constraint = $this->build_course_ids_sql( $course_ids );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$wpdb->prefix}masteriyo_user_items
				WHERE 1=1 {$course_constraint['sql']}
				AND status IN (%s, %s)
				AND date_start >= %s AND date_start <= %s",
				array_merge( $course_constraint['params'], array( UserCourseStatus::ACTIVE, UserCourseStatus::ENROLLED, $start_date, $end_date ) )
			)
		);
		// phpcs:enable

		$result = (int) $total;
		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_user_courses_group' );

		return $result;
	}

	/**
	 * All-time completed orders count.
	 *
	 * @param array $course_ids
	 *
	 * @return int
	 */
	private function get_total_orders_count( $course_ids, $force_course_scope = false, $statuses = null ) {
		$statuses = $this->resolve_order_count_statuses( $statuses );

		if ( empty( $statuses ) ) {
			return 0;
		}

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$scope_ids           = $is_admin_or_manager && ! $force_course_scope ? array() : $course_ids;
		sort( $scope_ids );
		$cache_key = 'analytics_orders_count_' . md5( ( $force_course_scope ? '1' : '0' ) . implode( ',', $scope_ids ) . '|' . implode( ',', $statuses ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_sales_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $is_admin_or_manager && ! $force_course_scope ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($status_placeholders)",
					array_merge( array( PostType::ORDER ), $statuses )
				)
			);
			// phpcs:enable
			$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_sales_group' );
			return $result;
		}

		if ( empty( $course_ids ) ) {
			return 0;
		}

		$course_constraint = $this->build_course_ids_sql( $course_ids, 'oim.meta_value' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}masteriyo_order_items oi     ON p.ID = oi.order_id
				INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
				WHERE oim.meta_key = 'course_id' {$course_constraint['sql']}
				AND p.post_type = %s AND p.post_status IN ($status_placeholders)",
				array_merge( $course_constraint['params'], array( PostType::ORDER ), $statuses )
			)
		);
		// phpcs:enable

		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_sales_group' );

		return $result;
	}

	/**
	 * Net revenue: total earnings minus total refunds.
	 *
	 * May be negative when refunds exceed earnings.
	 *
	 * @param array $course_ids
	 *
	 * @return string
	 */
	private function get_net_revenue( $course_ids, $force_course_scope = false ) {
		$earnings = floatval( $this->get_total_earnings( $course_ids, $force_course_scope ) );
		$refunds  = floatval( $this->get_total_refunds( $course_ids, $force_course_scope ) );
		return masteriyo_format_decimal( $earnings - $refunds );
	}

	/**
	 * Average order value: total earnings ÷ completed order count.
	 *
	 * Earnings come from completed orders only, so the denominator must also be
	 * completed orders (not the all-status Total Orders count) to stay consistent
	 * with the sales chart tooltip.
	 *
	 * @param array $course_ids
	 *
	 * @return string
	 */
	private function get_average_order_value_metric( $course_ids, $force_course_scope = false ) {
		$earnings = floatval( $this->get_total_earnings( $course_ids, $force_course_scope ) );
		$orders   = $this->get_total_orders_count( $course_ids, $force_course_scope, array( OrderStatus::COMPLETED ) );

		if ( $orders <= 0 ) {
			return '0';
		}

		return masteriyo_format_decimal( $earnings / $orders );
	}

	/**
	 * Daily discount amounts time-series.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array
	 */
	private function get_discounts_time_series( $course_ids, $start_date, $end_date ) {
		$fallback_start = $start_date ?? gmdate( 'Y-m-d', strtotime( '-29 days' ) );
		$fallback_end   = $end_date ?? gmdate( 'Y-m-d' );

		if ( ! $start_date || ! $end_date ) {
			return $this->format_series_data( array(), $fallback_start, $fallback_end, '1 day' );
		}

		global $wpdb;

		$is_admin_or_manager = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();

		if ( $is_admin_or_manager ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(p.post_modified) AS date, COUNT(*) AS count,
					SUM(CASE WHEN mc.meta_value IS NOT NULL AND mc.meta_value != ''
						THEN CAST(mc.meta_value AS DECIMAL(10,4))
						ELSE CAST(pm.meta_value AS DECIMAL(10,4)) END) AS amount
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_discount_total'
					LEFT JOIN {$wpdb->postmeta} mc ON p.ID = mc.post_id AND mc.meta_key = '_conversion_discount_total'
					WHERE p.post_type = %s AND p.post_status = %s
					AND p.post_modified >= %s AND p.post_modified <= %s
					AND (CAST(COALESCE(NULLIF(mc.meta_value,''), pm.meta_value) AS DECIMAL(10,4)) > 0)
					GROUP BY DATE(p.post_modified)
					ORDER BY DATE(p.post_modified) ASC",
					PostType::ORDER,
					OrderStatus::COMPLETED,
					$start_date,
					$end_date
				),
				ARRAY_A
			);
			// phpcs:enable
		} else {
			if ( empty( $course_ids ) ) {
				return $this->format_series_data( array(), $start_date, $end_date, '1 day' );
			}

			$course_constraint = $this->build_course_ids_sql( $course_ids, 'oim.meta_value' );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(p.post_modified) AS date, COUNT(DISTINCT p.ID) AS count,
					SUM(CASE WHEN mc.meta_value IS NOT NULL AND mc.meta_value != ''
						THEN CAST(mc.meta_value AS DECIMAL(10,4))
						ELSE CAST(pm.meta_value AS DECIMAL(10,4)) END) AS amount
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_discount_total'
					LEFT JOIN {$wpdb->postmeta} mc ON p.ID = mc.post_id AND mc.meta_key = '_conversion_discount_total'
					INNER JOIN {$wpdb->prefix}masteriyo_order_items oi ON p.ID = oi.order_id
					INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
					WHERE oim.meta_key = 'course_id' {$course_constraint['sql']}
					AND p.post_type = %s AND p.post_status = %s
					AND p.post_modified >= %s AND p.post_modified <= %s
					AND (CAST(COALESCE(NULLIF(mc.meta_value,''), pm.meta_value) AS DECIMAL(10,4)) > 0)
					GROUP BY DATE(p.post_modified)
					ORDER BY DATE(p.post_modified) ASC",
					array_merge( $course_constraint['params'], array( PostType::ORDER, OrderStatus::COMPLETED, $start_date, $end_date ) )
				),
				ARRAY_A
			);
			// phpcs:enable
		}

		return $this->format_series_data( $results ?? array(), $start_date, $end_date, '1 day' );
	}

	/**
	 * Daily quiz attempt counts (all attempts, including incomplete).
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array
	 */
	protected function get_quiz_attempts_data( $course_ids, $start_date, $end_date ) {
		if ( ! $start_date || ! $end_date || empty( $course_ids ) ) {
			$s = $start_date ?? gmdate( 'Y-m-d', strtotime( '-29 days' ) );
			$e = $end_date ?? gmdate( 'Y-m-d' );
			return $this->format_series_data( array(), $s, $e, '1 day' );
		}

		$sorted = $course_ids;
		sort( $sorted );
		$cache_key = 'analytics_quiz_attempts_' . md5( $start_date . $end_date . implode( ',', $sorted ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_activities_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$course_constraint = $this->build_course_ids_sql( $course_ids, 'cp.item_id' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(ua.created_at) AS date, COUNT(*) AS count
				FROM {$wpdb->prefix}masteriyo_user_activities ua
				INNER JOIN {$wpdb->prefix}masteriyo_user_activities cp ON ua.parent_id = cp.id
				WHERE ua.activity_type = %s
				AND ua.created_at IS NOT NULL
				AND ua.created_at >= %s AND ua.created_at <= %s
				{$course_constraint['sql']}
				GROUP BY DATE(ua.created_at)
				ORDER BY DATE(ua.created_at) ASC",
				array_merge( array( CourseProgressItemType::QUIZ, $start_date, $end_date ), $course_constraint['params'] )
			),
			ARRAY_A
		);
		// phpcs:enable

		$result = $this->format_series_data( $results ?? array(), $start_date, $end_date, '1 day' );
		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_activities_group' );

		return $result;
	}

	/**
	 * Daily review publication counts.
	 *
	 * Public because it backs a pro chart — see `get_most_popular_courses_data()`.
	 * Half of the Engagement chart, which is pro's; core omits `reviews_time_series`
	 * and `pro/Analytics/EngagementPopularSeries.php` calls this back.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array
	 */
	public function get_reviews_time_series_data( $course_ids, $start_date, $end_date ) {
		if ( ! $start_date || ! $end_date || empty( $course_ids ) ) {
			$s = $start_date ?? gmdate( 'Y-m-d', strtotime( '-29 days' ) );
			$e = $end_date ?? gmdate( 'Y-m-d' );
			return $this->format_series_data( array(), $s, $e, '1 day' );
		}

		$sorted = $course_ids;
		sort( $sorted );
		$cache_key = 'analytics_reviews_series_' . md5( $start_date . $end_date . implode( ',', $sorted ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_activities_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$course_constraint = $this->build_course_ids_sql( $course_ids, 'comment_post_ID' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(comment_date) AS date, COUNT(*) AS count
				FROM {$wpdb->comments}
				WHERE comment_type = %s
				AND comment_approved = %s
				AND comment_date >= %s AND comment_date <= %s
				{$course_constraint['sql']}
				GROUP BY DATE(comment_date)
				ORDER BY DATE(comment_date) ASC",
				array_merge( array( CommentType::COURSE_REVIEW, CommentStatus::APPROVE, $start_date, $end_date ), $course_constraint['params'] )
			),
			ARRAY_A
		);
		// phpcs:enable

		$result = $this->format_series_data( $results ?? array(), $start_date, $end_date, '1 day' );
		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_activities_group' );

		return $result;
	}

	/**
	 * Daily Q&A post counts.
	 *
	 * Public because it backs a pro chart — see `get_most_popular_courses_data()`.
	 * The other half of the Engagement chart; core omits `qa_time_series` and
	 * `pro/Analytics/EngagementPopularSeries.php` calls this back.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array
	 */
	public function get_qa_time_series_data( $course_ids, $start_date, $end_date ) {
		if ( ! $start_date || ! $end_date || empty( $course_ids ) ) {
			$s = $start_date ?? gmdate( 'Y-m-d', strtotime( '-29 days' ) );
			$e = $end_date ?? gmdate( 'Y-m-d' );
			return $this->format_series_data( array(), $s, $e, '1 day' );
		}

		$sorted = $course_ids;
		sort( $sorted );
		$cache_key = 'analytics_qa_series_' . md5( $start_date . $end_date . implode( ',', $sorted ) );
		$cache     = masteriyo_transient_cache();
		$cached    = $cache->get_cache( $cache_key, 'analytics_activities_group' );

		if ( ! is_null( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$course_constraint = $this->build_course_ids_sql( $course_ids, 'comment_post_ID' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(comment_date) AS date, COUNT(*) AS count
				FROM {$wpdb->comments}
				WHERE comment_type = %s
				AND comment_approved = %s
				AND comment_date >= %s AND comment_date <= %s
				{$course_constraint['sql']}
				GROUP BY DATE(comment_date)
				ORDER BY DATE(comment_date) ASC",
				array_merge( array( CommentType::COURSE_QA, CommentStatus::APPROVE, $start_date, $end_date ), $course_constraint['params'] )
			),
			ARRAY_A
		);
		// phpcs:enable

		$result = $this->format_series_data( $results ?? array(), $start_date, $end_date, '1 day' );
		$cache->set_cache( $cache_key, $result, DAY_IN_SECONDS, 'analytics_activities_group' );

		return $result;
	}

	/**
	 * Subscription time-series data grouped into new/active/cancelled buckets.
	 * Returns null when the Pro subscription feature is not available.
	 *
	 * @param array       $course_ids
	 * @param string|null $start_date
	 * @param string|null $end_date
	 *
	 * @return array|null
	 */
	protected function get_subscriptions_time_series_data( $course_ids, $start_date, $end_date ) {
		if ( ! masteriyo_service_provider_exists( 'subscription' ) ) {
			return null;
		}

		if ( ! $start_date || ! $end_date ) {
			return null;
		}

		global $wpdb;

		$sorted_ids = array_filter( array_map( 'absint', (array) $course_ids ) );
		sort( $sorted_ids );
		$subs_cache_key = 'analytics_subscriptions_' . md5( $start_date . $end_date . implode( ',', $sorted_ids ) );
		$subs_cache     = masteriyo_transient_cache();
		$subs_cached    = $subs_cache->get_cache( $subs_cache_key, 'analytics_sales_group' );

		if ( ! is_null( $subs_cached ) ) {
			return $subs_cached;
		}

		$cpt           = 'mto-subscription';
		$start_day     = gmdate( 'Y-m-d', strtotime( $start_date ) );
		$end_day       = gmdate( 'Y-m-d', strtotime( $end_date ) );
		$course_ids    = array_filter( array_map( 'absint', (array) $course_ids ) );
		$course_join   = '';
		$course_where  = '';
		$course_params = array();

		if ( ! empty( $course_ids ) ) {
			$course_ids_placeholder = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
			$course_join            = " INNER JOIN {$wpdb->prefix}masteriyo_order_items oi ON s.post_parent = oi.order_id
				INNER JOIN {$wpdb->prefix}masteriyo_order_itemmeta oim ON oi.order_item_id = oim.order_item_id";
			$course_where           = " AND oim.meta_key = 'course_id' AND oim.meta_value IN ($course_ids_placeholder)";
			$course_params          = $course_ids;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$new_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(s.post_date) AS date, COUNT(DISTINCT s.ID) AS count
				FROM {$wpdb->posts} s
				{$course_join}
				WHERE s.post_type = %s
				{$course_where}
				AND DATE(s.post_date) >= %s AND DATE(s.post_date) <= %s
				GROUP BY DATE(s.post_date)
				ORDER BY DATE(s.post_date) ASC",
				array_merge( array( $cpt ), $course_params, array( $start_day, $end_day ) )
			),
			ARRAY_A
		);

		$cancelled_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(s.post_modified) AS date, COUNT(DISTINCT s.ID) AS count
				FROM {$wpdb->posts} s
				{$course_join}
				WHERE s.post_type = %s AND s.post_status = %s
				{$course_where}
				AND DATE(s.post_modified) >= %s AND DATE(s.post_modified) <= %s
				GROUP BY DATE(s.post_modified)
				ORDER BY DATE(s.post_modified) ASC",
				array_merge( array( $cpt, 'cancelled' ), $course_params, array( $start_day, $end_day ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		// Single query replaces the previous per-day N+1 loop.
		// Subscriptions that are already inactive AND were modified before the period
		// starts can never appear as "active" on any day in the range — skip them.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all_subs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.ID,
						DATE(s.post_date)     AS created_day,
						s.post_status,
						DATE(s.post_modified) AS modified_day
				FROM {$wpdb->posts} s
				{$course_join}
				WHERE s.post_type = %s
				{$course_where}
				AND s.post_date <= %s
				AND (
					s.post_status = %s
					OR s.post_modified >= %s
				)",
				array_merge(
					array( $cpt ),
					$course_params,
					array(
						$end_date,
						\Masteriyo\Pro\Enums\SubscriptionStatus::ACTIVE,
						$start_date,
					)
				)
			),
			ARRAY_A
		);
		// phpcs:enable

		$inactive_statuses = array(
			\Masteriyo\Pro\Enums\SubscriptionStatus::CANCELLED,
			\Masteriyo\Pro\Enums\SubscriptionStatus::EXPIRED,
			\Masteriyo\Pro\Enums\SubscriptionStatus::PENDING_CANCEL,
		);

		// Snapshot the active count once per bucket (not per day) so a large range
		// stays bounded — matches the bucket keys used by the new/cancelled series.
		$bucket         = $this->get_analytics_bucket_interval( $start_date, $end_date );
		$active_results = array();
		foreach ( $this->get_analytics_bucket_dates( $start_date, $end_date, $bucket ) as $key ) {
			$count = 0;
			foreach ( $all_subs as $sub ) {
				if ( $sub['created_day'] > $key ) {
					continue;
				}
				if ( \Masteriyo\Pro\Enums\SubscriptionStatus::ACTIVE === $sub['post_status'] ) {
					++$count;
				} elseif ( in_array( $sub['post_status'], $inactive_statuses, true ) && $sub['modified_day'] > $key ) {
					++$count;
				}
			}
			$active_results[] = array(
				'date'  => $key,
				'count' => $count,
			);
		}

		$result = array(
			'new'       => array( 'data' => $this->format_series_data( $new_results ?? array(), $start_date, $end_date, '1 day' ) ),
			'active'    => array( 'data' => $active_results ),
			'cancelled' => array( 'data' => $this->format_series_data( $cancelled_results ?? array(), $start_date, $end_date, '1 day' ) ),
		);

		$subs_cache->set_cache( $subs_cache_key, $result, HOUR_IN_SECONDS, 'analytics_sales_group' );

		return $result;
	}
}
