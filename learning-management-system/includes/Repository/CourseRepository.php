<?php
/**
 * Course Repository.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Repository;
 */

namespace Masteriyo\Repository;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Helper\Number;
use Masteriyo\Models\Course;
use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Models\CourseProgress;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;

/**
 * Course repository class.
 *
 * @since 1.0.0
 */
class CourseRepository extends AbstractRepository implements RepositoryInterface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'price'                              => '_price',
		'regular_price'                      => '_regular_price',
		'sale_price'                         => '_sale_price',
		'category_ids'                       => '_category_ids',
		'tag_ids'                            => '_tag_ids',
		'difficulty_id'                      => '_difficulty_id',
		'featured_image'                     => '_thumbnail_id',
		'rating_counts'                      => '_rating_counts',
		'average_rating'                     => '_average_rating',
		'review_count'                       => '_review_count',
		'date_on_sale_from'                  => '_date_on_sale_from',
		'date_on_sale_to'                    => '_date_on_sale_to',
		'enrollment_limit'                   => '_enrollment_limit',
		'duration'                           => '_duration',
		'access_mode'                        => '_access_mode',
		'billing_cycle'                      => '_billing_cycle',
		'show_curriculum'                    => '_show_curriculum',
		'purchase_note'                      => '_purchase_note',
		'highlights'                         => '_highlights',
		'is_ai_created'                      => '_is_ai_created',
		'is_creating'                        => '_is_creating',
		'end_date'                           => '_end_date',
		'enable_course_retake'               => '_enable_course_retake',
		'review_after_course_completion'     => '_review_after_course_completion',
		'disable_course_content'             => '_disable_course_content',
		'fake_enrolled_count'                => '_fake_enrolled_count',
		'welcome_message_to_first_time_user' => '_welcome_message_to_first_time_user',
		'course_badge'                       => '_course_badge',
		'reviews_allowed'                    => '_reviews_allowed',

		// Multiple Currency
		'currency'                           => '_currency',
		'exchange_rate'                      => '_exchange_rate',
		'pricing_method'                     => '_pricing_method',

		'flow'                               => '_flow',
		// Custom Fields
		'custom_fields'                      => '_custom_fields',
	);

	/**
	 * Create a course in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public function create( Model &$course ) {
		if ( ! $course->get_date_created( 'edit' ) ) {
			$course->set_date_created( time() );
		}

		if ( empty( $course->get_author_id() ) ) {
			$course->set_author_id( get_current_user_id() );
		}

		$id = wp_insert_post(
			/**
			 * Filters new course data before creating.
			 *
			 * @since 1.0.0
			 *
			 * @param array $data New course data.
			 * @param Masteriyo\Models\Course $course Course object.
			 */
			apply_filters(
				'masteriyo_new_course_data',
				array(
					'post_type'      => PostType::COURSE,
					'post_status'    => $course->get_status() ? $course->get_status() : PostStatus::PUBLISH,
					'post_author'    => $course->get_author_id( 'edit' ),
					'post_title'     => $course->get_name() ? $course->get_name() : __( 'Course', 'learning-management-system' ),
					'post_content'   => $course->get_description(),
					'post_excerpt'   => $course->get_short_description(),
					'post_parent'    => $course->get_parent_id(),
					'comment_status' => $course->get_reviews_allowed() ? 'open' : 'closed',
					'ping_status'    => 'closed',
					'menu_order'     => $course->get_menu_order(),
					'post_password'  => $course->get_post_password( 'edit' ),
					'post_name'      => $course->get_slug( 'edit' ),
					'post_date'      => gmdate( 'Y-m-d H:i:s', $course->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $course->get_date_created( 'edit' )->getTimestamp() ),
				),
				$course
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$course->set_id( $id );

			$this->update_post_meta( $course, true );
			$this->update_terms( $course, true );
			$this->handle_updated_props( $course );
			$this->update_visibility( $course, true );
			// TODO Invalidate caches.

			$course->save_meta_data();
			$course->apply_changes();

			/**
			 * Fires after creating a course.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The course ID.
			 * @param \Masteriyo\Models\Course $object The course object.
			 */
			do_action( 'masteriyo_new_course', $id, $course );
		}

	}

	/**
	 * Read a course.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If invalid course.
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public function read( Model &$course ) {
		$course_post = get_post( $course->get_id() );

		if ( ! $course->get_id() || ! $course_post || PostType::COURSE !== $course_post->post_type ) {
			throw new \Exception( __( 'Invalid course.', 'learning-management-system' ) );
		}

		if ( ! $course_post->post_author ) {
			$admins = get_users( array( 'role' => 'administrator' ) );

			if ( ! empty( $admins ) ) {
				$first_admin              = reset( $admins );
				$course_post->post_author = $first_admin ? $first_admin->ID : 1;
			}
		}

		$course->set_props(
			array(
				'name'              => $course_post->post_title,
				'slug'              => $course_post->post_name,
				'date_created'      => $this->string_to_timestamp( $course_post->post_date_gmt ) ? $this->string_to_timestamp( $course_post->post_date_gmt ) : $this->string_to_timestamp( $course_post->post_date ),
				'date_modified'     => $this->string_to_timestamp( $course_post->post_modified_gmt ) ? $this->string_to_timestamp( $course_post->post_modified_gmt ) : $this->string_to_timestamp( $course_post->post_modified ),
				'status'            => $course_post->post_status,
				'description'       => $course_post->post_content,
				'short_description' => $course_post->post_excerpt,
				'parent_id'         => $course_post->post_parent,
				'author_id'         => $course_post->post_author,
				'menu_order'        => $course_post->menu_order,
				'post_password'     => $course_post->post_password,
				'reviews_allowed'   => 'open' === $course_post->comment_status,
			)
		);

		$this->read_visibility( $course );
		$this->read_course_data( $course );
		$this->read_extra_data( $course );
		$course->set_object_read( true );

		/**
		 * Fires after reading a course from database.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $id The course ID.
		 * @param \Masteriyo\Models\Course $object The new course object.
		 */
		do_action( 'masteriyo_course_read', $course->get_id(), $course );
	}

	/**
	 * Update a course in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $course Course object.
	 *
	 * @return void
	 */
	public function update( Model &$course ) {
		$changes = $course->get_changes();

		$post_data_keys = array(
			'description',
			'short_description',
			'name',
			'parent_id',
			'reviews_allowed',
			'status',
			'menu_order',
			'date_created',
			'date_modified',
			'slug',
			'author_id',
			'post_password',
		);

		// Only update the post when the post data changes.
		if ( array_intersect( $post_data_keys, array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $course->get_description( 'edit' ),
				'post_excerpt'   => $course->get_short_description( 'edit' ),
				'post_title'     => $course->get_name( 'edit' ),
				'post_parent'    => $course->get_parent_id( 'edit' ),
				'comment_status' => $course->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
				'post_status'    => $course->get_status( 'edit' ) ? $course->get_status( 'edit' ) : PostStatus::PUBLISH,
				'menu_order'     => $course->get_menu_order( 'edit' ),
				'post_password'  => $course->get_post_password( 'edit' ),
				'post_name'      => $course->get_slug( 'edit' ),
				'post_author'    => $course->get_author_id( 'edit' ),
				'post_type'      => PostType::COURSE,
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $course->get_id() ) );
				clean_post_cache( $course->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $course->get_id() ), $post_data ) );
			}

			$course->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID' => $course->get_id(),
				)
			);
			clean_post_cache( $course->get_id() );
		}

		if ( isset( $changes['author_id'] ) ) {
			$this->update_authors( $course );
		}

		$this->update_post_meta( $course );
		$this->update_terms( $course );
		$this->handle_updated_props( $course );
		$this->update_visibility( $course );

		$course->apply_changes();

		/**
		 * Fires after updating a course in database.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $id The course ID.
		 * @param \Masteriyo\Models\Course $object The new course object.
		 */
		do_action( 'masteriyo_update_course', $course->get_id(), $course );
	}

	/**
	 * Update the authors of the course's children (lesson, section, quiz and question).
	 *
	 * @since 1.3.2
	 *
	 * @param Course $course Course id or Course Model or Post.
	 */
	protected function update_authors( $course ) {
		global $wpdb;

		$query = new \WP_Query(
			array(
				'post_type'      => array( 'mto-lesson', 'mto-question', 'mto-quiz', 'mto-section' ),
				'post_status'    => PostStatus::all(),
				'nopaging'       => true,
				'posts_per_page' => -1,
				'meta_query'     => array(
					'course' => array(
						'key'   => '_course_id',
						'value' => $course->get_id(),
						'type'  => 'NUMERIC',
					),
				),
				'fields'         => 'ids',
			)
		);

		$children_ids = (array) $query->posts;

		// Bail early if the course doesn't have children.
		if ( empty( $children_ids ) ) {
			return;
		}

		$sql = $wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_author = %d WHERE ID IN (" . implode( ', ', $children_ids ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$course->get_author_id()
		);

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete a course from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param array $args   Array of args to pass.alert-danger
	 */
	public function delete( Model &$course, $args = array() ) {
		$id          = $course->get_id();
		$object_type = $course->get_object_type();
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

		if ( $args['force_delete'] ) {
			/**
			 * Fires before deleting a course from database.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The course ID.
			 * @param \Masteriyo\Models\Course $course The deleted course object.
			 */
			do_action( 'masteriyo_before_delete_' . $object_type, $id, $course );

			// Delete children
			if ( $args['children'] ) {
				$this->delete_children( $course );
			}

			wp_delete_post( $id, true );
			$course->set_id( 0 );

			/**
			 * Fires after deleting a course from database.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The course ID.
			 * @param \Masteriyo\Models\Course $course The deleted course object.
			 */
			do_action( 'masteriyo_after_delete_' . $object_type, $id, $course );
		} else {
			/**
			 * Fires before moving a course to trash in database.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $id The course ID.
			 * @param \Masteriyo\Models\Course $course The trashed course object.
			 */
			do_action( 'masteriyo_before_trash_' . $object_type, $id, $course );

			wp_trash_post( $id );
			$course->set_status( 'trash' );

			/**
			 * Fires after moving a course to trash in database.
			 *
			 * @since 1.5.2
			 *
			 * @param integer $id The course ID.
			 * @param \Masteriyo\Models\Course $course The trashed course object.
			 */
			do_action( 'masteriyo_after_trash_' . $object_type, $id, $course );
		}
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $model Model object.
	 * @param bool       $force Force update. Used during create.
	 */
	protected function update_terms( &$model, $force = false ) {
		$changes = $model->get_changes();

		if ( $force || array_key_exists( 'category_ids', $changes ) ) {
			$categories = $model->get_category_ids( 'edit' );

			if ( empty( $categories ) && get_option( 'masteriyo_default_course_cat', 0 ) ) {
				$categories = array( get_option( 'masteriyo_default_course_cat', 0 ) );
			}

			wp_set_post_terms( $model->get_id(), $categories, 'course_cat', false );
		}

		if ( $force || array_key_exists( 'tag_ids', $changes ) ) {
			wp_set_post_terms( $model->get_id(), $model->get_tag_ids( 'edit' ), 'course_tag', false );
		}

		if ( $force || array_key_exists( 'difficulty_id', $changes ) ) {
			wp_set_post_terms( $model->get_id(), (array) $model->get_difficulty_id( 'edit' ), 'course_difficulty', false );
		}
	}

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since 1.0.0
	 * @param Course $course Course Object.
	 */
	protected function handle_updated_props( $course ) {
		if ( in_array( 'regular_price', $this->updated_props, true ) || in_array( 'sale_price', $this->updated_props, true ) ) {
			if ( $course->get_sale_price( 'edit' ) >= $course->get_regular_price( 'edit' ) ) {
				update_post_meta( $course->get_id(), '_sale_price', '' );
				$course->set_sale_price( '' );
			}
		}

		if ( in_array( 'date_on_sale_from', $this->updated_props, true ) || in_array( 'date_on_sale_to', $this->updated_props, true )
			|| in_array( 'regular_price', $this->updated_props, true ) || in_array( 'sale_price', $this->updated_props, true ) ) {
			if ( $course->is_on_sale( 'edit' ) ) {
				update_post_meta( $course->get_id(), '_price', $course->get_sale_price( 'edit' ) );
				$course->set_price( $course->get_sale_price( 'edit' ) );
			} else {
				update_post_meta( $course->get_id(), '_price', $course->get_regular_price( 'edit' ) );
				$course->set_price( $course->get_regular_price( 'edit' ) );
			}
		}

		// Update the prices according to the access mode.
		if ( in_array( $course->get_access_mode( 'edit' ), array( CourseAccessMode::OPEN, CourseAccessMode::NEED_REGISTRATION ), true ) ) {
			update_post_meta( $course->get_id(), '_price', '0' );
			update_post_meta( $course->get_id(), '_regular_price', '0' );
			update_post_meta( $course->get_id(), '_sale_price', '' );

			$course->set_price( $course->set_price( '0' ) );
			$course->set_sale_price( $course->set_sale_price( '0' ) );
			$course->set_regular_price( $course->set_regular_price( '0' ) );
		}

		// Update the price type according to the access mode.
		$access_mode = $course->get_access_mode( 'edit' );
		if ( in_array( $access_mode, array( CourseAccessMode::OPEN, CourseAccessMode::NEED_REGISTRATION ), true ) ) {
			$course->set_price_type( CoursePriceType::FREE );
		} else {
			$course->set_price_type( CoursePriceType::PAID );
		}
	}

	/**
	 * Update visibility terms based on props.
	 *
	 * @since 1.0.0
	 *
	 * @param Course $course Course object.
	 * @param bool       $force Force update. Used during create.
	 */
	protected function update_visibility( &$course, $force = false ) {
		$changes           = $course->get_changes();
		$course_attributes = array( 'featured', 'price_type', 'stock_status', 'average_rating', 'catalog_visibility' );

		if ( $force || array_intersect( $course_attributes, array_keys( $changes ) ) ) {
			$terms = array();

			if ( $course->get_featured() ) {
				$terms[] = 'featured';
			}

			if ( ! empty( $course->get_price_type() ) ) {
				$terms[] = $course->get_price_type();
			}

			$rating = min( 5, masteriyo_round( $course->get_average_rating(), 0 ) );

			if ( $rating > 0 ) {
				$terms[] = 'rated-' . $rating;
			}

			switch ( $course->get_catalog_visibility() ) {
				case 'hidden':
					$terms[] = 'exclude-from-search';
					$terms[] = 'exclude-from-catalog';
					break;
				case 'catalog':
					$terms[] = 'exclude-from-search';
					break;
				case 'search':
					$terms[] = 'exclude-from-catalog';
					break;
			}

			if ( ! is_wp_error( wp_set_post_terms( $course->get_id(), $terms, 'course_visibility', false ) ) ) {
				/**
				 * Fires after updating a course's visibility in database.
				 *
				 * @since 1.0.0
				 *
				 * @param integer $id The course ID.
				 * @param string $visibility The course visibility.
				 */
				do_action( 'masteriyo_course_set_visibility', $course->get_id(), $course->get_catalog_visibility() );
			}
		}
	}


	/**
	 * Read course data. Can be overridden by child classes to load other props.
	 *
	 * @since 1.0.0
	 *
	 * @param Course $course course object.
	 */
	protected function read_course_data( &$course ) {
		$id          = $course->get_id();
		$meta_values = $this->read_meta( $course );

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

		$set_props['category_ids']  = $this->get_term_ids( $course, 'course_cat' );
		$set_props['tag_ids']       = $this->get_term_ids( $course, 'course_tag' );
		$set_props['difficulty_id'] = $this->get_term_ids( $course, 'course_difficulty' );

		$course->set_props( $set_props );
	}

	/**
	 * Read extra data associated with the course, like button text or course URL for external courses.
	 *
	 * @since 1.0.0
	 *
	 * @param Course $course course object.
	 */
	protected function read_extra_data( &$course ) {
		$meta_values = $this->read_meta( $course );

		foreach ( $course->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $course, $function ) )
				&& isset( $meta_values[ '_' . $key ] ) ) {
				$course->{$function}( $meta_values[ '_' . $key ] );
			}
		}
	}

	/**
	 * Fetch courses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_vars Query vars.
	 * @return Course[]
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
			update_post_caches( $query->posts, array( PostType::COURSE ) );
		}

		$courses = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'masteriyo_get_course', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'courses'       => $courses,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $courses;
	}

	/**
	 * Convert visibility terms to props.
	 * Catalog visibility valid values are 'visible', 'catalog', 'search', and 'hidden'.
	 *
	 * @param Course $course Course object.
	 * @since 1.0.0
	 */
	protected function read_visibility( &$course ) {
		$terms           = get_the_terms( $course->get_id(), 'course_visibility' );
		$term_names      = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
		$featured        = in_array( 'featured', $term_names, true );
		$exclude_search  = in_array( 'exclude-from-search', $term_names, true );
		$exclude_catalog = in_array( 'exclude-from-catalog', $term_names, true );
		$price_type      = in_array( CoursePriceType::FREE, $term_names, true ) ? CoursePriceType::FREE : CoursePriceType::PAID;

		if ( $exclude_search && $exclude_catalog ) {
			$catalog_visibility = 'hidden';
		} elseif ( $exclude_search ) {
			$catalog_visibility = 'catalog';
		} elseif ( $exclude_catalog ) {
			$catalog_visibility = 'search';
		} else {
			$catalog_visibility = 'visible';
		}

		$course->set_props(
			array(
				'featured'           => $featured,
				'catalog_visibility' => $catalog_visibility,
				'price_type'         => $price_type,
			)
		);
	}

	/**
	 * Get valid WP_Query args from a CourseQuery's query variables.
	 *
	 * @since 1.0.0
	 * @param array $query_vars Query vars from a CourseQuery.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// These queries cannot be auto-generated so we have to remove them and build them manually.
		$manual_queries = array(
			'featured'   => '',
			'visibility' => '',
		);

		foreach ( $manual_queries as $key => $manual_query ) {
			if ( isset( $query_vars[ $key ] ) ) {
				$manual_queries[ $key ] = $query_vars[ $key ];
				unset( $query_vars[ $key ] );
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Handle course categories.
		if ( ! empty( $query_vars['category'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'course_cat',
				'field'    => 'slug',
				'terms'    => is_array( $query_vars['category'] ) ? $query_vars['category'] : array( $query_vars['category'] ),
				'operator' => 'IN',
			);
		}

		// Handle course tags.
		if ( ! empty( $query_vars['tag'] ) ) {
			unset( $wp_query_args['tag'] );
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'course_tag',
				'field'    => 'slug',
				'terms'    => $query_vars['tag'],
			);
		}

		// Handle course difficulties.
		if ( ! empty( $query_vars['difficulty'] ) ) {
			unset( $wp_query_args['difficulty'] );
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'course_difficulty',
				'field'    => 'slug',
				'terms'    => $query_vars['difficulty'],
			);
		}

		// Handle featured.
		if ( '' !== $manual_queries['featured'] ) {
			if ( $manual_queries['featured'] ) {
				$course_visibility_term_ids = masteriyo_get_course_visibility_term_ids();

				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'course_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $course_visibility_term_ids['featured'] ),
				);

				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'course_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $course_visibility_term_ids['exclude-from-catalog'] ),
					'operator' => 'NOT IN',
				);
			} else {
				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'course_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $course_visibility_term_ids['featured'] ),
					'operator' => 'NOT IN',
				);
			}
		}

		// Handle date queries.
		$date_queries = array(
			'date_created'      => 'post_date',
			'date_modified'     => 'post_modified',
			'date_on_sale_from' => '_sale_price_dates_from',
			'date_on_sale_to'   => '_sale_price_dates_to',
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

		// Handle meta queries.
		$meta_queries = array(
			'course',
		);

		foreach ( $query_vars as $query_var => $value ) {
			if ( in_array( $query_var, $meta_queries, true ) ) {
				$wp_query_vars['meta_query'][] = array(
					'key'     => $query_var,
					'value'   => $value,
					'compare' => '=',
				);
			}
		}

		if ( isset( $wp_query_vars['meta_query'] ) ) {
			$wp_query_args['meta_query'][] = array( 'relation' => 'AND' );
		}

		// Handle paginate.
		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		// Handle reviews_allowed.
		if ( isset( $query_vars['reviews_allowed'] ) && is_bool( $query_vars['reviews_allowed'] ) ) {
			add_filter( 'posts_where', array( $this, 'reviews_allowed_query_where' ), 10, 2 );
		}

		// Handle orderby.
		if ( isset( $query_vars['orderby'] ) && 'include' === $query_vars['orderby'] ) {
			$wp_query_args['orderby'] = 'post__in';
		}

		/**
		 * Filters WP Query args for course post type query.
		 *
		 * @since 1.0.0
		 *
		 * @param array $wp_query_args WP Query args.
		 * @param array $query_vars Query vars.
		 * @param \Masteriyo\Repository\CourseRepository $repository Course repository object.
		 */
		return apply_filters( 'masteriyo_course_data_store_cpt_get_courses_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Get course progress status in fraction.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\Course|int $course Course object.
	 * @param Masteriyo\Models\User|int $user User object.
	 *
	 * @return string
	 */
	public function get_progress_status( $course, $user = null ) {
		$course_id = is_a( $course, 'Masteriyo\Models\Course' ) ? $course->get_id() : $course;
		$user_id   = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : $user;
		$user_id   = is_null( $user_id ) ? get_current_user_id() : $user_id;

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'per_page'  => 1,
			)
		);

		$course_progress = current( $query->get_course_progress() );
		$completed       = 0;
		$total           = 0;

		if ( ! empty( $course_progress ) ) {
			$summary   = $course_progress->get_summary();
			$completed = $summary['total']['completed'];
			$total     = $summary['total']['total'];
		}

		return array(
			'completed' => $completed,
			'total'     => $total,
		);
	}

	/**
	 * Retrieves progress data for a given course and user.
	 *
	 * @since 1.11.0
	 *
	 * @param Masteriyo\Models\Course|int $course Course object.
	 * @param Masteriyo\Models\User|int $user User object.
	 *
	 * @return array The progress data for the course and user.
	 */
	public function get_progress_data( $course, $user = null ) {
		$course_id = is_a( $course, 'Masteriyo\Models\Course' ) ? $course->get_id() : $course;
		$user_id   = is_a( $user, 'Masteriyo\Models\User' ) ? $user->get_id() : $user;
		$user_id   = is_null( $user_id ) ? get_current_user_id() : $user_id;

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'per_page'  => 1,
			)
		);

		$course_progress = current( $query->get_course_progress() );

		$progress_data = array();

		if ( ! empty( $course_progress ) ) {
			$progress_data = array(
				'status'               => $course_progress->get_status(),
				'started_at'           => masteriyo_rest_prepare_date_response( $course_progress->get_started_at() ),
				'modified_at'          => masteriyo_rest_prepare_date_response( $course_progress->get_modified_at() ),
				'completed_at'         => masteriyo_rest_prepare_date_response( $course_progress->get_completed_at() ),
				'is_password_required' => post_password_required( get_post( $course_progress->get_course_id() ) ),
				'retake_url'           => 'completed' === $course_progress->get_status() ? $course->get_retake_url() : '',
			);
		}

		return $progress_data;
	}

	/**
	 * Delete sections, lessons, quizzes and questions under the course.
	 *
	 * @since 1.3.10
	 *
	 * @param Masteriyo\Models\Course $course Course object.
	 */
	protected function delete_children( $course ) {
		$children = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => array_merge( CourseChildrenPostType::all(), array( PostType::QUESTION ) ),
				'post_status' => PostStatus::ANY,
				'meta_key'    => '_course_id',
				'meta_value'  => $course->get_id(),
			)
		);

		foreach ( $children as $child ) {
			wp_delete_post( $child->ID, true );
		}
	}
}
