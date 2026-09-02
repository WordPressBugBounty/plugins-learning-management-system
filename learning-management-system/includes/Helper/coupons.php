<?php
/**
 * Coupon seams.
 *
 * Core has no coupons — the post type, the model and the discount calculation are
 * the coupons addon's, and that addon is pro. Shared code that has to tell a coupon
 * from something else asks here rather than naming the model.
 *
 * Core asks; pro answers by hooking the filter from
 * `Masteriyo\Addons\Coupons\CouponsAddon::init_hooks()`. With the addon absent — or
 * present and deactivated — the answer is the no-coupons one, which is the correct
 * answer for the free product.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Whether a value is a coupon model.
 *
 * @param mixed $coupon Coupon model, or anything at all.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_coupon( $coupon ) {
	/**
	 * Filters whether a value is a coupon model.
	 *
	 * @param bool  $is_coupon Whether the value is a coupon.
	 * @param mixed $coupon    Coupon model, or anything at all.
	 */
	return (bool) apply_filters( 'masteriyo_is_coupon', false, $coupon );
}
