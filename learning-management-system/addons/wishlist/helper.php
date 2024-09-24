<?php
/**
 * Wishlist addon helper functions.
 *
 * @since 1.12.2
 */

use Masteriyo\Addons\WishList\WishlistItemsQuery;

if ( ! function_exists( 'masteriyo_get_wishlist_items' ) ) {
	/**
	 * Get wishlist items.
	 *
	 * @since 1.12.2
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	function masteriyo_get_wishlist_items( $args = array() ) {
		$query = new WishlistItemsQuery( $args );
		$items = $query->get_wishlist_items();

		/**
		 * Filters queried wishlist items.
		 *
		 * @since 1.12.2
		 *
		 * @param array $items Queried wishlist items.
		 * @param array $args Query args.
		 */
		return apply_filters( 'masteriyo_get_wishlist_items', $items, $args );
	}
}

if ( ! function_exists( 'masteriyo_user_has_course_in_wishlist' ) ) {
	/**
	 * Check if a user has a course in their wishlist.
	 *
	 * @since 1.12.2
	 *
	 * @param integer $user_id User ID.
	 * @param integer $course_id Course ID.
	 *
	 * @return boolean True if the given user has the given course in their wishlist.
	 */
	function masteriyo_user_has_course_in_wishlist( $user_id, $course_id ) {
		$items = masteriyo_get_wishlist_items(
			array(
				'course' => $course_id,
				'author' => $user_id,
			)
		);
		$bool  = count( $items ) > 0;

		/**
		 * Filters boolean: True if the given user has the given course in their wishlist.
		 *
		 * @since 1.12.2
		 *
		 * @param boolean $bool True if the given user has the given course in their wishlist.
		 * @param integer $user_id User ID.
		 * @param integer $course_id Course ID.
		 */
		return apply_filters( 'masteriyo_user_has_course_in_wishlist', $bool, $user_id, $course_id );
	}
}

if ( ! function_exists( 'masteriyo_current_user_has_course_in_wishlist' ) ) {
	/**
	 * Check if the current user has a course in their wishlist.
	 *
	 * @since 1.12.2
	 *
	 * @param integer $course_id Course ID.
	 *
	 * @return boolean True if the current user has the given course in their wishlist.
	 */
	function masteriyo_current_user_has_course_in_wishlist( $course_id ) {
		return masteriyo_user_has_course_in_wishlist( get_current_user_id(), $course_id );
	}
}

if ( ! function_exists( 'masteriyo_get_wishlist_item_by_user_and_course' ) ) {
	/**
	 * Get the wishlist item ID, if the given user has the given course in their wishlist.
	 *
	 * @since 1.12.2
	 *
	 * @param integer $user_id User ID.
	 * @param integer $course_id Course ID.
	 *
	 * @return Masteriyo\Addons\WishList\Models\WishListItem|null
	 */
	function masteriyo_get_wishlist_item_by_user_and_course( $user_id, $course_id ) {
		$items = masteriyo_get_wishlist_items(
			array(
				'course' => $course_id,
				'author' => $user_id,
			)
		);

		$item = empty( $items ) ? null : current( $items );

		/**
		 * Filters the wishlist item ID, that contains the given user ID and course ID.
		 *
		 * @since 1.12.2
		 *
		 * @param integer $item The wishlist item.
		 * @param integer $user_id User ID.
		 * @param integer $course_id Course ID.
		 */
		return apply_filters( 'masteriyo_get_wishlist_item_by_user_and_course', $item, $user_id, $course_id );
	}
}

if ( ! function_exists( 'masteriyo_get_wishlist_item' ) ) {
	/**
	 * Get wishlist item.
	 *
	 * @since 1.12.2
	 *
	 * @param int|\Masteriyo\Addons\WishList\Models\WishListItem|\WP_Post $wishlist_item ID or model object or WP_Post object.
	 *
	 * @return \Masteriyo\Addons\WishList\Models\WishListItem|null
	 */
	function masteriyo_get_wishlist_item( $wishlist_item ) {
		$wishlist_item_obj   = masteriyo_create_wishlist_item_object();
		$wishlist_item_store = masteriyo_get_wishlist_item_store();

		if ( is_a( $wishlist_item, \Masteriyo\Addons\WishList\Models\WishListItem::class ) ) {
			$id = $wishlist_item->get_id();
		} elseif ( is_a( $wishlist_item, \WP_Post::class ) ) {
			$id = $wishlist_item->ID;
		} else {
			$id = $wishlist_item;
		}

		try {
			$id = absint( $id );
			$wishlist_item_obj->set_id( $id );
			$wishlist_item_store->read( $wishlist_item_obj );
		} catch ( \Exception $e ) {
			return null;
		}

		/**
		 * Filters wishlist item object.
		 *
		 * @since 1.12.2
		 *
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item_obj The wishlist item object.
		 * @param int|\Masteriyo\Addons\WishList\Models\WishListItem|WP_Post $wishlist_item ID or model object or WP_Post object.
		 */
		return apply_filters( 'masteriyo_get_wishlist_item', $wishlist_item_obj, $wishlist_item );
	}
}

