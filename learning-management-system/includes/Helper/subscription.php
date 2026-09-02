<?php
/**
 * Subscription seams.
 *
 * Core has no subscriptions — the models, statuses and queries are pro's. But core
 * ships the checkout, the PayPal gateway and the admin menu, and each of those has
 * to ask a subscription question it cannot answer itself.
 *
 * These functions are that question. Core asks; pro answers by hooking the filter
 * from `Masteriyo\Pro\Providers\SubscriptionServiceProvider::boot()`. With pro
 * absent, every one of them returns the no-subscriptions answer, which is the
 * correct answer for the free product.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Whether an order contains recurring (subscription) courses.
 *
 * @param \Masteriyo\Models\Order\Order|\WP_Post|int $order Order object, post or ID.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_order_has_recurring_courses( $order ) {
	/**
	 * Filters whether an order contains recurring (subscription) courses.
	 *
	 * Pro hooks this and resolves it against the order's course access modes.
	 *
	 * @param bool                                      $has_recurring_courses Whether the order has recurring courses.
	 * @param \Masteriyo\Models\Order\Order|\WP_Post|int $order                 Order object, post or ID.
	 */
	return (bool) apply_filters( 'masteriyo_order_has_recurring_courses', false, $order );
}

/**
 * Get the subscription attached to an order.
 *
 * @param \Masteriyo\Models\Order\Order|\WP_Post|int $order Order object, post or ID.
 *
 * @return object|null The subscription model, or null when there is none.
 */
function masteriyo_get_order_subscription( $order ) {
	/**
	 * Filters the subscription attached to an order.
	 *
	 * @param object|null                               $subscription The subscription model, or null.
	 * @param \Masteriyo\Models\Order\Order|\WP_Post|int $order        Order object, post or ID.
	 */
	return apply_filters( 'masteriyo_order_subscription', null, $order );
}

/**
 * Get the subscription-capable product backing an order item.
 *
 * Defaults to the course an order item references. Addons that introduce other
 * subscription-capable products (course bundles, for instance) resolve through
 * the same seam on pro's side.
 *
 * @param \Masteriyo\Models\Order\OrderItem $order_item Order item.
 *
 * @return object|null The product model, or null when the item has none.
 */
function masteriyo_get_order_item_subscription_product( $order_item ) {
	/**
	 * Filters the subscription-capable product resolved from an order item.
	 *
	 * @param object|null                       $product    The product model, or null.
	 * @param \Masteriyo\Models\Order\OrderItem $order_item Order item.
	 */
	return apply_filters( 'masteriyo_order_item_subscription_product', null, $order_item );
}

/**
 * Get the count of subscriptions awaiting moderation.
 *
 * Drives the admin menu badge. Zero without pro, which is also what the badge
 * should show when there are no subscriptions at all.
 *
 * @return int
 */
function masteriyo_get_moderated_subscriptions_count() {
	/**
	 * Filters the count of subscriptions awaiting moderation.
	 *
	 * @param int $count Number of subscriptions awaiting moderation.
	 */
	return (int) apply_filters( 'masteriyo_moderated_subscriptions_count', 0 );
}
