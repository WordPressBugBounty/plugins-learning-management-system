<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo stripe service providers.
 *
 * @since 1.14.0
 */
return array_unique(
	array(
		'Masteriyo\Addons\Stripe\Providers\StripeServiceProvider',
	)
);
