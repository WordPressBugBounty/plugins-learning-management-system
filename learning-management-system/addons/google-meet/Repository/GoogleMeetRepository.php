<?php
/**
 * Google Meet Repository class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\GoogleMeet
 */

namespace Masteriyo\Addons\GoogleMeet\Repository;

use Masteriyo\Database\Model;
use Masteriyo\DateTime;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Repository\AbstractRepository;
use Masteriyo\Repository\RepositoryInterface;

/**
 * Google Meet class.
 *
 * @since 1.11.0
 */
class GoogleMeetRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.11.0
	 * @var array
	*/
	protected $internal_meta_keys = array(
		'created_at'                   => '_created_at',
		'course_id'                    => '_course_id',
		'meeting_id'                   => '_meeting_id',
		'calender_url'                 => '_calender_url',
		'meet_url'                     => '_meet_url',
		'time_zone'                    => '_time_zone',
		'starts_at'                    => '_starts_at',
		'ends_at'                      => '_ends_at',
		'add_all_students_as_attendee' => '_add_all_students_as_attendee',
	);

	/**
	* Create a Google Meet meeting  in the database.
	*
	* @since 1.11.0
	*
	* @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet GoogleMeet object.
	*/
	public function create( Model &$google_meet ) {

		if ( ! $google_meet->get_created_at( 'edit' ) ) {
			$google_meet->set_created_at( time() );
		}

		if ( ! $google_meet->get_starts_at( 'edit' ) ) {
			$google_meet->set_starts_at( time() );
		}

		if ( ! $google_meet->get_ends_at( 'edit' ) ) {
			$google_meet->set_ends_at( time() );
		}

		// Author of the google_meet should be same as that of course, because google_meets are children of courses.
		if ( $google_meet->get_course_id() ) {
			$google_meet->set_author_id( masteriyo_get_course_author_id( $google_meet->get_course_id() ) );
		}

		// Set the author of the google_meet to the current user id, if the google_meet doesn't have a author.
		if ( empty( $google_meet->get_author_id() ) ) {
			$google_meet->set_author_id( get_current_user_id() );
		}

		if ( empty( $google_meet->get_meeting_id() ) ) {
			$google_meet->set_meeting_id( '' );
		}

		if ( empty( $google_meet->get_calender_url() ) ) {
			$google_meet->set_calender_url( '' );
		}

		if ( empty( $google_meet->get_meet_url() ) ) {
			$google_meet->set_meet_url( '' );
		}

		if ( empty( $google_meet->get_time_zone() ) ) {
			$google_meet->set_time_zone( '' );
		}

		if ( empty( $google_meet->get_add_all_students_as_attendee() ) ) {
			$google_meet->set_add_all_students_as_attendee( true );
		}

		$id = wp_insert_post(
			/**
			 * Filters new google_meet data before creating.
			 *
			 * @since 1.11.0
			 *
			 * @param array $data New google_meet session data.
			 * @param Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet google_meet object.
			 */
			apply_filters(
				'masteriyo_new_google_meet_data',
				array(
					'post_type'      => PostType::GOOGLEMEET,
					'post_status'    => PostStatus::PUBLISH,
					'post_author'    => $google_meet->get_author_id( 'edit' ),
					'post_title'     => $google_meet->get_name() ? $google_meet->get_name() : __( 'google_meet Meeting', 'learning-management-system' ),
					'post_content'   => $google_meet->get_description(),
					'post_parent'    => $google_meet->get_parent_id(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'menu_order'     => $google_meet->get_menu_order(),
				),
				$google_meet
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$google_meet->set_id( $id );
			$this->update_post_meta( $google_meet, true );

			$google_meet->save_meta_data();
			$google_meet->apply_changes();

			/**
			 * Fires after creating a google_meet session.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The google_meet ID.
			 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $object The GoogleMeet object.
			 */
			do_action( 'masteriyo_new_google_meet', $id, $google_meet );
		}
	}

	/**
	 * Read a Google Meet session.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet google_meet object.
	 * @throws \Exception If invalid google_meet.
	 */
	public function read( Model &$google_meet ) {
		$google_meet_post = get_post( $google_meet->get_id() );

		if ( ! $google_meet->get_id() || ! $google_meet_post || PostType::GOOGLEMEET !== $google_meet_post->post_type ) {
			throw new \Exception( __( 'Invalid Google Meet.', 'learning-management-system' ) );
		}

		$google_meet->set_props(
			array(
				'name'        => $google_meet_post->post_title,
				'created_at'  => $this->string_to_timestamp( $google_meet_post->post_date_gmt ),
				'modified_at' => $this->string_to_timestamp( $google_meet_post->post_modified_gmt ),
				'description' => $google_meet_post->post_content,
				'parent_id'   => $google_meet_post->post_parent,
				'menu_order'  => $google_meet_post->menu_order,
				'status'      => $google_meet_post->post_status,
				'author_id'   => $google_meet_post->post_author,
				'password'    => $google_meet_post->post_password,
			)
		);

		$this->read_google_meet_data( $google_meet );
		$this->read_extra_data( $google_meet );
		$google_meet->set_object_read( true );

		/**
		 * Fires after reading a GoogleMeet from database.
		 *
		 * @since 1.11.0
		 *
		 * @param integer $id The GoogleMeet ID.
		 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $object The GoogleMeet object.
		 */
		do_action( 'masteriyo_google_meet_read', $google_meet->get_id(), $google_meet );
	}

	/**
	 * Read GoogleMeet data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet google_meet object.
	 */
	protected function read_google_meet_data( &$google_meet ) {
		$meta_values = $this->read_meta( $google_meet );

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
			$set_props[ $prop ] = maybe_unserialize( $meta_value );
		}

		$google_meet->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the google_meet session, like button text or google_meet URL.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet GoogleMeet object.
	 */
	protected function read_extra_data( &$google_meet ) {
		$meta_values = $this->read_meta( $google_meet );

		foreach ( $google_meet->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $google_meet, $function ) ) && isset( $meta_values[ '_' . $key ] ) ) {
				$google_meet->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Update a GoogleMeet in the database.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet google_meet object.
	 *
	 * @return void
	 */
	public function update( Model &$google_meet ) {
		$changes = $google_meet->get_changes();

		$post_data_keys = array(
			'name',
			'status',
			'parent_id',
			'menu_order',
			'description',
			'created_at',
			'modified_at',
			'slug',
			'parent_id',
			'author_id',
			'password',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_title'     => $google_meet->get_name( 'edit' ),
				'post_content'   => $google_meet->get_description( 'edit' ),
				'post_parent'    => $google_meet->get_parent_id( 'edit' ),
				'comment_status' => 'closed',
				'post_status'    => $google_meet->get_status( 'edit' ) ? $google_meet->get_status( 'edit' ) : PostStatus::PUBLISH,
				'menu_order'     => $google_meet->get_menu_order( 'edit' ),
				'post_type'      => PostType::GOOGLEMEET,
				'post_author'    => $google_meet->get_author_id( 'edit' ),
				'post_date'      => gmdate( 'Y-m-d H:i:s', $google_meet->get_created_at( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $google_meet->get_created_at( 'edit' )->getTimestamp() ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $google_meet->get_id() ) );
				clean_post_cache( $google_meet->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $google_meet->get_id() ), $post_data ) );
			}
			$google_meet->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $google_meet->get_id(),
				)
			);
			clean_post_cache( $google_meet->get_id() );
		}

		$this->update_post_meta( $google_meet );

		$google_meet->apply_changes();

		/* *
		 * Fires after updating a GoogleMeet.
		 *
		 * @since 1.11.0
		 *
		 * @param integer $id The GoogleMeet ID.
		 * @param \Masteriyo\Addons\GoogleM\Models\GoogleM $object The GoogleM object.
		 */
		do_action( 'masteriyo_update_google_meet', $google_meet->get_id(), $google_meet );
	}

	/**
	 * Delete a GoogleMeet from the database.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet GoogleMeet object.
	 * @param array $args   Array of args to pass.alert-danger.
	 */
	public function delete( Model &$google_meet, $args = array() ) {
		$id          = $google_meet->get_id();
		$object_type = $google_meet->get_object_type();

		$args = array_merge(
			array(
				'force_delete' => false,
			),
			$args
		);

		if ( ! $id ) {
			return;
		}

			/**
			 * Fires before deleting a google_meet.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The GoogleMeet ID.
			 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $object The google_meet object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $google_meet );

			wp_delete_post( $id, true );
			$google_meet->set_id( 0 );

			/**
			 * Fires after deleting a google_meet.
			 *
			 * @since 1.11.0
			 *
			 * @param integer $id The google_meet ID.
			 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $object The google_meet object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $google_meet );
	}
}
