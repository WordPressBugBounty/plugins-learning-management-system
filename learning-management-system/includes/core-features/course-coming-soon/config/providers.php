<?php
/**
 * Service providers configuration for Course Coming Soon core feature.
 *
 * @since 2.1.0
 * @package Masteriyo\CoreFeatures\CourseComingSoon
 */

defined( 'ABSPATH' ) || exit;

use Masteriyo\CoreFeatures\CourseComingSoon\Providers\CourseComingSoonServiceProvider;

/**
 * List of service providers to be registered for this feature.
 *
 * @since 2.1.0
 *
 * @return array
 */
return array_unique(
	array(
		CourseComingSoonServiceProvider::class,
	)
);
