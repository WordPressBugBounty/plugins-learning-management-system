<?php
/**
 * Course progress item service provider.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Models\CourseProgressItem;
use Masteriyo\Repository\CourseProgressItemRepository;
use Masteriyo\RestApi\Controllers\Version1\CourseProgressItemsController;

class CourseProgressItemServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->getContainer()->add( 'course-progress-item.store', CourseProgressItemRepository::class );

		$this->getContainer()
			->add( 'course-progress-item.rest', CourseProgressItemsController::class )
			->addArgument( 'permission' );

		$this->getContainer()
			->add( 'course-progress-item', CourseProgressItem::class )
			->addArgument( 'course-progress-item.store' );
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
				'course-progress-item',
				'course-progress-item.store',
				'course-progress-item.rest',
			),
			true
		);
	}
}
