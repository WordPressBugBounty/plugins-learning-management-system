<?php

defined( 'ABSPATH' ) || exit;

/**
 * Service providers for the addon.
 *
 * @since 1.11.0 [free]
 */

use Masteriyo\Addons\MultipleCurrency\Providers\MultipleCurrencyServiceProvider;

return array_unique(
	array(
		MultipleCurrencyServiceProvider::class,
	)
);
