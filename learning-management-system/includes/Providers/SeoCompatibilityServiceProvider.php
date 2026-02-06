<?php
/**
 * Seo plugin compatibility service provider.
 *
 * @since 1.6.11
 */

namespace Masteriyo\Providers;

use Masteriyo\Compatibility\Seo\RankMathSeo;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Compatibility\Seo\AllInOneSeo;
use Masteriyo\Compatibility\Seo\YoastSeo;

defined( 'ABSPATH' ) || exit;

/**
 * Seo plugin compatibility service provider.
 *
 * @since 1.6.11
 */
class SeoCompatibilityServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Seo plugin compatibilities class.
	 *
	 * @since 1.6.11
	 *
	 * @var array
	 */
	private $seo_plugins = array(
		RankMathSeo::class,
		YoastSeo::class,
	);



	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.11
	 */
	public function register(): void {
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
			array(),
			true
		);
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
	 * @since 1.6.11
	 */
	public function boot(): void {
		if ( ! is_blog_installed() ) {
			return;
		}

		/**
		 * Fires before registering seo plugin compatibilities.
		 *
		 * @since 1.6.11
		 */
		do_action( 'masteriyo_register_seo_plugin_compatibilities' );

		/**
		 * Filters seo plugins classes.
		 *
		 * @since 1.6.11
		 *
		 * @param string[] $seo_plugins Seo plugins classes.
		 */
		$seo_plugins = apply_filters( 'masteriyo_register_seo_plugin_compatibilities', $this->seo_plugins );

		$seo_plugins = array_filter(
			$seo_plugins,
			function ( $plugin ) {
				return class_exists( $plugin ) && is_callable( $plugin, 'init' );
			}
		);

		$seo_plugin_objects = array_map(
			function( $class ) {
				return new $class();
			},
			$seo_plugins
		);

		foreach ( $seo_plugin_objects as $object ) {
			$object->init();
		}

		/**
		 * Fires after registering seo plugin compatibilities.
		 *
		 * @since 1.6.11
		 */
		do_action( 'masteriyo_after_register_seo_plugin_compatibilities' );
	}
}
