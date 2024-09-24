<?php
/**
 * Multiple Currency service provider.
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\MultipleCurrency\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\MultipleCurrency\Controllers\MultipleCurrencySettingsController;
use Masteriyo\Addons\MultipleCurrency\Controllers\PriceZonesController;
use Masteriyo\Addons\MultipleCurrency\Models\PriceZone;
use Masteriyo\Addons\MultipleCurrency\MultipleCurrencyAddon;
use Masteriyo\Addons\MultipleCurrency\Repository\PriceZoneRepository;

/**
 * Multiple Currency service provider.
 *
 * @since 1.11.0
 */
class MultipleCurrencyServiceProvider extends AbstractServiceProvider {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.11.0
	 *
	 * @var array
	 */
	protected $provides = array(
		'addons.multiple-currency',
		MultipleCurrencyAddon::class,
		'multiple-currency.settings.rest',
		'pricing-zones.store',
		'pricing-zones.rest',
		'mto-pricing-zone',
	);

	/**
	 * Registers services and dependencies for the Multiple Currency.
	 * Accesses the container to register or retrieve necessary services,
	 * ensuring each service declared here is included in the `$provides` array.
	 *
	 * @since 1.11.0
	 */
	public function register() {

		$this->getContainer()->add( 'addons.multiple-currency', MultipleCurrencyAddon::class, true );

		$this->getContainer()->add( 'multiple-currency.settings.rest', MultipleCurrencySettingsController::class, true );

		$this->getContainer()->add( 'pricing-zones.rest', PriceZonesController::class )->addArgument( 'permission' );

		$this->getContainer()->add( 'pricing-zones.store', PriceZoneRepository::class );

		$this->getContainer()->add( 'mto-pricing-zone', PriceZone::class )->addArgument( 'pricing-zones.store' );
	}
}
