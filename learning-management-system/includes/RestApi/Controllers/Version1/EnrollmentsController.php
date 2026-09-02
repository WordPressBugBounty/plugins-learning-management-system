<?php
/**
 * Enrollments controller.
 *
 * @since 2.31.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Emails\EmailHooks;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Roles;

/**
 * Enrollments controller class.
 *
 * List, preview, and bulk-create enrollments without going through an order.
 *
 * @since 2.31.0
 */
class EnrollmentsController extends \WP_REST_Controller {

	/**
	 * Cap on emails per bulk request. Above this the client must chunk — a single synchronous
	 * request creating accounts and firing emails for hundreds of rows risks max_execution_time.
	 *
	 * @since 2.31.0
	 *
	 * @var int
	 */
	const MAX_EMAILS_PER_REQUEST = 100;

	/**
	 * Endpoint namespace.
	 *
	 * @since 2.31.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 2.31.0
	 *
	 * @var string
	 */
	protected $rest_base = 'enrollments';

	/**
	 * Lazily-resolved, request-scoped OrdersController instance. The container binding for
	 * 'order.rest' is not shared, so resolving it fresh per row (up to MAX_EMAILS_PER_REQUEST
	 * times) would re-register its constructor's action/filter hooks that many times over.
	 *
	 * @since 2.31.0
	 *
	 * @var \Masteriyo\RestApi\Controllers\Version1\OrdersController|null
	 */
	protected $orders_controller;

	/**
	 * Register routes.
	 *
	 * @since 2.31.0
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
					'callback'            => array( $this, 'create_items' ),
					'permission_callback' => array( $this, 'create_items_permissions_check' ),
					'args'                => $this->get_create_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'preview_items' ),
					'permission_callback' => array( $this, 'create_items_permissions_check' ),
					'args'                => array_merge(
						$this->get_bulk_action_params(),
						array(
							// Preview-only: create recomputes real availability per request, so an offset there would double-count its own writes.
							'seats_taken' => array(
								'type'              => 'integer',
								'default'           => 0,
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
								'description'       => __( 'Seats already claimed by earlier preview chunks of the same batch, subtracted from the available seats so a chunked preview matches what enrollment will accept.', 'learning-management-system' ),
							),
						)
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description'       => __( 'Unique identifier for the enrollment (masteriyo_user_items row id).', 'learning-management-system' ),
							'type'              => 'integer',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'force' => array(
							'description'       => __( 'Whether to permanently delete the enrollment row instead of revoking access.', 'learning-management-system' ),
							'type'              => 'boolean',
							'default'           => false,
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to list enrollments.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_masteriyo_enrollments' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to preview/create enrollments.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function create_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_masteriyo_enrollments' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to revoke (delete) an enrollment.
	 *
	 * Same capability as read/create — no per-course ownership check ships in this release.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_masteriyo_enrollments' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the collection params for enrollments listing.
	 *
	 * @since 2.31.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context'   => $this->get_context_param( array( 'default' => 'view' ) ),
			'page'      => array(
				'description'       => __( 'Current page of the collection.', 'learning-management-system' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page'  => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'learning-management-system' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'course_id' => array(
				'description'       => __( 'Limit result set to enrollments in a specific course.', 'learning-management-system' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'    => array(
				'description'       => __( 'Limit result set to enrollments matching a student name or email.', 'learning-management-system' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'    => array(
				'description'       => __( 'Limit result set to enrollments with a specific status.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => UserCourseStatus::ANY,
				'enum'              => UserCourseStatus::all(),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'source'    => array(
				'description'       => __( 'Limit result set to enrollments by origin: manually granted, or produced by an order/integration.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'any',
				'enum'              => array( 'any', 'manual', 'automatic' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'     => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'   => array(
				'description'       => __( 'Sort collection by object attribute.', 'learning-management-system' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => array( 'id', 'date_start', 'status', 'student', 'course' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'after'     => array(
				'description'       => __( 'Limit result set to enrollments started after a given ISO8601 compliant date.', 'learning-management-system' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before'    => array(
				'description'       => __( 'Limit result set to enrollments started before a given ISO8601 compliant date.', 'learning-management-system' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Get the params shared by the preview and create (bulk-enroll) endpoints.
	 *
	 * @since 2.31.0
	 *
	 * @return array
	 */
	protected function get_bulk_action_params() {
		return array(
			'course_id'           => array(
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => __( 'Course ID to enroll students into.', 'learning-management-system' ),
			),
			'emails'              => array(
				'required'          => true,
				'type'              => 'array',
				'items'             => array( 'type' => 'string' ),
				// Above this the client must chunk. Enforced again in code (get_too_many_emails_error()) since hand-rolled REST args like these only get schema-checked when a validate_callback is set.
				'maxItems'          => self::MAX_EMAILS_PER_REQUEST,
				'validate_callback' => 'rest_validate_request_arg',
				/* translators: %d: maximum number of emails allowed per request. */
				'description'       => sprintf( __( 'List of student email addresses. Maximum %d per request.', 'learning-management-system' ), self::MAX_EMAILS_PER_REQUEST ),
			),
			'override_seat_limit' => array(
				'type'              => 'boolean',
				'default'           => false,
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => __( 'Enroll even when the course has no seats left under its enrollment_limit.', 'learning-management-system' ),
			),
		);
	}

