<?php
/**
 * Analytics controller.
 *
 * @since 1.6.7
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\DateTime;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Roles;

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

		add_action( 'masteriyo_new_user_course', array( $this, 'delete_user_courses_related_cache_keys' ), 10, 2 );
		add_action( 'masteriyo_update_user_course', array( $this, 'delete_user_courses_related_cache_keys' ), 10, 2 );
		add_action( 'masteriyo_delete_user_course', array( $this, 'delete_user_courses_related_cache_keys' ), 10, 2 );

		add_action( 'masteriyo_new_course', array( $this, 'delete_courses_list_cache' ), 10 );
		add_action( 'masteriyo_update_course', array( $this, 'delete_courses_list_cache' ), 10 );
		add_action( 'masteriyo_trash_course', array( $this, 'delete_courses_list_cache' ), 10 );
	}

	/**
	 * Clears the cached course ID list used by analytics queries.
	 * Fires when any course is created, updated, or trashed.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function delete_courses_list_cache() {
		masteriyo_transient_cache()->clear_caches( 'analytics_courses_list_group' );
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
						'start_date' => array(
							'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'learning-management-system' ),
							'type'              => 'string',
							'format'            => 'date-time',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'end_date'   => array(
							'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'learning-management-system' ),
							'type'              => 'string',
							'format'            => 'date-time',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
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
	 * Prepare items for response.
	 *
	 * @since 1.6.7
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	protected function prepare_items_for_response( \WP_REST_Request $request ) {
		$start_date = masteriyo_analytics_normalize_datetime( $request->get_param( 'start_date' ), 'start' );
		$end_date   = masteriyo_analytics_normalize_datetime( $request->get_param( 'end_date' ), 'end' );
		$items      = array();

		$courses_data    = $this->get_courses_data();
		$course_ids      = $courses_data['ids'];
		$course_id_param = absint( $request->get_param( 'course_id' ) );

		if ( $course_id_param && in_array( $course_id_param, $course_ids, true ) ) {
			$course_ids = array( $course_id_param );
		}

		$items['courses'] = array(
			'total' => $this->get_courses_count( $course_ids, $start_date, $end_date ),
		);

		$items['lessons']           = $this->get_lessons_data( $course_ids, $start_date, $end_date );
		$items['quizzes']           = $this->get_quizzes_data( $course_ids, $start_date, $end_date );
		$items['questions']         = $this->get_questions_data( $course_ids, $start_date, $end_date );
		$items['questions_answers'] = $this->get_questions_answers_data( $course_ids, $start_date, $end_date );
		$items['reviews']           = $this->get_reviews_data( $course_ids, $start_date, $end_date );
		$items['instructors']       = $this->get_instructors_data( $start_date, $end_date );
		$items['user_courses']      = $this->get_enrolled_courses_data( $course_ids, $start_date, $end_date );

		/**
		 * Filters rest prepared analytics items.
		 *
		 * @since 1.6.7
		 *
		 * @param array $items Items data.
		 * @param \WP_REST_Request $request Request.
		 */
		return apply_filters( 'masteriyo_rest_prepared_analytics_items', $items, $request );
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
	 * Get lessons count.
	 *
	 * @since 1.6.7
	 *
	 * @param array $course_ids Course IDs.
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
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
					'fields'         => 'ids',
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
	 * @param array $course_ids Course IDs.
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
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
					'fields'         => 'ids',
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
	 * @param array $course_ids Course IDs.
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
					'date_query'     => $this->analytics_date_query( $start_date, $end_date ),
					'fields'         => 'ids',
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
	 * @param array $course_ids Course IDs.
	 *
	 * @return array
	 */
	protected function get_instructors_data( $start_date = null, $end_date = null ) {
		$cache     = masteriyo_transient_cache();
		$range_key = ( $start_date && $end_date )
			? gmdate( 'Y-m-d', strtotime( $start_date ) ) . '_' . gmdate( 'Y-m-d', strtotime( $end_date ) )
			: 'all';
		$cache_key = 'analytics_instructors_count_' . $range_key;
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
	 * Get reviews count.
	 *
	 * @since 1.6.7
	 *
	 * @param array $course_ids Course IDs.
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
	 * Deletes user courses related cache keys.
	 *
	 * This function deletes the user courses related cache keys by clearing the caches
	 * for the 'analytics_user_courses_group' transient. It takes two parameters:
	 * - $id: The ID of the user courses related cache key.
	 * - $user_course: An instance of the \Masteriyo\Models\UserCourse class.
	 *
	 * @since 1.13.0
	 *
	 * @param mixed                        $id          The ID of the user courses related cache key.
	 * @param \Masteriyo\Models\UserCourse $user_course An instance of the \Masteriyo\Models\UserCourse class.
	 *
	 * @return void
	 */
	public function delete_user_courses_related_cache_keys( $id, $user_course ) {
		if ( ! $id || ! ( $user_course instanceof \Masteriyo\Models\UserCourse ) ) {
			return;
		}

		masteriyo_transient_cache()->clear_caches( 'analytics_user_courses_group' );
	}

	/**
	 * Generates an array of cache keys for analytics data based on the provided user ID, start date, and end date.
	 *
	 * @since 1.13.0
	 *
	 * @param int|null    $user_id    The ID of the user (default: null)
	 * @param string|null $start_date The start date for the analytics data (default: null)
	 * @param string|null $end_date   The end date for the analytics data (default: null)
	 * @param array       $course_ids The course IDs the data is scoped to (default: empty).
	 *
	 * @return array An array of cache keys for analytics data
	 */
	private function analytics_cache_keys( $user_id = null, $start_date = null, $end_date = null, $course_ids = array() ) {
		$is_admin_or_manager  = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$current_user_id      = $user_id ? $user_id : get_current_user_id();
		$start_date_formatted = $start_date ? gmdate( 'Y-m-d', strtotime( $start_date ) ) : 'no_start_date';
		$end_date_formatted   = $end_date ? gmdate( 'Y-m-d', strtotime( $end_date ) ) : 'no_end_date';
		$course_key           = empty( $course_ids ) ? 'all' : md5( implode( ',', array_map( 'absint', (array) $course_ids ) ) );

		return array(
			'enrolled_courses_data' => 'analytics_enrolled_courses_data_' . ( $is_admin_or_manager ? 'all_' : $current_user_id . '_' ) . $start_date_formatted . '_' . $end_date_formatted . '_' . $course_key,
		);
	}

	/**
	 * Build a WP date_query clause for the given range, or empty when no range is set.
	 *
	 * @since x.x.x
	 *
	 * @param string|null $start  Normalized start date ('Y-m-d H:i:s') or null.
	 * @param string|null $end    Normalized end date ('Y-m-d H:i:s') or null.
	 * @param string      $column Optional date column to filter on (e.g. 'user_registered').
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
	 * Count courses created within the date range, scoped to the given course IDs.
	 *
	 * Falls back to the full count of $course_ids when no date range is supplied.
	 *
	 * @since x.x.x
	 *
	 * @param array       $course_ids Course IDs that form the filter base.
	 * @param string|null $start_date Normalized start date ('Y-m-d H:i:s') or null.
	 * @param string|null $end_date   Normalized end date ('Y-m-d H:i:s') or null.
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
	 * Get question/answers count.
	 *
	 * @since 1.6.7
	 *
	 * @param array $course_ids Course IDs.
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
	 * @param  \WP_REST_Request $request Request.
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
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$placeholders = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );

			// Clamp the series start to the first enrollment so very wide ranges
			// (e.g. "All Time" starting in 2000) don't crush recent data into an
			// invisible sliver — only kicks in when the requested start predates
			// any enrollment, leaving normal ranges untouched.
			$earliest = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(date_start)
					FROM {$wpdb->prefix}masteriyo_user_items
					WHERE item_id IN ($placeholders) AND status = %s",
					array_merge( $course_ids, array( UserCourseStatus::ACTIVE ) )
				)
			);

			if ( $earliest && strtotime( $earliest ) > strtotime( $start_date ) ) {
				$start_date = gmdate( 'Y-m-d 00:00:00', strtotime( $earliest ) );
			}

			$data['data'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(date_start) as date, COUNT(*) as count
					FROM {$wpdb->prefix}masteriyo_user_items
					WHERE item_id IN ($placeholders)
					AND status = %s
					AND DATE(date_start) >= %s AND DATE(date_start) <= %s
					GROUP BY DATE(date_start)",
					array_merge( $course_ids, array( UserCourseStatus::ACTIVE, $start_date, $end_date ) )
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		}

		$data['data'] = $this->format_series_data( $data['data'] ?? array(), $start_date, $end_date );

		$cache->set_cache( $cache_key, $data, DAY_IN_SECONDS, 'analytics_user_courses_group' );

		return $data;
	}

	/**
	 * Format series data with automatic bucket coarsening.
	 *
	 * Folds sparse daily SQL rows into day/week/month buckets so large ranges
	 * (e.g. "All Time") stay bounded to a few hundred points instead of one per day.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $data  Rows with 'date' and 'count' keys.
	 * @param string $start Start date string.
	 * @param string $end   End date string.
	 *
	 * @return array
	 */
	protected function format_series_data( $data, $start, $end ) {
		$bucket = $this->get_analytics_bucket_interval( $start, $end );

		$acc = array();
		foreach ( $data as $row ) {
			if ( ! isset( $row['date'] ) ) {
				continue;
			}
			$key = $this->get_analytics_bucket_key( new \DateTime( $row['date'] ), $bucket );
			if ( ! isset( $acc[ $key ] ) ) {
				$acc[ $key ] = 0;
			}
			$acc[ $key ] += (int) ( $row['count'] ?? 0 );
		}

		$formatted_data = array();
		foreach ( $this->get_analytics_bucket_dates( $start, $end, $bucket ) as $key ) {
			$formatted_data[] = array(
				'date'  => $key,
				'count' => $acc[ $key ] ?? 0,
			);
		}

		return $formatted_data;
	}

	/**
	 * Pick the time-series bucket size for a date range so large ranges stay bounded.
	 *
	 * @since x.x.x
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 *
	 * @return string One of 'day', 'week', 'month'.
	 */
	private function get_analytics_bucket_interval( $start, $end ) {
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
	 * @since x.x.x
	 *
	 * @param \DateTime $date     Date to map.
	 * @param string    $interval 'day', 'week' or 'month'.
	 *
	 * @return string Y-m-d bucket-start key.
	 */
	private function get_analytics_bucket_key( \DateTime $date, $interval ) {
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
	 * @since x.x.x
	 *
	 * @param string $start    Start date.
	 * @param string $end      End date.
	 * @param string $interval 'day', 'week' or 'month'.
	 *
	 * @return string[] Y-m-d bucket-start keys.
	 */
	private function get_analytics_bucket_dates( $start, $end, $interval ) {
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
}
