<?php

defined( 'ABSPATH' ) || exit;

/**
 * Google Meet Integration config.
 *
 * @since 1.11.0 [free]
 */
use Masteriyo\Addons\GoogleMeet\Providers\GoogleMeetServiceProvider;

/**
 * Masteriyo Google Meet Integration service providers.
 *
 * @since 1.11.0 [free]
 */
return array_unique(
	array(
		GoogleMeetServiceProvider::class,
	)
);
