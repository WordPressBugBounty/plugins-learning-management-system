<?php
/**
 * Notification Repository class.
 *
 * @since 1.4.1
 *
 * @package Masteriyo\Repository
 */

namespace Masteriyo\Repository;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Database\Model;

/**
 * Notification Repository class.
 */
class NotificationRepository extends AbstractRepository implements RepositoryInterface {
	/**
	 * Create notification in database.
	 *
	 * @since 1.4.1
	 *
	 * @param \Masteriyo\Models\Notification $notification Notification object.
	 */
	public function create( Model &$notification ) {
		global $wpdb;

		if ( ! $notification->get_created_at( 'edit' ) ) {
			$notification->set_created_at( current_time( 'mysql', true ) );
		}

		if ( empty( $notification->get_created_by() ) ) {
			$notification->set_created_by( get_current_user_id() );
		}

		$result = $wpdb->insert(
			"{$wpdb->prefix}masteriyo_notifications",
			/**
			 * Filters new notification data before creating.
			 *
			 * @since 1.4.1
			 *
			 * @param array $data New notification data.
			 * @param Masteriyo\Models\Notification $notification Notification object.
			 */
			apply_filters(
				'masteriyo_new_notification_data',
				array(
					'title'       => $notification->get_title( 'edit' ),
					'description' => $notification->get_description( 'edit' ),
					'user_id'     => $notification->get_user_id( 'edit' ),
					'created_by'  => $notification->get_created_by( 'edit' ),
					'status'      => $notification->get_status( 'edit' ),
					'type'        => $notification->get_type( 'edit' ),
					'topic_url'   => $notification->get_topic_url( 'edit' ),
					'post_id'     => $notification->get_post_id( 'edit' ),
					'created_at'  => gmdate( 'Y-m-d H:i:s', $notification->get_created_at( 'edit' )->getTimestamp() ),
					'modified_at' => $notification->get_modified_at( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_modified_at( 'edit' )->getTimestamp() ) : '',
					'expire_at'   => $notification->get_expire_at( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_expire_at( 'edit' )->getTimestamp() ) : '',
				)
			)
		);

		if ( $result && $wpdb->insert_id ) {
			$notification->set_id( $wpdb->insert_id );
			$notification->apply_changes();
			$this->clear_cache( $notification );

			/**
			 * Fires after creating a notification.
			 *
			 * @since 1.4.1
			 *
			 * @param integer $id The notification ID.
			 * @param \Masteriyo\Models\Notification $object The notification object.
			 */
			do_action( 'masteriyo_new_notification', $notification->get_id(), $notification );
		}
	}

