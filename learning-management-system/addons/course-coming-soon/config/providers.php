<?php
/**
 * Course Coming Soon config.
 *
 * @since 1.11.0
 */

use Masteriyo\Addons\CourseComingSoon\Providers\CourseComingSoonServiceProvider;

/**
 * Masteriyo Course Coming Soon Integration service providers.
 *
 * @since 1.11.0
 */
return array_unique(
	array(
		CourseComingSoonServiceProvider::class,
	)
);
