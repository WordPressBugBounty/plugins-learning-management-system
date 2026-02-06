<?php
/**
 * Google Classroom Integration service provider.
 *
 * @since 1.8.3
 * @package \Masteriyo\Addons\GoogleClassroomIntegration\Providers
 */
namespace Masteriyo\Addons\GoogleClassroomIntegration\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\GoogleClassroomIntegration\Controllers\GoogleClassroomIntegrationController;
use Masteriyo\Addons\GoogleClassroomIntegration\GoogleClassroomIntegrationAddon;
use Masteriyo\Addons\GoogleClassroomIntegration\Models\GoogleClassroomSetting;

/**
 * Google Classroom Integration service provider.
 *
 * @since 1.8.3
 */
class GoogleClassroomIntegrationServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.8.3
	 */
	public function register(): void {

		$this->getContainer()->add( 'google-classroom.rest', GoogleClassroomIntegrationController::class )
			->addArgument( 'permission' );

		// Register based on post type.
		$this->getContainer()->add( 'mto-google-classroom.rest', GoogleClassroomIntegrationController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'addons.google-classroom-integration.setting', GoogleClassroomSetting::class );

		$this->getContainer()->add( 'addons.google-classroom-integration', GoogleClassroomIntegrationAddon::class )->addArgument( 'addons.google-classroom-integration.setting' );
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
				'google-classroom',
				'google-classroom.store',
				'google-classroom.rest',
				'mto-google-classroom',
				'mto-google-classroom.store',
				'mto-google-classroom.rest',
				'addons.google-classroom-integration',
				'addons.google-classroom-integration.setting',
				GoogleClassroomIntegrationAddon::class,
			),
			true
		);
	}
}
