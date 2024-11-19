<?php
/**
 * Stripe service provider.
 *
 * @since 1.14.0
 */

namespace Masteriyo\Addons\Stripe\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\Stripe\Stripe;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Stripe service provider.
 *
 * @since 1.14.0
 */
class StripeServiceProvider extends AbstractServiceProvider {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.14.0
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.14.0
	 */
	public function register() {
	}
}
