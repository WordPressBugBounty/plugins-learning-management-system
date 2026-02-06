<?php
/**
 * Course Coming Soon service provider.
 *
 * @since 3.1.0
 * @package Masteriyo\CoreFeatures\BunnyNet
 */

namespace Masteriyo\CoreFeatures\BunnyNet\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\CoreFeatures\BunnyNet\BunnyNet;

/**
 * Service provider for BunnyNet core feature.
 *
 * Registers the BunnyNet feature inside the service container.
 *
 * @since 3.1.0
 */
class BunnyNetServiceProvider extends AbstractServiceProvider {

	/**
	 * List of services provided by this provider.
	 *
	 * Every service registered in {@see register()} must be declared here.
	 *
	 * @since 3.1.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if this provider provides the service.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'core-features.bunny-net',
			),
			true
		);
	}

	/**
	 * Register services into the container.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->add(
			'core-features.bunny-net',
			BunnyNet::class,
			true
		);
	}
}
