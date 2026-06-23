<?php
/**
 * NavMenu service provider.
 *
 * @since x.x.x
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\NavMenu\NavMenu;

/**
 * Registers and boots the login/logout nav menu feature.
 *
 * @since x.x.x
 */
class NavMenuServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * No container bindings needed — LoginLogoutNavMenu is instantiated
	 * directly in boot() following the same pattern as BlocksServiceProvider.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register(): void {}

	/**
	 * Check if the service provider provides a given service.
	 *
	 * @since x.x.x
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return false;
	}

	/**
	 * Boot the nav menu feature.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function boot(): void {
		( new NavMenu() )->boot();
	}
}
