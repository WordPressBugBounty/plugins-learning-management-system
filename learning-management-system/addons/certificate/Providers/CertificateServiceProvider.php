<?php
/**
 * Certificate model service provider.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\Certificate\Models\Certificate;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Addons\Certificate\Repository\CertificateRepository;
use Masteriyo\Addons\Certificate\RestApi\Controllers\Version1\CertificatesController;

class CertificateServiceProvider extends AbstractServiceProvider {


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.13.0
	*/
	public function register(): void {
		$this->getContainer()->add( 'certificate.store', CertificateRepository::class );

		$this->getContainer()->add( 'certificate.rest', CertificatesController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( CertificatesController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'certificate', Certificate::class )
			->addArgument( 'certificate.store' );

		// Register based on post type.
		$this->getContainer()->add( 'mto-certificate.store', CertificateRepository::class );

		$this->getContainer()->add( 'mto-certificate.rest', CertificatesController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'mto-certificate', Certificate::class )
			->addArgument( 'mto-certificate.store' );
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
				'certificate',
				'certificate.store',
				'certificate.rest',
				'mto-certificate',
				'mto-certificate.store',
				'mto-certificate.rest',
			),
			true
		);
	}
}
