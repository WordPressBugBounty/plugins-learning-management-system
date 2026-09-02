<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'masteriyo_get_item_from_cart' ) ) {
	/**
	 * Get the first item from the cart. The item is expected to be either a course or a course bundle.
	 *
	 * @since 1.17.1 [free]
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

if ( ! function_exists( 'masteriyo_get_user_billing_country' ) ) {
	/**
	 * Get the billing country of the current user.
	 *
	 * @since 2.21.0
	 *
	 * @return string|null Billing country.
	 */
	function masteriyo_get_user_billing_country() {
		$country_state = masteriyo_create_session_object()->get( 'selected_country_state' );

		if ( $country_state ) {
			list($country, ) = array_pad( explode( '|', $country_state, 2 ), 2, '' );

			if ( $country ) {
				return $country;
			}
		}

		if ( ! is_user_logged_in() ) {
			return null;
		}

		return get_user_meta( get_current_user_id(), '_billing_country', true );
	}
}

if ( ! function_exists( 'masteriyo_get_user_billing_state' ) ) {
	/**
	 * Get the billing country of the current user.
	 *
	 * @since 2.21.0
	 *
	 * @return string|null Billing country.
	 */
	function masteriyo_get_user_billing_state() {
		$country_state = masteriyo_create_session_object()->get( 'selected_country_state' );

		if ( $country_state ) {
			list(, $state) = array_pad( explode( '|', $country_state, 2 ), 2, '' );

			if ( $state ) {
				return $state;
			}
		}

		if ( ! is_user_logged_in() ) {
			return null;
		}

		return get_user_meta( get_current_user_id(), '_billing_state', true );
	}
}
