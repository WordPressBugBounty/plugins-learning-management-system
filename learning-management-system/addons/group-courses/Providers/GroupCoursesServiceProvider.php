<?php
/**
 * Group Courses service provider.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\GroupCourses\Controllers\GroupsController;
use Masteriyo\Addons\GroupCourses\GroupCoursesAddon;
use Masteriyo\Addons\GroupCourses\Models\Group;
use Masteriyo\Addons\GroupCourses\Repository\GroupRepository;

/**
 * Group Courses service provider.
 *
 * @since 1.9.0
 */
class GroupCoursesServiceProvider extends AbstractServiceProvider {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.9.0
	 *
	 * @var array
	 */
	protected $provides = array(
		'group-courses',
		'group-courses.store',
		'group-courses.rest',
		'mto-group',
		'mto-group.store',
		'mto-group.rest',
		'addons.group-courses',
		GroupCoursesAddon::class,
	);

	/**
	 * Registers services and dependencies for the Group Courses.
	 * Accesses the container to register or retrieve necessary services,
	 * ensuring each service declared here is included in the `$provides` array.
	 *
	 * @since 1.9.0
	 */
	public function register() {

		$this->getContainer()->add( 'group-courses.store', GroupRepository::class );

		// Register the REST controller for migration operations.
		$this->getContainer()->add( 'group-courses.rest', GroupsController::class )
			->addArgument( 'permission' );

			$this->getContainer()->add( 'group-courses', Group::class )
			->addArgument( 'group-courses.store' );

		// Register based on post type.
		$this->getContainer()->add( 'mto-group.store', GroupRepository::class );

		$this->getContainer()->add( 'mto-group.rest', GroupsController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'mto-group', Group::class )
			->addArgument( 'mto-group.store' );

		// Register the main addon class.
		$this->getContainer()->add( 'addons.group-courses', GroupCoursesAddon::class, true );
	}
}