	/**
	 * Get the params for the create (bulk-enroll) endpoint: the shared preview/create params
	 * plus the write-only options that have no meaning for a preview.
	 *
	 * @since 2.31.0
	 *
	 * @return array
	 */
	protected function get_create_params() {
		return array_merge(
			$this->get_bulk_action_params(),
			array(
				'send_notifications' => array(
					'type'              => 'boolean',
					'default'           => true,
					'validate_callback' => 'rest_validate_request_arg',
					'description'       => __( 'Send the automatic-registration email to accounts created by this request.', 'learning-management-system' ),
				),
				'names'              => array(
					'type'              => 'object',
					'default'           => array(),
					'validate_callback' => 'rest_validate_request_arg',
					'description'       => __( 'Optional map of email to {first_name, last_name}, applied only to accounts this request creates — never to existing users.', 'learning-management-system' ),
				),
				'create_order'       => array(
					'type'              => 'boolean',
					'default'           => false,
					'validate_callback' => 'rest_validate_request_arg',
					'description'       => __( 'Create an order for this enrollment. Only honoured for one_time (paid) courses; ignored otherwise.', 'learning-management-system' ),
				),
				'order_amount'       => array(
					'type'              => 'string',
					'enum'              => array( 'zero', 'full' ),
					'default'           => 'zero',
					'validate_callback' => 'rest_validate_request_arg',
					'description'       => __( 'Amount to record on the created order: zero, or the course price excluding tax.', 'learning-management-system' ),
				),
			)
		);
	}

