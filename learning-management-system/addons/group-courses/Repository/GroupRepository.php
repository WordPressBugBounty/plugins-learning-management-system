<?php
/**
 * GroupRepository class.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Addons\GroupCourses\Repository
 */

namespace Masteriyo\Addons\GroupCourses\Repository;

use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Addons\GroupCourses\Models\Group;
use Masteriyo\Repository\AbstractRepository;
use Masteriyo\Repository\RepositoryInterface;

/**
 * GroupRepository class.
 */
class GroupRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.9.0
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'emails' => '_emails',
	);

	/**
	 * Create a group in the database.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
	 */
	public function create( Model &$group ) {
		if ( ! $group->get_date_created( 'edit' ) ) {
			$group->set_date_created( time() );
		}

		if ( ! $group->get_author_id( 'edit' ) ) {
			$group->set_author_id( get_current_user_id() );
		}

		$id = wp_insert_post(
			/**
			 * Filters new group data before creating.
			 *
			 * @since 1.9.0
			 *
			 * @param array $data New group data.
			 * @param Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
			 */
			apply_filters(
				'masteriyo_new_group_data',
				array(
					'post_type'      => PostType::GROUP,
					'post_status'    => $group->get_status() ? $group->get_status() : PostStatus::DRAFT,
					'post_author'    => $group->get_author_id( 'edit' ),
					'post_title'     => $group->get_title(),
					'post_content'   => $group->get_description(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_date'      => gmdate( 'Y-m-d H:i:s', $group->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $group->get_date_created( 'edit' )->getTimestamp() ),
				),
				$group
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$group->set_id( $id );
			$this->update_post_meta( $group, true );
			// TODO Invalidate caches.

			$group->save_meta_data();
			$group->apply_changes();

			/**
			 * Fires after creating a group.
			 *
			 * @since 1.9.0
			 *
			 * @param integer $id The group ID.
			 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
			 */
			do_action( 'masteriyo_new_group', $id, $group );
		}

	}

	/**
	 * Read a group.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
	 * @throws \Exception If invalid group.
	 */
	public function read( Model &$group ) {
		$group_post = get_post( $group->get_id() );

		if ( ! $group->get_id() || ! $group_post || PostType::GROUP !== $group_post->post_type ) {
			throw new \Exception( __( 'Invalid group.', 'learning-management-system' ) );
		}

		$group->set_props(
			array(
				'title'         => $group_post->post_title,
				'status'        => $group_post->post_status,
				'author_id'     => $group_post->post_author,
				'description'   => $group_post->post_content,
				'date_created'  => $this->string_to_timestamp( $group_post->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $group_post->post_modified_gmt ),
			)
		);

		$this->read_group_data( $group );
		$this->read_extra_data( $group );
		$group->set_object_read( true );

		/**
		 * Fires after reading a group from database.
		 *
		 * @since 1.9.0
		 *
		 * @param integer $id The group ID.
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
		 */
		do_action( 'masteriyo_group_read', $group->get_id(), $group );
	}

	/**
	 * Update a group in the database.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
	 *
	 * @return void
	 */
	public function update( Model &$group ) {
		$changes = $group->get_changes();

		$post_data_keys = array(
			'title',
			'description',
			'emails',
			'status',
			'author_id',
			'created_at',
			'modified_at',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $group->get_description( 'edit' ),
				'post_title'     => $group->get_title( 'edit' ),
				'post_author'    => $group->get_author_id( 'edit' ),
				'comment_status' => 'closed',
				'post_status'    => $group->get_status(),
				'post_type'      => PostType::GROUP,
				'post_date'      => gmdate( 'Y-m-d H:i:s', $group->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $group->get_date_created( 'edit' )->getTimestamp() ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $group->get_id() ) );
				clean_post_cache( $group->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $group->get_id() ), $post_data ) );
			}
			$group->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $group->get_id(),
				)
			);
			clean_post_cache( $group->get_id() );
		}

		$this->update_post_meta( $group );

		$group->apply_changes();

		/**
		 * Fires after updating a group.
		 *
		 * @since 1.9.0
		 *
		 * @param integer $id The group ID.
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
		 */
		do_action( 'masteriyo_update_group', $group->get_id(), $group );
	}

	/**
	 * Delete a group from the database.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group Group object.
	 * @param array $args   Array of args to pass.alert-danger.
	 */
	public function delete( Model &$group, $args = array() ) {
		$id          = $group->get_id();
		$object_type = $group->get_object_type();

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
			 * Fires before deleting a group.
			 *
			 * @since 1.9.0
			 *
			 * @param integer $id The group ID.
			 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $group );

			wp_delete_post( $id, true );
			$group->set_id( 0 );

			/**
			 * Fires after deleting a group.
			 *
			 * @since 1.9.0
			 *
			 * @param integer $id The group ID.
			 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $group );
		} else {
			/**
			 * Fires before moving a group to trash.
			 *
			 * @since 1.9.0
			 *
			 * @param integer $id The group ID.
			 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $group );

			wp_trash_post( $id );
			$group->set_status( PostStatus::TRASH );

			/**
			 * Fires after moving a group to trash.
			 *
			 * @since 1.9.0
			 *
			 * @param integer $id The group ID.
			 * @param \Masteriyo\Addons\GroupCourses\Models\Group $object The group object.
			 */
			do_action( 'masteriyo_after_trash_' . $object_type, $id, $group );
		}
	}

	/**
	 * Restore an group from the database to previous status.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group group object.
	 * @param array $args   Array of args to pass.
	 */
	public function restore( Model &$group, $args = array() ) {

		$previous_status = get_post_meta( $group->get_id(), '_wp_trash_meta_status', true );

		wp_untrash_post( $group->get_id() );

		$group->set_status( $previous_status );

		$post_data = array(
			'post_status'       => $group->get_status( 'edit' ),
			'post_type'         => PostType::GROUP,
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
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $group->get_id() ) );
		} else {
			wp_update_post( array_merge( array( 'ID' => $group->get_id() ), $post_data ) );
		}
		clean_post_cache( $group->get_id() );

		$id          = $group->get_id();
		$object_type = $group->get_object_type();

		/**
		 * Fires after restoring an group.
		 *
		 * @since 1.9.0
		 *
		 * @param integer $id The group ID.
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group  $object The group object.
		 */
		do_action( 'masteriyo_after_restore_' . $object_type, $id, $group );
	}

	/**
	 * Read group data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.9.0
	 *
	 * @param Group $group Group object.
	 */
	protected function read_group_data( &$group ) {
		$id          = $group->get_id();
		$meta_values = $this->read_meta( $group );

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

		$group->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the group, like button text or group URL for external groups.
	 *
	 * @since 1.9.0
	 *
	 * @param Group $group Group object.
	 */
	protected function read_extra_data( &$group ) {
		$meta_values = $this->read_meta( $group );

		foreach ( $group->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $group, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$group->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Fetch groups.
	 *
	 * @since 1.9.0
	 *
	 * @param array $query_vars Query vars.
	 * @return \Masteriyo\Addons\GroupCourses\Models\Group[]
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
			update_post_caches( $query->posts, array( PostType::GROUP ) );
		}

		$groups = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'masteriyo_get_group', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'groups'        => $groups,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $groups;
	}

	/**
	 * Get valid WP_Query args from a GroupQuery's query variables.
	 *
	 * @since 1.9.0
	 * @param array $query_vars Query vars from a GroupQuery.
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

		$query_vars['post_type'] = PostType::GROUP;

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
		 * Filters WP Query args for group post type query.
		 *
		 * @since 1.9.0
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars Query vars.
		 * @param \Masteriyo\Addons\GroupCourses\Repository\GroupRepository $repository Group repository object.
		 */
		return apply_filters( 'masteriyo_group_wp_query_args', $wp_query_args, $query_vars, $this );
	}
}
