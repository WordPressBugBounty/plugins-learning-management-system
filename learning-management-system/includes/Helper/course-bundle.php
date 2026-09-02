<?php
/**
 * Course bundle seams.
 *
 * Core has no course bundles — the post type, the model, the order item and the
 * archive page are the course-bundle addon's, and that addon is pro. But core
 * ships the cart, the checkout, the order abstraction, the tax calculator and the
 * order REST resource, and each of those has to ask a bundle question it cannot
 * answer itself.
 *
 * These functions are that question. Core asks; pro answers by hooking the filter
 * from `Masteriyo\Addons\CourseBundle\CourseBundleAddon::init_hooks()`. With the
 * addon absent — or present and deactivated — every one of them returns the
 * no-bundles answer, which is the correct answer for the free product.
 *
 * The names deliberately avoid the addon's own. `masteriyo_get_course_bundle()`,
 * `masteriyo_is_archive_course_bundle_page()` and friends are declared by the
 * addon behind `function_exists` guards, so a core declaration of any of those
 * names would silently win and the real implementation would never load. Slice 08
 * recorded the mirror of this trap for the subscription helpers.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Whether course bundles are available at all.
 *
 * The feature-availability question, as distinct from the questions about a
 * particular product below. Integrations ask it before registering a bundle
 * product type, a bundle settings tab or a bundle admin panel — there is no
 * bundle in hand at that point, only the question of whether bundles exist.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_bundles_enabled() {
	/**
	 * Filters whether course bundles are available.
	 *
	 * @param bool $enabled Whether course bundles are available.
	 */
	return (bool) apply_filters( 'masteriyo_bundles_enabled', false );
}

/**
 * Get the bundle product for an ID, post or model.
 *
 * @param int|\WP_Post|object $bundle Bundle ID, post or model.
 *
 * @return object|null The bundle model, or null when there is none.
 */
function masteriyo_get_bundle_product( $bundle ) {
	/**
	 * Filters the bundle product resolved from an ID, post or model.
	 *
	 * @param object|null         $product The bundle model, or null.
	 * @param int|\WP_Post|object $bundle  Bundle ID, post or model.
	 */
	return apply_filters( 'masteriyo_bundle_product', null, $bundle );
}

/**
 * Whether a product is a bundle rather than a single course.
 *
 * A bundle model extends `\Masteriyo\Models\Course`, so an `is_a( $x, Course )`
 * test already passes for one. This is the *distinguishing* question — use it
 * only where the two have to be told apart.
 *
 * @param mixed $product Product model, or anything at all.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_bundle_product( $product ) {
	/**
	 * Filters whether a product is a bundle rather than a single course.
	 *
	 * @param bool  $is_bundle Whether the product is a bundle.
	 * @param mixed $product   Product model, or anything at all.
	 */
	return (bool) apply_filters( 'masteriyo_is_bundle_product', false, $product );
}

/**
 * Whether an order item is a bundle line rather than a course line.
 *
 * @param mixed $order_item Order item model, or anything at all.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_bundle_order_item( $order_item ) {
	/**
	 * Filters whether an order item is a bundle line.
	 *
	 * @param bool  $is_bundle_item Whether the order item is a bundle line.
	 * @param mixed $order_item     Order item model, or anything at all.
	 */
	return (bool) apply_filters( 'masteriyo_is_bundle_order_item', false, $order_item );
}

/**
 * Whether the current request is the bundles archive.
 *
 * @param bool $check_shortcode Also treat a page carrying the bundles shortcode as the archive.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_bundles_archive_page( $check_shortcode = false ) {
	/**
	 * Filters whether the current request is the bundles archive.
	 *
	 * @param bool $is_archive      Whether this is the bundles archive.
	 * @param bool $check_shortcode Whether a page carrying the bundles shortcode counts.
	 */
	return (bool) apply_filters( 'masteriyo_is_bundles_archive_page', false, $check_shortcode );
}

/**
 * Whether the current request is a single bundle page.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_bundle_page() {
	/**
	 * Filters whether the current request is a single bundle page.
	 *
	 * @param bool $is_bundle_page Whether this is a single bundle page.
	 */
	return (bool) apply_filters( 'masteriyo_is_bundle_page', false );
}
