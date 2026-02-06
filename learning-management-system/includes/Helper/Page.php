<?php

use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;


//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Utility functions.
 *
 * @since 1.0.0
 */

/**
 * Check if the current page is a single course page.
 *
 * @since 1.0.0
 *
 * @return boolean
 */
function masteriyo_is_archive_course_page() {
	return is_post_type_archive( 'mto-course' );
}



if ( ! function_exists( 'get_hide_home_page' ) ) {
	/**
	 * Fetch all necessary data and determine if home page should be hidden
	 *
	 * @since 2.0.2
	 * @return bool
	 */
	function get_hide_home_page() {
		$page_check_result       = check_required_pages();
		$stripe_setting          = new StripeSetting();
		$payment_data            = array(
			'offline_payment' => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
			'paypal'          => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
			'paypal_email'    => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
			'stripe'          => $stripe_setting->get( 'enable' ) ?? false,
			'stripe_user_id'  => $stripe_setting->get( 'stripe_user_id' ) ?? false,
		);
		$show_starters_templates = get_option( 'show_starters_templates', 'yes' );
		$skip_payment_setup      = get_option( 'skip_payment_setup' );
		$course_count            = masteriyo_array_get( (array) wp_count_posts( PostType::COURSE ), PostStatus::PUBLISH, 0 );
		$course_created          = $course_count > 0;

		$missing_pages = $page_check_result['missing_pages'] ?? array();

		$hide_home_page = $course_created &&
						empty( $missing_pages ) &&
						(
							$skip_payment_setup === 'yes' ||
							( $payment_data['offline_payment'] && $payment_data['paypal'] && $payment_data['stripe'] )
						) &&
						$show_starters_templates === 'no';

		return $hide_home_page;
	}
}


if ( ! function_exists( 'check_required_pages' ) ) {
	/**
		*  Check the status of required pages.
		*
		* Checks if the required pages (Learn, Account, Checkout) are set up correctly.
		*
		* @since 2.0.2
		*
		* @return array Status of the pages (either 'success' or 'error' with a message).
		*/
	function check_required_pages() {
		$required_pages = array(
			'learn'    => array(
				'setting_key' => 'general.pages.learn_page_id',
				'name'        => 'Learn',
			),
			'account'  => array(
				'setting_key' => 'general.pages.account_page_id',
				'name'        => 'Account',
			),
			'checkout' => array(
				'setting_key' => 'general.pages.checkout_page_id',
				'name'        => 'Checkout',
			),
		);

		$missing_pages = array();

		foreach ( $required_pages as $slug => $details ) {
			$page_id = absint( masteriyo_get_setting( $details['setting_key'] ) );

			if ( empty( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
				$missing_pages[ $slug ] = $details['name'];
			}
		}

		if ( ! empty( $missing_pages ) ) {
			return array_values( $missing_pages );
		}

		return array();
	}
}
