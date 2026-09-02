<?php

//As this files autoload from composer.

use Masteriyo\Setup\HomeGuide;

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
	 * @since 2.0.2 [Free]
	 * @return bool
	 */
	function get_hide_home_page() {
		// Finished when the guide has nothing numbered left to do.
		$complete = HomeGuide::is_complete( HomeGuide::get_facts() );

		// Every accurate answer refreshes the flag the admin menu reads, so the
		// menu never has to compute one itself.
		HomeGuide::remember_completion( $complete );

		return $complete;
	}
}


if ( ! function_exists( 'masteriyo_site_needs_checkout' ) ) {
	/**
	 * Whether this site needs a Checkout page.
	 *
	 * Checkout is not created on every install any more, because a site that enrols
	 * its own learners never reaches it — free courses start directly or route via
	 * the Account page. It becomes required once the site sells, which is either the
	 * answer given in onboarding or a payment method being switched on.
	 *
	 * @return bool
	 */
	function masteriyo_site_needs_checkout() {
		$onboarding = get_option( 'masteriyo_onboarding_data', array() );
		$access     = $onboarding['steps']['welcome']['options']['learner_access'] ?? '';

		if ( in_array( $access, array( 'sell', 'both' ), true ) ) {
			return true;
		}

		// The same rule the Home guide uses; two would disagree about who sells.
		if ( HomeGuide::is_payment_connected() ) {
			return true;
		}

		// Already assigned: if it was created earlier, a missing one is worth reporting.
		$needs = (bool) absint( masteriyo_get_setting( 'general.pages.checkout_page_id' ) );

		/**
		 * Filters whether this site needs a Checkout page, so gateway addons that
		 * keep their enable flag outside masteriyo_settings can declare themselves.
		 *
		 * @param bool $needs Whether the site needs a Checkout page.
		 */
		return (bool) apply_filters( 'masteriyo_site_needs_checkout', $needs );
	}
}

if ( ! function_exists( 'check_required_pages' ) ) {
	/**
		*  Check the status of required pages.
		*
		* Checks if the required pages (Learn, Account, Checkout) are set up correctly.
		*
		* @since 2.0.2 [Free]
		*
		* @param bool $keyed Return the pages keyed by slug (learn, account, checkout).
		*                    The default plain list of names is a REST/JS contract:
		*                    Home.tsx does missing_pages.includes('Checkout').
		*
		* @return array Missing page names, keyed by slug when $keyed is true.
		*/
	function check_required_pages( $keyed = false ) {
		$required_pages = array(
			'learn'   => array(
				'setting_key' => 'general.pages.learn_page_id',
				'name'        => 'Learn',
			),
			'account' => array(
				'setting_key' => 'general.pages.account_page_id',
				'name'        => 'Account',
			),
		);

		// Only a selling site needs checkout; requiring it everywhere would nag every
		// site that enrols its own learners about a page it will never use.
		if ( masteriyo_site_needs_checkout() ) {
			$required_pages['checkout'] = array(
				'setting_key' => 'general.pages.checkout_page_id',
				'name'        => 'Checkout',
			);
		}

		$missing_pages = array();

		foreach ( $required_pages as $slug => $details ) {
			$page_id = absint( masteriyo_get_setting( $details['setting_key'] ) );

			if ( empty( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
				$missing_pages[ $slug ] = $details['name'];
			}
		}

		if ( ! empty( $missing_pages ) ) {
			return $keyed ? $missing_pages : array_values( $missing_pages );
		}

		return array();
	}
}
