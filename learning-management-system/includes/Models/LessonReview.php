<?php
/**
 * Comment model.
 *
 * @since 1.14.0
 *
 * @package Masteriyo\Models;
 */

namespace Masteriyo\Models;

use Masteriyo\Database\Model;
use Masteriyo\Repository\LessonReviewRepository;
use Masteriyo\Helper\Utils;
use Masteriyo\Cache\CacheInterface;

defined( 'ABSPATH' ) || exit;

/**
 * LessonReview Model.
 *
 * @since 1.14.0
 */
class LessonReview extends Model {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $object_type = 'lesson_review';

	/**
	 * Cache group.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $cache_group = 'mto_lesson_reviews';


	/**
	 * Stores lesson review data.
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	protected $data = array(
		'lesson_id'    => 0,
		'author_name'  => '',
		'author_email' => '',
		'author_url'   => '',
		'ip_address'   => '',
		'date_created' => null,
		'content'      => '',
		'status'       => 'approve',
		'agent'        => '',
		'type'         => 'mto_lesson_review',
		'parent'       => 0,
		'author_id'    => 0,
		'is_new'       => false,
	);

	/**
	 * Get the lesson review if ID.
	 *
	 * @since 1.14.0
	 *
	 * @param LessonReviewRepository $lesson_review_repository Course Review Repository.
	 */
	public function __construct( LessonReviewRepository $lesson_review_repository ) {
		$this->repository = $lesson_review_repository;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters and Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return array of replies with status along with counts.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function replies_count() {
		return masteriyo_count_comment_replies( "mto_{$this->object_type}", $this->get_id(), $this->get_lesson_id() );
	}

	/**
	 * Return  total replies.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function total_replies_count() {
		$replies = masteriyo_count_comment_replies( "mto_{$this->object_type}", $this->get_id(), $this->get_lesson_id() );

		return array_sum( $replies );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get lesson_id.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_lesson_id( $context = 'view' ) {
		return $this->get_prop( 'lesson_id', $context );
	}

	/**
	 * Get author_name.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_name( $context = 'view' ) {
		return $this->get_prop( 'author_name', $context );
	}

	/**
	 * Get author_email.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_email( $context = 'view' ) {
		return $this->get_prop( 'author_email', $context );
	}

	/**
	 * Get author_url.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_url( $context = 'view' ) {
		return $this->get_prop( 'author_url', $context );
	}

	/**
	 * Get ip_address.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_ip_address( $context = 'view' ) {
		return $this->get_prop( 'ip_address', $context );
	}

	/**
	 * Get date_created.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}


	/**
	 * Get content.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_content( $context = 'view' ) {
		return $this->get_prop( 'content', $context );
	}

	/**
	 * Get status.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get agent.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_agent( $context = 'view' ) {
		return $this->get_prop( 'agent', $context );
	}

	/**
	 * Get type.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get parent.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_parent( $context = 'view' ) {
		return $this->get_prop( 'parent', $context );
	}

	/**
	 * Check if this is a reply.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public function is_reply() {
		return absint( $this->get_parent( 'edit' ) ) > 0;
	}

	/**
	 * Get author_id.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Get author.
	 *
	 * @since 1.14.0
	 *
	 * @return User
	 */
	public function get_author() {
		return masteriyo_get_user( $this->get_author_id() );
	}

	/**
	 * Get is_new status.
	 *
	 * @since 1.14.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_is_new( $context = 'view' ) {
		return $this->get_prop( 'is_new', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set lesson_id.
	 *
	 * @since 1.14.0
	 *
	 * @param int $lesson_id lesson_id.
	 */
	public function set_lesson_id( $lesson_id ) {
		$this->set_prop( 'lesson_id', absint( $lesson_id ) );
	}

	/**
	 * Set author_name.
	 *
	 * @since 1.14.0
	 *
	 * @param string $author_name Comment author name.
	 */
	public function set_author_name( $author_name ) {
		$this->set_prop( 'author_name', $author_name );
	}

	/**
	 * Set author_email.
	 *
	 * @since 1.14.0
	 *
	 * @param string $author_email Comment author email.
	 */
	public function set_author_email( $author_email ) {
		$this->set_prop( 'author_email', $author_email );
	}

	/**
	 * Set author_url.
	 *
	 * @since 1.14.0
	 *
	 * @param string $author_url Comment author url.
	 */
	public function set_author_url( $author_url ) {
		$this->set_prop( 'author_url', $author_url );
	}

	/**
	 * Set ip_address.
	 *
	 * @since 1.14.0
	 *
	 * @param string $ip_address Comment author IP.
	 */
	public function set_ip_address( $ip_address ) {
		$this->set_prop( 'ip_address', $ip_address );
	}

	/**
	 * Set date_created.
	 *
	 * @since 1.14.0
	 *
	 * @param string $date_created Comment date_created.
	 */
	public function set_date_created( $date_created ) {
		$this->set_date_prop( 'date_created', $date_created );
	}

	/**
	 * Set content.
	 *
	 * @since 1.14.0
	 *
	 * @param string $content Comment content.
	 */
	public function set_content( $content ) {
		$this->set_prop( 'content', $content );
	}

	/**
	 * Set status.
	 *
	 * @since 1.14.0
	 *
	 * @param string $status Comment status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set agent.
	 *
	 * @since 1.14.0
	 *
	 * @param string $agent Comment Agent.
	 */
	public function set_agent( $agent ) {
		$this->set_prop( 'agent', $agent );
	}

	/**
	 * Set type.
	 *
	 * @since 1.14.0
	 *
	 * @param string $type Comment Type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set parent.
	 *
	 * @since 1.14.0
	 *
	 * @param int $parent Comment Parent.
	 */
	public function set_parent( $parent ) {
		$this->set_prop( 'parent', absint( $parent ) );
	}

	/**
	 * Set author_id.
	 *
	 * @since 1.14.0
	 *
	 * @param int $author_id User ID.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set is_new status.
	 *
	 * @since 1.14.0
	 *
	 * @param string $is_new is_new status.
	 */
	public function set_is_new( $is_new ) {
		$this->set_prop( 'is_new', $is_new );
	}

}
