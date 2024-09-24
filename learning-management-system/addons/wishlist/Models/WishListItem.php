<?php
/**
 * Wishlist item model
 *
 * @since 1.12.2
 *
 * @package Masteriyo\Addons\WishList\Models;
 */

namespace Masteriyo\Addons\WishList\Models;

use Masteriyo\Addons\WishList\Repository\WishListItemRepository;
use Masteriyo\Database\Model;

defined( 'ABSPATH' ) || exit;

class WishListItem extends Model {

	/**
	 * The model's object type.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $object_type = 'wishlist_item';

	/**
	 * Post type.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $post_type = 'mto-wishlist-item';

	/**
	 * Cache group.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $cache_group = 'wishlist_item';

	/**
	 * Stores model data.
	 *
	 * @since 1.12.2
	 *
	 * @var array
	 */
	protected $data = array(
		'author_id'           => 0,
		'course_id'           => 0,
		'course_title'        => '',
		'course_category_ids' => array(),
		'course_difficulty'   => null,
		'course_price'        => '',
		'date_created'        => null,
		'status'              => false,
		'type'                => 'mto-wishlist-item',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Repository\WishListItemRepository $wishlist_item_repository Wishlist item repository.
	 */
	public function __construct( WishListItemRepository $wishlist_item_repository ) {
		$this->repository = $wishlist_item_repository;
	}

	/**
	 * Return object type.
	 *
	 * @since 1.12.2
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get course title.
	 *
	 * @since  1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_course_title( $context = 'view' ) {
		/**
		 * Filters course title from a wishlist item.
		 *
		 * @since 1.12.2
		 *
		 * @param string $title Course title.
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item WishListItem object.
		 */
		return apply_filters( 'masteriyo_wishlist_item_course_title', $this->get_prop( 'course_title', $context ), $this );
	}

	/**
	 * Get type.
	 *
	 * @since  1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Returns course id.
	 *
	 * @since  1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_course_id( $context = 'view' ) {
		return $this->get_prop( 'course_id', $context );
	}

	/**
	 * Returns the course's category id.
	 *
	 * @since 1.12.2
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer[]
	 */
	public function get_course_category_ids( $context = 'view' ) {
		return $this->get_prop( 'course_category_ids', $context );
	}

	/**
	 * Returns course difficulty id.
	 *
	 * @since 1.12.2
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array|null
	 */
	public function get_course_difficulty( $context = 'view' ) {
		return $this->get_prop( 'course_difficulty', $context );
	}

	/**
	 * Returns course price.
	 *
	 * @since 1.12.2
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_course_price( $context = 'view' ) {
		return $this->get_prop( 'course_price', $context );
	}

	/**
	 * Get author ID.
	 *
	 * @since 1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Get status.
	 *
	 * @since 1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get created date.
	 *
	 * @since  1.12.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set course title.
	 *
	 * @since 1.12.2
	 *
	 * @param string $course_title The course title.
	 */
	public function set_course_title( $course_title ) {
		$this->set_prop( 'course_title', $course_title );
	}

	/**
	 * Set type.
	 *
	 * @since 1.12.2
	 *
	 * @param string $type Type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set the course ID.
	 *
	 * @since 1.12.2
	 *
	 * @param int $value Course ID.
	 */
	public function set_course_id( $value ) {
		$this->set_prop( 'course_id', absint( $value ) );
	}

	/**
	 * Set the course category IDs.
	 *
	 * @since 1.12.2
	 *
	 * @param integer[] $category_ids Course category IDs.
	 */
	public function set_course_category_ids( $category_ids ) {
		$this->set_prop( 'course_category_ids', array_unique( array_map( 'intval', $category_ids ) ) );
	}

	/**
	 * Set the course difficulty.
	 *
	 * @since 1.12.2
	 *
	 * @param array|null $difficulty
	 */
	public function set_course_difficulty( $difficulty ) {
		$this->set_prop( 'course_difficulty', $difficulty );
	}

	/**
	 * Set the course price.
	 *
	 * @since 1.12.2
	 *
	 * @param string $course_price price.
	 */
	public function set_course_price( $course_price ) {
		$this->set_prop( 'course_price', $course_price );
	}

	/**
	 * Set the author ID.
	 *
	 * @since 1.12.2
	 *
	 * @param int $value author ID.
	 */
	public function set_author_id( $value ) {
		$this->set_prop( 'author_id', absint( $value ) );
	}

	/**
	 * Set the status.
	 *
	 * @since 1.12.2
	 *
	 * @param string $status The status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set created date.
	 *
	 * @since 1.12.2
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}
}