	/**
	 * Get a collection of enrollments.
	 *
	 * Reads directly from the custom table with joins on wp_users and wp_posts so
	 * the response is renderable without per-row lookups.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;

		$page      = max( 1, (int) $request['page'] );
		$per_page  = max( 1, (int) $request['per_page'] );
		$course_id = absint( $request['course_id'] );
		$search    = trim( (string) $request['search'] );
		$status    = $request['status'];
		$source    = isset( $request['source'] ) ? $request['source'] : 'any';

		$order       = 'asc' === strtolower( $request['order'] ) ? 'ASC' : 'DESC';
		$orderby_map = array(
			'id'         => 'ui.id',
			'date_start' => 'ui.date_start',
			'status'     => 'ui.status',
			'student'    => 'u.display_name',
			'course'     => 'p.post_title',
		);
		$orderby = isset( $orderby_map[ $request['orderby'] ] ) ? $orderby_map[ $request['orderby'] ] : 'ui.id';

		// Low-cardinality sorts (status, duplicate names) need a unique tie-breaker,
		// or rows shift between the separately-executed pages and paging skips or
		// repeats them.
		if ( 'ui.id' !== $orderby ) {
			$orderby .= " {$order}, ui.id";
		}

		$where        = array( "ui.item_type = 'user_course'" );
		$prepare_args = array();

		if ( $course_id ) {
			$where[]        = 'ui.item_id = %d';
			$prepare_args[] = $course_id;
		}

		if ( ! empty( $status ) && UserCourseStatus::ANY !== $status ) {
			$where[]        = 'ui.status = %s';
			$prepare_args[] = $status;
		}

		if ( '' !== $search ) {
			$like           = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]        = '( u.display_name LIKE %s OR u.user_email LIKE %s )';
			$prepare_args[] = $like;
			$prepare_args[] = $like;
		}

		// Exclude admin/instructor/manager unless they also hold the student role — a
		// promoted student keeps their enrollments visible. Transient-cached instead of
		// running get_users()'s unindexed wp_capabilities LIKE scan on every request.
		$excluded_user_ids = masteriyo_get_enrollment_excluded_user_ids();

		if ( ! empty( $excluded_user_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $excluded_user_ids ), '%d' ) );
			$where[]      = "ui.user_id NOT IN ({$placeholders})";
			$prepare_args = array_merge( $prepare_args, $excluded_user_ids );
		}

		// Date-range filters on date_start, stored in the DB as GMT (see UserCourseRepository::create()).
		$after  = ! empty( $request['after'] ) ? rest_parse_date( $request['after'], true ) : false;
		$before = ! empty( $request['before'] ) ? rest_parse_date( $request['before'], true ) : false;

		if ( false !== $after ) {
			$where[]        = 'ui.date_start >= %s';
			$prepare_args[] = gmdate( 'Y-m-d H:i:s', $after );
		}

		if ( false !== $before ) {
			$where[]        = 'ui.date_start <= %s';
			$prepare_args[] = gmdate( 'Y-m-d H:i:s', $before );
		}

		// Derived at read time with stored intent taking precedence: a row's own _source meta wins when present (this endpoint and integrations stamp it); a _wc_order_id marks a WooCommerce purchase as automatic; otherwise a non-empty _order_id whose order's _created_via is something other than manual-enrollment means automatic, and everything else is 'manual'. This expression is what the list actually filters and sorts on, so historical and new rows agree. Known gap: historical SureCart rows carry no meta at all and land in 'manual'.
		$source_case_sql = "( CASE
			WHEN srcm.meta_value IN ( 'manual', 'automatic' ) THEN srcm.meta_value
			WHEN wcm.meta_value IS NOT NULL AND wcm.meta_value != '' THEN 'automatic'
			WHEN eddm.meta_value IS NOT NULL AND eddm.meta_value != '' THEN 'automatic'
			WHEN oim.meta_value IS NOT NULL AND oim.meta_value != '' AND ( ocv.meta_value IS NULL OR ocv.meta_value NOT IN ( 'manual-enrollment', 'manual-enrollment__trashed' ) ) THEN 'automatic'
			ELSE 'manual' END )";

		if ( 'any' !== $source ) {
			$where[]        = "{$source_case_sql} = %s";
			$prepare_args[] = $source;
		}

		$where_sql = implode( ' AND ', $where );
		// The WHERE only ever touches these joins (search on u, source CASE on
		// srcm/wcm/eddm/oim/ocv), so the COUNT reuses them alone.
		$count_from_sql = "FROM {$wpdb->prefix}masteriyo_user_items ui
			LEFT JOIN {$wpdb->users} u ON u.ID = ui.user_id
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta srcm ON srcm.user_item_id = ui.id AND srcm.meta_key = '_source'
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta wcm ON wcm.user_item_id = ui.id AND wcm.meta_key = '_wc_order_id'
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta eddm ON eddm.user_item_id = ui.id AND eddm.meta_key = '_edd_order_id'
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta oim ON oim.user_item_id = ui.id AND oim.meta_key = '_order_id'
			LEFT JOIN {$wpdb->posts} o ON o.ID = CAST( oim.meta_value AS UNSIGNED )
			LEFT JOIN {$wpdb->postmeta} ocv ON ocv.post_id = o.ID AND ocv.meta_key = '_created_via'";

		// LEFT JOIN both posts and users — an enrollment whose course or WP user was deleted must still appear, not silently vanish behind an INNER JOIN.
		// The group is the row's own _group_id stamp, written at enrollment time
		// for members, leaders and buyers alike. It is deliberately NOT derived
		// from the order's _created_group_id: order-level meta labels every row
		// of the order, including co-purchased courses the group never covered.
		$from_sql = "{$count_from_sql}
			LEFT JOIN {$wpdb->posts} p ON p.ID = ui.item_id
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta gbm ON gbm.user_item_id = ui.id AND gbm.meta_key = '_granted_by'
			LEFT JOIN {$wpdb->users} gbu ON gbu.ID = CAST( gbm.meta_value AS UNSIGNED )
			LEFT JOIN {$wpdb->prefix}masteriyo_user_itemmeta gim ON gim.user_item_id = ui.id AND gim.meta_key = '_group_id'
			LEFT JOIN {$wpdb->posts} g ON g.ID = CAST( gim.meta_value AS UNSIGNED )";

		$offset   = ( $page - 1 ) * $per_page;
		$list_sql = "SELECT ui.id, ui.user_id, ui.item_id AS course_id, ui.status, ui.date_start, ui.date_end,
				u.display_name, u.user_email, p.post_title AS course_name,
				oim.meta_value AS order_id, gbm.meta_value AS granted_by_id, gbu.display_name AS granted_by_name,
				g.ID AS group_id, g.post_title AS group_name,
				{$source_case_sql} AS source
			{$from_sql}
			WHERE {$where_sql}
			ORDER BY {$orderby} {$order}
			LIMIT %d OFFSET %d";

		$list_args = array_merge( $prepare_args, array( $per_page, $offset ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// A page shorter than requested is provably the last one — no separate COUNT(*) needed. Zero rows is ambiguous ("no matches" vs "offset past the end"), so that case still runs the real count below.
		if ( $per_page > 0 && count( $rows ) > 0 && count( $rows ) < $per_page ) {
			$total = $offset + count( $rows );
		} else {
			$count_sql = "SELECT COUNT(*) {$count_from_sql} WHERE {$where_sql}";
			$total     = (int) ( empty( $prepare_args ) ? $wpdb->get_var( $count_sql ) : $wpdb->get_var( $wpdb->prepare( $count_sql, $prepare_args ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Course-level expiration lives in course settings (date_start + duration), not in the
		// date_end column, so without this an expiring enrollment shows no expiry at all.
		// Resolved once per unique course on the page, then projected onto its active rows.
		$expiration_days_by_course = array();
		$get_expiration_days       = function ( $course_id ) use ( &$expiration_days_by_course ) {
			if ( ! array_key_exists( $course_id, $expiration_days_by_course ) ) {
				$course = masteriyo_get_course( $course_id );

				$expiration_days_by_course[ $course_id ] = ( $course && ! is_wp_error( $course ) && $course->get_enrollment_expiration_enabled() )
					? absint( $course->get_enrollment_expiration_duration() )
					: 0;
			}

			return $expiration_days_by_course[ $course_id ];
		};

		$data = array_map(
			function( $row ) use ( $get_expiration_days ) {
				// course_name is NULL when the LEFT JOIN found no post row — the course was deleted. Render a flagged placeholder instead of dropping the row.
				$course_deleted = is_null( $row->course_name );
				// display_name is NULL when the LEFT JOIN found no wp_users row — the student's WP account was deleted. Same flagged-placeholder treatment.
				$student_deleted = is_null( $row->display_name );

				// The table stores '0000-00-00 00:00:00' for "no date", which is not
				// empty() — fed to the date formatter it renders as year 0001.
				$date_end = self::is_real_date( $row->date_end ) ? $row->date_end : null;

				if ( ! $date_end && UserCourseStatus::ACTIVE === $row->status && ! $course_deleted && self::is_real_date( $row->date_start ) ) {
					$expiration_days = $get_expiration_days( (int) $row->course_id );

					if ( $expiration_days > 0 ) {
						$date_end = gmdate( 'Y-m-d H:i:s', strtotime( $row->date_start ) + $expiration_days * DAY_IN_SECONDS );
					}
				}

				return array(
					'id'         => (int) $row->id,
					'user_id'    => (int) $row->user_id,
					'course_id'  => (int) $row->course_id,
					'status'     => $row->status,
					'date_start' => self::is_real_date( $row->date_start ) ? masteriyo_rest_prepare_date_response( $row->date_start ) : null,
					// Stored value, or the projected course-setting expiry computed above; null when the course never expires.
					'date_end'   => $date_end ? masteriyo_rest_prepare_date_response( $date_end ) : null,
					'source'     => $row->source,
					'order_id'   => empty( $row->order_id ) ? null : (int) $row->order_id,
					// Null for order/integration rows, which never write _granted_by.
					'granted_by' => empty( $row->granted_by_id ) ? null : array(
						'id'           => (int) $row->granted_by_id,
						'display_name' => $row->granted_by_name,
					),
					'student'    => array(
						'id'           => (int) $row->user_id,
						'display_name' => $student_deleted ? null : $row->display_name,
						'email'        => $student_deleted ? null : $row->user_email,
						'deleted'      => $student_deleted,
					),
					'course'     => array(
						'id'      => (int) $row->course_id,
						'name'    => $course_deleted ? null : wp_specialchars_decode( $row->course_name ),
						'deleted' => $course_deleted,
					),
					// The row's own _group_id stamp; null for individual enrollments.
					'group'      => empty( $row->group_id ) ? null : array(
						'id'   => (int) $row->group_id,
						'name' => wp_specialchars_decode( (string) $row->group_name ),
					),
				);
			},
			$rows
		);

		$max_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $max_pages );

		return $response;
	}

	/**
	 * Whether a stored datetime is a real value rather than MySQL's zero date.
	 *
	 * @since 2.31.0
	 *
	 * @param string|null $datetime Raw column value.
	 * @return bool
	 */
	protected static function is_real_date( $datetime ) {
		return ! empty( $datetime ) && 0 !== strpos( $datetime, '0000-00-00' );
	}

