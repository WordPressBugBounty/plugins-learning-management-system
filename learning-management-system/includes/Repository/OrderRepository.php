<?php
/**
 * OrderRepository class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Repository;
 */

namespace Masteriyo\Repository;

defined( 'ABSPATH' ) || exit;

use DateTime;
use Masteriyo\Constants;
use Masteriyo\Models\Order;
use Masteriyo\Database\Model;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Contracts\OrderRepository as OrderRepositoryInterface;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;

/**
 * OrderRepository class.
 */
class OrderRepository extends AbstractRepository implements RepositoryInterface, OrderRepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'total'                     => '_total',
		'currency'                  => '_currency',
		'version'                   => '_version',
		'expiry_date'               => '_expiry_date',
		'transaction_id'            => '_transaction_id',
		'date_paid'                 => '_date_paid',
		'date_completed'            => '_date_completed',
		'created_via'               => '_created_via',
		'customer_id'               => '_customer_id',
		'customer_ip_address'       => '_customer_ip_address',
		'customer_user_agent'       => '_customer_user_agent',
		'customer_note'             => '_customer_note',
		'payment_method'            => '_payment_method',
		'payment_method_title'      => '_payment_method_title',
		'order_key'                 => '_order_key',
		'cart_hash'                 => '_cart_hash',
		'discount_total'            => '_discount_total',
		'conversion_discount_total' => '_conversion_discount_total',
		'tax_total'                 => '_tax_total',
		'prices_include_tax'        => '_prices_include_tax',
		'billing_first_name'        => '_billing_first_name',
		'billing_last_name'         => '_billing_last_name',
		'billing_company'           => '_billing_company',
		'billing_address_1'         => '_billing_address_1',
		'billing_address_2'         => '_billing_address_2',
		'billing_city'              => '_billing_city',
		'billing_postcode'          => '_billing_postcode',
		'billing_country'           => '_billing_country',
		'billing_state'             => '_billing_state',
		'billing_email'             => '_billing_email',
		'billing_phone'             => '_billing_phone',

		// Attachment.
		'attachment_id'             => '_attachment_id',

		//Group Courses
		'group_ids'                 => '_group_ids',

		// Multiple Currency
		'conversion_total'          => '_conversion_total',
		'base_currency'             => '_base_currency',
		'exchange_rate'             => '_exchange_rate',
		'pricing_method'            => '_pricing_method',
	);

	/**
	 * Create a order in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order order object.
	 */
	public function create( Model &$order ) {
		if ( ! $order->get_date_created( 'edit' ) ) {
			$order->set_date_created( time() );
		}

		if ( '' === $order->get_order_key() ) {
			$order->set_order_key( masteriyo_generate_order_key() );
		}

		$order->set_currency( $order->get_currency() ? $order->get_currency() : masteriyo_get_currency() );
		$order->set_version( Constants::get( 'MASTERIYO_VERSION' ) );

		$id = wp_insert_post(
			/**
			 * Filters new order data before creating.
			 *
			 * @since 1.0.0
			 *
			 * @param array $data New order data.
			 * @param Masteriyo\Models\Order\Order $order Order object.
			 */
			apply_filters(
				'masteriyo_new_order_data',
				array(
					'post_type'     => PostType::ORDER,
					'post_status'   => $order->get_status() ? $order->get_status() : OrderStatus::PENDING,
					'post_author'   => 1,
					'post_title'    => $this->get_order_title(),
					'post_password' => $this->get_order_key( $order ),
					'ping_status'   => 'closed',
					'post_excerpt'  => $order->get_customer_note( 'edit' ),
					'post_date'     => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				),
				$order
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
			$this->update_post_meta( $order, true );
			// TODO Invalidate caches.

			$order->save_meta_data();
			$order->apply_changes();
			$this->clear_caches( $order );

			$this->create_or_update_user_course( $order );

			/**
			 * Fires after creating an order.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The order ID.
			 * @param \Masteriyo\Models\Order\Order $object The order object.
			 * @param \Masteriyo\Repository\OrderRepository $this The order repository.
			 */
			do_action( 'masteriyo_new_order', $id, $order, $this );
		}
	}

	/**
	 * Read an order.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order Course object.
	 *
	 * @throws \Exception If invalid order.
	 */
	public function read( Model &$order ) {
		$order_post = get_post( $order->get_id() );

		if ( ! $order->get_id() || ! $order_post || PostType::ORDER !== $order_post->post_type ) {
			throw new \Exception( esc_html__( 'Invalid order.', 'learning-management-system' ) );
		}

		$order->set_props(
			array(
				'status'        => $order_post->post_status,
				'parent_id'     => $order_post->post_parent,
				'date_created'  => $this->string_to_timestamp( $order_post->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $order_post->post_modified_gmt ),
				'customer_note' => $order_post->post_excerpt,
			)
		);

		$this->read_order_data( $order );
		$this->read_extra_data( $order );
		$order->set_object_read( true );

		/**
		 * Fires after reading an order object from database.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $id The order ID.
		 * @param \Masteriyo\Models\Order\Order $object The order object.
		 */
		do_action( 'masteriyo_order_read', $order->get_id(), $order );
	}

	/**
	 * Update an order in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order order object.
	 *
	 * @return void
	 */
	public function update( Model &$order ) {
		$order->set_version( Constants::get( 'MASTERIYO_VERSION' ) );

		$changes = $order->get_changes();

		$post_data_keys = array(
			'status',
			'parent_id',
			'date_modified',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_status' => $order->get_status( 'edit' ),
				'post_type'   => PostType::ORDER,
				'post_parent' => $order->get_parent_id(),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				// TODO Abstract the $wpdb WordPress class.
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $order->get_id() ) );
				clean_post_cache( $order->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $order->get_id() ), $post_data ) );
			}
			$order->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $order->get_id(),
				)
			);
			clean_post_cache( $order->get_id() );
		}

		$this->update_post_meta( $order );
		$this->create_or_update_user_course( $order );

		$order->apply_changes();

		/**
		 * Fires after updating an order object in database.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $id The order ID.
		 * @param \Masteriyo\Models\Order\Order $object The order object.
		 * @param \Masteriyo\Repository\OrderRepository $this The order repository.
		 */
		do_action( 'masteriyo_update_order', $order->get_id(), $order, $this );
	}

	/**
	 * Delete an order from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order order object.
	 * @param array $args   Array of args to pass.
	 */
	public function delete( Model &$order, $args = array() ) {
		$id          = $order->get_id();
		$object_type = $order->get_object_type();

		$args = array_merge(
			array(
				'force_delete' => false,
			),
			$args
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			/**
			 * Fires before deleting an order object from database.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The order ID.
			 * @param \Masteriyo\Models\Order\Order $object The order object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $order );

			// `_order_id` itemmeta cleanup runs from OrderServiceProvider's `before_delete_post` hook, so it also covers force-deletes reaching wp_delete_post() directly — not just this path.
			wp_delete_post( $id, true );
			$this->delete_items( $order );
			$order->set_id( 0 );

			/**
			 * Fires after deleting an order object from database.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The order ID.
			 * @param \Masteriyo\Models\Order\Order $object The order object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $order );
		} else {
			/**
			 * Fires before moving an order to trash.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The order ID.
			 * @param \Masteriyo\Models\Order\Order $object The order object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $order );

			wp_trash_post( $id );
			$order->set_status( 'trash' );

			$this->update_user_course_status( $order );

			/**
			 * Fires after moving an order to trash.
			 *
			 * @since 1.5.2
			 *
			 * @param integer $id The order ID.
			 * @param \Masteriyo\Models\Order\Order $object The order object.
			 * @param \Masteriyo\Repository\OrderRepository $this The order repository.
			 */
			do_action( 'masteriyo_after_trash_' . $object_type, $id, $order, $this );
		}
	}

	/**
	 * Clear the `_order_id` itemmeta on any `masteriyo_user_items` row pointing at this order,
	 * so a force-deleted order does not leave an enrollment reading as 'automatic' against a
	 * dangling order link.
	 *
	 * Called from OrderServiceProvider's `before_delete_post` hook, which fires only on an
	 * actual delete (a trashed order is still a real post, so its enrollments' order link
	 * stays valid), regardless of whether the delete came from this class or elsewhere.
	 *
	 * @since 2.31.0
	 *
	 * @param int $order_id Order (post) ID that was force-deleted.
	 */
	public function clear_dangling_order_id_meta( $order_id ) {
		global $wpdb;

		$meta_table = "{$wpdb->prefix}masteriyo_user_itemmeta";

		$item_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_item_id FROM {$meta_table} WHERE meta_key = '_order_id' AND meta_value = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(string) $order_id
			)
		);

		if ( empty( $item_ids ) ) {
			return;
		}

		/*
		 * The link about to be removed is also what classifies these enrollments as
		 * manual or automatic on the Enrollments screen. Persist that verdict as
		 * _source first (never overwriting one already stamped), or deleting a
		 * checkout order would silently move its enrollments to the Manual tab.
		 */
		$created_via = (string) get_post_meta( $order_id, '_created_via', true );
		$source      = ( '' === $created_via || 0 === strpos( $created_via, 'manual-enrollment' ) ) ? 'manual' : 'automatic';

		foreach ( $item_ids as $item_id ) {
			if ( '' === (string) get_metadata( 'user_item', absint( $item_id ), '_source', true ) ) {
				update_metadata( 'user_item', absint( $item_id ), '_source', $source );
			}
		}

		$wpdb->delete(
			$meta_table,
			array(
				'meta_key'   => '_order_id',
				'meta_value' => (string) $order_id,
			)
		);

		foreach ( $item_ids as $item_id ) {
			wp_cache_delete( absint( $item_id ), 'user_item_meta' );
			wp_cache_delete( 'item' . absint( $item_id ), 'masteriyo-user-course' );
		}

		masteriyo_cache()->invalidate_cache_group( 'masteriyo-user-course-query' );
	}

	/**
	 * Restore an order from the database to previous status.
	 *
	 * @since 1.4.1
	 *
	 * @param \Masteriyo\Models\Order\Order $order order object.
	 * @param array $args   Array of args to pass.
	 */
	public function restore( Model &$order, $args = array() ) {

		$previous_status = get_post_meta( $order->get_id(), '_wp_trash_meta_status', true );

		wp_untrash_post( $order->get_id() );

		$order->set_status( $previous_status );

		$post_data = array(
			'post_status'       => $order->get_status( 'edit' ),
			'post_type'         => PostType::ORDER,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', true ),
		);

		/**
		 * When updating this object, to prevent infinite loops, use $wpdb
		 * to update data, since wp_update_post spawns more calls to the
		 * save_post action.
		 *
		 * This ensures hooks are fired by either WP itself (admin screen save),
		 * or an update purely from CRUD.
		 */
		if ( doing_action( 'save_post' ) ) {
			// TODO Abstract the $wpdb WordPress class.
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $order->get_id() ) );
		} else {
			wp_update_post( array_merge( array( 'ID' => $order->get_id() ), $post_data ) );
		}
		clean_post_cache( $order->get_id() );

		$id          = $order->get_id();
		$object_type = $order->get_object_type();

		/**
		 * Fires after restoring an order.
		 *
		 * @since 1.7.3
		 *
		 * @param integer $id The order ID.
		 * @param \Masteriyo\Models\Order\Order $object The order object.
		 */
		do_action( 'masteriyo_after_restore_' . $object_type, $id, $order );
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order order object.
	 */
	protected function read_order_data( &$order ) {
		$id          = $order->get_id();
		$meta_values = $this->read_meta( $order );

		$set_props = array();

		$meta_values = array_reduce(
			$meta_values,
			function ( $result, $meta_value ) {
				$result[ $meta_value->key ][] = $meta_value->value;
				return $result;
			},
			array()
		);

		foreach ( $this->internal_meta_keys as $prop => $meta_key ) {
			$meta_value         = isset( $meta_values[ $meta_key ][0] ) ? $meta_values[ $meta_key ][0] : null;
			$set_props[ $prop ] = maybe_unserialize( $meta_value ); // get_post_meta only unserializes single values.
		}

		$order->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the order.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 */
	protected function read_extra_data( &$order ) {
		$meta_values = $this->read_meta( $order );

		foreach ( $order->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $order, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$order->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Fetch orders.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_vars Query vars.
	 * @return Order[]
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {
			$query = new \WP_Query( $args );
		}

		if ( isset( $query_vars['return'] ) && 'objects' === $query_vars['return'] && ! empty( $query->posts ) ) {
			// Prime caches before grabbing objects.
			update_post_caches( $query->posts, array( PostType::ORDER ) );
		}

		$orders = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'masteriyo_get_order', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'orders'        => $orders,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $orders;
	}

	/**
	 * Get valid WP_Query args from a OrderQuery's query variables.
	 *
	 * @since 1.0.0
	 * @param array $query_vars Query vars from a OrderQuery.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// Map customer id.
		if ( isset( $query_vars['customer_id'] ) ) {
			$query_vars['author'] = $query_vars['customer_id'];
			unset( $query_vars['customer_id'] );
		}

		// Add the 'masteriyo-' prefix to status if needed.
		if ( ! empty( $query_vars['status'] ) ) {
			if ( is_array( $query_vars['status'] ) ) {
				foreach ( $query_vars['status'] as &$status ) {
					$status = masteriyo_is_order_status( 'masteriyo-' . $status ) ? 'masteriyo-' . $status : $status;
				}
			} else {
				$query_vars['status'] = masteriyo_is_order_status( 'masteriyo-' . $query_vars['status'] ) ? 'masteriyo-' . $query_vars['status'] : $query_vars['status'];
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Handle date queries.
		$date_queries = array(
			'date_created'   => 'post_date',
			'date_modified'  => 'post_modified',
			'date_paid'      => '_date_paid',
			'date_completed' => '_date_completed',
		);
		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				foreach ( $existing_queries as $query_index => $query_contents ) {
					unset( $wp_query_args['meta_query'][ $query_index ] );
				}

				$wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
			}
		}

		// Handle paginate.
		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		// Handle orderby.
		if ( isset( $query_vars['orderby'] ) && 'include' === $query_vars['orderby'] ) {
			$wp_query_args['orderby'] = 'post__in';
		}

		/**
		 * Filters WP Query args for order post type query.
		 *
		 * @since 1.0.0
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars Query vars.
		 * @param Masteriyo\Repository\OrderRepository $repository Order repository object.
		 */
		return apply_filters( 'masteriyo_order_data_store_cpt_get_orders_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Read order items of a specific type from the database for this order.
	 *
	 * @since 1.0.0
	 *
	 * @param  Order $order Order object.
	 * @param  string $type Order item type.
	 * @return array
	 */
	public function read_items( $order, $type ) {
		$type        = trim( $type );
		$order_items = array();

		if ( ! empty( $order_items ) ) {
			return $order_items;
		}

		// Fetch from database.
		$order_items = masteriyo_get_order_items(
			array(
				'order_id' => $order->get_id(),
				'type'     => $type,
			)
		);

		return $order_items;
	}

	/**
	 * Remove all line items (orders) from the order.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 * @param string $type Order item type. Default null.
	 */
	public function delete_items( $order, $type = null ) {
		$order_items_repo = masteriyo( 'order-item.store' );

		$order_items_repo->delete_batch(
			array(
				'order_id' => $order->get_id(),
				'type'     => $type,
			)
		);

		$this->clear_caches( $order );
	}

	/**
	 * Get token ids for an order.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 * @return array
	 */
	public function get_payment_token_ids( $order ) {
		$token_ids = array_filter( (array) get_post_meta( $order->get_id(), '_payment_tokens', true ) );
		return $token_ids;
	}

	/**
	 * Update token ids for an order.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 * @param array    $token_ids Payment token ids.
	 */
	public function update_payment_token_ids( $order, $token_ids ) {
		update_post_meta( $order->get_id(), '_payment_tokens', $token_ids );
	}

	/**
	 * Clear any caches.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 */
	protected function clear_caches( &$order ) {
		clean_post_cache( $order->get_id() );
		// Delete shop order transients.
		masteriyo( 'cache' )->delete( 'masteriyo-order-items-' . $order->get_id(), 'masteriyo-orders' );
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_order_title() {
		$order_date     = new DateTime();
		$formatted_date = $order_date->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'learning-management-system' ) );

		/* translators: %s: Order date */
		return sprintf( __( 'Order - %s', 'learning-management-system' ), $formatted_date );
	}

	/**
	 * Get order key.
	 *
	 * @since 1.0.0
	 *
	 * @param Order $order Order object.
	 * @return string
	 */
	protected function get_order_key( $order ) {
		return masteriyo_generate_order_key();
	}

	/**
	 * Get amount already refunded.
	 *
	 * @since 1.0.0
	 *
	 * @param  Order $order Order object.
	 * @return float
	 */
	public function get_total_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( postmeta.meta_value )
				FROM $wpdb->postmeta AS postmeta
				INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'mto-order-refund' AND posts.post_parent = %d )
				WHERE postmeta.meta_key = '_refund_amount'
				AND postmeta.post_id = posts.ID",
				$order->get_id()
			)
		);

		return floatval( $total );
	}

	/**
	 * Create or update user's course.
	 *
	 * The enrollment clock starts only when access begins: a new enrollment,
	 * a newer order (re-purchase), or this order becoming active. A plain
	 * re-save keeps the start date; resetting it would silently re-activate
	 * expired time-limited access. Saves from an order older than the
	 * enrollment's current order are ignored entirely.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 * @param int $customer_id Customer ID.
	 */
	public function create_or_update_user_course( $order, $customer_id = 0 ) {
		// Filter order item courses.
		$order_items = array_filter(
			$order->get_items(),
			function( $order_item ) {
				return is_a( $order_item, '\Masteriyo\Models\Order\OrderItemCourse' ) && 0 !== $order_item->get_course_id();
			}
		);

		$course_ids = array_map(
			function( $order_item ) {
				return $order_item->get_course_id();
			},
			$order_items
		);

		$customer_id = $customer_id ? $customer_id : $order->get_customer_id( 'edit' );

		$query = new UserCourseQuery(
			array(
				'user_id'    => $customer_id,
				'course__in' => $course_ids,
				'per_page'   => -1,
			)
		);

		$user_courses_map = array();
		$user_courses     = $query->get_user_courses();
		foreach ( $user_courses as $user_course ) {
			$user_courses_map[ $user_course->get_course_id() ] = $user_course;
		}

		$status   = OrderStatus::COMPLETED === $order->get_status() ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE;
		$order_id = $order->get_id();

		// Update user course.
		foreach ( $order_items as $order_item ) {
			if ( isset( $user_courses_map[ $order_item->get_course_id() ] ) ) {
				$user_course = $user_courses_map[ $order_item->get_course_id() ];
			} else {
				$user_course = masteriyo( 'user-course' );
			}

			$previous_status   = $user_course->get_status( 'edit' );
			$previous_order_id = absint( $user_course->get_order_id( 'edit' ) );

			// Stale: a newer order owns this enrollment.
			if ( $previous_order_id && $order_id < $previous_order_id ) {
				continue;
			}

			$user_course->set_user_id( $customer_id );
			$user_course->set_status( $status );

			$is_activating = UserCourseStatus::ACTIVE === $status && UserCourseStatus::ACTIVE !== $previous_status;

			if ( ! $user_course->get_date_start( 'edit' ) || $is_activating || $order_id > $previous_order_id ) {
				$user_course->set_date_start( current_time( 'mysql' ) );
			}

			$user_course->set_order_id( $order_id );
			$user_course->set_course_id( $order_item->get_course_id( 'edit' ) );
			$user_course->set_price( $order_item->get_total( 'edit' ) );

			/**
			 * Filters user course object before saving.
			 *
			 * @since 1.0.0
			 *
			 * @param Masteriyo\Models\UserCourse $user_course User course object.
			 * @param object $order_item Order item object.
			 * @param Masteriyo\Models\Order\Order $order Order object.
			 */
			$user_course = apply_filters( 'masteriyo_save_user_course', $user_course, $order_item, $order );

			$user_course->save();
		}
	}

	/**
	 * Update the user course status.
	 *
	 * Status changes from an order older than the enrollment's current order
	 * are ignored: a stale order must not re-activate or deactivate an
	 * enrollment that a newer purchase owns.
	 *
	 * @since 2.9.3
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 * @param int $customer_id Customer ID.
	 */
	public function update_user_course_status( $order, $customer_id = 0 ) {
		// Filter order item courses.
		$order_items = array_filter(
			$order->get_items(),
			function( $order_item ) {
				return is_a( $order_item, '\Masteriyo\Models\Order\OrderItemCourse' ) && 0 !== $order_item->get_course_id();
			}
		);

		$course_ids = array_map(
			function( $order_item ) {
				return $order_item->get_course_id();
			},
			$order_items
		);

		$customer_id = $customer_id ? $customer_id : $order->get_customer_id( 'edit' );

		$query = new UserCourseQuery(
			array(
				'user_id'    => $customer_id,
				'course__in' => $course_ids,
				'per_page'   => -1,
			)
		);

		$user_courses_map = array();
		$user_courses     = $query->get_user_courses();
		foreach ( $user_courses as $user_course ) {
			$user_courses_map[ $user_course->get_course_id() ] = $user_course;
		}

		$status = OrderStatus::COMPLETED === $order->get_status() ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE;

		// Update user course.
		foreach ( $order_items as $order_item ) {
			if ( isset( $user_courses_map[ $order_item->get_course_id() ] ) ) {
				$user_course = $user_courses_map[ $order_item->get_course_id() ];

				// Stale: a newer order owns this enrollment.
				$enrollment_order_id = absint( $user_course->get_order_id( 'edit' ) );
				if ( $enrollment_order_id && $order->get_id() < $enrollment_order_id ) {
					continue;
				}

				$user_course->set_status( $status );

				/**
				 * Filters user course object before updating.
				 *
				 * @since 2.9.3
				 *
				 * @param Masteriyo\Models\UserCourse $user_course User course object.
				 * @param object $order_item Order item object.
				 * @param Masteriyo\Models\Order\Order $order Order object.
				 */
				$user_course = apply_filters( 'masteriyo_user_course_update', $user_course, $order_item, $order );

				$user_course->save();
			}
		}
	}
}
