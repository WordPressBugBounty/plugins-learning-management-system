<?php
/**
 * PriceZoneRepository class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency\Repository
 */

namespace Masteriyo\Addons\MultipleCurrency\Repository;

use Masteriyo\Addons\MultipleCurrency\Enums\PriceZoneStatus;
use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Addons\MultipleCurrency\Models\PriceZone;
use Masteriyo\Repository\AbstractRepository;
use Masteriyo\Repository\RepositoryInterface;

/**
 * PriceZoneRepository class.
 */
class PriceZoneRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.11.0
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'countries'     => '_countries',
		'exchange_rate' => '_exchange_rate',
		'currency'      => '_currency',
	);

	/**
	 * Create a prize zone in the database.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone PriceZone object.
	 */
	public function create( Model &$pricing_zone ) {
		if ( ! $pricing_zone->get_date_created( 'edit' ) ) {
			$pricing_zone->set_date_created( time() );
		}

		if ( ! $pricing_zone->get_author_id( 'edit' ) ) {
			$pricing_zone->set_author_id( get_current_user_id() );
		}

		$id = wp_insert_post(
			/**
			 * Filters new prize zone data before creating.
			 *
			 * @since 1.11.0
			 *
			 * @param array $data New prize zone data.
			 * @param Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone PriceZone object.
			 */
			apply_filters(
				'masteriyo_new_pricing_zone_data',
				array(
					'post_type'      => PostType::PRICE_ZONE,
					'post_status'    => $pricing_zone->get_status() ? $pricing_zone->get_status() : PriceZoneStatus::ACTIVE,
					'post_author'    => $pricing_zone->get_author_id( 'edit' ),
					'post_title'     => $pricing_zone->get_title(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_date'      => gmdate( 'Y-m-d H:i:s', $pricing_zone->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $pricing_zone->get_date_created( 'edit' )->getTimestamp() ),
				),
				$pricing_zone
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$pricing_zone->set_id( $id );
			$this->update_post_meta( $pricing_zone, true );
			// TODO Invalidate caches.

			$pricing_zone->save_meta_data();
			$pricing_zone->apply_changes();

			/**
			 * Fires after creating a prize zone.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The prize zone ID.
			 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
			 */
			do_action( 'masteriyo_new_pricing_zone', $id, $pricing_zone );
		}

	}

	/**
	 * Read a prize zone.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone PriceZone object.
	 * @throws \Exception If invalid pricing zone.
	 */
	public function read( Model &$pricing_zone ) {
		$pricing_zone_post = get_post( $pricing_zone->get_id() );

		if ( ! $pricing_zone->get_id() || ! $pricing_zone_post || PostType::PRICE_ZONE !== $pricing_zone_post->post_type ) {
			throw new \Exception( __( 'Invalid pricing zone.', 'learning-management-system' ) );
		}

		$pricing_zone->set_props(
			array(
				'title'         => $pricing_zone_post->post_title,
				'status'        => $pricing_zone_post->post_status,
				'author_id'     => $pricing_zone_post->post_author,
				'date_created'  => $this->string_to_timestamp( $pricing_zone_post->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $pricing_zone_post->post_modified_gmt ),
			)
		);

		$this->read_pricing_zone_data( $pricing_zone );
		$this->read_extra_data( $pricing_zone );
		$pricing_zone->set_object_read( true );

		/**
		 * Fires after reading a prize zone from database.
		 *
		 * @since 1.11.0
		 *
		 * @param integer $id The prize zone ID.
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
		 */
		do_action( 'masteriyo_pricing_zone_read', $pricing_zone->get_id(), $pricing_zone );
	}

	/**
	 * Update a prize zone in the database.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone PriceZone object.
	 *
	 * @return void
	 */
	public function update( Model &$pricing_zone ) {
		$changes = $pricing_zone->get_changes();

		$post_data_keys = array(
			'title',
			'countries',
			'exchange_rate',
			'currency',
			'status',
			'author_id',
			'created_at',
			'modified_at',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_title'     => $pricing_zone->get_title( 'edit' ),
				'post_author'    => $pricing_zone->get_author_id( 'edit' ),
				'comment_status' => 'closed',
				'post_status'    => $pricing_zone->get_status(),
				'post_type'      => PostType::PRICE_ZONE,
				'post_date'      => gmdate( 'Y-m-d H:i:s', $pricing_zone->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $pricing_zone->get_date_created( 'edit' )->getTimestamp() ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $pricing_zone->get_id() ) );
				clean_post_cache( $pricing_zone->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $pricing_zone->get_id() ), $post_data ) );
			}
			$pricing_zone->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $pricing_zone->get_id(),
				)
			);
			clean_post_cache( $pricing_zone->get_id() );
		}

		$this->update_post_meta( $pricing_zone );

		$pricing_zone->apply_changes();

		/**
		 * Fires after updating a prize zone.
		 *
		 * @since 1.11.0
		 *
		 * @param integer $id The prize zone ID.
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
		 */
		do_action( 'masteriyo_update_pricing_zone', $pricing_zone->get_id(), $pricing_zone );
	}

	/**
	 * Delete a prize zone from the database.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone PriceZone object.
	 * @param array $args   Array of args to pass.alert-danger.
	 */
	public function delete( Model &$pricing_zone, $args = array() ) {
		$id          = $pricing_zone->get_id();
		$object_type = $pricing_zone->get_object_type();

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
			 * Fires before deleting a prize zone.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The prize zone ID.
			 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $pricing_zone );

			wp_delete_post( $id, true );
			$pricing_zone->set_id( 0 );

			/**
			 * Fires after deleting a prize zone.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The prize zone ID.
			 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $pricing_zone );
		} else {
			/**
			 * Fires before moving a prize zone to trash.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The prize zone ID.
			 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $pricing_zone );

			wp_trash_post( $id );
			$pricing_zone->set_status( PostStatus::TRASH );

			/**
			 * Fires after moving a prize zone to trash.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The prize zone ID.
			 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $object The prize zone object.
			 */
			do_action( 'masteriyo_after_trash_' . $object_type, $id, $pricing_zone );
		}
	}

	/**
	 * Restore an prize zone from the database to previous status.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $pricing_zone prize zone object.
	 * @param array $args   Array of args to pass.
	 */
	public function restore( Model &$pricing_zone, $args = array() ) {

		$previous_status = get_post_meta( $pricing_zone->get_id(), '_wp_trash_meta_status', true );

		wp_untrash_post( $pricing_zone->get_id() );

		$pricing_zone->set_status( $previous_status );

		$post_data = array(
			'post_status'       => $pricing_zone->get_status( 'edit' ),
			'post_type'         => PostType::PRICE_ZONE,
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
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $pricing_zone->get_id() ) );
		} else {
			wp_update_post( array_merge( array( 'ID' => $pricing_zone->get_id() ), $post_data ) );
		}
		clean_post_cache( $pricing_zone->get_id() );

		$id          = $pricing_zone->get_id();
		$object_type = $pricing_zone->get_object_type();

		/**
		 * Fires after restoring an prize zone.
		 *
		 * @since 1.11.0
		 *
		 * @param integer $id The prize zone ID.
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone  $object The prize zone object.
		 */
		do_action( 'masteriyo_after_restore_' . $object_type, $id, $pricing_zone );
	}

	/**
	 * Read prize zone data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.11.0
	 *
	 * @param PriceZone $pricing_zone PriceZone object.
	 */
	protected function read_pricing_zone_data( &$pricing_zone ) {
		$id          = $pricing_zone->get_id();
		$meta_values = $this->read_meta( $pricing_zone );

		$set_props = array();

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

		$pricing_zone->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the prize zone, like button text or prize zone URL for external prize zones.
	 *
	 * @since 1.11.0
	 *
	 * @param PriceZone $pricing_zone PriceZone object.
	 */
	protected function read_extra_data( &$pricing_zone ) {
		$meta_values = $this->read_meta( $pricing_zone );

		foreach ( $pricing_zone->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $pricing_zone, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$pricing_zone->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Fetch prize zones.
	 *
	 * @since 1.11.0
	 *
	 * @param array $query_vars Query vars.
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone[]
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
			update_post_caches( $query->posts, array( PostType::PRICE_ZONE ) );
		}

		$pricing_zones = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'masteriyo_get_price_zone', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'pricing_zones' => $pricing_zones,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $pricing_zones;
	}

	/**
	 * Get valid WP_Query args from a PriceZoneQuery's query variables.
	 *
	 * @since 1.11.0
	 * @param array $query_vars Query vars from a PriceZoneQuery.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'status' => 'post_status',
			'page'   => 'paged',
		);

		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		$query_vars['post_type'] = PostType::PRICE_ZONE;

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Handle date queries.
		$date_queries = array(
			'created_at'  => 'post_date',
			'modified_at' => 'post_modified',
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
		 * Filters WP Query args for prize zone post type query.
		 *
		 * @since 1.11.0
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars Query vars.
		 * @param \Masteriyo\Addons\MultipleCurrency\Repository\PriceZoneRepository $repository PriceZone repository object.
		 */
		return apply_filters( 'masteriyo_pricing_zone_wp_query_args', $wp_query_args, $query_vars, $this );
	}
}
