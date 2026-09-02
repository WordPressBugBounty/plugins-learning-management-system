<?php
/**
 * Public profile seam.
 *
 * Public instructor and student profiles are the public-profile addon's feature,
 * and that addon is pro. Core's user REST resource still has to put a
 * `public_profile_url` in every response, so it asks here rather than calling the
 * addon's helper behind an `is_callable()` guard.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Get a user's public profile URL.
 *
 * @param string $username Username.
 *
 * @return string Empty string unless pro answers otherwise.
 */
function masteriyo_get_user_public_profile_url( $username ) {
	/**
	 * Filters a user's public profile URL.
	 *
	 * @param string $url      The public profile URL, or an empty string when there is none.
	 * @param string $username Username.
	 */
	return (string) apply_filters( 'masteriyo_user_public_profile_url', '', $username );
}
