<?php
/**
 * Course model.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Models;
 */

namespace Masteriyo\Models;

use Masteriyo\Helper\Utils;
use Masteriyo\Database\Model;
use Masteriyo\Enums\CourseFlow;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Repository\RepositoryInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Course model (post type).
 *
 * @since 1.0.0
 */
class Course extends Model {

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @since 1.6.9
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $object_type = 'course';

	/**
	 * Post type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-course';

	/**
	 * Cache group.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $cache_group = 'courses';

	/**
	 * Course Progress.
	 *
	 * @since 1.14.2
	 *
	 * @var \Masteriyo\Models\CourseProgressItem|null
	 * Default: null
	 */
	public $progress = null;

	/**
	 * Stores course data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $data = array(
		'name'                               => '',
		'slug'                               => '',
		'date_created'                       => null,
		'date_modified'                      => null,
		'status'                             => false,
		'menu_order'                         => 0,
		'featured'                           => false,
		'catalog_visibility'                 => 'visible',
		'description'                        => '',
		'short_description'                  => '',
		'post_password'                      => '',
		'author_id'                          => 0,
		'parent_id'                          => 0,
		'reviews_allowed'                    => true,
		'date_on_sale_from'                  => null,
		'date_on_sale_to'                    => null,
		'price'                              => '',
		'regular_price'                      => '',
		'sale_price'                         => '',
		'price_type'                         => CoursePriceType::FREE,
		'category_ids'                       => array(),
		'tag_ids'                            => array(),
		'difficulty_id'                      => 0,
		'featured_image'                     => '',
		'rating_counts'                      => array(),
		'average_rating'                     => 0,
		'review_count'                       => 0,
		'enrollment_limit'                   => 0,
		'duration'                           => 0,
		'access_mode'                        => CourseAccessMode::OPEN,
		'billing_cycle'                      => '',
		'show_curriculum'                    => true,
		'purchase_note'                      => '',
		'highlights'                         => '',
		'is_ai_created'                      => false,
		'is_creating'                        => false,
		'end_date'                           => '',
		'enable_course_retake'               => false,
		'review_after_course_completion'     => false,
		'disable_course_content'             => false,
		'fake_enrolled_count'                => 0,
		'welcome_message_to_first_time_user' => array(
			'enabled'     => false,
			'title'       => 'Welcome to the Course.',
			'description' => "Get ready to dive into exciting lessons, connect with peers, and unlock new possibilities. Let's embark on this educational adventure together!",
		),
		'course_badge'                       => '',

		// Multiple currency
		'currency'                           => '',
		'exchange_rate'                      => '',
		'pricing_method'                     => '',
		'flow'                               => CourseFlow::FREE_FLOW,
		'custom_fields'                      => null,

	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param RepositoryInterface $course_repository Course Repository,
	 */
	public function __construct( RepositoryInterface $course_repository ) {
		$this->repository = $course_repository;
	}

	/**
	 * Save data to the database.
	 *
	 * @since 1.6.9
	 *
	 * @return int order ID
	 */
	public function save() {
		parent::save();
		$this->status_transition();
		return $this->get_id();
	}