	/**
	 * Resolve a course for a bulk-enrollment request.
	 *
	 * @since 2.31.0
	 *
	 * @param int $course_id Course ID.
	 * @return \Masteriyo\Models\Course|null
	 */
	protected function get_valid_course( $course_id ) {
		$course = masteriyo_get_course( $course_id );

		if ( is_null( $course ) || is_wp_error( $course ) || ! $course->get_id() ) {
			return null;
		}

		return $course;
	}

	/**
	 * Reject `recurring` access-mode courses for manual enrollment.
	 *
	 * There is no coherent manual-subscription concept without pro's subscription machinery,
	 * and masteriyo_can_start_course() would grant permanent, unrevokable access for a
	 * recurring course enrolled without an order. `one_time` courses are allowed — access is
	 * permanent by design for a one-off purchase, so a manual grant is coherent.
	 *
	 * @since 2.31.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return \WP_Error|null
	 */
	protected function get_course_access_mode_error( $course ) {
		if ( CourseAccessMode::RECURRING === $course->get_access_mode() ) {
			return new \WP_Error(
				'masteriyo_rest_recurring_course_not_enrollable',
				__( 'This course uses a subscription (recurring) access mode. Manual enrollment does not support recurring-access courses because access could never be tied to a subscription.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return null;
	}

	/**
	 * Reject unpublished courses for manual enrollment.
	 *
	 * The rest of the codebase assumes an enrolled course is `publish` or `private`
	 * (the learn-page guard admits only those two); an enrollment into a draft is a dead link.
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return \WP_Error|null
	 */
	protected function get_course_status_error( $course ) {
		if ( ! in_array( $course->get_status(), array( PostStatus::PUBLISH, PostStatus::PVT ), true ) ) {
			return new \WP_Error(
				'masteriyo_rest_unpublished_course_not_enrollable',
				__( 'This course is not published. Publish the course before enrolling students.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return null;
	}

	/**
	 * Find a user's existing user-course row for a course, in any status.
	 *
	 * @since 2.31.0
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 * @return \Masteriyo\Models\UserCourse|null
	 */
	protected function get_existing_user_course( $user_id, $course_id ) {
		$query = new UserCourseQuery(
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'status'    => UserCourseStatus::ANY,
				'per_page'  => 1,
			)
		);

		$user_courses = $query->get_user_courses();

		return $user_courses ? current( $user_courses ) : null;
	}

	/**
	 * Preview a bulk enrollment. Writes nothing.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function preview_items( $request ) {
		$course_id = absint( $request['course_id'] );
		$emails    = (array) $request['emails'];

		$too_many_error = $this->get_too_many_emails_error( $emails );

		if ( $too_many_error ) {
			return $too_many_error;
		}

		$course = $this->get_valid_course( $course_id );

		if ( ! $course ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid course ID.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$access_mode_error = $this->get_course_access_mode_error( $course );

		if ( $access_mode_error ) {
			return $access_mode_error;
		}

		$status_error = $this->get_course_status_error( $course );

		if ( $status_error ) {
			return $status_error;
		}

		$override_seat_limit = (bool) $request['override_seat_limit'];
		// Null means unlimited (enrollment_limit === 0). Decremented below so a batch
		// that would overflow the course is previewed the same way create_items() enforces it.
		// seats_taken carries the claim from earlier chunks of the same client-side batch,
		// which the database can't know about yet.
		$remaining_seats = $course->get_enrollment_limit() > 0 ? max( 0, $course->get_available_seats() - absint( $request['seats_taken'] ) ) : null;

		$results = array();
		$totals  = array(
			'existing'         => 0,
			'already_enrolled' => 0,
			'reactivate'       => 0,
			'new'              => 0,
			'course_full'      => 0,
			'invalid'          => 0,
		);

		foreach ( $emails as $email ) {
			$raw_email = trim( (string) $email );
			$email     = sanitize_email( $raw_email );

			if ( empty( $email ) || ! is_email( $email ) ) {
				++$totals['invalid'];
				$results[] = $this->invalid_email_row( $raw_email );
				continue;
			}

			$user = get_user_by( 'email', $email );

			if ( ! $user ) {
				if ( $this->is_course_full( $remaining_seats, $override_seat_limit ) ) {
					++$totals['course_full'];
					$results[] = array(
						'email'  => $email,
						'status' => 'course_full',
					);
					continue;
				}

				++$totals['new'];
				$results[]       = array(
					'email'  => $email,
					'status' => 'new',
				);
				$remaining_seats = $this->decrement_seats( $remaining_seats );
				continue;
			}

			// Only an ACTIVE row counts as already enrolled; an INACTIVE row is reactivated by create_items() rather than silently no-op'd.
			if ( masteriyo_is_user_already_enrolled( $user->ID, $course_id, UserCourseStatus::ACTIVE ) ) {
				++$totals['already_enrolled'];
				$results[] = array(
					'email'   => $email,
					'status'  => 'already_enrolled',
					'user_id' => $user->ID,
				);
				continue;
			}

			$existing_user_course = $this->get_existing_user_course( $user->ID, $course_id );

			if ( $existing_user_course && UserCourseStatus::INACTIVE === $existing_user_course->get_status() ) {
				++$totals['reactivate'];
				$results[] = array(
					'email'   => $email,
					'status'  => 'reactivate',
					'user_id' => $user->ID,
				);
				continue;
			}

			// A brand-new row is the only case that consumes a seat — reactivation above does not, it already counts towards the course.
			if ( $this->is_course_full( $remaining_seats, $override_seat_limit ) ) {
				++$totals['course_full'];
				$results[] = array(
					'email'   => $email,
					'status'  => 'course_full',
					'user_id' => $user->ID,
				);
				continue;
			}

			++$totals['existing'];
			$results[]       = array(
				'email'   => $email,
				'status'  => 'existing',
				'user_id' => $user->ID,
			);
			$remaining_seats = $this->decrement_seats( $remaining_seats );
		}

		return rest_ensure_response(
			array(
				'course_id' => $course_id,
				'course'    => array(
					'id'          => $course->get_id(),
					'name'        => $course->get_name(),
					'access_mode' => $course->get_access_mode(),
					'price'       => $course->get_price(),
					// The confirm step must disclose that a one_time paid course is being granted for free via manual enrollment.
					'is_paid'     => CourseAccessMode::ONE_TIME === $course->get_access_mode() && (float) $course->get_price() > 0,
					// So the client can warn that the grant is time-limited.
					'expiration'  => array(
						'enabled'  => (bool) $course->get_enrollment_expiration_enabled(),
						'duration' => (int) $course->get_enrollment_expiration_duration(),
					),
				),
				'results'   => $results,
				'totals'    => $totals,
			)
		);
	}

	/**
	 * A request over the cap must fail clearly, not silently truncate or time out.
	 *
	 * @since 2.31.0
	 *
	 * @param array $emails Posted emails.
	 * @return \WP_Error|null
	 */
	protected function get_too_many_emails_error( $emails ) {
		if ( count( $emails ) <= self::MAX_EMAILS_PER_REQUEST ) {
			return null;
		}

		return new \WP_Error(
			'masteriyo_rest_too_many_emails',
			sprintf(
				/* translators: %d: maximum number of emails allowed per request. */
				__( 'A maximum of %d emails are allowed per request. Split the list into smaller batches and send them one at a time.', 'learning-management-system' ),
				self::MAX_EMAILS_PER_REQUEST
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Result row for an address that failed validation, shared by preview and create.
	 *
	 * sanitize_email() empties an invalid address, so echo the submitted text back —
	 * otherwise the admin can't see WHICH address was rejected.
	 *
	 * @since 2.31.0
	 *
	 * @param string $raw_email The address as submitted, trimmed.
	 * @return array
	 */
	protected function invalid_email_row( $raw_email ) {
		return array(
			'email'  => sanitize_text_field( $raw_email ),
			'status' => 'invalid',
		);
	}

	/**
	 * Whether the next brand-new enrollment would exceed the course's seat cap.
	 *
	 * @since 2.31.0
	 *
	 * @param int|null $remaining_seats      Null when the course has no enrollment_limit.
	 * @param bool     $override_seat_limit Whether the caller asked to bypass the cap.
	 * @return bool
	 */
	protected function is_course_full( $remaining_seats, $override_seat_limit ) {
		return ! $override_seat_limit && null !== $remaining_seats && $remaining_seats <= 0;
	}

	/**
	 * @since 2.31.0
	 *
	 * @param int|null $remaining_seats
	 * @return int|null
	 */
	protected function decrement_seats( $remaining_seats ) {
		return null === $remaining_seats ? null : $remaining_seats - 1;
	}

	/**
	 * Bulk-enroll students, creating accounts for unknown emails. Creates no order row.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_items( $request ) {
		$course_id = absint( $request['course_id'] );
		$emails    = (array) $request['emails'];

		$too_many_error = $this->get_too_many_emails_error( $emails );

		if ( $too_many_error ) {
			return $too_many_error;
		}

		$course = $this->get_valid_course( $course_id );

		if ( ! $course ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid course ID.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$access_mode_error = $this->get_course_access_mode_error( $course );

		if ( $access_mode_error ) {
			return $access_mode_error;
		}

		$status_error = $this->get_course_status_error( $course );

		if ( $status_error ) {
			return $status_error;
		}

		$override_seat_limit = (bool) $request['override_seat_limit'];
		$send_notifications  = (bool) $request['send_notifications'];
		$order_amount        = $request['order_amount'];
		$names_by_email      = (array) $request['names'];
		// Order creation is only ever honoured for a one_time course with a real price; free and zero-priced courses never get one, regardless of what was posted.
		$create_order = (bool) $request['create_order'] && CourseAccessMode::ONE_TIME === $course->get_access_mode() && (float) $course->get_price() > 0;

		$remaining_seats = $course->get_enrollment_limit() > 0 ? $course->get_available_seats() : null;

		$results = array();
		$totals  = array(
			'enrolled'         => 0,
			'reactivated'      => 0,
			'already_enrolled' => 0,
			'course_full'      => 0,
			'invalid'          => 0,
			'failed'           => 0,
		);

		// Attached only for the duration of this batch's account creations, removed unconditionally below — never left installed as a standing global filter.
		if ( ! $send_notifications ) {
			// masteriyo_new_user carries six listeners; these three are the ones a batch
			// row can trigger — the welcome-with-password, the address verification, and
			// the per-row admin notice, which is noise for a roster the admin uploaded.
			remove_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_automatic_registration_email_to_student' ), 10 );
			remove_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_email_verification_email' ), 11 );
			remove_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_student_registration_email_to_admin' ), 10 );
		}

		try {
			foreach ( $emails as $email ) {
				// A throw mid-batch would otherwise kill the request with no body, after earlier accounts, orders and enrolments are already committed. Report the row and keep going.
				try {
					$order_id = null;

					$raw_email = trim( (string) $email );
					$email     = sanitize_email( $raw_email );

					if ( empty( $email ) || ! is_email( $email ) ) {
						++$totals['invalid'];
						$results[] = $this->invalid_email_row( $raw_email );
						continue;
					}

					$user    = get_user_by( 'email', $email );
					$user_id = $user ? $user->ID : 0;

					// Only an ACTIVE row is already-enrolled; an INACTIVE row is reactivated below by loading and updating it directly — never via create().
					if ( $user_id && masteriyo_is_user_already_enrolled( $user_id, $course_id, UserCourseStatus::ACTIVE ) ) {
						++$totals['already_enrolled'];
						$results[] = array(
							'email'           => $email,
							'status'          => 'already_enrolled',
							'user_id'         => $user_id,
							'created_account' => false,
						);
						continue;
					}

					$existing_user_course = $user_id ? $this->get_existing_user_course( $user_id, $course_id ) : null;

					// A brand-new row (new account, or an existing user with no prior row) consumes a seat; reactivating an existing row does not.
					if ( ! $existing_user_course && $this->is_course_full( $remaining_seats, $override_seat_limit ) ) {
						++$totals['course_full'];
						$row = array(
							'email'  => $email,
							'status' => 'course_full',
						);
						if ( $user_id ) {
							$row['user_id'] = $user_id;
						}
						$results[] = $row;
						continue;
					}

					$created_account = false;

					if ( ! $user_id ) {
						// The client keys its map by lower-cased email; the raw and sanitized forms are tried too for older callers.
						$name_row = $names_by_email[ strtolower( $email ) ] ?? $names_by_email[ $email ] ?? $names_by_email[ $raw_email ] ?? array();
						$name_row = is_array( $name_row ) ? $name_row : array();

						// Empty username/password so the generate-username/password paths fire; remove both filters right after so they never leak past this call.
						add_filter( 'masteriyo_registration_is_generate_username', '__return_true' );
						add_filter( 'masteriyo_registration_is_generate_password', '__return_true' );

						$new_user = masteriyo_create_new_user(
							$email,
							'',
							'',
							Roles::STUDENT,
							array_filter(
								array(
									'first_name' => sanitize_text_field( $name_row['first_name'] ?? '' ),
									'last_name'  => sanitize_text_field( $name_row['last_name'] ?? '' ),
								)
							)
						);

						remove_filter( 'masteriyo_registration_is_generate_username', '__return_true' );
						remove_filter( 'masteriyo_registration_is_generate_password', '__return_true' );

						if ( is_wp_error( $new_user ) ) {
							++$totals['failed'];
							$results[] = array(
								'email'   => $email,
								'status'  => 'failed',
								'message' => $new_user->get_error_message(),
							);
							continue;
						}

						$user_id         = $new_user->get_id();
						$created_account = true;
					}

					$adopted_row = false;

					if ( $create_order ) {
						$order_result = $this->create_manual_enrollment_order( $user_id, $course_id, $order_amount );

						if ( is_wp_error( $order_result ) ) {
							++$totals['failed'];
							$results[] = array(
								'email'           => $email,
								'status'          => 'failed',
								'user_id'         => $user_id,
								'created_account' => $created_account,
								'message'         => $order_result->get_error_message(),
							);
							continue;
						}

						$order_id = $order_result;
						// The order creation just wrote/updated the user_course row via OrderRepository; read it back.
						$user_course = $this->get_existing_user_course( $user_id, $course_id );
					} elseif ( $existing_user_course ) {
						$existing_user_course->set_status( UserCourseStatus::ACTIVE );
						// Local time, not GMT: set_date_prop() reads a string as site-local and
						// converts it itself, so passing UTC applies the offset twice and dates
						// the enrolment wrong everywhere outside UTC.
						$existing_user_course->set_date_start( current_time( 'mysql' ) );
						$existing_user_course->save();
						$user_course = $existing_user_course;
					} else {
						$user_course = masteriyo( 'user-course' );

						if ( ! $user_course instanceof \Masteriyo\Models\UserCourse ) {
							throw new \RuntimeException( 'user-course container binding did not resolve to a UserCourse model.' );
						}
						$user_course->set_user_id( $user_id );
						$user_course->set_course_id( $course_id );
						$user_course->set_status( UserCourseStatus::ACTIVE );
						// Local time — see the note on the reactivation branch above.
						$user_course->set_date_start( current_time( 'mysql' ) );
						$user_course->save();

						/*
						 * On a duplicate-key race create() adopts the winning request's row
						 * instead of inserting — and only that read-back path flips
						 * object_read. The adopted row is someone else's write (typically a
						 * checkout), so the manual stamp below would relabel a purchase as
						 * an admin grant and mail the student a manual-enrollment notice
						 * for access they just bought.
						 */
						$adopted_row = $user_course->get_object_read();
					}

					if ( ! $user_course || ! $user_course->get_id() || UserCourseStatus::ACTIVE !== $user_course->get_status() ) {
						++$totals['failed'];
						$results[] = array(
							'email'           => $email,
							'status'          => 'failed',
							'user_id'         => $user_id,
							'created_account' => $created_account,
							'message'         => __( 'Could not enroll student.', 'learning-management-system' ),
						);
						continue;
					}

					// Every successful write from this endpoint is admin-granted — 'manual' even when create_order minted an order behind it — and stamped with who did it. An adopted row is the one exception: not this endpoint's write, so its source stays whatever its real origin says.
					if ( ! $adopted_row ) {
						$user_course->update_meta_data( '_source', 'manual' );
						$user_course->update_meta_data( '_granted_by', get_current_user_id() );
						$user_course->save_meta_data();

						// Pro schedules its manual-enrollment email to the student on this action, so it is
						// gated the same way as the registration emails above: the quiet checkbox means no
						// student-facing mail at all. Nothing else fires it for this endpoint's writes — the
						// OrdersController hook reads course_lines from the global request, which has none here.
						if ( $send_notifications ) {
							/** This action is documented in includes/RestApi/Controllers/Version1/OrdersController.php */
							do_action( 'masteriyo_user_enrolled_manually', $user_course, 'individual' );
						}
					}

					if ( $existing_user_course ) {
						++$totals['reactivated'];
						$status = 'reactivated';
					} else {
						++$totals['enrolled'];
						$status          = 'enrolled';
						$remaining_seats = $this->decrement_seats( $remaining_seats );
					}

					$results[] = array(
						'email'           => $email,
						'status'          => $status,
						'user_id'         => $user_id,
						'enrollment_id'   => $user_course->get_id(),
						'created_account' => $created_account,
						'order_id'        => $order_id,
					);
				} catch ( \Throwable $e ) {
					++$totals['failed'];
					$results[] = array(
						'email'    => $email,
						'status'   => 'failed',
						'message'  => $e->getMessage(),
						'order_id' => $order_id,
					);
				}
			}
		} finally {
			// Unconditional: a wedged hook would silence registration emails for the
			// rest of the request, and for a WP-CLI worker's whole lifetime.
			if ( ! $send_notifications ) {
				add_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_automatic_registration_email_to_student' ), 10, 3 );
				add_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_email_verification_email' ), 11, 2 );
				add_action( 'masteriyo_new_user', array( EmailHooks::class, 'schedule_student_registration_email_to_admin' ), 10, 2 );
			}
		}

		return rest_ensure_response(
			array(
				'course_id' => $course_id,
				'results'   => $results,
				'totals'    => $totals,
			)
		);
	}

	/**
	 * Create a completed manual-enrollment order for a one_time paid course, reusing the
	 * Orders REST machinery so totals, meta and the order->enrollment link
	 * (OrderRepository::create_or_update_user_course()) all follow the exact same path as
	 * every other order-driven enrollment.
	 *
	 * @since 2.31.0
	 *
	 * @param int    $user_id      Customer/user ID.
	 * @param int    $course_id    Course ID.
	 * @param string $order_amount 'zero' or 'full'.
	 * @return int|\WP_Error Order ID, or WP_Error on failure.
	 */
	protected function create_manual_enrollment_order( $user_id, $course_id, $order_amount ) {
		$course_line = array( 'course_id' => $course_id );

		// 'full', by contrast, posts no total/subtotal so prepare_course_lines() computes it from masteriyo_get_price_excluding_tax(), exactly as OrdersController does.
		if ( 'zero' === $order_amount ) {
			$course_line['total']    = 0;
			$course_line['subtotal'] = 0;
		}

		$order_request = new \WP_REST_Request( 'POST', '/' . $this->namespace . '/orders' );
		$order_request->set_param( 'customer_id', $user_id );
		$order_request->set_param( 'status', OrderStatus::COMPLETED );
		$order_request->set_param( 'created_via', 'manual-enrollment' );
		$order_request->set_param( 'course_lines', array( $course_line ) );

		if ( null === $this->orders_controller ) {
			$this->orders_controller = masteriyo( 'order.rest' );
		}

		$response = $this->orders_controller->create_item( $order_request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = rest_ensure_response( $response )->get_data();

		return isset( $data['id'] ) ? (int) $data['id'] : 0;
	}

	/**
	 * Revoke (delete) a single enrollment.
	 *
	 * Deletes the masteriyo_user_items row via the model/repository — never raw SQL —
	 * so meta rows and cache invalidation follow the same path as every other user-course
	 * write. Nothing this list can grant is left non-revocable.
	 *
	 * @since 2.31.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id = absint( $request['id'] );

		$user_course = masteriyo_get_user_course( $id );

		if ( is_null( $user_course ) || ! $user_course->get_id() || 'user_course' !== $user_course->get_type() ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_id',
				__( 'Invalid enrollment ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$previous = array(
			'id'        => $user_course->get_id(),
			'user_id'   => $user_course->get_user_id(),
			'course_id' => $user_course->get_course_id(),
			'status'    => $user_course->get_status(),
		);

		/*
		 * Revoking (setting the row inactive) is the default; hard deletion is opt-in. This
		 * matches how access is withdrawn everywhere else — the expiration job and
		 * course-trash both do the same — so a revoke round-trips through reactivation and
		 * the ledger keeps the record that access was once granted.
		 */
		if ( $request['force'] ) {
			if ( ! $user_course->delete( true ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_delete',
					__( 'The enrollment cannot be deleted.', 'learning-management-system' ),
					array( 'status' => 500 )
				);
			}

			/*
			 * Same duplicate-row rule as the revoke path below: on sites with historical
			 * duplicates, deleting only the clicked row would report "deleted" while an
			 * active sibling keeps the student enrolled. Active siblings go with it;
			 * inactive ones are separate historical records and stay.
			 */
			global $wpdb;
			$active_sibling_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}masteriyo_user_items
					WHERE user_id = %d AND item_id = %d AND item_type = 'user_course' AND status = %s",
					$previous['user_id'],
					$previous['course_id'],
					UserCourseStatus::ACTIVE
				)
			);

			foreach ( $active_sibling_ids as $sibling_id ) {
				$sibling = masteriyo_get_user_course( absint( $sibling_id ) );

				if ( $sibling && $sibling->get_id() ) {
					$sibling->delete( true );
				}
			}

			return rest_ensure_response(
				array(
					'deleted'  => true,
					'revoked'  => false,
					'previous' => $previous,
				)
			);
		}

		/*
		 * An already-inactive target skips only its own re-save. The duplicate-row
		 * sweep below must still run: on pre-unique-index sites the student may hold
		 * an ACTIVE sibling row, and answering "revoked" while that sibling keeps
		 * their access would leave the revoke without effect.
		 */
		if ( UserCourseStatus::INACTIVE !== $user_course->get_status() ) {
			$user_course->set_status( UserCourseStatus::INACTIVE );
			$user_course->save();
			// Attribute the revocation like grants are attributed; rows without it can
			// never be attributed retroactively.
			update_metadata( 'user_item', $user_course->get_id(), '_revoked_by', get_current_user_id() );
		}

		/*
		 * Historical duplicates (pre-unique-index) mean this student may hold OTHER active
		 * rows for the same course; leaving any one alive keeps their access and lets the
		 * dedupe migration later keep the active sibling over this revoked row. Revoke the
		 * whole (user, course) pair, not just the row that was clicked.
		 */
		global $wpdb;
		$sibling_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}masteriyo_user_items
				WHERE user_id = %d AND item_id = %d AND item_type = 'user_course' AND status = %s AND id != %d",
				$user_course->get_user_id(),
				$user_course->get_course_id(),
				UserCourseStatus::ACTIVE,
				$user_course->get_id()
			)
		);

		foreach ( $sibling_ids as $sibling_id ) {
			$sibling = masteriyo_get_user_course( absint( $sibling_id ) );

			if ( $sibling && $sibling->get_id() ) {
				$sibling->set_status( UserCourseStatus::INACTIVE );
				$sibling->save();
				update_metadata( 'user_item', $sibling->get_id(), '_revoked_by', get_current_user_id() );
			}
		}

		if ( UserCourseStatus::INACTIVE !== $user_course->get_status() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_revoke',
				__( 'Access to this course could not be revoked.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'deleted'  => false,
				'revoked'  => true,
				'previous' => $previous,
			)
		);
	}
}
