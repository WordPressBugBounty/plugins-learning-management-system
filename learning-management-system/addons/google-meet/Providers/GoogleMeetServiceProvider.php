<?php
/**
 * GoogleMeet service provider.
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\GoogleMeet\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\GoogleMeet\GoogleMeetAddon;
use Masteriyo\Addons\GoogleMeet\Models\GoogleMeet;
use Masteriyo\Addons\GoogleMeet\Repository\GoogleMeetRepository;
use Masteriyo\Addons\GoogleMeet\RestApi\GoogleMeetController;

/**
 * GoogleMeet service provider.
 *
 * @since 1.11.0
 */
class GoogleMeetServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.11.0
	 */
	public function register(): void {

		$this->getContainer()->addShared( 'addons.google-meet', GoogleMeetAddon::class );

		$this->getContainer()->add( 'google-meet.store', GoogleMeetRepository::class );

		$this->getContainer()->add( 'google-meet', GoogleMeet::class )
			->addArgument( 'google-meet.store' );

		$this->getContainer()->add( 'google-meet.rest', GoogleMeetController::class );

		$this->getContainer()->add( 'mto-google-meet.store', GoogleMeetRepository::class );

		$this->getContainer()->add( 'mto-google-meet', GoogleMeet::class )
			->addArgument( 'mto-google-meet.store' );

		$this->getContainer()->add( 'mto-google-meet.rest', GoogleMeetController::class );

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
				'addons.google-meet',
				GoogleMeetAddon::class,
				'google-meet',
				'google-meet.store',
				'google-meet.rest',
				'mto-google-meet',
				'mto-google-meet.store',
				'mto-google-meet.rest',
			),
			true
		);
	}
}
