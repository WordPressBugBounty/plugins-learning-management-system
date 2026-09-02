<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo wishlist service providers.
 *
 * @since 2.3.4
 */

use Masteriyo\Addons\WishList\Providers\WishListServiceProvider;

return array_unique(
	array(
		WishListServiceProvider::class,
	)
);
