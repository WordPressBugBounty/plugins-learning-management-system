<?php
/**
 * Recaptcha service provider.
 *
 * @since 1.18.2
 */

namespace Masteriyo\Addons\Recaptcha\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\Recaptcha\Request;
use Masteriyo\Addons\Recaptcha\GlobalSetting;
use Masteriyo\Addons\Recaptcha\RecaptchaAddon;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Recaptcha service provider.
 *
 * @since 1.18.2
 */
class RecaptchaServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.18.2
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'addons.recaptcha.global_setting', GlobalSetting::class );

		$this->getContainer()->add( 'addons.recaptcha.request', Request::class );

		$this->getContainer()->addShared( 'addons.recaptcha', RecaptchaAddon::class )
			->addArgument( 'addons.recaptcha.global_setting' )
			->addArgument( 'addons.recaptcha.request' );
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
				'addons.recaptcha',
				'addons.recaptcha.global_setting',
				'addons.recaptcha.request',
				RecaptchaAddon::class,
				GlobalSetting::class,
				Request::class,
			),
			true
		);
	}
}