	/**
	 * Read a notification.
	 *
	 * @since 1.4.1
	 *
	 * @param \Masteriyo\Models\Notification $notification notification object.
	 *
	 * @throws \Exception If invalid notification.
	 */
	public function read( Model &$notification ) {
		global $wpdb;

		$cache = masteriyo_transient_cache();

		$cache_key        = 'notification_' . $notification->get_id();
		$notification_obj = $cache->get_cache( $cache_key );

		if ( ! $notification_obj ) {

			$notification_obj = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}masteriyo_notifications WHERE id = %d;",
					$notification->get_id()
				)
			);

			if ( ! $notification_obj ) {
				throw new \Exception( __( 'Invalid notification.', 'learning-management-system' ) );
			}

			$cache->set_cache( $cache_key, $notification_obj, 2 * HOUR_IN_SECONDS );
		}

		$notification->set_props(
			array(
				'title'       => $notification_obj->title,
				'description' => $notification_obj->description,
				'user_id'     => $notification_obj->user_id,
				'created_by'  => $notification_obj->created_by,
				'status'      => $notification_obj->status,
				'type'        => $notification_obj->type,
				'topic_url'   => $notification_obj->topic_url,
				'post_id'     => $notification_obj->post_id,
				'created_at'  => $this->string_to_timestamp( $notification_obj->created_at ),
				'modified_at' => $this->string_to_timestamp( $notification_obj->modified_at ),
				'expire_at'   => $this->string_to_timestamp( $notification_obj->expire_at ),
			)
		);

		$notification->set_object_read( true );

		/**
		 * Notification read hook.
		 *
		 * @since 1.4.1
		 *
		 * @param int $id Notification ID.
		 * @param \Masteriyo\Models\Notification $notification Notification object.
		 */
		do_action( 'masteriyo_notification_read', $notification->get_id(), $notification );
	}

	/**
	 * Update a notification.
	 *
	 * @since 1.4.1
	 *
	 * @param \Masteriyo\Models\Notification $notification notification object.
	 *
	 * @return void.
	 */
	public function update( Model &$notification ) {
		global $wpdb;

		$changes = $notification->get_changes();

		$notification_data_keys = array(
			'title',
			'description',
			'user_id',
			'created_by',
			'status',
			'type',
			'topic_url',
			'post_id',
			'created_at',
			'modified_at',
			'expire_at',
		);

		if ( array_intersect( $notification_data_keys, array_keys( $changes ) ) ) {
			if ( ! isset( $changes['modified_at'] ) ) {
				$notification->set_modified_at( current_time( 'mysql', true ) );
			}

			$wpdb->update(
				$notification->get_table_name(),
				array(
					'title'       => $notification->get_title( 'edit' ),
					'description' => $notification->get_description( 'edit' ),
					'user_id'     => $notification->get_user_id( 'edit' ),
					'created_by'  => $notification->get_created_by( 'edit' ),
					'status'      => $notification->get_status( 'edit' ),
					'type'        => $notification->get_type( 'edit' ),
					'topic_url'   => $notification->get_topic_url( 'edit' ),
					'post_id'     => $notification->get_post_id( 'edit' ),
					'created_at'  => ! empty( $notification->get_created_at( 'edit' ) ) ? gmdate( 'Y-m-d H:i:s', $notification->get_created_at( 'edit' )->getTimestamp() ) : '',
				),
				array( 'id' => $notification->get_id() )
			);
		}

		$notification->apply_changes();
		$this->clear_cache( $notification );

		/**
		 * Fires after updating a notification.
		 *
		 * @since 1.4.1
		 *
		 * @param integer $id The notification ID.
		 * @param \Masteriyo\Models\Notification $object The notification object.
		 */
		do_action( 'masteriyo_update_notification', $notification->get_id(), $notification );
	}

	/**
	 * Delete a notification.
	 *
	 * @since 1.4.1
	 *
	 * @param Model $notification notification object.
	 * @param array $args Array of args to pass.alert-danger.
	 */
	public function delete( Model &$notification, $args = array() ) {
		global $wpdb;

		if ( $notification->get_id() ) {
			/**
			 * Fires before deleting a notification.
			 *
			 * @since 1.4.1
			 *
			 * @param integer $id The notification ID.
			 */
			do_action( 'masteriyo_before_delete_notification', $notification->get_id() );

			$wpdb->delete( $wpdb->prefix . 'masteriyo_notifications', array( 'id' => $notification->get_id() ) );

			/**
			 * Fires after deleting a notification.
			 *
			 * @since 1.4.1
			 *
			 * @param integer $id The notification ID.
			 */
			do_action( 'masteriyo_delete_notification', $notification->get_id() );

			$this->clear_cache( $notification );
		}
	}

	/**
	 * Clear meta cache.
	 *
	 * @since 1.4.1
	 *
	 * @param Notification $notification Notification object.
	 */
	public function clear_cache( &$notification ) {
		wp_cache_delete( 'item' . $notification->get_id(), 'masteriyo-notification' );
		wp_cache_delete( 'items-' . $notification->get_id(), 'masteriyo-notification' );

		// clear the transient cache.
		masteriyo_transient_cache()->delete_cache( 'notification_' . $notification->get_id() );
	}

	/**
	 * Fetch notifications.
	 *
	 * @since 1.4.1
	 *
	 * @param array $query_vars Query vars.
	 * @param Masteriyo\Query\NotificationQuery $query Notification query object.
	 *
	 * @return \Masteriyo\Models\Notification[]
	 */
	public function query( $query_vars, $query ) {
		global $wpdb;

		$search_criteria = array();
		$sql[]           = "SELECT * FROM {$wpdb->prefix}masteriyo_notifications";

		// Construct where clause.
		if ( ! empty( $query_vars['user_id'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'user_id = %d', $query_vars['user_id'] );
		}

		if ( ! empty( $query_vars['created_by'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'created_by = %d', $query_vars['created_by'] );
		}

		if ( ! empty( $query_vars['status'] ) && 'any' !== $query_vars['status'] ) {
			$search_criteria[] = $this->create_sql_in_query( 'status', $query_vars['status'] );
		}

		if ( ! empty( $query_vars['type'] ) ) {
			$search_criteria[] = $this->create_sql_in_query( 'type', $query_vars['type'] );
		}

		if ( ! empty( $query_vars['topic_url'] ) ) {
			$search_criteria[] = $this->create_sql_in_query( 'topic_url', $query_vars['topic_url'] );
		}

		if ( ! empty( $query_vars['post_id'] ) ) {
			$search_criteria[] = $this->create_sql_in_query( 'post_id', $query_vars['post_id'] );
		}

		if ( ! empty( $query_vars['name'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'title LIKE %s', '%' . $wpdb->esc_like( $query_vars['name'] ) . '%' );
		}

		if ( ! empty( $query_vars['include'] ) ) {
			$include_ids = array_map( 'absint', (array) $query_vars['include'] );
			if ( ! empty( $include_ids ) ) {
				$search_criteria[] = 'id IN (' . implode( ',', $include_ids ) . ')';
			}
		}

		if ( ! empty( $query_vars['exclude'] ) ) {
			$exclude_ids = array_map( 'absint', (array) $query_vars['exclude'] );
			if ( ! empty( $exclude_ids ) ) {
				$search_criteria[] = 'id NOT IN (' . implode( ',', $exclude_ids ) . ')';
			}
		}

		if ( ! empty( $query_vars['created_at'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'created_at = %s', $query_vars['created_at'] );
		}

		if ( ! empty( $query_vars['modified_at'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'modified_at = %s', $query_vars['modified_at'] );
		}

		if ( ! empty( $query_vars['expire_at'] ) ) {
			$search_criteria[] = $wpdb->prepare( 'expire_at = %s', $query_vars['expire_at'] );
		}

		if ( 1 <= count( $search_criteria ) ) {
			$criteria = implode( ' AND ', $search_criteria );
			$sql[]    = 'WHERE ' . $criteria;
		}

		// Construct order and order by part.
		$sql[] = 'ORDER BY ' . sanitize_sql_orderby( $query_vars['orderby'] . ' ' . $query_vars['order'] );

		$query->rows_count = $this->get_rows_count( $sql );

		$per_page = ! empty( $query_vars['per_page'] ) ? intval( $query_vars['per_page'] ) : 10;

		if ( -1 !== $per_page && isset( $query_vars['limit'] ) ) {
			$per_page = intval( $query_vars['limit'] );
		} else {
			$per_page = -1;
		}

		$page = ! empty( $query_vars['paged'] ) ? absint( $query_vars['paged'] ) : 1;
		if ( ! empty( $query_vars['page'] ) && empty( $query_vars['paged'] ) ) {
			$page = absint( $query_vars['page'] );
		}

		$offset = ( $page - 1 ) * $per_page;
		if ( isset( $query_vars['offset'] ) && $query_vars['offset'] > 0 ) {
			$offset = absint( $query_vars['offset'] );
		}

		if ( $per_page > 0 ) {
			$sql[] = $wpdb->prepare( 'LIMIT %d OFFSET %d', $per_page, $offset );
		}

		// Generate SQL from the SQL parts.
		$sql = implode( ' ', $sql ) . ';';

		// Fetch the results.
		$notifications = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$ids = wp_list_pluck( $notifications, 'id' );

		$query->found_rows = count( $ids );

		if ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) {
			return $ids;
		}

		return array_filter( array_map( 'masteriyo_get_notification', $ids ) );
	}

	/**
	 * Get total rows or rows count
	 *
	 * @since 1.4.1
	 *
	 * @param string[] $sql SQL Array.
	 * @return void
	 */
	protected function get_rows_count( $sql ) {
		global $wpdb;

		$sql[0] = "SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_notifications";

		// Generate SQL from the SQL parts.
		$sql = implode( ' ', $sql ) . ';';

		return absint( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
