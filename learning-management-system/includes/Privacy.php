<?php
/**
 * GDPR / personal-data integration with WordPress core privacy tools.
 *
 * @package Masteriyo
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Masteriyo with WordPress's export/erase personal-data tools and
 * stores the consent given on registration and checkout.
 */
class Privacy {

	/**
	 * User meta key holding the consent records (a list of {context, message, timestamp}).
	 *
	 * @var string
	 */
	const CONSENT_META_KEY = '_masteriyo_gdpr_consent';

	/**
	 * How many records one export/erase page handles.
	 *
	 * @var int
	 */
	const PAGE_SIZE = 100;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
	}

	/**
	 * Register the personal-data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 *
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['masteriyo'] = array(
			/* translators: %s: the product's name */
			'exporter_friendly_name' => sprintf( __( '%s LMS Data', 'learning-management-system' ), masteriyo_get_plugin_name() ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Register the personal-data eraser.
	 *
	 * @param array $erasers Registered erasers.
	 *
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['masteriyo'] = array(
			/* translators: %s: the product's name */
			'eraser_friendly_name' => sprintf( __( '%s LMS Data', 'learning-management-system' ), masteriyo_get_plugin_name() ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Export one page of a learner's Masteriyo personal data.
	 *
	 * Each source (consent, enrollments, progress, quiz attempts, orders) is
	 * split into fixed-size chunks so a learner with a lot of history never
	 * loads everything at once; WordPress calls this until `done` is true.
	 *
	 * @param string $email Email address to export for.
	 * @param int    $page  1-based page number.
	 *
	 * @return array
	 */
	public function export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$page   = max( 1, (int) $page );
		$chunks = $this->export_chunks( $user );

		if ( $page > count( $chunks ) ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		list( $slice, $offset ) = $chunks[ $page - 1 ];

		return array(
			'data' => call_user_func( $slice, $user, $offset, self::PAGE_SIZE ),
			'done' => $page >= count( $chunks ),
		);
	}

	/**
	 * The ordered list of export chunks, one per page: [ slice_callback, offset ].
	 *
	 * @param \WP_User $user User.
	 *
	 * @return array
	 */
	protected function export_chunks( $user ) {
		$sources = array(
			array( array( $this, 'count_account_details' ), array( $this, 'export_account_details' ) ),
			array( array( $this, 'count_consent' ), array( $this, 'export_consent' ) ),
			array( array( $this, 'count_enrollments' ), array( $this, 'export_enrollments' ) ),
			array( array( $this, 'count_progress' ), array( $this, 'export_progress' ) ),
			array( array( $this, 'count_quiz_attempts' ), array( $this, 'export_quiz_attempts' ) ),
			array( array( $this, 'count_notifications' ), array( $this, 'export_notifications' ) ),
			array( array( $this, 'count_orders' ), array( $this, 'export_orders' ) ),
		);

		$chunks = array();

		foreach ( $sources as $source ) {
			$count = (int) call_user_func( $source[0], $user );

			for ( $offset = 0; $offset < $count; $offset += self::PAGE_SIZE ) {
				$chunks[] = array( $source[1], $offset );
			}
		}

		return $chunks;
	}

	/**
	 * Erase one page of a learner's Masteriyo personal data.
	 *
	 * Learning records and consent are deleted; orders are anonymized in
	 * fixed-size chunks and retained for the store's own accounting obligations.
	 *
	 * @param string $email Email address to erase for.
	 * @param int    $page  1-based page number.
	 *
	 * @return array
	 */
	public function erase( $email, $page = 1 ) {
		global $wpdb;

		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return $response;
		}

		$user_id = (string) $user->ID;

		// Learning records and consent go in bounded single statements, so they
		// run on every page — deleting nothing the second time — rather than
		// being special-cased to page one. What pages the request is the order
		// anonymization below: an anonymized order drops out of the query, so
		// each call takes the next chunk until none are left.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}masteriyo_user_itemmeta WHERE user_item_id IN ( SELECT id FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %s )", $user_id ) ); // phpcs:ignore WordPress.DB
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}masteriyo_user_activitymeta WHERE user_activity_id IN ( SELECT id FROM {$wpdb->prefix}masteriyo_user_activities WHERE user_id = %s )", $user_id ) ); // phpcs:ignore WordPress.DB

		$removed  = (int) $wpdb->delete( "{$wpdb->prefix}masteriyo_user_items", array( 'user_id' => $user_id ), array( '%s' ) );
		$removed += (int) $wpdb->delete( "{$wpdb->prefix}masteriyo_user_activities", array( 'user_id' => $user_id ), array( '%s' ) );
		$removed += (int) $wpdb->delete( "{$wpdb->prefix}masteriyo_quiz_attempts", array( 'user_id' => $user_id ), array( '%s' ) );
		$removed += (int) $wpdb->delete( "{$wpdb->prefix}masteriyo_notifications", array( 'user_id' => $user->ID ), array( '%d' ) );

		if ( metadata_exists( 'user', $user->ID, self::CONSENT_META_KEY ) ) {
			delete_user_meta( $user->ID, self::CONSENT_META_KEY );
			++$removed;
		}

		foreach ( $this->account_meta_keys() as $key ) {
			if ( metadata_exists( 'user', $user->ID, $key ) ) {
				delete_user_meta( $user->ID, $key );
				++$removed;
			}
		}

		$order_ids = $this->get_customer_order_ids( $user->ID );
		$retained  = $this->anonymize_orders( array_slice( $order_ids, 0, self::PAGE_SIZE ) );

		// anonymize_orders() clears the customer id on this page's orders, so
		// anything beyond it is a later page — no need to re-query to find out.
		$more_orders = count( $order_ids ) > self::PAGE_SIZE;

		$response['items_removed']  = $removed > 0;
		$response['items_retained'] = $retained > 0;
		$response['done']           = ! $more_orders;

		if ( $retained > 0 ) {
			/* translators: %s: the product's name */
			$response['messages'][] = sprintf( __( '%s orders were anonymized but retained for accounting and tax records.', 'learning-management-system' ), masteriyo_get_plugin_name() );
		}

		return $response;
	}

	/**
	 * Suggest privacy-policy text describing the learner data Masteriyo stores.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = __( 'When you register, enroll in courses, take quizzes or purchase a course, we store your account details, course enrollment and progress, quiz attempts and answers, order and billing information, and the consent you give on our forms.', 'learning-management-system' );

		wp_add_privacy_policy_content(
			/* translators: %s: the product's name */
			sprintf( __( '%s LMS', 'learning-management-system' ), masteriyo_get_plugin_name() ),
			wp_kses_post( wpautop( $content ) )
		);
	}

	/**
	 * Masteriyo-owned user-meta keys holding billing and public-profile details.
	 *
	 * WordPress core exports/erases the account profile but not this plugin meta.
	 *
	 * @return string[]
	 */
	protected function account_meta_keys() {
		return array(
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company_name',
			'_billing_company_id',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_postcode',
			'_billing_country',
			'_billing_state',
			'_billing_email',
			'_billing_phone',
			'_public_profile_biographical_info',
			'_public_profile_phone',
			'_public_profile_address_1',
			'_public_profile_address_2',
			'_public_profile_city',
			'_public_profile_postcode',
			'_public_profile_country',
			'_public_profile_state',
			'_public_profile_facebook_url',
			'_public_profile_website_url',
			'_public_profile_linkedin_url',
			'_public_profile_behance_url',
		);
	}

	/**
	 * One item's worth of account details, when the user has any stored.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int 0 or 1.
	 */
	protected function count_account_details( $user ) {
		foreach ( $this->account_meta_keys() as $key ) {
			if ( '' !== (string) get_user_meta( $user->ID, $key, true ) ) {
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Export the learner's Masteriyo account details.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_account_details( $user, $offset, $limit ) {
		$fields = array();

		foreach ( $this->account_meta_keys() as $key ) {
			$value = (string) get_user_meta( $user->ID, $key, true );

			if ( '' !== $value ) {
				$fields[] = array(
					'name'  => ucwords( str_replace( '_', ' ', ltrim( $key, '_' ) ) ),
					'value' => $value,
				);
			}
		}

		if ( empty( $fields ) ) {
			return array();
		}

		return array(
			array(
				'group_id'    => 'masteriyo-account-details',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Account Details', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-account-details',
				'data'        => $fields,
			),
		);
	}

	/**
	 * Number of consent records the user has.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_consent( $user ) {
		return count( (array) get_user_meta( $user->ID, self::CONSENT_META_KEY, false ) );
	}

	/**
	 * Export a page of consent records.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_consent( $user, $offset, $limit ) {
		$records = array_slice( (array) get_user_meta( $user->ID, self::CONSENT_META_KEY, false ), $offset, $limit );
		$items   = array();

		foreach ( $records as $index => $record ) {
			$items[] = array(
				'group_id'    => 'masteriyo-consent',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Consent Records', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-consent-' . ( $offset + $index ),
				'data'        => array(
					array(
						'name'  => __( 'Consented on', 'learning-management-system' ),
						'value' => empty( $record['timestamp'] ) ? '' : wp_date( 'Y-m-d H:i:s', (int) $record['timestamp'] ),
					),
					array(
						'name'  => __( 'Context', 'learning-management-system' ),
						'value' => isset( $record['context'] ) ? $record['context'] : '',
					),
					array(
						'name'  => __( 'Consent text', 'learning-management-system' ),
						'value' => isset( $record['message'] ) ? $record['message'] : '',
					),
					array(
						'name'  => __( 'Privacy policy', 'learning-management-system' ),
						'value' => isset( $record['policy_url'] ) ? $record['policy_url'] : '',
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Number of enrollment rows the user has.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_enrollments( $user ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %s", (string) $user->ID ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Export a page of course enrollments.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_enrollments( $user, $offset, $limit ) {
		global $wpdb;

		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT item_id, item_type, status, date_start FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %s ORDER BY id ASC LIMIT %d OFFSET %d", (string) $user->ID, $limit, $offset ) ); // phpcs:ignore WordPress.DB
		$items = array();

		foreach ( $rows as $index => $row ) {
			$items[] = array(
				'group_id'    => 'masteriyo-enrollments',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Course Enrollments', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-enrollment-' . ( $offset + $index ),
				'data'        => array(
					array(
						'name'  => __( 'Course', 'learning-management-system' ),
						'value' => get_the_title( $row->item_id ),
					),
					array(
						'name'  => __( 'Type', 'learning-management-system' ),
						'value' => $row->item_type,
					),
					array(
						'name'  => __( 'Status', 'learning-management-system' ),
						'value' => $row->status,
					),
					array(
						'name'  => __( 'Enrolled on', 'learning-management-system' ),
						'value' => $row->date_start,
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Number of course-progress rows the user has.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_progress( $user ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_activities WHERE user_id = %s", (string) $user->ID ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Export a page of course progress.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_progress( $user, $offset, $limit ) {
		global $wpdb;

		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT item_id, activity_type, activity_status, completed_at FROM {$wpdb->prefix}masteriyo_user_activities WHERE user_id = %s ORDER BY id ASC LIMIT %d OFFSET %d", (string) $user->ID, $limit, $offset ) ); // phpcs:ignore WordPress.DB
		$items = array();

		foreach ( $rows as $index => $row ) {
			$items[] = array(
				'group_id'    => 'masteriyo-progress',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Course Progress', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-progress-' . ( $offset + $index ),
				'data'        => array(
					array(
						'name'  => __( 'Item', 'learning-management-system' ),
						'value' => get_the_title( $row->item_id ),
					),
					array(
						'name'  => __( 'Type', 'learning-management-system' ),
						'value' => $row->activity_type,
					),
					array(
						'name'  => __( 'Status', 'learning-management-system' ),
						'value' => $row->activity_status,
					),
					array(
						'name'  => __( 'Completed on', 'learning-management-system' ),
						'value' => $row->completed_at,
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Number of quiz attempts the user has.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_quiz_attempts( $user ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts WHERE user_id = %s", (string) $user->ID ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Export a page of quiz attempts and answers.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_quiz_attempts( $user, $offset, $limit ) {
		global $wpdb;

		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT quiz_id, course_id, total_marks, earned_marks, answers, attempt_status, attempt_started_at FROM {$wpdb->prefix}masteriyo_quiz_attempts WHERE user_id = %s ORDER BY id ASC LIMIT %d OFFSET %d", (string) $user->ID, $limit, $offset ) ); // phpcs:ignore WordPress.DB
		$items = array();

		foreach ( $rows as $index => $row ) {
			$items[] = array(
				'group_id'    => 'masteriyo-quiz-attempts',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Quiz Attempts', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-quiz-attempt-' . ( $offset + $index ),
				'data'        => array(
					array(
						'name'  => __( 'Quiz', 'learning-management-system' ),
						'value' => get_the_title( $row->quiz_id ),
					),
					array(
						'name'  => __( 'Course', 'learning-management-system' ),
						'value' => get_the_title( $row->course_id ),
					),
					array(
						'name'  => __( 'Marks', 'learning-management-system' ),
						'value' => $row->earned_marks . ' / ' . $row->total_marks,
					),
					array(
						'name'  => __( 'Status', 'learning-management-system' ),
						'value' => $row->attempt_status,
					),
					array(
						'name'  => __( 'Answers', 'learning-management-system' ),
						'value' => $row->answers,
					),
					array(
						'name'  => __( 'Attempted on', 'learning-management-system' ),
						'value' => $row->attempt_started_at,
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Number of notifications the user has.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_notifications( $user ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_notifications WHERE user_id = %d", $user->ID ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Export a page of the user's notifications. The eraser deletes these rows,
	 * so the archive must carry their learner-linked text.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_notifications( $user, $offset, $limit ) {
		global $wpdb;

		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT title, description, type, created_at FROM {$wpdb->prefix}masteriyo_notifications WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $limit, $offset ) ); // phpcs:ignore WordPress.DB
		$items = array();

		foreach ( $rows as $index => $row ) {
			$items[] = array(
				'group_id'    => 'masteriyo-notifications',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Notifications', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-notification-' . ( $offset + $index ),
				'data'        => array(
					array(
						'name'  => __( 'Title', 'learning-management-system' ),
						'value' => $row->title,
					),
					array(
						'name'  => __( 'Description', 'learning-management-system' ),
						'value' => $row->description,
					),
					array(
						'name'  => __( 'Type', 'learning-management-system' ),
						'value' => $row->type,
					),
					array(
						'name'  => __( 'Received on', 'learning-management-system' ),
						'value' => $row->created_at,
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Number of orders the customer owns.
	 *
	 * @param \WP_User $user User.
	 *
	 * @return int
	 */
	protected function count_orders( $user ) {
		return count( $this->get_customer_order_ids( $user->ID ) );
	}

	/**
	 * The ids of the orders a customer owns.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int[]
	 */
	protected function get_customer_order_ids( $user_id ) {
		return get_posts(
			array(
				'post_type'   => 'mto-order',
				// 'any' drops trashed orders, whose billing data is just as personal.
				'post_status' => array_merge( array_keys( masteriyo_get_order_statuses() ), array( 'trash' ) ),
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'fields'      => 'ids',
				'meta_key'    => '_customer_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Export a page of orders with their billing details.
	 *
	 * @param \WP_User $user   User.
	 * @param int      $offset Offset.
	 * @param int      $limit  Limit.
	 *
	 * @return array
	 */
	protected function export_orders( $user, $offset, $limit ) {
		$order_ids = array_slice( $this->get_customer_order_ids( $user->ID ), $offset, $limit );
		$items     = array();

		foreach ( $order_ids as $order_id ) {
			$order = masteriyo_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			$date = $order->get_date_created();

			$items[] = array(
				'group_id'    => 'masteriyo-orders',
				/* translators: %s: the product's name */
				'group_label' => sprintf( __( '%s Orders', 'learning-management-system' ), masteriyo_get_plugin_name() ),
				'item_id'     => 'masteriyo-order-' . $order->get_id(),
				'data'        => array(
					array(
						'name'  => __( 'Order', 'learning-management-system' ),
						'value' => '#' . $order->get_id(),
					),
					array(
						'name'  => __( 'Date', 'learning-management-system' ),
						'value' => $date instanceof \DateTimeInterface ? wp_date( 'Y-m-d H:i:s', $date->getTimestamp() ) : '',
					),
					array(
						'name'  => __( 'Status', 'learning-management-system' ),
						'value' => $order->get_status(),
					),
					array(
						'name'  => __( 'Total', 'learning-management-system' ),
						'value' => $order->get_total(),
					),
					array(
						'name'  => __( 'Billing name', 'learning-management-system' ),
						'value' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
					),
					array(
						'name'  => __( 'Billing company', 'learning-management-system' ),
						'value' => $order->get_billing_company(),
					),
					array(
						'name'  => __( 'Billing email', 'learning-management-system' ),
						'value' => $order->get_billing_email(),
					),
					array(
						'name'  => __( 'Billing phone', 'learning-management-system' ),
						'value' => $order->get_billing_phone(),
					),
					array(
						'name'  => __( 'Billing address', 'learning-management-system' ),
						'value' => trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() ),
					),
					array(
						'name'  => __( 'Billing city', 'learning-management-system' ),
						'value' => $order->get_billing_city(),
					),
					array(
						'name'  => __( 'Billing postcode', 'learning-management-system' ),
						'value' => $order->get_billing_postcode(),
					),
					array(
						'name'  => __( 'Billing state', 'learning-management-system' ),
						'value' => $order->get_billing_state(),
					),
					array(
						'name'  => __( 'Billing country', 'learning-management-system' ),
						'value' => $order->get_billing_country(),
					),
					array(
						'name'  => __( 'Customer note', 'learning-management-system' ),
						'value' => $order->get_customer_note(),
					),
					array(
						'name'  => __( 'IP address', 'learning-management-system' ),
						'value' => $order->get_customer_ip_address(),
					),
					array(
						'name'  => __( 'User agent', 'learning-management-system' ),
						'value' => $order->get_customer_user_agent(),
					),
				),
			);
		}

		return $items;
	}

	/**
	 * Blank the billing details on the given orders, keeping the orders themselves.
	 *
	 * Written directly rather than through `Order::save()`, which would refill
	 * an empty billing email from the owning account, recreate a user-course row
	 * per item for the cleared customer id, and rewrite the note only in
	 * `_customer_note` while the model reads it back from `post_excerpt`.
	 *
	 * @param int[] $order_ids Order IDs.
	 *
	 * @return int Number of orders anonymized.
	 */
	protected function anonymize_orders( $order_ids ) {
		$meta_keys = array(
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_postcode',
			'_billing_state',
			'_billing_country',
			'_billing_email',
			'_billing_phone',
			'_customer_note',
			'_customer_ip_address',
			'_customer_user_agent',
		);
		$count = 0;

		foreach ( $order_ids as $order_id ) {
			foreach ( $meta_keys as $key ) {
				update_post_meta( $order_id, $key, '' );
			}

			// A learner-supplied file attached at checkout is their content, not
			// an accounting record — delete it and drop the reference.
			$attachment_id = (int) get_post_meta( $order_id, '_attachment_id', true );

			if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
			}

			update_post_meta( $order_id, '_attachment_id', '' );
			update_post_meta( $order_id, '_customer_id', 0 );
			wp_update_post(
				array(
					'ID'           => $order_id,
					'post_excerpt' => '',
				)
			);
			clean_post_cache( $order_id );
			++$count;
		}

		return $count;
	}
}
