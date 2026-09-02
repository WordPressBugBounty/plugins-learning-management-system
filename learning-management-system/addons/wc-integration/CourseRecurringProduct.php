<?php
/**
 * Course recurring product type for WooCommerce.
 *
 * @since 2.6.11
 * @package Masteriyo\Addons\WcIntegration
 */

namespace Masteriyo\Addons\WcIntegration;

defined( 'ABSPATH' ) || exit;


class CourseRecurringProduct extends \WC_Product_Subscription {

	/**
	 * Get internal type.
	 *
	 * @since 2.6.11
	 *
	 * @return string
	 */
	public function get_type() {
		return 'mto_course_recurring';
	}

	/**
	 * Check if a product is sold individually (no quantities).
	 *
	 * @since 2.6.11
	 *
	 * @return bool
	 */
	public function is_sold_individually() {
		return apply_filters( 'woocommerce_is_sold_individually', true, $this );
	}

	/**
	 * Return if product manage stock.
	 *
	 * @since  2.7.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return boolean
	 */
	public function get_manage_stock( $context = 'view' ) {
		return false;
	}

	/**
	 * Get virtual.
	 *
	 * @since  2.7.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_virtual( $context = 'view' ) {
		return true;
	}

	/**
	 * Get downloadable.
	 *
	 * @since  2.7.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_downloadable( $context = 'view' ) {
		return true;
	}
}
