<?php
/**
 * Course Coming Soon service provider.
 *
 * @since 2.1.0
 * @package Masteriyo\CoreFeatures\CourseComingSoon
 */

namespace Masteriyo\CoreFeatures\CourseComingSoon\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\CoreFeatures\CourseComingSoon\CourseComingSoon;

/**
 * Service provider for Course Coming Soon core feature.
 *
 * Registers the Course Coming Soon feature inside the service container.
 *
 * @since 2.1.0
 */
class CourseComingSoonServiceProvider extends AbstractServiceProvider {

	/**
	 * List of services provided by this provider.
	 *
	 * Every service registered in {@see register()} must be declared here.
	 *
	 * @since 2.1.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if this provider provides the service.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'core-features.course-coming-soon',
			),
			true
		);
	}

	/**
	 * Register services into the container.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->add(
			'core-features.course-coming-soon',
			CourseComingSoon::class,
			true
		);
	}
}
