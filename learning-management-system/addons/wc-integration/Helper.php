<?php
/**
 * WC Integration helper functions.
 *
 * @since 1.8.1
 * @package Masteriyo\Addons\WcIntegration
 */

namespace Masteriyo\Addons\WcIntegration;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\PostStatus;

class Helper {
	/**
	 * Return if WooCommerce is active.
	 *
	 * @since 1.8.1
	 *
	 * @return boolean
	 */
	public static function is_wc_active() {
		return in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Return if WooCommerce Subscriptions is active.
	 *
	 * @since 1.8.1
	 * @return boolean
	 */
	public static function is_wc_subscriptions_active() {
		return in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', get_option( 'active_plugins', array() ), true );
	}


	/**
	 * Checks if a given course is currently in the WooCommerce cart.
	 *
	 * @since 1.11.3
	 *
	 * @param int $course_id The ID of the course to check.
	 * @return bool|null True if the course is in the cart, false if not, or null if the course is not associated with a WooCommerce product.
	 */
	public static function is_course_added_to_cart( $course_id ) {
		$product_id = self::is_course_wc_product( $course_id );

		if ( ! $product_id || PostStatus::PUBLISH !== get_post_status( $product_id ) ) {
			return null;
		}

		if ( empty( \WC()->cart ) ) {
			return false;
		}

		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['product_id'] ) && absint( $product_id ) === $cart_item['product_id'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a given course ID is associated with a WooCommerce product.
	 *
	 * @since 1.11.3
	 *
	 * @param int $course_id The ID of the course to check.
	 * @return int|false The ID of the associated WooCommerce product, or false if no product is found.
	 */
	public static function is_course_wc_product( $course_id ) {
		if ( ! $course_id || ! function_exists( 'wc_get_product' ) ) {
			return false;
		}

		$product_id = absint( get_post_meta( $course_id, '_wc_product_id', true ) );
		$product    = \wc_get_product( $product_id );

		if ( $product ) {
			return $product_id;
		}

		return false;
	}

	/**
	 * Checks if the "Add to Cart" functionality is enabled.
	 *
	 * @since 1.11.3
	 *
	 * @return bool True if the "Add to Cart" functionality is enabled, false otherwise.
	 */
	public static function is_add_to_cart_enable() {
		$setting = new Setting();
		return masteriyo_string_to_bool( $setting->get( 'add_to_cart.enable' ) );
	}

	/**
	 * Gets the label for the "Add to Cart" button before the course adding to the cart.
	 *
	 * @since 1.11.3
	 *
	 * @return string The label for the "Add to Cart" button before the course adding to the cart.
	 */
	public static function get_enroll_btn_label_before() {
		$setting = new Setting();

		$label = $setting->get( 'add_to_cart.enroll_btn_label_before' );

		return $label ? $label : __( 'Add to Cart', 'learning-management-system' );
	}

	/**
	 * Checks whether at least one WooCommerce order item is linked to a Masteriyo course or bundle.
	 *
	 * Result is cached in a transient for 1 hour to avoid a DB hit on every page load.
	 * The transient is cleared when the first qualifying order item is saved so the notice
	 * appears immediately after the first WC-based enrolment.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public static function has_wc_orders_for_masteriyo_courses() {
		$transient_key = 'masteriyo_wc_orders_exist';
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		global $wpdb;

		// Order item meta is stored in woocommerce_order_itemmeta regardless of HPOS mode.
		$exists = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1
				 FROM {$wpdb->prefix}woocommerce_order_itemmeta
				 WHERE meta_key = %s
				 LIMIT 1",
				'_masteriyo_course_id'
			)
		);

		set_transient( $transient_key, $exists ? '1' : '0', HOUR_IN_SECONDS );

		return $exists;
	}

	/**
	 * Clears the cached WC-orders-exist flag so the orders-page notice appears immediately
	 * after the first Masteriyo course item is added to a WooCommerce order.
	 *
	 * @since x.x.x
	 */
	public static function clear_wc_orders_cache() {
		delete_transient( 'masteriyo_wc_orders_exist' );
	}

	/**
	 * Gets the label for the "Add to Cart" button after the course adding to the cart.
	 *
	 * @since 1.11.3
	 *
	 * @return string The label for the "Add to Cart" button after the course adding to the cart.
	 */
	public static function get_enroll_btn_label_after() {
		$setting = new Setting();

		$label = $setting->get( 'add_to_cart.enroll_btn_label_after' );

		return $label ? $label : __( 'View Cart', 'learning-management-system' );
	}
}
