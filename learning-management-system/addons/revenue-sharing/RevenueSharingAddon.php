<?php
/**
 * Revenue sharing addon.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\Addons\RevenueSharing
 */

namespace Masteriyo\Addons\RevenueSharing;

use Masteriyo\Addons\RevenueSharing\PostType\Earning;
use Masteriyo\Addons\RevenueSharing\PostType\Withdraw;
use Masteriyo\Addons\RevenueSharing\Controllers\WithdrawsController;
use Masteriyo\Addons\RevenueSharing\Query\EarningQuery;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\AddonsFramework\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Revenue sharing addon class.
 *
 * @since 1.6.14
 */
class RevenueSharingAddon {

	/**
	 * Setting object.
	 *
	 * @since 1.6.14
	 *
	 * @var Setting
	 */
	public $setting = null;

	/**
	 * Constructor.
	 *
	 * @since 1.6.14
	 *
	 * @param Setting $setting Setting object.
	 */
	public function __construct( Setting $setting ) {
		$this->setting = $setting;
	}

	/**
	 * Init addon.
	 *
	 * @since 1.6.14
	 */
	public function init() {
		$this->setting->init();
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.6.14
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_post_types' ) );

		if ( $this->is_revenue_sharing_enabled() ) {
			add_filter( 'masteriyo_admin_submenus', array( $this, 'add_withdraw_submenu' ) );
			add_action( 'masteriyo_new_order', array( $this, 'create_earning' ), 10, 2 );
			add_action( 'masteriyo_order_status_changed', array( $this, 'update_earnings' ), 10, 3 );
			add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
			add_filter( 'masteriyo_rest_pre_insert_user_object', array( $this, 'save_user_withdraw_data' ), 10, 3 );
			add_filter( 'masteriyo_rest_response_user_data', array( $this, 'append_withdraw_data_to_response' ), 10, 2 );
			add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
			add_filter( 'masteriyo_rest_prepared_analytics_course_items', array( $this, 'append_commission_data' ), 10, 2 );
			add_filter( 'masteriyo_rest_prepared_analytics_items', array( $this, 'append_analytics_time_series' ), 10, 3 );
			add_filter( 'masteriyo_analytics_timeseries_data', array( $this, 'correct_popular_courses_earnings' ), 20, 2 );
			add_filter( 'masteriyo_analytics_summary_data', array( $this, 'correct_popular_courses_earnings' ), 20, 2 );
		}
	}

	/**
	 * add commissions for admin and instructors
	 *
	 * @since 2.15.0
	 */
	public function append_commission_data( $items, $request ) {
		$courses_data = $request->get_url_params();
		$course_id    = $courses_data['id'];
		$course       = masteriyo_get_course( $course_id );

		$author_id = $course->get_author_id();
		if ( masteriyo_is_user_admin( $author_id ) ) {
			return $items;
		}
		$items['commissions'] = $this->get_commission_data( $course_id );
		return $items;
	}

	/**
	 * Append instructor_earnings time-series to analytics dashboard data.
	 *
	 * @param array            $items      Analytics items.
	 * @param \WP_REST_Request $request    Request.
	 * @param array            $course_ids Course IDs used to scope analytics data.
	 *
	 * @return array
	 */
	public function append_analytics_time_series( $items, $request, $course_ids = array() ) {
		$start_date = masteriyo_analytics_normalize_datetime( $request->get_param( 'start_date' ), 'start' );
		$end_date   = masteriyo_analytics_normalize_datetime( $request->get_param( 'end_date' ), 'end' );
		$course_ids = array_filter( array_map( 'absint', (array) $course_ids ) );

		if ( ! $start_date || ! $end_date || empty( $course_ids ) ) {
			$items['instructor_earnings'] = array( 'data' => array() );
			return $items;
		}

		global $wpdb;

		$course_ids_placeholder = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(p.post_date) AS date, COUNT(*) AS count,
				SUM(CAST(pm.meta_value AS DECIMAL(10,4))) AS amount
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_instructor_amount'
				INNER JOIN {$wpdb->postmeta} course_meta ON p.ID = course_meta.post_id AND course_meta.meta_key = '_course_id'
				LEFT JOIN {$wpdb->postmeta} order_meta ON p.ID = order_meta.post_id AND order_meta.meta_key = '_order_id'
				LEFT JOIN {$wpdb->posts} order_post ON order_post.ID = CAST(order_meta.meta_value AS UNSIGNED)
				WHERE p.post_type = %s
				AND (
					p.post_status = %s
					OR (p.post_status = 'publish' AND order_post.post_status = %s)
				)
				AND course_meta.meta_value IN ($course_ids_placeholder)
				AND p.post_date >= %s AND p.post_date <= %s
				GROUP BY DATE(p.post_date)
				ORDER BY DATE(p.post_date) ASC",
				array_merge( array( PostType::EARNING, OrderStatus::COMPLETED, OrderStatus::COMPLETED ), $course_ids, array( $start_date, $end_date ) )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$start = new \DateTime( gmdate( 'Y-m-d', strtotime( $start_date ) ) );
		$end   = new \DateTime( gmdate( 'Y-m-d', strtotime( $end_date ) ) );
		$end->modify( '+1 day' );
		$period  = new \DatePeriod( $start, new \DateInterval( 'P1D' ), $end );
		$indexed = array();
		foreach ( $results ?? array() as $row ) {
			$indexed[ $row['date'] ] = $row;
		}

		$series = array();
		foreach ( $period as $date ) {
			$key      = $date->format( 'Y-m-d' );
			$row      = $indexed[ $key ] ?? null;
			$series[] = array(
				'date'   => $key,
				'count'  => $row ? (int) $row['count'] : 0,
				'amount' => $row ? (float) $row['amount'] : 0,
			);
		}

		$items['instructor_earnings'] = array( 'data' => $series );

		return $items;
	}

