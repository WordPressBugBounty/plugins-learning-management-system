<?php
/**
 * Bunny Net config.
 *
 * @since 1.11.0
 */

use Masteriyo\Addons\BunnyNet\Providers\BunnyNetServiceProvider;

/**
 * Masteriyo Bunny Net service providers.
 *
 * @since 1.11.0
 */
return array_unique(
	array(
		BunnyNetServiceProvider::class,
	)
);
