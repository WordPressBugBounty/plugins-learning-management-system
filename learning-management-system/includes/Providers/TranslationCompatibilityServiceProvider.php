<?php
/**
 * Translation plugin compatibility service provider.
 *
 * @since 2.1.0
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Compatibility\Translation\TranslatePressSimple;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Translation plugin compatibility service provider.
 *
 * @since 2.1.0
 */
class TranslationCompatibilityServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Translation plugin compatibilities class.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	private $translation_plugins = array(
		TranslatePressSimple::class,
	);

	/**
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
			array(),
			true
		);
	}

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 2.1.0
	 */
	public function register(): void {
	}

	/**
	 * In much the same way, this method has access to the container
	 * itself and can interact with it however you wish, the difference
	 * is that the boot method is invoked as soon as you register
	 * the service provider with the container meaning that everything
	 * in this method is eagerly loaded.
	 *
	 * If you wish to apply inflectors or register further service providers
	 * from this one, it must be from a bootable service provider like
	 * this one, otherwise they will be ignored.
	 *
	 * @since 2.1.0
	 */
	public function boot(): void {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Simple initialization - no fancy filters or loops.
		foreach ( $this->translation_plugins as $class ) {
			if ( class_exists( $class ) ) {
				$integration = new $class();
				$integration->init();
			}
		}
	}
}
