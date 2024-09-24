<?php
/**
 * Price zone status enum class.
 *
 * @since 1.11.0
 * @package Masteriyo\Addons\MultipleCurrency
 */

namespace Masteriyo\Addons\MultipleCurrency\Enums;

use Masteriyo\Enums\PostStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Price zone status enum class.
 *
 * @since 1.11.0
 */
class PriceZoneStatus extends PostStatus {

	/**
	 * Price zone active status.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	const ACTIVE = 'active';

	/**
	 * Price zone inactive status.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	const INACTIVE = 'inactive';

	/**
	 * Return all the price zone statuses.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters price zone status list.
			 *
			 * @since 1.11.0
			 *
			 * @param string[] $statuses Coupon status list.
			 */
			apply_filters(
				'masteriyo_price_zone_statuses',
				array_merge(
					parent::all(),
					array(
						self::ACTIVE,
						self::INACTIVE,
					)
				)
			)
		);
	}

	/**
	 * List pricing zone status primarily used for registering status.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public static function list() {
		$price_zone_statuses = array(
			'active'   => array(
				'label'                     => _x( 'Active', 'Price zone status', 'learning-management-system' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: %s: number of price zones
				'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'learning-management-system' ),
			),
			'inactive' => array(
				'label'                     => _x( 'Inactive', 'Price zone status', 'learning-management-system' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of price zones */
				'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'learning-management-system' ),
			),
		);

		/**
		 * Filters pricing zone statuses.
		 *
		 * @since 1.11.0
		 *
		 * @param array $price_zone_statuses The pricing zone statuses and its parameters.
		 */
		return apply_filters( 'masteriyo_price_zone_statuses', $price_zone_statuses );
	}
}
