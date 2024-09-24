<?php
/**
 * Certificate model.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\Models;

use Masteriyo\Database\Model;
use Masteriyo\Addons\Certificate\Repository\CertificateRepository;

defined( 'ABSPATH' ) || exit;

class Certificate extends Model {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	protected $object_type = 'certificate';

	/**
	 * Post type.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-certificate';

	/**
	 * Cache group.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	protected $cache_group = 'certificates';

	/**
	 * Stores certificate data.
	 *
	 * @since 1.13.0
	 *
	 * @var array
	 */
	protected $data = array(
		'name'          => '',
		'slug'          => '',
		'date_created'  => null,
		'date_modified' => null,
		'status'        => 'draft',
		'html_content'  => '',
		'parent_id'     => 0,
		'author_id'     => 0,
	);

	/**
	 * Get the certificate if ID.
	 *
	 * @since 1.13.0
	 *
	 * @param CertificateRepository $certificate_repository Certificate Repository,
	 */
	public function __construct( CertificateRepository $certificate_repository ) {
		$this->repository = $certificate_repository;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the product's title. For products this is the product name.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'masteriyo_certificate_title', $this->get_name(), $this );
	}

	/**
	 * Product permalink.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Returns the children IDs if applicable. Overridden by child classes.
	 *
	 * @since 1.13.0
	 *
	 * @return array Array of IDs.
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Get the object type.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get post preview link.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_post_preview_link() {
		$preview_link = add_query_arg( 'preview_id', $this->get_id(), get_preview_post_link( $this->get_id() ) );

		/**
		 * Certificate post preview link.
		 *
		 * @since 1.13.0
		 */
		return apply_filters( 'masteriyo_certificate_post_preview_link', $preview_link, $this );
	}

	/**
	 * Get edit post link.
	 *
	 * @since 1.13.0
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
		 * Certificate edit post link.
		 *
		 * @since 1.13.0
		 * @param string $edit_post_link Edit post link.
		 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate Certificate object.
		 * @param string $context Context.
		 */
		return apply_filters( 'masteriyo_certificate_edit_post_link', $edit_post_link, $this, $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get certificate name.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get certificate slug.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get certificate created date.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get certificate modified date.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get certificate status.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get certificate html_content.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_html_content( $context = 'view' ) {
		return $this->get_prop( 'html_content', $context );
	}

	/**
	 * Returns certificate parent id.
	 *
	 * @since 1.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int Certificate parent id.
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Returns the certificate's author id.
	 *
	 * @since  1.13.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set certificate name.
	 *
	 * @since 1.13.0
	 *
	 * @param string $name certificate name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set certificate slug.
	 *
	 * @since 1.13.0
	 *
	 * @param string $slug certificate slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set certificate created date.
	 *
	 * @since 1.13.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set certificate modified date.
	 *
	 * @since 1.13.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set certificate status.
	 *
	 * @since 1.13.0
	 *
	 * @param string $status certificate status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set certificate html_content.
	 *
	 * @since 1.13.0
	 *
	 * @param string $html_content Certificate html_content.
	 */
	public function set_html_content( $html_content ) {
		$this->set_prop( 'html_content', $html_content );
	}

	/**
	 * Set the certificate parent id.
	 *
	 * @since 1.13.0
	 *
	 * @param string $parent Parent id.
	 */
	public function set_parent_id( $parent ) {
		$this->set_prop( 'parent_id', absint( $parent ) );
	}

	/**
	 * Set the certificate's author id.
	 *
	 * @since 1.13.0
	 *
	 * @param int $author_id author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}
}
