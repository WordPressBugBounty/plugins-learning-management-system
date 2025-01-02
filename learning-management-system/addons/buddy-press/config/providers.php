<?php
/**
 * BuddyPress config.
 *
 * @since 1.15.0
 */

use Masteriyo\Addons\BuddyPress\Providers\BuddyPressServiceProvider;

/**
 * Masteriyo BuddyPress service providers.
 *
 * @since 1.15.0
 */
return array_unique(
	array(
		BuddyPressServiceProvider::class,
	)
);