	/**
	 * Handle the status transition.
	 *
	 * @since 1.6.9
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( ! $status_transition ) {
			return;
		}

		/**
		 * Fires after course model's status transition.
		 *
		 * @since 1.6.9
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 * @param string $old_status Old status.
		 * @param string $new_status New status.
		 */
		do_action( 'masteriyo_course_status_changed', $this, $status_transition['from'], $status_transition['to'] );
	}

	/**
	 * Get featured image URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_featured_image_url( $size = 'thumbnail' ) {
		if ( empty( $this->get_featured_image() ) ) {
			return masteriyo_placeholder_img_src( $size );
		}
		return strval( wp_get_attachment_image_url( $this->get_featured_image(), $size ) );
	}

	/**
	 * If the stock level comes from another product ID, this should be modified.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_stock_managed_by_id() {
		return $this->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the course's title. For courses this is the course name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title() {
		/**
		 * Filters course title.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title Course title.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_title', $this->get_name(), $this );
	}

	/**
	 * Course permalink.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Course post preview URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_preview_course_link() {
		return get_preview_post_link( $this->get_id() );
	}

	/**
	 * Get course preview link with learn page.
	 *
	 * @since 1.4.1
	 *
	 * @return string
	 */
	public function get_preview_link() {
		$learn_page_url = masteriyo_get_page_permalink( 'learn' );

		$preview_link = add_query_arg(
			array(
				'course_name' => $this->get_id(),
				'mto-preview' => 'true',
			),
			$learn_page_url
		);

		$preview_link .= '#/course/' . $this->get_id();

		/**
		 * Filters course preview link.
		 *
		 * @since 1.4.1
		 *
		 * @param string $link Course preview link.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_preview_link', $preview_link, $this );
	}


	/**
	 * Get edit post link.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_edit_post_link() {
		$context        = 'edit';
		$edit_post_link = get_edit_post_link( $this->get_id(), $context );

		if ( is_null( $edit_post_link ) ) {
			$edit_post_link = '';
		}

		/**
		 * Certificate post preview link.
		 *
		 * @since 1.5.35
		 *
		 * @param string $edit_post_link Edit post link.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param string $context Context.
		 */
		return apply_filters( 'masteriyo_course_edit_post_link', $edit_post_link, $this, $context );
	}

	/**
	 * Get course retake URL.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public function get_retake_url() {
		$retake_url = add_query_arg(
			array(
				'masteriyo_course_retake' => '1',
				'course_id'               => $this->get_id(),
				'nonce'                   => wp_create_nonce( 'masteriyo_course_retake' ),
			),
			home_url()
		);

		/**
		 * Filters course retake URL.
		 *
		 * @since 1.7.0
		 *
		 * @param string $retake_url cCurse retake URL.
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_retake_url', $retake_url, $this );
	}

	/**
	 * Returns the children IDs if applicable. Overridden by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array of IDs
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Get lecture hours in human readable format.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_human_readable_lecture_hours() {
		return masteriyo_get_lecture_hours( $this );
	}

	/**
	 * Get the object type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get rest formatted price.
	 *
	 * @since 1.5.36
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_rest_formatted_price( $context = 'view' ) {
		$price = masteriyo_price( $this->get_price( $context ), array( 'html' => false ) );

		/**
		 * Filters the rest formatted course price.
		 *
		 * @since 1.5.36
		 *
		 * @param integer $price Formatted price.
		 * @param Masteriyo\Models\Course $course The course object.
		 */
		return apply_filters( 'masteriyo_course_formatted_price', $price, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get course name.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get course slug.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get course created date.
	 *
	 * @since  1.0.0
	 * @since 1.5.32 Return \Masteriyo\DateTime|null
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get course modified date.
	 *
	 * @since  1.0.0
	 * @since 1.5.32 Return \Masteriyo\DateTime|null
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get course status.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get catalog visibility.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_catalog_visibility( $context = 'view' ) {
		return $this->get_prop( 'catalog_visibility', $context );
	}

	/**
	 * Get course description.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Get course short description.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_short_description( $context = 'view' ) {
		return $this->get_prop( 'short_description', $context );
	}

	/**
	 * Get course excerpt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_excerpt( $context = 'view' ) {
		$excerpt = get_the_excerpt( $this->get_id() );

		/**
		 * Filters course excerpt length.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $length Course excerpt length.
		 */
		$excerpt_length = absint( apply_filters( 'masteriyo_course_excerpt_length', 35 ) );

		/**
		 * Filters course excerpt more symbol.
		 *
		 * @since 1.0.0
		 *
		 * @param string $symbol Course excerpt more symbol.
		 */
		$excerpt_more = apply_filters( 'masteriyo_course_excerpt_more', '&hellip;' );

		$excerpt = wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );

		/**
		 * Filters course excerpt.
		 *
		 * @since 1.0.0
		 *
		 * @param string $excerpt Course excerpt.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_excerpt', $excerpt, $this );
	}

	/**
	 * Returns the course's password.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_post_password( $context = 'view' ) {
		return $this->get_prop( 'post_password', $context );
	}

	/**
	 * Returns whether review is allowed or not..
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_reviews_allowed( $context = 'view' ) {
		return $this->get_prop( 'reviews_allowed', $context );
	}


	/**
	 * Get date on sale from.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return Masteriyo\DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_on_sale_from( $context = 'view' ) {
		return $this->get_prop( 'date_on_sale_from', $context );
	}

	/**
	 * Get date on sale to.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return Masteriyo\DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_on_sale_to( $context = 'view' ) {
		return $this->get_prop( 'date_on_sale_to', $context );
	}

	/**
	 * Returns course author id.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Returns course parent id.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Returns course menu order.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Returns course's active price.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Course's active price
	 */
	public function get_price( $context = 'view' ) {
		return $this->get_prop( 'price', $context );
	}

	/**
	 * Returns course's regular price.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Course's regular price
	 */
	public function get_regular_price( $context = 'view' ) {
		return $this->get_prop( 'regular_price', $context );
	}

	/**
	 * Returns course's sale price.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Course's sale price
	 */
	public function get_sale_price( $context = 'view' ) {
		return $this->get_prop( 'sale_price', $context );
	}
	/**
	 * Returns course's price type.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_price_type( $context = 'view' ) {
		return $this->get_prop( 'price_type', $context );
	}

	/**
	 * Returns course category ids.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array[integer] Category IDs.
	 */
	public function get_category_ids( $context = 'view' ) {
		return $this->get_prop( 'category_ids', $context );
	}

	/**
	 * Returns course tag ids.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_tag_ids( $context = 'view' ) {
		return $this->get_prop( 'tag_ids', $context );
	}

	/**
	 * Returns course difficulty id.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_difficulty_id( $context = 'view' ) {
		return $this->get_prop( 'difficulty_id', $context );
	}

	/**
	 * Get the difficulty object.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_difficulty() {
		$terms = Utils::get_object_terms( $this->get_id(), 'course_difficulty' );

		$terms = array_map(
			function( $term ) {
				return array(
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'color' => strval( get_term_meta( $term->term_id, '_color', true ) ),
				);
			},
			$terms
		);

		return array_shift( $terms );
	}

	/**
	 * Returns course tag ids.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string price
	 */
	public function get_featured_image( $context = 'view' ) {
		return $this->get_prop( 'featured_image', $context );
	}

	/**
	 * Check whether the course is featured or not.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function get_featured( $context = 'view' ) {
		return $this->get_prop( 'featured', $context );
	}

	/**
	 * Returns whether or not the course is featured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_featured() {
		return true === $this->get_featured();
	}

	/**
	 * Get the total amount (COUNT) of ratings, or just the count for one rating e.g. number of 5 star ratings.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $value Optional. Rating value to get the count for. By default returns the count of all rating values.
	 *
	 * @return int
	 */
	public function get_rating_count( $value = null ) {
		$counts = $this->get_rating_counts();

		if ( is_null( $value ) ) {
			return array_sum( $counts );
		} elseif ( isset( $counts[ $value ] ) ) {
			return absint( $counts[ $value ] );
		}
		return 0;
	}

	/**
	 * Get rating count.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array of counts
	 */
	public function get_rating_counts( $context = 'view' ) {
		return $this->get_prop( 'rating_counts', $context );
	}

	/**
	 * Get average rating.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return float
	 */
	public function get_average_rating( $context = 'view' ) {
		return $this->get_prop( 'average_rating', $context );
	}

	/**
	 * Get review count.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_review_count( $context = 'view' ) {
		return $this->get_prop( 'review_count', $context );
	}

	/**
	 * Get the enrollment limit (maximum number of students allowed to enroll).
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_enrollment_limit( $context = 'view' ) {
		return $this->get_prop( 'enrollment_limit', $context );
	}

	/**
	 * Get course duration.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_duration( $context = 'view' ) {
		return $this->get_prop( 'duration', $context );
	}

	/**
	 * Get course access mode.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_access_mode( $context = 'view' ) {
		return $this->get_prop( 'access_mode', $context );
	}

	/**
	 * Get course billing cycle.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_billing_cycle( $context = 'view' ) {
		return $this->get_prop( 'billing_cycle', $context );
	}

	/**
	 * Get course curriculum.
	 *
	 * True = Visible to all.
	 * False = Visible to only enrollees.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_show_curriculum( $context = 'view' ) {
		return $this->get_prop( 'show_curriculum', $context );
	}

	/**
	 * Get enable review after course completion.
	 *
	 * True = Review option after completion of course for student.
	 * False = Not visible review option after completion of course for student.
	 *
	 * @since 1.7.1
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_review_after_course_completion( $context = 'view' ) {
		return $this->get_prop( 'review_after_course_completion', $context );
	}

	/**
	 * Get course purchase note.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_purchase_note( $context = 'view' ) {
		return $this->get_prop( 'purchase_note', $context );
	}

	/**
	 * Get course highlights.
	 *
	 * @since 1.0.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_highlights( $context = 'view' ) {
		return $this->get_prop( 'highlights', $context );
	}

	/**
	 * Get main image ID.
	 *
	 * @since  1.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_image_id( $context = 'view' ) {
		return $this->get_featured_image( $context );
	}

	/**
	 * Get course created using AI or not.
	 *
	 * True = If created using AI.
	 * False = If not created using AI.
	 *
	 * @since 1.6.15
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_is_ai_created( $context = 'view' ) {
		return $this->get_prop( 'is_ai_created', $context );
	}

	/**
	 * Get course is creating status.
	 *
	 * True = If course is creating.
	 * False = If not creating.
	 *
	 * @since 1.6.15
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_is_creating( $context = 'view' ) {
		return $this->get_prop( 'is_creating', $context );
	}

	/**
	 * Get course end date.
	 *
	 * @since 1.7.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_end_date( $context = 'view' ) {
		return $this->get_prop( 'end_date', $context );
	}
	/**
	 * Get enable_course_retake attribute.
	 *
	 * @since 1.7.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_enable_course_retake( $context = 'view' ) {
		return $this->get_prop( 'enable_course_retake', $context );
	}

	/**
	 * Get disable_course_content attribute.
	 *
	 * @since 1.8.0 [free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_disable_course_content( $context = 'view' ) {
		return $this->get_prop( 'disable_course_content', $context );
	}

	/**
	 * Get welcome_message_to_first_time_user attribute.
	 *
	 * @since 1.9.3
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_welcome_message_to_first_time_user( $context = 'view' ) {
		return $this->get_prop( 'welcome_message_to_first_time_user', $context );
	}

	/**
	 * Get the fake enrolled count (minimum number of students enrolled that appears despite no students are enrolled).
	 *
	 * @since 1.9.3
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_fake_enrolled_count( $context = 'view' ) {
		return $this->get_prop( 'fake_enrolled_count', $context );
	}

	/**
	 * Get course badge.
	 *
	 * @since 1.9.3
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_course_badge( $context = 'view' ) {
		return $this->get_prop( 'course_badge', $context );
	}

	/**
	 * Returns course's currency.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Course's currency
	 */
	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	/**
	 * Get the exchange rate for the order.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context The context for the property value. Accepts 'view' or 'edit'.
	 *
	 * @return string The exchange rate for the order.
	 */
	public function get_exchange_rate( $context = 'view' ) {
		return $this->get_prop( 'exchange_rate', $context );
	}

	/**
	 * Get the pricing method for the order.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context The context for the property value. Accepts 'view' or 'edit'.
	 *
	 * @return string The pricing method for the order.
	 */
	public function get_pricing_method( $context = 'view' ) {
		return $this->get_prop( 'pricing_method', $context );
	}

	/**
	 * Return course flow.
	 *
	 * @since  1.15.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_flow( $context = 'view' ) {
		return $this->get_prop( 'flow' );
	}

	/**
	 * Get custom fields values.
	 *
	 * @since 2.11.0
	 *
	 * @param string $context The context for the property value. Accepts 'view' or 'edit'.
	 *
	 * @return string Custom fields values.
	 */
	public function get_custom_fields( $context = 'view' ) {
		return $this->get_prop( 'custom_fields', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set course name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name course name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set course slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug course slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set course created date.
	 *
	 * @since 1.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set course modified date.
	 *
	 * @since 1.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set course status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_status course status.
	 */
	public function set_status( $new_status ) {
		$old_status = $this->get_status();

		$this->set_prop( 'status', $new_status );

		if ( true === $this->object_read && ! empty( $old_status ) && $old_status !== $new_status ) {
			$this->status_transition = array(
				'from' => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $old_status,
				'to'   => $new_status,
			);
		}
	}

	/**
	 * Set course description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $description Course description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set course short description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $short_description Course short description.
	 */
	public function set_short_description( $short_description ) {
		$this->set_prop( 'short_description', $short_description );
	}

	/**
	 * Set the course's password.
	 *
	 * @since 1.0.0
	 *
	 * @param string $password Password.
	 */
	public function set_post_password( $password ) {
		$this->set_prop( 'post_password', $password );
	}

	/**
	 * Set the course's review status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reviews_allowed Reviews allowed.( Value can be 'open' or 'closed')
	 */
	public function set_reviews_allowed( $reviews_allowed ) {
		$this->set_prop( 'reviews_allowed', $reviews_allowed );
	}

	/**
	 * Set the course author id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $author_id Author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set the course parent id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent Parent id.
	 */
	public function set_parent_id( $parent ) {
		$this->set_prop( 'parent_id', absint( $parent ) );
	}

	/**
	 * Set the course menu order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $menu_order Menu order id.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Set the course's active price.
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Price.
	 */
	public function set_price( $price ) {
		$this->set_prop( 'price', masteriyo_format_decimal( $price ) );
	}

	/**
	 * Set the course's regular price.
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Regular price.
	 */
	public function set_regular_price( $price ) {
		$this->set_prop( 'regular_price', masteriyo_format_decimal( $price ) );
	}

	/**
	 * Set the course's sale price.
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Sale price.
	 */
	public function set_sale_price( $price ) {
		$this->set_prop( 'sale_price', masteriyo_format_decimal( $price ) );
	}

	/**
	 * Set the course's price type (free or paid).
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Course's price type (free or paid)
	 */
	public function set_price_type( $type ) {
		$this->set_prop( 'price_type', $type );
	}

	/**
	 * Set date on sale from.
	 *
	 * @since 1.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_on_sale_from( $date = null ) {
		$this->set_date_prop( 'date_on_sale_from', $date );
	}

	/**
	 * Set date on sale to.
	 *
	 * @since 1.0.0
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_on_sale_to( $date = null ) {
		$this->set_date_prop( 'date_on_sale_to', $date );
	}

	/**
	 * Set the course category ids.
	 *
	 * @since 1.0.0
	 *
	 * @param array $category_ids Category ids.
	 */
	public function set_category_ids( $category_ids ) {
		$this->set_prop( 'category_ids', array_unique( array_map( 'intval', $category_ids ) ) );
	}

	/**
	 * Set the course tag ids.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag_ids Tag ids.
	 */
	public function set_tag_ids( $tag_ids ) {
		$this->set_prop( 'tag_ids', array_unique( array_map( 'intval', $tag_ids ) ) );
	}

	/**
	 * Set the course difficulty id.
	 *
	 * @since 1.0.0
	 *
	 * @param array $difficulty_id Difficulty id.
	 */
	public function set_difficulty_id( $difficulty_id ) {
		$this->set_prop( 'difficulty_id', absint( $difficulty_id ) );
	}

	/**
	 * Set the featured image, in other words thumbnail post id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $featured_image Featured image id.
	 */
	public function set_featured_image( $featured_image ) {
		$this->set_prop( 'featured_image', absint( $featured_image ) );
	}

	/**
	 * Set the featured.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $featured Featured.
	 */
	public function set_featured( $featured ) {
		$this->set_prop( 'featured', Utils::string_to_bool( $featured ) );
	}

	/**
	 * Set rating counts. Read only.
	 *
	 * @since 1.0.0
	 * @param array $counts Course rating counts.
	 */
	public function set_rating_counts( $counts ) {
		$this->set_prop( 'rating_counts', array_filter( array_map( 'absint', (array) $counts ) ) );
	}

	/**
	 * Set average rating. Read only.
	 *
	 * @since 1.0.0
	 * @param float $average Course average rating.
	 */
	public function set_average_rating( $average ) {
		$this->set_prop( 'average_rating', masteriyo_format_decimal( $average ) );
	}

	/**
	 * Set review count. Read only.
	 *
	 * @since 1.0.0
	 * @param int $count Course review count.
	 */
	public function set_review_count( $count ) {
		$this->set_prop( 'review_count', absint( $count ) );
	}

	/**
	 * Set the enrollment limit. (maximum number of students allowed )
	 *
	 * @since 1.0.0
	 * @param int $value Enrollment limit.
	 */
	public function set_enrollment_limit( $value ) {
		$this->set_prop( 'enrollment_limit', absint( $value ) );
	}

	/**
	 * Set the course duration (minutes).
	 *
	 * @since 1.0.0
	 * @param int $value Course duration (minutes).
	 */
	public function set_duration( $value ) {
		$this->set_prop( 'duration', absint( $value ) );
	}

	/**
	 * Set the course access mode.
	 *
	 * @since 1.0.0
	 * @param string $value Course access mode (open, need_registration, one_time, recurring ).
	 */
	public function set_access_mode( $value ) {
		$this->set_prop( 'access_mode', $value );
	}

	/**
	 * Set the course billing cycle.
	 *
	 * @since 1.0.0
	 * @param string $value Course billing cycle (1d, 2w, 3m, 4y)
	 */
	public function set_billing_cycle( $value ) {
		$this->set_prop( 'billing_cycle', masteriyo_strtolower( $value ) );
	}

	/**
	 * Set the course curriculum.
	 *
	 * True = Visible to all.
	 * False = Visible to only enrollees.
	 *
	 * @since 1.0.0
	 * @param string $value
	 */
	public function set_show_curriculum( $value ) {
		$this->set_prop( 'show_curriculum', masteriyo_string_to_bool( $value ) );
	}


	/**
	 * Set enable review after course completion.
	 *
	 * True = Review option after completion of course for student.
	 * False = Not visible review option after completion of course for student.
	 *
	 * @since 1.7.1
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function set_review_after_course_completion( $value = 'view' ) {
		return $this->set_prop( 'review_after_course_completion', masteriyo_string_to_bool( $value ) );
	}

	/**
	 * Set the course purchase note.
	 *
	 *
	 * @since 1.0.0
	 * @param string $value
	 */
	public function set_purchase_note( $value ) {
		$this->set_prop( 'purchase_note', $value );
	}

	/**
	 * Set the course highlights.
	 *
	 *
	 * @since 1.0.0
	 * @param array $value
	 */
	public function set_highlights( $value ) {
		$this->set_prop( 'highlights', $value );
	}

	/**
	 * Get main image ID.
	 *
	 * @since  1.0.0
	 * @param  string $value Set main image ID.
	 * @return string
	 */
	public function set_image_id( $value ) {
		return $this->set_featured_image( $value );
	}

	/**
	 * Set the course created using AI or not.
	 *
	 * True = If created using AI.
	 * False = if not created using AI.
	 *
	 * @since 1.6.15
	 *
	 * @param string $value
	 */
	public function set_is_ai_created( $value ) {
		$this->set_prop( 'is_ai_created', masteriyo_string_to_bool( $value ) );
	}

	/**
	 * Set the course is creating status.
	 *
	 * True = If creating.
	 * False = if not creating
	 *
	 * @since 1.6.15
	 *
	 * @param string $value
	 */
	public function set_is_creating( $value ) {
		$this->set_prop( 'is_creating', masteriyo_string_to_bool( $value ) );
	}

	/**
	 * Set course end date.
	 *
	 * @since 1.7.0
	 *
	 * @param string $end_date Course end date.
	 */
	public function set_end_date( $end_date ) {
		$this->set_prop( 'end_date', $end_date );
	}

	/**
	 * Set the enable_course_retake attribute.
	 *
	 * @since 1.7.0
	 *
	 * @param boolean $value
	 */
	public function set_enable_course_retake( $value ) {
		$this->set_prop( 'enable_course_retake', masteriyo_string_to_bool( $value ) );
	}

	/**
	 * Set the disable_course_content attribute.
	 *
	 * @since 1.8.0 [free]
	 *
	 * @param boolean $value
	 */
	public function set_disable_course_content( $value ) {
		$this->set_prop( 'disable_course_content', masteriyo_string_to_bool( $value ) );
	}

	/**
	 * Set the welcome_message_to_first_time_user attribute.
	 *
	 * @since 1.9.3
	 *
	 * @param boolean $value
	 */
	public function set_welcome_message_to_first_time_user( $value ) {
		$this->set_prop( 'welcome_message_to_first_time_user', $value );
	}

	/**
	 * Set the fake enrolled count.
	 *
	 * @since 1.9.3
	 * @param int $value Fake enrolled Count limit.
	 */
	public function set_fake_enrolled_count( $value ) {
		$this->set_prop( 'fake_enrolled_count', absint( $value ) );
	}

	/**
	 * Set course badge.
	 *
	 * @since 1.9.3
	 *
	 * @param string $course_badge Course end date.
	 */
	public function set_course_badge( $course_badge ) {
		$this->set_prop( 'course_badge', $course_badge );
	}

	/**
	 * Set the course's currency. (It will be used as temporary currency for showing to users based on the country.)
	 *
	 * @since 1.11.0
	 *
	 * @param string $currency Price.
	 */
	public function set_currency( $currency ) {
		$this->set_prop( 'currency', $currency );
	}

	/**
	 * Set the exchange rate for the course.
	 *
	 * @since 1.11.0
	 *
	 * @param string $exchange_rate the exchange rate.
	 */
	public function set_exchange_rate( $exchange_rate ) {
		$this->set_prop( 'exchange_rate', $exchange_rate );
	}

	/**
	 * Set the pricing method for the course.
	 *
	 * @since 1.11.0
	 *
	 * @param string $pricing_method The pricing method.
	 */
	public function set_pricing_method( $pricing_method ) {
		$this->set_prop( 'pricing_method', $pricing_method );
	}

	/**
	 * Set course flow.
	 *
	 * @since  1.15.0
	 * @param  string $flow Course flow.
	 * @return string
	 */
	public function set_flow( $flow ) {
		$this->set_prop( 'flow', $flow );
	}

	/**
	 * Set the custom fields value for the course.
	 *
	 * @since 2.11.0
	 *
	 * @param string $custom_fields_value The custom field values.
	 */
	public function set_custom_fields( $custom_fields_value ) {
		$this->set_prop( 'custom_fields', $custom_fields_value );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns whether or not the course is on sale.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function is_on_sale( $context = 'view' ) {
		if ( '' !== (string) $this->get_sale_price( $context ) && $this->get_regular_price( $context ) > $this->get_sale_price( $context ) ) {
			$on_sale = true;

			if ( $this->get_date_on_sale_from( $context ) && $this->get_date_on_sale_from( $context )->getTimestamp() > time() ) {
				$on_sale = false;
			}

			if ( $this->get_date_on_sale_to( $context ) && $this->get_date_on_sale_to( $context )->getTimestamp() < time() ) {
				$on_sale = false;
			}
		} else {
			$on_sale = false;
		}

		/**
		 * Filters boolean: true if given course is on sale.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $bool true if given course is on sale.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return 'view' === $context ? apply_filters( 'masteriyo_course_is_on_sale', $on_sale, $this ) : $on_sale;
	}

	/**
	 * Returns false if the course cannot be bought.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		/**
		 * Filters boolean: true if the course is purchasable.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $bool true if the course is purchasable.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters(
			'masteriyo_is_purchasable',
			( PostStatus::PUBLISH === $this->get_status() || current_user_can( 'edit_post', $this->get_id() ) ) && '' !== $this->get_price(),
			$this
		);
	}

	/**
	 * Check whether the course exists in the database or not.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function exists() {
		return false !== $this->get_status();
	}

	/**
	 * Returns whether or not the course is visible in the catalog.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_visible() {
		$visible = $this->is_visible_core();

		/**
		 * Filters boolean: true if the course is visible on catalog.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $bool true if the course is visible on catalog.
		 * @param integer $course_id Course ID.
		 */
		return apply_filters( 'masteriyo_course_is_visible', $visible, $this->get_id() );
	}

	/**
	 * Returns whether or not the course is visible in the catalog (doesn't trigger filters).
	 *
	 * @return bool
	 */
	protected function is_visible_core() {
		$visible = 'visible' === $this->get_catalog_visibility() || ( is_search() && 'search' === $this->get_catalog_visibility() ) || ( ! is_search() && 'catalog' === $this->get_catalog_visibility() );

		if ( PostStatus::TRASH === $this->get_status() ) {
			$visible = false;
		} elseif ( PostStatus::PUBLISH !== $this->get_status() && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;
		}

		if ( 'yes' === get_option( 'masteriyo_hide_out_of_stock_items' ) ) {
			$visible = false;
		}

		return $visible;
	}

	/**
	 * Get course category list (CourseCategory objects).
	 *
	 * @since 1.0.0
	 *
	 * @return Masteriyo\Models\CourseCategory[]
	 */
	public function get_categories() {
		$cat_ids    = $this->get_category_ids();
		$categories = array();
		$store      = masteriyo( 'course_cat.store' );

		$categories = array_map(
			function( $cat_id ) use ( $store ) {
				$cat_obj = masteriyo( 'course_cat' );
				$cat_obj->set_id( $cat_id );
				$store->read( $cat_obj );
				return $cat_obj;
			},
			$cat_ids
		);

		/**
		 * Filters categories of the course.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Models\CourseCategory[] $categories Categories of the course.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_categories_objects', $categories, $this );
	}

	/**
	 * Get course tag list (CourseTag objects).
	 *
	 * @since 1.0.0
	 *
	 * @return Masteriyo\Models\CourseTag[]
	 */
	public function get_tags() {
		$tag_ids = $this->get_tags_ids();
		$tags    = array();
		$store   = masteriyo( 'course_tag.store' );

		$tags = array_map(
			function( $tag_id ) use ( $store ) {
				$tag_obj = masteriyo( 'course_tag' );
				$tag_obj->set_id( $tag_id );
				$store->read( $tag_obj );
				return $tag_obj;
			},
			$tag_ids
		);

		/**
		 * Filters tags of the course.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Models\CourseTag[] $tags Tags of the course.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_categories_objects', $tags, $this );
	}

	/**
	 * Get course difficulties list (CourseDifficulties objects).
	 *
	 * @since 1.0.0
	 *
	 * @return Masteriyo\Models\CourseDifficulty[]
	 */
	public function get_difficulties() {
		$difficulty_id = $this->get_difficulty_id();
		$store         = masteriyo( 'course_difficulty.store' );

		$difficulty_obj = masteriyo( 'course_difficulty' );
		$difficulty_obj->set_id( $difficulty_id );
		$store->read( $difficulty_obj );

		/**
		 * Filters difficulties of the course.
		 *
		 * @since 1.5.1
		 *
		 * @param Masteriyo\Models\CourseDifficulty[] $difficulties Difficulties of the course.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_difficulties_objects', $difficulty_obj, $this );
	}

	/**
	 * Get add_to_cart now button text for the single page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		$text = __( 'Buy Now', 'learning-management-system' );

		if ( CourseAccessMode::NEED_REGISTRATION === $this->get_access_mode() ) {
			$text = __( 'Need Registration', 'learning-management-system' );
		}

		/**
		 * Filters add to cart button text for a course.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text Add to cart button text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_add_to_cart_text', $text, $this );
	}

	/**
	 * Get start course button text for the single page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function single_course_start_text() {
		/**
		 * Filters start course button text for a course.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text Start course button text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_start_text', __( 'Start Course', 'learning-management-system' ), $this );
	}

	/**
	 * Get retake course button text for the single page.
	 *
	 * @since 1.7.3
	 *
	 * @return string
	 */
	public function retake_course_text() {
		/**
		 * Filters retake course button text for a course.
		 *
		 * @since 1.7.3
		 *
		 * @param string $text Retake course button text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_retake_course_text', __( 'Retake', 'learning-management-system' ), $this );
	}

	/**
	 * Get continue course button text for the single page.
	 *
	 * @since 1.3.11
	 *
	 * @return string
	 */
	public function single_course_continue_text() {
		/**
		 * Filters continue button text for a course.
		 *
		 * @since 1.3.11
		 *
		 * @param string $text Continue button text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_continue_text', __( 'Continue', 'learning-management-system' ), $this );
	}

	/**
	 * Get continue course quiz button text for the single page.
	 *
	 * @since 1.8.0 [free]
	 *
	 * @return string
	 */
	public function single_course_continue_quiz_text() {
		/**
		 * Filters continue quiz button text for a course.
		 *
		 * @since 1.8.0 [free]
		 *
		 * @param string $text Continue button text.
		 * @param Masteriyo\Models|Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_continue_quiz_text', __( 'Continue Quiz', 'learning-management-system' ), $this );
	}



	/**
	 * Get continue course button text for the single page.
	 *
	 * @since 1.3.11
	 *
	 * @return string
	 */
	public function single_course_completed_text() {
		/**
		 * Filters completed button text for a course.
		 *
		 * @since 1.3.11
		 *
		 * @param string $text Completed button text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_completed_text', __( 'Completed', 'learning-management-system' ), $this );
	}

	/**
	 * Get start course URL.
	 *
	 * @since 1.0.0
	 * @since 1.3.11 Added whether to append first lesson  or quiz to the URL or not.
	 *
	 * @param boolean $append_first_lesson_or_quiz Whether to append first lesson or quiz or not.
	 *
	 * @return string
	 */
	public function start_course_url( $append_first_lesson_or_quiz = true ) {
		$lesson_or_quiz = $this->get_first_lesson_or_quiz();
		$learn_page_url = masteriyo_get_page_permalink( 'learn' );
		$url            = trailingslashit( $learn_page_url ) . 'course/' . $this->get_slug();

		if ( '' === get_option( 'permalink_structure' ) ) {
			$url = add_query_arg(
				array(
					'course_name' => $this->get_id(),
				),
				$learn_page_url
			);
		}

		$url .= '#/course/' . $this->get_id();

		if ( $append_first_lesson_or_quiz && $lesson_or_quiz ) {
			$url .= "/{$lesson_or_quiz->get_object_type()}/" . $lesson_or_quiz->get_id();
		}

		/**
		 * Filter start course URL.
		 *
		 * @since 1.0.0
		 * @since 1.4.8 Added `append_first_lesson_or_quiz` parameter whether to append first lesson or quiz or not.
		 *
		 * @param string $url Start course URL.
		 * @param Masteriyo\Models\Course $course Course object.
		 * @param boolean $append_first_lesson_or_quiz Whether to append first lesson or quiz or not.
		 */
		return apply_filters( 'masteriyo_start_course_url', $url, $this, $append_first_lesson_or_quiz );
	}

	/**
	 * Get continue course url.
	 *
	 * @since 1.6.14
	 * @param \Masteriyo\Models\CourseProgress $course_progress Course progress object.
	 * @return string
	 */
	public function continue_course_url( $course_progress ) {
		$data                  = \Masteriyo\Resources\CourseProgressResource::to_array( $course_progress );
		$course_progress_items = array_reduce(
			$data['items'],
			function( $acc, $curr ) {
				if ( isset( $curr['contents'] ) ) {
					$acc = array_merge( $acc, $curr['contents'] );
				}
				return $acc;
			},
			array()
		);

		$first_course_progress_item = current(
			array_filter(
				$course_progress_items,
				function( $course_progress_content ) {
					return ! $course_progress_content['completed'];
				}
			)
		);

		list (
			'item_type' => $item_type,
			'item_id'   => $item_id
		) = $first_course_progress_item;

		$continue_url  = $this->start_course_url( false );
		$continue_url .= "/$item_type/$item_id";

		/**
		 * Filter continue course URL.
		 *
		 * @since 1.6.14
		 * @param string $url Continue course URL.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param \Masteriyo\Models\CourseProgress $course_progress Course progress object.
		 */
		return apply_filters( 'masteriyo_continue_course_url', $continue_url, $this, $course_progress );
	}

	/**
	 * Get add_to_cart url.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->get_permalink();

		if ( $this->is_purchasable() && CourseAccessMode::NEED_REGISTRATION !== $this->get_access_mode() ) {
			$base_url = ( function_exists( 'is_feed' ) && is_feed() ) || ( function_exists( 'is_404' ) && is_404() ) ? $this->get_permalink() : '';

			$url = add_query_arg(
				array(
					'add-to-cart' => $this->get_id(),
				),
				$base_url
			);

			// Skip if guest checkout enable as user will be created on checkout process.
			if ( ! masteriyo_is_guest_checkout_enabled() ) {
				$url = is_user_logged_in() ? $url : add_query_arg( array( 'redirect_to' => $url ), masteriyo_get_page_permalink( 'account', $url ) );
			}
		} else {
			$args = array(
				'redirect_to' => $url,
			);
			$url  = add_query_arg( $args, masteriyo_get_page_permalink( 'account', $url ) );
		}

		/**
		 * Filters add to cart URL for a course.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url Add to cart URL.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_add_to_cart_url', $url, $this );
	}

	/**
	 * Get add to cart text.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = __( 'Read more', 'learning-management-system' );

		if ( $this->is_purchasable() ) {
			$text = __( 'Buy Now', 'learning-management-system' );
		}

		if ( CourseAccessMode::NEED_REGISTRATION === $this->get_access_mode() ) {
			$text = __( 'Register Now', 'learning-management-system' );
		}

		/**
		 * Filters add to cart button text.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text The add to cart button text.
		 */
		$text = apply_filters( 'masteriyo_add_to_cart_text', $text );

		/**
		 * Filters add to cart text for a course.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text The add to cart text.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_add_to_cart_text', $text, $this );
	}


	/**
	 * Get add_to_cart  button text description - used in aria tags.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function add_to_cart_description() {
		/* translators: %s: Course title */
		$text = __( 'Read more about &ldquo;%s&rdquo;', 'learning-management-system' );

		if ( $this->is_purchasable() ) {
			/* translators: %s: Course title */
			$text = __( 'Enroll &ldquo;%s&rdquo; course', 'learning-management-system' );
		}

		/**
		 * Filters add to cart button description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description Add to cart button description - used in aria tags.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_course_add_to_cart_description', sprintf( $text, $this->get_name() ), $this );
	}

	/**
	 * Returns the main product image.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $size (default: 'masteriyo_thumbnail').
	 * @param  array  $attr Image attributes.
	 * @param  bool   $placeholder True to return $placeholder if no image is found, or false to return an empty string.
	 * @return string
	 */
	public function get_image( $size = 'masteriyo_thumbnail', $attr = array(), $placeholder = true ) {
		$image = '';
		if ( $this->get_image_id() ) {
			$image = wp_get_attachment_image( $this->get_image_id(), $size, false, $attr );
		} elseif ( $this->get_parent_id() ) {
			$parent_product = masteriyo_get_course( $this->get_parent_id() );
			if ( $parent_product ) {
				$image = $parent_product->get_image( $size, $attr, $placeholder );
			}
		}

		if ( ! $image && $placeholder ) {
			$image = masteriyo_placeholder_img( $size, $attr );
		}

		/**
		 * Filters main product image html.
		 *
		 * @since 1.0.0
		 *
		 * @param string $image The main product image html.
		 * @param Masteriyo\Models\Course $course Course object.
		 * @param string $size Image size.
		 * @param array $attr Image attributes.
		 * @param boolean $placeholder True to return $placeholder if no image is found, or false to return an empty string.
		 * @param string $image The main product image html.
		 */
		return apply_filters( 'masteriyo_product_get_image', $image, $this, $size, $attr, $placeholder, $image );
	}

	/**
	 * Gets the progress status for a course.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|false $percentage Whether to return the progress as a percentage.
	 * @param Masteriyo\Models\User|int|null| $user       The user to get the progress for. If null, the current user is used.
	 *
	 * @return mixed The progress status for the course.
	 */
	public function get_progress_status( $percentage = false, $user = null ) {
		$progress = $this->repository->get_progress_status( $this, $user );

		$progress['total'] = ( 0 === $progress['total'] ) ? 1 : $progress['total'];
		$progress          = masteriyo_round( ( $progress['completed'] / $progress['total'] ) * 100 );

		if ( $percentage ) {
			$progress = $progress . '%';
		}

		return $progress;
	}

	/**
	 * Retrieves progress data for a given course and user.
	 *
	 * @since 1.11.0
	 *
	 * @param Masteriyo\Models\User|int $user User object.
	 *
	 * @return array The progress data for the course and user.
	 */
	public function get_progress_data( $user = null ) {
		$progress_data = $this->repository->get_progress_data( $this, $user );

		return $progress_data;
	}

	/**
	 * Get available seats.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_available_seats() {
		$total_courses_bought = masteriyo_get_user_courses_count_by_course( $this->get_id() );
		$available_seats      = $this->get_enrollment_limit() - $total_courses_bought;

		return max( $available_seats, 0 );
	}

	/**
	 * Get the first lesson or quiz of the course.
	 *
	 * @since 1.3.11
	 *
	 * @return null|Masteriyo\Models\Lesson|Masteriyo\Models\Quiz
	*/
	public function get_first_lesson_or_quiz() {
		$first_lesson_or_quiz = null;

		$posts = get_posts(
			array(
				'post_status'    => PostStatus::PUBLISH || 'active' || 'upcoming',
				'post_type'      => CourseChildrenPostType::all(),
				'posts_per_page' => -1,
				'meta_key'       => '_course_id',
				'meta_value'     => $this->get_id(),

			)
		);

		$sections = array_filter(
			$posts,
			function( $post ) {
				return CourseChildrenPostType::SECTION === $post->post_type;
			}
		);

		// Sort sections by menu order in ascending order.
		usort(
			$sections,
			function( $a, $b ) {
				if ( $a->menu_order === $b->menu_order ) {
					return 0;
				}

				return $a->menu_order > $b->menu_order ? 1 : -1;
			}
		);

		$lessons_quizzes = array_filter(
			$posts,
			function( $post ) {
				return CourseChildrenPostType::SECTION !== $post->post_type;
			}
		);

		foreach ( $sections as $section ) {
			$section_contents = array_filter(
				$lessons_quizzes,
				function( $lesson_quiz ) use ( $section ) {
					return $lesson_quiz->post_parent === $section->ID;
				}
			);

			// Sort lessons and quizzes by menu order in ascending order.
			usort(
				$section_contents,
				function( $a, $b ) {
					if ( $a->menu_order === $b->menu_order ) {
						return 0;
					}

					return $a->menu_order > $b->menu_order ? 1 : -1;
				}
			);

			if ( empty( $section_contents ) ) {
				continue;
			}

			$post = current( $section_contents );

			try {
				$first_lesson_or_quiz = masteriyo( $post->post_type );
				$first_lesson_or_quiz->set_id( $post->ID );
				$store = masteriyo( $post->post_type . '.store' );
				$store->read( $first_lesson_or_quiz );
				break;
			} catch ( \Exception $e ) {
				$first_lesson_or_quiz = null;
			}
		}

		/**
		 * Filters the first lesson or quiz of a course.
		 *
		 * @since 1.3.11
		 *
		 * @param null|Masteriyo\Models\Lesson|Masteriyo\Models\Quiz $first_lesson_or_quiz The first lesson or quiz of the course.
		 * @param Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_single_course_get_first_lesson_or_quiz', $first_lesson_or_quiz, $this );
	}

	/**
	 * Return true if review is allowed.
	 *
	 * It also checks for global settings as well.
	 *
	 * @since 1.5.37
	 *
	 * @return boolean
	 */
	public function is_review_allowed() {
		$review_allowed = masteriyo_get_setting( 'single_course.display.enable_review' );

		if ( $review_allowed ) {
			$review_allowed = $this->get_reviews_allowed();
		}

		/**
		 * Filters whether course review is enable or not.
		 *
		 * @since 1.5.37
		 *
		 * @param bool $review_allowed
		 * @param \Masteriyo\Models\Course $course
		 */
		return apply_filters( 'masteriyo_is_course_review_allowed', $review_allowed, $this );
	}
}
