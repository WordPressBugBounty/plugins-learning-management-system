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
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.13.0
	 *
	 * @var array
	 */
	protected $provides = array(
		'certificate',
		'certificate.store',
		'certificate.rest',
		'mto-certificate',
		'mto-certificate.store',
		'mto-certificate.rest',
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.13.0
	*/
	public function register() {
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
}