	/**
	 * Replace the Popular Courses table earnings with the instructor's revenue-share cut.
	 *
	 * The shared analytics controller sums the gross order total for each course, which
	 * is what the site owner is paid, not what the instructor keeps. Admins and managers
	 * view business-wide totals, so their figure is left as the gross amount.
	 *
	 * @since 3.3.2
	 *
	 * @param array            $items   Analytics payload.
	 * @param \WP_REST_Request $request Request object, carrying the date range.
	 *
	 * @return array
	 */
	public function correct_popular_courses_earnings( $items, $request = null ) {
		if ( ! is_array( $items ) || empty( $items['popular_courses'] ) || ! is_array( $items['popular_courses'] ) ) {
			return $items;
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return $items;
		}

		$ids = array_filter( array_map( 'absint', wp_list_pluck( $items['popular_courses'], 'id' ) ) );

		if ( empty( $ids ) ) {
			return $items;
		}

		// The analytics filters pass no date range, so read it from the request the
		// same way the gross Popular Courses query does, or the figure ignores the
		// date picker and sums every sale ever.
		$start_date = $request instanceof \WP_REST_Request ? masteriyo_analytics_normalize_datetime( $request->get_param( 'start_date' ), 'start' ) : null;
		$end_date   = $request instanceof \WP_REST_Request ? masteriyo_analytics_normalize_datetime( $request->get_param( 'end_date' ), 'end' ) : null;

		$shares = $this->get_instructor_earnings_by_course( $ids, get_current_user_id(), $start_date, $end_date );

		foreach ( $items['popular_courses'] as &$course ) {
			$course_id          = absint( $course['id'] );
			$course['earnings'] = masteriyo_format_decimal( isset( $shares[ $course_id ] ) ? $shares[ $course_id ] : 0 );
		}
		unset( $course );

		return $items;
	}

