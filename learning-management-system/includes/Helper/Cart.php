<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}



if ( ! function_exists( 'masteriyo_get_item_from_cart' ) ) {
	/**
	 * Get the first item from the cart. The item is expected to be either a course or a course bundle.
	 *
	 * @since 1.17.1
	 *
	 * @param \Masteriyo\Cart\Cart $cart Instance of the cart.
	 *
	 * @return \Masteriyo\Models\Course|\Masteriyo\Addons\CourseBundle\Models\CourseBundle|null Item object.
	 */
	function masteriyo_get_item_from_cart( $cart ) {
		if ( ! $cart instanceof \Masteriyo\Cart\Cart ) {
			return null;
		}

		$items = array_column( $cart->get_cart_contents(), 'data' );

		return ! empty( $items ) ? reset( $items ) : null;
	}
}
