<?php
/**
 * @since 2.1.0
 * @class OrderSummaryShortcode
 */

namespace Masteriyo\Shortcodes;

use Masteriyo\Abstracts\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Order Summary Shortcode.
 *
 * @since 2.1.0
 */
class OrderSummaryShortcode extends Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected $tag = 'masteriyo_order_summary';

	/**
	 * Get shortcode content.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_content() {

		global $wp;

		$order_id = 0;
		$order    = false;

		if ( isset( $wp->query_vars['order-received'] ) ) {
			$order_id = absint( $wp->query_vars['order-received'] );
		}

		$order_key = isset( $_GET['key'] ) && empty( $_GET['key'] ) ? '' : masteriyo_clean( wp_unslash( $_GET['key'] ?? '' ) );

		/**
		 * @since 2.1.0
		 *
		 * @param integer $order_id
		 */
		$order_id = apply_filters( 'masteriyo_thankyou_order_id', $order_id );

		/**
		 * @since 2.1.0
		 *
		 * @param string $order_key
		 */
		$order_key = apply_filters( 'masteriyo_thankyou_order_key', $order_key );

		if ( $order_id > 0 ) {
			$order = masteriyo_get_order( $order_id );

			if ( ! $order || ( $order_key && ! hash_equals( $order->get_order_key(), $order_key ) ) ) {
				$order = false;
			}
		}

		ob_start();

		masteriyo_get_template(
			'checkout/thankyou.php',
			array(
				'order' => $order,
			)
		);

		return ob_get_clean();
	}
}
