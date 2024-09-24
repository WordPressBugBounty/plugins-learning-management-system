<?php
/**
 * Group model.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Addons\GroupCourses
 */

namespace Masteriyo\Addons\GroupCourses\Models;

use Masteriyo\Addons\GroupCourses\Repository\GroupRepository;
use Masteriyo\Database\Model;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Group model (post type).
 *
 * @since 1.9.0
 */
class Group extends Model {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $object_type = 'group';

	/**
	 * Post type.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $post_type = PostType::GROUP;

	/**
	 * Cache group.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $cache_group = 'groups';

	/**
	 * Stores group data.
	 *
	 * @since 1.9.0
	 *
	 * @var array
	 */
	protected $data = array(
		'title'         => '',
		'description'   => '',
		'author_id'     => 0,
		'emails'        => array(),
		'status'        => PostStatus::DRAFT,
		'menu_order'    => 0,
		'date_created'  => null,
		'date_modified' => null,
	);

	/**
		 * Constructor.
		 *
		 * @since 1.9.0
		 *
		 * @param GroupRepository|null $group_repository Group Repository.
		 */
	public function __construct( GroupRepository $group_repository ) {
		$this->repository = $group_repository;
	}


	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the object type.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get group title.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Get group description.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Get group author id.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Get group status.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Returns group menu order.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int Course group menu order.
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Get group created date.
	 *
	 * @since  1.9.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get group modified date.
	 *
	 * @since  1.9.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get group emails.
	 *
	 * @since 1.9.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_emails( $context = 'view' ) {
		return $this->get_prop( 'emails', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set group title.
	 *
	 * @since 1.9.0
	 *
	 * @param string $title group title.
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}

	/**
	 * Set group description.
	 *
	 * @since 1.9.0
	 *
	 * @param string $description Group description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set the group's author id.
	 *
	 * @since 1.9.0
	 *
	 * @param int $author_id author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set group status.
	 *
	 * @since 1.9.0
	 *
	 * @param string $status Group status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set the group menu order.
	 *
	 * @since 1.9.0
	 *
	 * @param string $menu_order Menu order id.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Set group created date.
	 *
	 * @since 1.9.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set group modified date.
	 *
	 * @since 1.9.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set emails.
	 *
	 * @since 1.9.0
	 *
	 * @param $emails Array of email addresses.
	 */
	public function set_emails( $emails ) {
		$this->set_prop( 'emails', $emails );
	}
}
