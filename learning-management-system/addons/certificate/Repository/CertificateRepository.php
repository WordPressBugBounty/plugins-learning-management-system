<?php
/**
 * Certificate Repository
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\Repository;

use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Repository\AbstractRepository;
use Masteriyo\Repository\RepositoryInterface;

class CertificateRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.13.0
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array();

	/**
	 * Create a certificate in the database.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
	 */
	public function create( Model &$certificate ) {
		if ( ! $certificate->get_date_created( 'edit' ) ) {
			$certificate->set_date_created( current_time( 'mysql', true ) );
		}

		if ( empty( $certificate->get_author_id( 'edit' ) ) ) {
			$certificate->set_author_id( get_current_user_id() );
		}

		$id = wp_insert_post(
			apply_filters(
				'masteriyo_new_certificate_data',
				array(
					'post_type'     => 'mto-certificate',
					'post_status'   => $certificate->get_status() ? $certificate->get_status() : PostStatus::PUBLISH,
					'post_author'   => $certificate->get_author_id(),
					'post_title'    => $certificate->get_name() ? $certificate->get_name() : __( 'Certificate', 'learning-management-system' ),
					'post_content'  => $certificate->get_html_content(),
					'post_parent'   => $certificate->get_parent_id(),
					'ping_status'   => 'closed',
					'post_date'     => gmdate( 'Y-m-d H:i:s', $certificate->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $certificate->get_date_created( 'edit' )->getTimestamp() ),
					'post_name'     => $certificate->get_slug( 'edit' ),
				),
				$certificate
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$certificate->set_id( $id );
			$this->update_post_meta( $certificate, true );
			// TODO Invalidate caches.

			$certificate->save_meta_data();
			$certificate->apply_changes();

			/**
			 * Fire after new certificate is created.
			 *
			 * @since 1.13.0
			 *
			 * @param int $id Certificate ID.
			 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
			 */
			do_action( 'masteriyo_new_certificate', $id, $certificate );
		}
	}

	/**
	 * Read a certificate.
	 *
	 * @since 1.13.0
	 *
	 * @throws \Exception If invalid certificate.
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
	 */
	public function read( Model &$certificate ) {
		$certificate_post = get_post( $certificate->get_id() );

		if ( ! $certificate->get_id() || ! $certificate_post || 'mto-certificate' !== $certificate_post->post_type ) {
			throw new \Exception( __( 'Invalid certificate.', 'learning-management-system' ) );
		}

		$certificate->set_props(
			array(
				'name'          => $certificate_post->post_title,
				'slug'          => $certificate_post->post_name,
				'date_created'  => $this->string_to_timestamp( $certificate_post->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $certificate_post->post_modified_gmt ),
				'status'        => $certificate_post->post_status,
				'html_content'  => $certificate_post->post_content,
				'parent_id'     => $certificate_post->post_parent,
				'menu_order'    => $certificate_post->menu_order,
				'author_id'     => $certificate_post->post_author,
			)
		);

		$this->read_certificate_data( $certificate );
		$this->read_extra_data( $certificate );
		$certificate->set_object_read( true );

		/**
		 * Fire after certificate is read.
		 *
		 * @since 1.13.0
		 *
		 * @param integer $id Certificate ID.
		 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
		 */
		do_action( 'masteriyo_certificate_read', $certificate->get_id(), $certificate );
	}

	/**
	 * Update a certificate in the database.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
	 */
	public function update( Model &$certificate ) {
		$changes        = $certificate->get_changes();
		$post_data_keys = array(
			'html_content',
			'name',
			'parent_id',
			'status',
			'date_created',
			'date_modified',
			'slug',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $certificate->get_html_content( 'edit' ),
				'post_title'     => $certificate->get_name( 'edit' ),
				'post_parent'    => $certificate->get_parent_id( 'edit' ),
				'comment_status' => 'closed',
				'post_status'    => $certificate->get_status( 'edit' ) ? $certificate->get_status( 'edit' ) : 'publish',
				'post_name'      => $certificate->get_slug( 'edit' ),
				'post_type'      => 'mto-certificate',
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $certificate->get_id() ) );
				clean_post_cache( $certificate->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $certificate->get_id() ), $post_data ) );
			}
			$certificate->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $certificate->get_id(),
				)
			);
			clean_post_cache( $certificate->get_id() );
		}

		$this->update_post_meta( $certificate );

		$certificate->apply_changes();

		/**
		 * Fire after certificate is updated.
		 *
		 * @since 1.13.0
		 *
		 * @param int $id Certificate ID.
		 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
		 */
		do_action( 'masteriyo_update_certificate', $certificate->get_id(), $certificate );
	}

	/**
	 * Delete a certificate from the database.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
	 * @param array $args   Array of args to pass
	 */
	public function delete( Model &$certificate, $args = array() ) {
		$id          = $certificate->get_id();
		$object_type = $certificate->get_object_type();

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
			 * Fire before certificate is deleted.
			 *
			 * @since 1.13.0
			 *
			 * @param integer $id Certificate ID.
			 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $certificate );

			wp_delete_post( $id, true );
			$certificate->set_id( 0 );

			/**
			 * Fire after certificate is deleted.
			 *
			 * @since 1.13.0
			 *
			 * @param integer $id Certificate ID.
			 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $certificate );
		} else {
			/**
			 * Fire before certificate is trashed.
			 *
			 * @since 1.13.0
			 *
			 * @param integer $id Certificate ID.
			 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $certificate );

			wp_trash_post( $id );
			$certificate->set_status( 'trash' );

			/**
			 * Fire after certificate is trashed.
			 *
			 * @since 1.13.0
			 *
			 * @param integer $id Certificate ID.
			 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $certificate );
		}
	}

	/**
	 * Read certificate data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate certificate object.
	 */
	protected function read_certificate_data( &$certificate ) {
		$set_props   = array();
		$meta_values = $this->read_meta( $certificate );

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
			$set_props[ $prop ] = maybe_unserialize( $meta_value ); // get_post_meta only unserializes single values.
		}

		$certificate->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the certificate, like button text or certificate URL for external certificates.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate certificate object.
	 */
	protected function read_extra_data( &$certificate ) {
		$meta_values = $this->read_meta( $certificate );

		foreach ( $certificate->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $certificate, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$certificate->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Query certificates.
	 *
	 * @since 1.13.0
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
			update_post_caches( $query->posts, array( 'certificate' ) );
		}

		$certificates = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'masteriyo_get_certificate', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'certificates'  => $certificates,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $certificates;
	}

	/**
	 * Get valid WP_Query args from a CertificateQuery's query variables.
	 *
	 * @since 1.13.0
	 *
	 * @param array $query_vars Query vars from a CertificateQuery.
	 *
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'status'    => 'post_status',
			'page'      => 'paged',
			'parent_id' => 'post_parent',
		);

		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		$query_vars['post_type'] = 'mto-certificate';

		$wp_query_args = parent::get_wp_query_args( $query_vars );

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

		// Handle orderby.
		if ( isset( $query_vars['orderby'] ) && 'include' === $query_vars['orderby'] ) {
			$wp_query_args['orderby'] = 'post__in';
		}

		/**
		 * Filters certificate repository wp query vars.
		 *
		 * @since 1.13.0
		 *
		 * @param array $wp_query_args WP query args.
		 * @param array $query_vars Query vars.
		 * @param \Masteriyo\Addons\Certificate\Repository\CertificateRepository $this Certificate repository.
		 */
		return apply_filters( 'masteriyo_certificate_data_store_cpt_get_certificates_query', $wp_query_args, $query_vars, $this );
	}
}
