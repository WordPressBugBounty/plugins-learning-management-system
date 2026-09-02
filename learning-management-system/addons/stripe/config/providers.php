<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo stripe service providers.
 *
 * @since 2.0.0
 */
return array_unique(
	array(
		'Masteriyo\Addons\Stripe\Providers\StripeServiceProvider',
	)
);
