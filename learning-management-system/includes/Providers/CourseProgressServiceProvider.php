<?php
/**
 * Course progress service provider.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Models\CourseProgress;
use Masteriyo\Repository\CourseProgressRepository;
use Masteriyo\RestApi\Controllers\Version1\CourseProgressController;

class CourseProgressServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->getContainer()->add( 'course-progress.store', CourseProgressRepository::class );

		$this->getContainer()
			->add( 'course-progress.rest', CourseProgressController::class )
			->addArgument( 'permission' );

		$this->getContainer()
			->add( '\Masteriyo\RestApi\Controllers\Version1\CourseProgressController' )
			->addArgument( 'permission' );

		$this->getContainer()
			->add( 'course-progress', CourseProgress::class )
			->addArgument( 'course-progress.store' );
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
				'course-progress',
				'course-progress.store',
				'course-progress.rest',
				'\Masteriyo\RestApi\Controllers\Version1\CourseProgressController',
			),
			true
		);
	}
}