if ( ! function_exists( 'masteriyo_sync_wishlist_items_with_course' ) ) {
	/**
	 * Sync wishlist items with a course, by updating them with the latest value of the course.
	 *
	 * @since 1.12.2
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id ID or model object or WP_Post object.
	 */
	function masteriyo_sync_wishlist_items_with_course( $course_id ) {
		$course = masteriyo_get_course( $course_id );

		if ( is_null( $course ) ) {
			return false;
		}

		global $wpdb;

		/**
		 * Update course title.
		 */
		$course_id    = $course->get_id();
		$table        = $wpdb->posts;
		$course_title = sanitize_text_field( $course->get_title() );
		$sql          = "UPDATE $table SET post_title = '%s' WHERE post_type = 'mto-wishlist-item' AND post_parent = %d";

		$wpdb->query( $wpdb->prepare( $sql, array( $course_title, $course_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		/**
		 * Prepare common data.
		 */
		$wishlist_item_ids = masteriyo_get_wishlist_items(
			array(
				'course'   => $course->get_id(),
				'per_page' => '',
				'return'   => 'ids',
			)
		);

		if ( empty( $wishlist_item_ids ) ) {
			return;
		}

		$post_meta_table = _get_meta_table( 'post' );

		if ( ! $post_meta_table ) {
			return;
		}

		$ids_where_condition = 'post_id IN (0';

		foreach ( $wishlist_item_ids as $id ) {
			$ids_where_condition .= ', %d';
		}
		$ids_where_condition .= ')';

		/**
		 * Delete all previous metadata for force update.
		 */
		$sql = "DELETE FROM $post_meta_table WHERE meta_key IN ('_course_price', '_course_difficulty', '_course_category_ids') AND " . $ids_where_condition;

		$wpdb->query( $wpdb->prepare( $sql, $wishlist_item_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		/**
		 * Set the new metadata.
		 */
		$sql_args       = array();
		$sql            = "INSERT INTO $post_meta_table (post_id, meta_key, meta_value) VALUES ";
		$course_price   = sanitize_text_field( $course->get_price() );
		$difficulty     = maybe_serialize( $course->get_difficulty() );
		$category_ids   = maybe_serialize( $course->get_category_ids() );
		$values_clauses = array();

		foreach ( $wishlist_item_ids as $id ) {
			$values_clauses[] = "( %d, '_course_price', %s )";
			$sql_args[]       = $id;
			$sql_args[]       = $course_price;

			$values_clauses[] = "( %d, '_course_difficulty', %s )";
			$sql_args[]       = $id;
			$sql_args[]       = $difficulty;

			$values_clauses[] = "( %d, '_course_category_ids', %s )";
			$sql_args[]       = $id;
			$sql_args[]       = $category_ids;
		}
		$sql .= implode( ', ', $values_clauses );

		$wpdb->query( $wpdb->prepare( $sql, $sql_args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

if ( ! function_exists( 'masteriyo_remove_course_from_wishlist' ) ) {
	/**
	 * Remove a course from a user's wishlist.
	 *
	 * @since 1.12.2
	 *
	 * @param integer $course_id ID or model object or WP_Post object.
	 * @param integer $user_id Wishlist owner's ID.
	 *
	 * @return integer|false
	 */
	function masteriyo_remove_course_from_wishlist( $course_id, $user_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->posts,
			array(
				'post_parent' => absint( $course_id ),
				'post_author' => absint( $user_id ),
				'post_type'   => 'mto-wishlist-item',
			)
		);

		return $result;
	}
}

if ( ! function_exists( 'masteriyo_create_wishlist_item_object' ) ) {
	/**
	 * Create an instance of WishListItem class.
	 *
	 * @since 1.12.2
	 *
	 * @return \Masteriyo\Addons\WishList\Models\WishListItem
	 */
	function masteriyo_create_wishlist_item_object() {
		return masteriyo( 'wishlist-item' );
	}
}

if ( ! function_exists( 'masteriyo_get_wishlist_item_store' ) ) {
	/**
	 * Get wishlist item store.
	 *
	 * @since 1.12.2
	 *
	 * @return \Masteriyo\Addons\WishList\Repository\WishListItemRepository
	 */
	function masteriyo_get_wishlist_item_store() {
		return masteriyo( 'wishlist-item.store' );
	}
}
