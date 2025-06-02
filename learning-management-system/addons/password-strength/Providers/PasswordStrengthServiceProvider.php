<?php
/**
 * Password strength service provider.
 *
 * @since 2.3.0
 */

namespace Masteriyo\Addons\PasswordStrength\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\PasswordStrength\PasswordStrengthAddon;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Password Strength  service provider.
 *
 * @since 2.3.0
 */
class PasswordStrengthServiceProvider extends AbstractServiceProvider {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 2.3.0
	 *
	 * @var array
	 */
	protected $provides = array(
		'addons.password-strength',
		PasswordStrengthAddon::class,
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 2.3.0
	 */
	public function register() {
		$this->getContainer()->add( 'addons.password-strength', PasswordStrengthAddon::class, true );
	}
}
