<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo elementor integration service providers.
 *
 * @since 2.5.14
 */

use Masteriyo\Addons\ElementorIntegration\Providers\ElementorIntegrationServiceProvider;

return array_unique(
	array(
		ElementorIntegrationServiceProvider::class,
	)
);
