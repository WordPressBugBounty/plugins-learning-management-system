<?php
/**
 * LessonReview Repository class.
 *
 * @since 1.14.0
 *
 * @package Masteriyo\Repository;
 */

namespace Masteriyo\Repository;

defined( 'ABSPATH' ) || exit;


use Masteriyo\LessonReviews;
use Masteriyo\Database\Model;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Models\LessonReview;

/**
 * LessonReview Repository class.
 */
class LessonReviewRepository extends AbstractRepository implements RepositoryInterface {
	/**
	 * Meta type.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $meta_type = 'comment';

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.14.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'is_new' => '_is_new',
	);

	/**
	 * Flag to indicate if the filter to modify comment queries by karma has been added.
	 *
	 * @since 1.14.0
	 *
	 * @var bool Defaults to false, indicating that the filter has not been added yet.
	 */
	private $is_comment_karma_filter_added = false;


	/**
	 * Create lesson review (comment) in database.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
	 */
	public function create( Model &$lesson_review ) {
		$current_user = wp_get_current_user();

		if ( ! $lesson_review->get_date_created( 'edit' ) ) {
			$lesson_review->set_date_created( time() );
		}

		if ( ! $lesson_review->get_ip_address( 'edit' ) ) {
			$lesson_review->set_ip_address( masteriyo_get_current_ip_address() );
		}

		if ( ! $lesson_review->get_agent( 'edit' ) ) {
			$lesson_review->set_agent( masteriyo_get_user_agent() );
		}

		if ( ! empty( $current_user ) ) {
			if ( ! $lesson_review->get_author_email( 'edit' ) ) {
				$lesson_review->set_author_email( $current_user->user_email );
			}

			if ( ! $lesson_review->get_author_id( 'edit' ) ) {
				$lesson_review->set_author_id( $current_user->ID );
			}

			if ( ! $lesson_review->get_author_name( 'edit' ) ) {
				$lesson_review->set_author_name( $current_user->user_nicename );
			}

			if ( ! $lesson_review->get_author_url( 'edit' ) ) {
				$lesson_review->set_author_url( $current_user->user_url );
			}
		}

		$id = wp_insert_comment(
			/**
			 * Filters new lesson review data before creating.
			 *
			 * @since 1.14.0
			 *
			 * @param array $data New lesson review data.
			 * @param Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			apply_filters(
				'masteriyo_new_lesson_review_data',
				array(
					'comment_post_ID'      => $lesson_review->get_lesson_id(),
					'comment_author'       => $lesson_review->get_author_name( 'edit' ),
					'comment_author_email' => $lesson_review->get_author_email( 'edit' ),
					'comment_author_url'   => $lesson_review->get_author_url( 'edit' ),
					'comment_author_IP'    => $lesson_review->get_ip_address( 'edit' ),
					'comment_content'      => $lesson_review->get_content(),
					'comment_approved'     => $lesson_review->get_status( 'edit' ),
					'comment_agent'        => $lesson_review->get_agent( 'edit' ),
					'comment_type'         => $lesson_review->get_type( 'edit' ),
					'comment_parent'       => $lesson_review->get_parent( 'edit' ),
					'user_id'              => $lesson_review->get_author_id( 'edit' ),
					'comment_date'         => gmdate( 'Y-m-d H:i:s', $lesson_review->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'comment_date_gmt'     => gmdate( 'Y-m-d H:i:s', $lesson_review->get_date_created( 'edit' )->getTimestamp() ),
				),
				$lesson_review
			)
		);

		// Set comment status.
		wp_set_comment_status( $id, $lesson_review->get_status() );

		if ( $id && ! is_wp_error( $id ) ) {
			$lesson_review->set_id( $id );
			$this->update_comment_meta( $lesson_review, true );

			$lesson_review->save_meta_data();
			$lesson_review->apply_changes();

			/**
			 * Fires after new lesson review is added.
			 *
			 * @since 1.14.0
			 *
			 * @param int $id Lesson review ID.
			 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			do_action( 'masteriyo_new_lesson_review', $id, $lesson_review );
		}
	}

	/**
	 * Read a lesson review.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\LessonReview $lesson_review lesson review object.
	 *
	 * @throws \Exception If invalid lesson review.
	 */
	public function read( Model &$lesson_review ) {
		$lesson_review_obj = get_comment( $lesson_review->get_id() );

		if ( ! $lesson_review->get_id() || ! $lesson_review_obj ) {
			throw new \Exception( __( 'Invalid Lesson Review.', 'learning-management-system' ) );
		}

		// Map the comment status from numerical to word.
		$status = $lesson_review_obj->comment_approved;
		if ( CommentStatus::APPROVE === $status ) {
			$status = CommentStatus::APPROVE_STR;
		} elseif ( CommentStatus::HOLD === $status ) {
			$status = CommentStatus::HOLD_STR;
		}

		$lesson_review->set_props(
			array(
				'lesson_id'    => $lesson_review_obj->comment_post_ID,
				'author_name'  => $lesson_review_obj->comment_author,
				'author_email' => $lesson_review_obj->comment_author_email,
				'author_url'   => $lesson_review_obj->comment_author_url,
				'ip_address'   => $lesson_review_obj->comment_author_IP,
				'date_created' => $this->string_to_timestamp( $lesson_review_obj->comment_date ),
				'content'      => $lesson_review_obj->comment_content,
				'status'       => $status,
				'agent'        => $lesson_review_obj->comment_agent,
				'type'         => $lesson_review_obj->comment_type,
				'parent'       => $lesson_review_obj->comment_parent,
				'author_id'    => $lesson_review_obj->user_id,
			)
		);

		$this->read_comment_data( $lesson_review );
		$this->read_extra_data( $lesson_review );
		$lesson_review->set_object_read( true );

		/**
		 * Fires after lesson review is read from database.
		 *
		 * @since 1.14.0
		 *
		 * @param int $id Lesson review ID.
		 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
		 */
		do_action( 'masteriyo_lesson_review_read', $lesson_review->get_id(), $lesson_review );
	}

	/**
	 * Update a lesson review in the database.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\LessonReview $lesson_review lesson review object.
	 *
	 * @return void
	 */
	public function update( Model &$lesson_review ) {
		$changes = $lesson_review->get_changes();

		$lesson_review_data_keys = array(
			'author_name',
			'author_email',
			'author_url',
			'ip_address',
			'date_created',
			'content',
			'status',
			'parent',
		);

		// Only update the lesson review when the lesson review data changes.
		if ( array_intersect( $lesson_review_data_keys, array_keys( $changes ) ) ) {
			$lesson_review_data = array(
				'comment_author'       => $lesson_review->get_author_name( 'edit' ),
				'comment_author_email' => $lesson_review->get_author_email( 'edit' ),
				'comment_author_url'   => $lesson_review->get_author_url( 'edit' ),
				'comment_author_IP'    => $lesson_review->get_ip_address( 'edit' ),
				'comment_content'      => $lesson_review->get_content( 'edit' ),
				'comment_approved'     => $lesson_review->get_status( 'edit' ),
				'comment_parent'       => $lesson_review->get_parent( 'edit' ),
				'user_id'              => $lesson_review->get_author_id( 'edit' ),
			);

			wp_update_comment( array_merge( array( 'comment_ID' => $lesson_review->get_id() ), $lesson_review_data ) );
		}

		$this->update_comment_meta( $lesson_review );
		$lesson_review->apply_changes();

		/**
		 * Fires after lesson review is updated.
		 *
		 * @since 1.14.0
		 *
		 * @param int $id Lesson review ID.
		 * @param \Masteriyo\Models\LessonReview Lesson review object.
		 */
		do_action( 'masteriyo_update_lesson_review', $lesson_review->get_id(), $lesson_review );
	}

	/**
	 * Delete a lesson review from the database.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\LessonReview $lesson_review lesson review object.
	 * @param array $args Array of args to pass.alert-danger.
	 */
	public function delete( Model &$lesson_review, $args = array() ) {
		$id          = $lesson_review->get_id();
		$object_type = $lesson_review->get_object_type();
		$args        = array_merge(
			array(
				'force_delete' => false,
				'children'     => false,
			),
			$args
		);

		if ( ! $id ) {
			return;
		}

		// Force delete replies.
		$force_delete = $lesson_review->is_reply() ? true : $args['force_delete'];

		// First delete replies because WP will change the comment_parent of replies later.
		if ( ! $lesson_review->is_reply() && $args['children'] ) {
			masteriyo_delete_comment_replies( $id );
		}

		if ( $force_delete ) {
			/**
			 * Fires before lesson review is permanently deleted.
			 *
			 * @since 1.14.0
			 *
			 * @param int $id Lesson review ID.
			 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $lesson_review );

			wp_delete_comment( $id, true );
			$lesson_review->set_id( 0 );

			/**
			 * Fires after lesson review is permanently deleted.
			 *
			 * @since 1.14.0
			 *
			 * @param int $id Lesson review ID.
			 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $lesson_review );
		} else {
			/**
			 * Fires before lesson review is trashed.
			 *
			 * @since 1.14.0
			 *
			 * @param int $id Lesson review ID.
			 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $lesson_review );

			wp_trash_comment( $id );
			$lesson_review->set_status( 'trash' );

			/**
			 * Fires after lesson review is trashed.
			 *
			 * @since 1.14.0
			 *
			 * @param int $id Lesson review ID.
			 * @param \Masteriyo\Models\LessonReview $lesson_review Lesson review object.
			 */
			do_action( 'masteriyo_after_trash_' . $object_type, $id, $lesson_review );
		}

		if ( $lesson_review->is_reply() ) {
			$this->maybe_delete_review_from_trash( $lesson_review->get_parent() );
		}
	}

	/**
	 * Read lesson review data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.14.0
	 *
	 * @param User $lesson_review Lesson review object.
	 */
	protected function read_comment_data( &$lesson_review ) {
		$id          = $lesson_review->get_id();
		$meta_values = $this->read_meta( $lesson_review );
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
			$set_props[ $prop ] = maybe_unserialize( $meta_value ); // get_post_meta only unserializes single values.
		}

		$lesson_review->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the lesson review.
	 *
	 * @since 1.14.0
	 *
	 * @param LessonReview $lesson_review lesson review object.
	 */
	protected function read_extra_data( &$lesson_review ) {
		$meta_values = $this->read_meta( $lesson_review );

		foreach ( $lesson_review->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $lesson_review, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$lesson_review->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Get valid \WP_Comment_Query args from a ObjectQuery's query variables.
	 *
	 * @since 1.14.0
	 *
	 * @param array $query_vars Query vars from a ObjectQuery.
	 *
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		$skipped_values = array( '', array(), null );
		$wp_query_args  = array(
			'errors'     => array(),
			'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		foreach ( $query_vars as $key => $value ) {
			if ( in_array( $value, $skipped_values, true ) || 'meta_query' === $key ) {
				continue;
			}

			// Build meta queries out of vars that are stored in internal meta keys.
			if ( in_array( '_' . $key, $this->internal_meta_keys, true ) ) {
				// Check for existing values if wildcard is used.
				if ( '*' === $value ) {
					$wp_query_args['meta_query'][] = array(
						array(
							'key'     => '_' . $key,
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_' . $key,
							'value'   => '',
							'compare' => '!=',
						),
					);
				} else {
					$wp_query_args['meta_query'][] = array(
						'key'     => '_' . $key,
						'value'   => $value,
						'compare' => is_array( $value ) ? 'IN' : '=',
					);
				}
			} else { // Other vars get mapped to wp_query args or just left alone.
				$key_mapping = array(
					'lesson_id'      => 'post_id',
					'status'         => 'status',
					'page'           => 'paged',
					'per_page'       => 'number',
					'include'        => 'comment__in',
					'exclude'        => 'comment__not_in',
					'parent'         => 'parent',
					'parent_include' => 'parent__in',
					'parent_exclude' => 'parent__not_in',
					'type'           => 'type',
					'return'         => 'fields',
				);

				if ( isset( $key_mapping[ $key ] ) ) {
					$wp_query_args[ $key_mapping[ $key ] ] = $value;
				} else {
					$wp_query_args[ $key ] = $value;
				}
			}
		}

		/**
		 * Filter WP query vars.
		 *
		 * @since 1.14.0
		 * @since 1.14.0  Added third parameter $repository.
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars query vars from a ObjectQuery.
		 * @param \Masteriyo\Repository\AbstractRepository $repository AbstractRepository object.
		 *
		 * @return array WP Query args.
		 */
		return apply_filters( 'masteriyo_get_wp_query_args', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Fetch lessons reviews.
	 *
	 * @since 1.14.0
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		// Fetching review of comment_type 'lesson_review', 'type' already map to 'post_type' so need to add 'type' as 'comment_type' here.
		$args = array_merge( $args, array( 'type' => 'mto_lesson_review' ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			$args['no_found_rows'] = false;
		}

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {

			if ( isset( $args['comment_karma'] ) && ! $this->is_comment_karma_filter_added ) {
				add_filter( 'comments_clauses', array( $this, 'filter_comments_by_karma' ), 10, 2 );
				$this->is_comment_karma_filter_added = true;
			}

			$query = new \WP_Comment_Query( $args );
		}

		if ( isset( $query_vars['return'] ) && 'objects' === $query_vars['return'] && ! empty( $query->comments ) ) {
			// Prime caches before grabbing objects.
			update_comment_cache( $query->comments );
		}

		if ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) {
			$lesson_review = $query->comments;
		} else {
			$lesson_review = array_filter( array_map( 'masteriyo_get_lesson_review', $query->comments ) );
		}

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'lesson_review' => $lesson_review,
				'total'         => $query->found_comments,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $lesson_review;
	}

	/**
	 * Delete a lesson review that has 'trash' status and doesn't have any replies.
	 *
	 * @since 1.14.0
	 *
	 * @param integer $review_id
	 */
	protected function maybe_delete_review_from_trash( $review_id ) {
		$parent_review = masteriyo_get_lesson_review( $review_id );

		if ( is_null( $parent_review ) ) {
			return;
		}

		$replies_count = masteriyo_get_lesson_review_replies_count( $parent_review->get_id() );

		if ( CommentStatus::TRASH === $parent_review->get_status() && 0 === $replies_count ) {
			$parent_review->delete( true );
		}
	}

	/**
	 * Modifies the WHERE clause of a comment query to filter comments by their karma score.
	 *
	 * @since 1.14.0
	 *
	 * @param array $pieces An associative array containing the query's JOIN, WHERE, GROUP BY, ORDER BY, and LIMITS clauses.
	 * @param \WP_Comment_Query $query The current WP_Comment_Query instance, providing access to the query variables.
	 *
	 * @return array Modified pieces of the SQL query with an additional WHERE condition if 'comment_karma' is set.
	 */
	public function filter_comments_by_karma( $pieces, $query ) {
		global $wpdb;

		if ( isset( $query->query_vars['comment_karma'] ) ) {
				$comment_karma    = (int) $query->query_vars['comment_karma'];
				$pieces['where'] .= $wpdb->prepare( " AND $wpdb->comments.comment_karma = %d", $comment_karma );
		}

		return $pieces;
	}
}