	/**
	 * Sum each course's instructor share from its earning records.
	 *
	 * Reads the same `_instructor_amount` earning meta the Withdraw page uses, scoped to
	 * the instructor and, when a range is given, the earning date.
	 *
	 * @since 3.3.2
	 *
	 * @param int[]       $course_ids Course IDs.
	 * @param int         $user_id    Instructor user ID.
	 * @param string|null $start_date Range start, Y-m-d H:i:s.
	 * @param string|null $end_date   Range end, Y-m-d H:i:s.
	 *
	 * @return array Map of course ID to instructor amount.
	 */
	protected function get_instructor_earnings_by_course( $course_ids, $user_id, $start_date = null, $end_date = null ) {
		global $wpdb;

		if ( ! $wpdb || empty( $course_ids ) ) {
			return array();
		}

		// Match the sibling instructor-earnings query: legacy shares are stored as
		// `publish` with the completed order carrying the status, so accept those too
		// or existing installs under-report on Popular Courses. EXISTS keeps duplicate
		// `_order_id` meta rows from multiplying the SUM.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT CAST(cm.meta_value AS UNSIGNED) AS course_id,
					SUM(CAST(am.meta_value AS DECIMAL(10,4))) AS instructor_amount
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} am ON p.ID = am.post_id AND am.meta_key = '_instructor_amount'
				INNER JOIN {$wpdb->postmeta} cm ON p.ID = cm.post_id AND cm.meta_key = '_course_id'
				WHERE p.post_type = %s
					AND (
						p.post_status = %s
						OR ( p.post_status = 'publish' AND EXISTS (
							SELECT 1
							FROM {$wpdb->postmeta} om
							INNER JOIN {$wpdb->posts} op ON op.ID = CAST(om.meta_value AS UNSIGNED)
							WHERE om.post_id = p.ID AND om.meta_key = '_order_id' AND op.post_status = %s
						) )
					)
					AND p.post_author = %d
					AND FIND_IN_SET( cm.meta_value, %s )
					AND p.post_date >= %s
					AND p.post_date <= %s
				GROUP BY cm.meta_value",
				PostType::EARNING,
				OrderStatus::COMPLETED,
				OrderStatus::COMPLETED,
				absint( $user_id ),
				// CSV instead of an IN() placeholder list keeps the statement static.
				// postmeta.meta_value is unindexed, so IN() bought no seek anyway.
				implode( ',', array_map( 'absint', $course_ids ) ),
				$start_date ? $start_date : '1970-01-01 00:00:00',
				$end_date ? $end_date : '9999-12-31 23:59:59'
			)
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ absint( $row->course_id ) ] = (float) $row->instructor_amount;
		}

		return $map;
	}

	/**
	 * get instructors and admin percentage
	 *
	 * @since 2.15.0
	 */
	public function get_commission_data( $course_id ) {
		$earnings = ( new EarningQuery(
			array(
				'post_type' => PostType::EARNING,
				'course_id' => $course_id,
				'per_page'  => -1,
				'status'    => 'completed',
			)
		) )->get_earnings();

		if ( ! empty( $earnings ) ) {
				$totals = $this->calculate_earnings_commissions( $earnings );
				return $totals;
		}
	}


	/**
	 * get commissions amount for admin and instructor
	 *
	 * @since 2.15.0
	 * @param object $earnings
	 * @return array
	 */
	public function calculate_earnings_commissions( $earnings ) {

		$totals = array(
			'admin_total'        => 0,
			'instructor_total'   => 0,
			'deductible_total'   => 0,
			'grand_total_amount' => 0,
			'total_amount'       => 0,
		);

		foreach ( $earnings as $earning ) {
			$totals['admin_total']        += $earning->get_admin_amount();
			$totals['instructor_total']   += $earning->get_instructor_amount();
			$totals['deductible_total']    = $earning->get_deductible_fee_amount();
			$totals['grand_total_amount'] += $earning->get_grand_total_amount();
			$totals['total_amount']       += $earning->get_total_amount();
		}

		foreach ( $totals as $key => &$total ) {
			$total = masteriyo_price( $total, array( 'html' => false ) );
		}
		return $totals;
	}





	/**
	 * Localize admin scripts.
	 *
	 * @since 1.6.14
	 * @param array $scripts Scripts.
	 * @return array
	 */
	public function localize_admin_scripts( $scripts ) {
		$scripts['backend']['data']['can_manage_withdraws'] = masteriyo_bool_to_string( current_user_can( 'manage_withdraws' ) );
		return $scripts;
	}

	/**
	 * Is revenue sharing enabled.
	 *
	 * @since 1.6.14
	 * @return bool
	 */
	protected function is_revenue_sharing_enabled() {
		return $this->setting->get( 'enable', false );
	}

	/**
	 * Add public script data.
	 *
	 * @since 1.6.14
	 *
	 * @param array $script_data Script data.
	 * @return array
	 */
	public function localize_public_scripts( $script_data ) {
		$script_data['account']['data']['withdraw_methods']           = $this->setting->get( 'withdraw.methods' );
		$script_data['account']['data']['is_revenue_sharing_enabled'] = masteriyo_bool_to_string(
			( new Addons() )->is_active( 'revenue-sharing' ) && $this->is_revenue_sharing_enabled()
		);

		return $script_data;
	}

	/**
	 * Register namespaces.
	 *
	 * @since 1.6.14
	 *
	 * @param array $namespaces Rest namespaces.
	 * @return array
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['withdraws'] = WithdrawsController::class;
		return $namespaces;
	}

	/**
	 * Add withdraw submenu.
	 *
	 * @since 1.6.14
	 *
	 * @param array $submenus Submenus.
	 */
	public function add_withdraw_submenu( $submenus ) {
		$submenus['withdraws'] = array(
			'page_title' => __( 'Withdraws', 'learning-management-system' ),
			'menu_title' => '↳ ' . __( 'Withdraws', 'learning-management-system' ),
			'capability' => 'manage_withdraws',
			'position'   => 17,
			'hide'       => true,
		);

		return $submenus;
	}

	/**
	 * Register post types.
	 *
	 * @since 1.6.14
	 *
	 * @param array $post_types
	 * @return array
	 */
	public function register_post_types( $post_types ) {
		$post_types['earning']  = Earning::class;
		$post_types['withdraw'] = Withdraw::class;

		return $post_types;
	}

	/**
	 * Create earning.
	 *
	 * @param int $order_id Order ID.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function create_earning( $order_id, $order ) {
		$order_item = current( $order->get_items() );

		if ( ! $order_item ) {
			return;
		}

		if ( masteriyo_is_bundle_order_item( $order_item ) ) {
			return;
		}

		$course = $order_item->get_course();

		if ( ! $course ) {
			return;
		}

		$earning = masteriyo_create_earning_object();

		$earning->set_status( $order->get_status() );
		$earning->set_course_id( $course->get_id() );
		$earning->set_order_id( $order_id );
		$earning->set_user_id( $course->get_author_id() );

		$admin_rate            = $this->setting->get( 'admin_rate' );
		$instructor_rate       = $this->setting->get( 'instructor_rate' );
		$deductible_fee        = $this->setting->get( 'deductible_fee.enable' );
		$deductible_fee_type   = $this->setting->get( 'deductible_fee.type' );
		$deductible_fee_amount = $this->setting->get( 'deductible_fee.amount', 0 );
		$deductible_fee_name   = $this->setting->get( 'deductible_fee.name' );

		$earning->set_deductible_fee_type( $deductible_fee_type );
		$earning->set_admin_rate( $admin_rate );
		$earning->set_instructor_rate( $instructor_rate );

		$total = floatval( $order->get_total() );

		if ( $deductible_fee ) {
			$earning->set_deductible_fee_name( $deductible_fee_name );
			$earning->set_deductible_fee_type( $deductible_fee_type );

			if ( $deductible_fee_amount ) {
				$earning->set_deductible_fee_amount( $deductible_fee_amount );
				$deductible_fee_amount = 'percentage' === $deductible_fee_type ? ( $total * ( $deductible_fee_amount / 100 ) ) : $deductible_fee_amount;
			}
		}

		$total_after_fee_deduction = $total - $deductible_fee_amount;

		$earning->set_grand_total_amount( $total );
		$earning->set_total_amount( $total_after_fee_deduction );
		$earning->set_deductible_fee_amount( $deductible_fee_amount );
		$earning->set_admin_amount( $total_after_fee_deduction * ( $admin_rate / 100 ) );
		$earning->set_instructor_amount( $total_after_fee_deduction * ( $instructor_rate / 100 ) );

		$earning->save();
	}

	/**
	 * Update earnings.
	 *
	 * @since 1.6.14
	 *
	 * @param int $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 */
	public function update_earnings( $order_id, $old_status, $new_status ) {
		$earnings = ( new EarningQuery(
			array(
				'post_type'   => PostType::EARNING,
				'post_status' => $old_status,
				'per_page'    => 1,
				'order_id'    => $order_id,
			)
		) )->get_earnings();

		if ( ! $earnings ) {
			return;
		}

		$earning = current( $earnings );

		$earning->set_status( $new_status );
		$earning->save();
	}

	/**
	 * @param \Masteriyo\Database\Model $user User object.
	 * @param \WP_REST_Request $request  Request object.
	 * @param bool $creating If is creating a new object.
	 */
	public function save_user_withdraw_data( $user, $request, $creating ) {
		if ( isset( $request['withdraw_method_preference'] ) ) {
			update_user_meta( $user->get_id(), '_withdraw_method_preference', $request['withdraw_method_preference'] );
		}
		return $user;
	}

	/**
	 * Append withdraw data to response.
	 *
	 * @since 1.6.14
	 *
	 * @param array $response The response object.
	 * @param \Masteriyo\Models\User $user User object .
	 */
	public function append_withdraw_data_to_response( $data, $user ) {
		$withdraw              = get_user_meta( $user->get_id(), '_withdraw_method_preference', true );
		$min_withdrawal_amount = $this->setting->get( 'withdraw.min_amount', 0 );

		$data['revenue_sharing'] = array(
			'withdraw_method_preference'        => $withdraw ? $withdraw : array(),
			'minimum_withdraw_amount'           => $min_withdrawal_amount,
			'minimum_withdraw_amount_formatted' => masteriyo_price(
				$min_withdrawal_amount,
				array(
					'html'                 => false,
					'show_price_free_text' => false,
				)
			),
		);

		$data['revenue_sharing'] += masteriyo_get_earning_summary( $user->get_id() );

		return $data;
	}
}
