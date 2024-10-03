<?php
/**
 * Addon Name: Wishlist
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Add courses to your wishlist.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Plan: Free
 */

use Masteriyo\Addons\WishList\WishListAddon;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_WISHLIST_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_WISHLIST_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_WISHLIST_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_WISHLIST_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'MASTERIYO_WISHLIST_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_WISHLIST_ADDON_SLUG', 'wishlist' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_WISHLIST_ADDON_SLUG ) ) {
	return;
}

/**
 * Include service providers.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once dirname( __FILE__ ) . '/config/providers.php' );
	}
);

require_once dirname( __FILE__ ) . '/helper.php';

// Initialize wishlist addon.
WishListAddon::instance()->init();
