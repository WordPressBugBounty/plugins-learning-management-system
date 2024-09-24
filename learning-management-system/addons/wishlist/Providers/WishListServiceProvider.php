<?php
/**
 * Wishlist service provider.
 *
 * @since 1.12.2
 * @package \Masteriyo\Addons\WishList\Providers
 */

namespace Masteriyo\Addons\WishList\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\WishList\WishListAddon;
use Masteriyo\Addons\WishList\Models\WishListItem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\WishList\Repository\WishListItemRepository;
use Masteriyo\Addons\WishList\RestApi\Controllers\Version1\WishListItemsController;
use Masteriyo\Addons\WishList\WishlistItemsQuery;

class WishListServiceProvider extends AbstractServiceProvider {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.12.2
	 *
	 * @var array
	 */
	protected $provides = array(
		'wishlist-item',
		'wishlist-item.store',
		'wishlist-item.rest',
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.12.2
	 */
	public function register() {
		$this->getContainer()->add( 'wishlist-item.store', WishListItemRepository::class );

		$this->getContainer()->add( 'wishlist-item.rest', WishListItemsController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'wishlist-item', WishListItem::class )
			->addArgument( 'wishlist-item.store' );

		$this->getContainer()->add( 'query.wishlist-items', WishlistItemsQuery::class );
	}
}
