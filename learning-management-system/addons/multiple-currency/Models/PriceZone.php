<?php
/**
 * PriceZone model.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency
 */

namespace Masteriyo\Addons\MultipleCurrency\Models;

use Masteriyo\Addons\MultipleCurrency\Enums\PriceZoneStatus;
use Masteriyo\Addons\MultipleCurrency\Repository\PriceZoneRepository;
use Masteriyo\Database\Model;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Price zone model (post type).
 *
 * @since 1.11.0
 */
class PriceZone extends Model {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $object_type = 'price-zone';

	/**
	 * Post type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $post_type = PostType::PRICE_ZONE;

	/**
	 * Cache pricing zone.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $cache_pricing_zone = 'price-zones';

	/**
	 * Stores pricing zone data.
	 *
	 * @since 1.11.0
	 *
	 * @var array
	 */
	protected $data = array(
		'title'         => '',
		'countries'     => array(),
		'exchange_rate' => 0,
		'currency'      => '',
		'status'        => PriceZoneStatus::ACTIVE,
		'author_id'     => 0,
		'menu_order'    => 0,
		'date_created'  => null,
		'date_modified' => null,
	);

	/**
		 * Constructor.
		 *
		 * @since 1.11.0
		 *
		 * @param PriceZoneRepository|null $pricing_zone_repository Prize zone Repository.
		 */
	public function __construct( PriceZoneRepository $pricing_zone_repository ) {
		$this->repository = $pricing_zone_repository;
	}


	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the object type.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.11.0
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
	 * Get price zone title.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Get price zone countries.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_countries( $context = 'view' ) {
		return $this->get_prop( 'countries', $context );
	}

	/**
	 * Get price zone exchange rate.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float The price zone exchange rate.
	 */
	public function get_exchange_rate( $context = 'view' ) {
		return $this->get_prop( 'exchange_rate', $context );
	}

	/**
	 * Get price zone currency.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	/**
	 * Get price zone status.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get price zone author id.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Returns price zone menu order.
	 *
	 * @since 1.11.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int Course price zone menu order.
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Get price zone created date.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get price zone modified date.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set price zone title.
	 *
	 * @since 1.11.0
	 *
	 * @param string $title price zone title.
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}


	/**
	 * Set the countries for the price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param array $countries The countries to set for the price zone.
	 */
	public function set_countries( $countries ) {
		$this->set_prop( 'countries', $countries );
	}

	/**
	 * Set the exchange rate for the price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param float $exchange_rate The exchange rate to set for the price zone.
	 */
	public function set_exchange_rate( $exchange_rate ) {
		$this->set_prop( 'exchange_rate', $exchange_rate );
	}

	/**
	 * Set the currency for the price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param string $currency The currency to set for the price zone.
	 */
	public function set_currency( $currency ) {
		$this->set_prop( 'currency', $currency );
	}

	/**
	 * Set price zone status.
	 *
	 * @since 1.11.0
	 *
	 * @param string $status Prize zone status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set the price zone's author id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $author_id author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set the price zone menu order.
	 *
	 * @since 1.11.0
	 *
	 * @param string $menu_order Menu order id.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Set price zone created date.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set price zone modified date.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}
}
