<?php
/**
 * Wishlist item repository class.
 *
 * @since 1.12.2
 *
 * @package Masteriyo\Addons\WishList\Repository;
 */

namespace Masteriyo\Addons\WishList\Repository;

use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Repository\AbstractRepository;
use Masteriyo\Repository\RepositoryInterface;

class WishListItemRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.12.2
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'course_price'        => '_course_price',
		'course_difficulty'   => '_course_difficulty',
		'course_category_ids' => '_course_category_ids',
	);

	/**
	 * Create a wishlist item in the database.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
	 */
	public function create( Model &$wishlist_item ) {
		if ( ! $wishlist_item->get_date_created( 'edit' ) ) {
			$wishlist_item->set_date_created( current_time( 'mysql', true ) );
		}

		if ( ! $wishlist_item->get_author_id( 'edit' ) ) {
			$wishlist_item->set_author_id( get_current_user_id() );
		}

		/**
		 * Filters new wishlist item data before creating.
		 *
		 * @since 1.12.2
		 *
		 * @param array $data New wishlist item data.
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
		 */
		$wishlist_item_data = apply_filters(
			'masteriyo_new_course_wishlist_item_data',
			array(
				'post_type'     => $wishlist_item->get_type(),
				'post_title'    => $wishlist_item->get_course_title(),
				'post_author'   => $wishlist_item->get_author_id(),
				'post_parent'   => $wishlist_item->get_course_id(),
				'post_status'   => $wishlist_item->get_status() ? $wishlist_item->get_status() : PostStatus::PUBLISH,
				'post_date'     => gmdate( 'Y-m-d H:i:s', $wishlist_item->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $wishlist_item->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'ping_status'   => 'closed',
			),
			$wishlist_item
		);
		$id                 = wp_insert_post( $wishlist_item_data );

		if ( $id && ! is_wp_error( $id ) ) {
			$wishlist_item->set_id( $id );
			$this->update_post_meta( $wishlist_item, true );

			$wishlist_item->save_meta_data();
			$wishlist_item->apply_changes();

			/**
			 * Fires after creating a wishlist item.
			 *
			 * @since 1.12.2
			 *
			 * @param \Masteriyo\Addons\WishList\Models\WishListItem $object The wishlist item object.
			 * @param integer $id The wishlist item ID.
			 */
			do_action( 'masteriyo_new_wishlist_item', $wishlist_item, $id );
		}
	}

	/**
	 * Read a wishlist item.
	 *
	 * @since 1.12.2
	 *
	 * @throws \Exception If invalid wishlist item.
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
	 */
	public function read( Model &$wishlist_item ) {
		$wishlist_item_obj = get_post( $wishlist_item->get_id() );

		if ( ! $wishlist_item->get_id() || ! $wishlist_item_obj || $wishlist_item->get_type() !== $wishlist_item_obj->post_type ) {
			throw new \Exception( __( 'Invalid Wishlist Item.', 'learning-management-system' ) );
		}

		$wishlist_item->set_props(
			array(
				'course_title' => $wishlist_item_obj->post_title,
				'author_id'    => $wishlist_item_obj->post_author,
				'course_id'    => $wishlist_item_obj->post_parent,
				'date_created' => $wishlist_item_obj->post_date_gmt,
				'status'       => $wishlist_item_obj->post_status,
			)
		);
		$this->read_metadata( $wishlist_item );
		$wishlist_item->set_object_read( true );

		/**
		 * Fires after reading a wishlist item from database.
		 *
		 * @since 1.12.2
		 *
		 * @param integer $id The wishlist item ID.
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $object The wishlist item object.
		 */
		do_action( 'masteriyo_wishlist_item_read', $wishlist_item->get_id(), $wishlist_item );
	}

	/**
	 * Update a wishlist item in the database.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
	 */
	public function update( Model &$wishlist_item ) {
		$changes = $wishlist_item->get_changes();

		$wishlist_item_data_keys = array(
			'course_title',
			'course_id',
			'status',
			'date_created',
			'author_id',
		);

		// Only update the wishlist item when the wishlist item data changes.
		if ( array_intersect( $wishlist_item_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_title'  => $wishlist_item->get_course_title( 'edit' ),
				'post_author' => $wishlist_item->get_author_id( 'edit' ),
				'post_parent' => $wishlist_item->get_course_id( 'edit' ),
				'post_status' => $wishlist_item->get_status( 'edit' ) ? $wishlist_item->get_status( 'edit' ) : PostStatus::PUBLISH,
				'post_type'   => $wishlist_item->get_type( 'edit' ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $wishlist_item->get_id() ) );
				clean_post_cache( $wishlist_item->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $wishlist_item->get_id() ), $post_data ) );
			}
			$wishlist_item->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $wishlist_item->get_id(),
				)
			);
			clean_post_cache( $wishlist_item->get_id() );
		}

		$this->update_post_meta( $wishlist_item );
		$wishlist_item->apply_changes();

		/**
		 * Fires after updating a wishlist item in database.
		 *
		 * @since 1.12.2
		 *
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $object The wishlist item object.
		 * @param integer $id The wishlist item ID.
		 */
		do_action( 'masteriyo_update_wishlist_item', $wishlist_item, $wishlist_item->get_id() );
	}

	/**
	 * Delete a wishlist item from the database.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
	 * @param array $args Array of args to pass.
	 */
	public function delete( Model &$wishlist_item, $args = array() ) {
		$id          = $wishlist_item->get_id();
		$object_type = $wishlist_item->get_object_type();

		if ( ! $id ) {
			return;
		}

		/**
		 * Fires before a wishlist item is permanently deleted.
		 *
		 * @since 1.12.2
		 *
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
		 * @param integer $id Wishlist item ID.
		 */
		do_action( 'masteriyo_before_delete_' . $object_type, $wishlist_item, $id );

		wp_delete_post( $id, true );
		$wishlist_item->set_id( 0 );

		/**
		 * Fires after wishlist item is permanently deleted.
		 *
		 * @since 1.12.2
		 *
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem  $wishlist_item Wishlist item object.
		 * @param int $id Wishlist item ID.
		 */
		do_action( 'masteriyo_after_delete_' . $object_type, $wishlist_item, $id );
	}

	/**
	 * Read metadata.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $course course object.
	 */
	protected function read_metadata( &$wishlist_item ) {
		$meta_values = $this->read_meta( $wishlist_item );
		$set_props   = array();
		$meta_values = array_reduce(
			$meta_values,
			function( $result, $meta_value ) {
				$result[ $meta_value->key ][] = $meta_value->value;
				return $result;
			},
			array()
		);

		foreach ( $this->internal_meta_keys as $prop => $meta_key ) {
			$meta_value         = isset( $meta_values[ $meta_key ][0] ) ? $meta_values[ $meta_key ][0] : null;
			$set_props[ $prop ] = maybe_unserialize( $meta_value ); // get_post_meta only unserialize single values.
		}

		$wishlist_item->set_props( $set_props );
	}

	/**
	 * Query wishlist items.
	 *
	 * @since 1.12.2
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
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
			update_post_caches( $query->posts );
		}

		if ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) {
			$wishlist_items = $query->posts;
		} else {
			$wishlist_items = array_filter( array_map( 'masteriyo_get_wishlist_item', $query->posts ) );
		}

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'items'         => $wishlist_items,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $wishlist_items;
	}

	/**
	 * Prepare valid WP_Query args from query variables.
	 *
	 * @since 1.12.2
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'course' => 'parent',
		);

		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		$wp_query_args              = parent::get_wp_query_args( $query_vars );
		$wp_query_args['post_type'] = 'mto-wishlist-item';

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Handle date queries.
		$date_queries = array(
			'date_created'  => 'post_date',
			'date_modified' => 'post_modified',
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

		/**
		 * Filters WP Query args for wishlist item post type query.
		 *
		 * @since 1.12.2
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars Query vars.
		 * @param \Masteriyo\Addons\WishList\Repository\WishListItemRepository $repository Wishlist item repository object.
		 */
		return apply_filters( 'masteriyo_data_store_cpt_get_wishlist_items_query', $wp_query_args, $query_vars, $this );
	}
}
