<?php
/**
 * Google Meet Integration config.
 *
 * @since 1.11.0
 */
use Masteriyo\Addons\GoogleMeet\Providers\GoogleMeetServiceProvider;

/**
 * Masteriyo Google Meet Integration service providers.
 *
 * @since 1.11.0
 */
return array_unique(
	array(
		GoogleMeetServiceProvider::class,
	)
);
