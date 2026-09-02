<?php

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress  config.
 *
 * @since 1.15.0 [Free]
 */

use Masteriyo\Addons\BuddyPress\Providers\BuddyPressServiceProvider;

/**
 * Masteriyo BuddyPress service providers.
 *
 * @since 1.15.0 [Free]
 */
return array_unique(
	array(
		BuddyPressServiceProvider::class,
	)
);
