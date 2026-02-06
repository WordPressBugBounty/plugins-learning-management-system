<?php
/**
 * App service provider.
 *
 * @since 1.0.0
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Masteriyo;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * App service provider.
 *
 * @since 1.0.0
 */
class AppServiceProvider extends AbstractServiceProvider {
	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'app', Masteriyo::class );
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
	 * @since 1.0.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'app',
				'\Masteriyo\Masteriyo',
			),
			true
		);
	}
}
