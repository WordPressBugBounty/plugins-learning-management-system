<?php
/**
 * Add to cart Ajax handler.
 *
 * @since 1.11.3
 *
 * @package Masteriyo\Addons\WcIntegration
 */

namespace Masteriyo\Addons\WcIntegration;

use Masteriyo\Abstracts\AjaxHandler;

/**
 * List course ajax handler.
 */
class AddToCartAjaxHandler extends AjaxHandler {

	/**
	 * Add to cart ajax action.
	 *
	 * @since 1.11.3
	 * @var string
	 */
	public $action = 'masteriyo_wc_integration_add_to_cart';

	/**
	 * Register ajax handler.
	 *
	 * @since 1.11.3
	 */
	public function register() {
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'add_to_cart' ) );
		add_action( "wp_ajax_{$this->action}", array( $this, 'add_to_cart' ) );
	}

	/**
	 * Add to cart.
	 *
	 * @since 1.11.3
	 */
	public function add_to_cart() {
		try {
			if ( ! function_exists( 'wc_get_product' ) ) {
				throw new \Exception( __( 'WooCommerce is not active.', 'learning-management-system' ) );
			}

			if ( ! isset( $_POST['_wpnonce'] ) ) {
				throw new \Exception( __( 'Nonce is required.', 'learning-management-system' ) );
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'masteriyo_wc_integration_add_to_cart' ) ) {
				throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			}

			if ( ! isset( $_POST['product_id'] ) ) {
				throw new \Exception( __( 'Product ID is required.', 'learning-management-system' ) );
			}

			$product_id = absint( $_POST['product_id'] );

			$product = \wc_get_product( $product_id );

			if ( ! $product ) {
				throw new \Exception( __( 'Product ID is not valid.', 'learning-management-system' ) );
			}

			$is_already_added = false;

			foreach ( \WC()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item['product_id'] ) && absint( $product_id ) === $cart_item['product_id'] ) {
					$is_already_added = true;
					break;
				}
			}

			$response = array(
				'message' => __( 'Already added to the cart.', 'learning-management-system' ),
			);

			if ( ! $is_already_added ) {
				$added = \WC()->cart->add_to_cart( $product_id );

				if ( ! $added ) {
					throw new \Exception( __( 'Unable to add product to the cart.', 'learning-management-system' ) );
				}

				$response['message'] = __( 'Added to the cart successfully.', 'learning-management-system' );
			}

			wp_send_json_success( $response );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}
	}
}
