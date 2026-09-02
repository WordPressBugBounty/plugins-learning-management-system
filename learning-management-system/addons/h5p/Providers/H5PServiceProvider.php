<?php
/**
 * H5P addon service provider.
 */

namespace Masteriyo\Addons\H5P\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\H5P\H5PAddon;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * H5PServiceProvider class.
 */
class H5PServiceProvider extends AbstractServiceProvider {

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'addons.h5p',
				H5PAddon::class,
			),
			true
		);
	}

	/**
	 * Register services with the container.
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'addons.h5p', H5PAddon::class );
	}
}
