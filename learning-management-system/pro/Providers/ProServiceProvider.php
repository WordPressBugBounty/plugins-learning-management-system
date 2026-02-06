<?php
/**
 * Pro service provider.
 *
 * @since 1.6.11
 * @package Masteriyo\Pro\Providers
 */

namespace Masteriyo\Pro\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Pro\Pro;

/**
 * pro service provider.
 *
 * @since 1.6.11
 */
class ProServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.11
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'pro', Pro::class )
			->addArgument( 'addons' );
	}

	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * Check if the service provider provides a specific service.
	 *
	 * @since 2.1.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'pro',
				'\Masteriyo\Masteriyo',
			),
			true
		);
	}
}
