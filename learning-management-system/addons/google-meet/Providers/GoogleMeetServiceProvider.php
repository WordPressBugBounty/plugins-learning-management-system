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
		'addons.google-meet',
		GoogleMeetAddon::class,
		'google-meet',
		'google-meet.store',
		'google-meet.rest',
		'mto-google-meet',
		'mto-google-meet.store',
		'mto-google-meet.rest',
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.11.0
	 */
	public function register() {

		$this->getLeagueContainer()->add( 'addons.google-meet', GoogleMeetAddon::class, true );

		$this->getLeagueContainer()->add( 'google-meet.store', GoogleMeetRepository::class );

		$this->getLeagueContainer()->add( 'google-meet', GoogleMeet::class )
			->addArgument( 'google-meet.store' );

		$this->getLeagueContainer()->add( 'google-meet.rest', GoogleMeetController::class );

		$this->getLeagueContainer()->add( 'mto-google-meet.store', GoogleMeetRepository::class );

		$this->getLeagueContainer()->add( 'mto-google-meet', GoogleMeet::class )
			->addArgument( 'mto-google-meet.store' );

		$this->getLeagueContainer()->add( 'mto-google-meet.rest', GoogleMeetController::class );

	}


}
