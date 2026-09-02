<?php
/**
 * Checkout service provider.
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Checkout;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Models\Course;

class CheckoutServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * Check if the service provider provides a specific service.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'checkout',
				'\Masteriyo\Checkout',
			),
			true
		);
	}

	/**
	 * This is where the magic happens, within the method you can
	* access the container and register or retrieve anything
	* that you need to, but remember, every alias registered
	* within this method must be declared in the `$provides` array.
	*
	* @since 1.0.0
	*/
	public function register(): void {
		$this->getContainer()
			->addShared( 'checkout', Checkout::class )
			->addArgument( 'cart' )
			->addArgument( 'session' );
	}


	/**
	 * In much the same way, this method has access to the container
	 * itself and can interact with it however you wish, the difference
	 * is that the boot method is invoked as soon as you register
	 * the service provider with the container meaning that everything
	 * in this method is eagerly loaded.
	 *
	 * If you wish to apply inflectors or register further service providers
	 * from this one, it must be from a bootable service provider like
	 * this one, otherwise they will be ignored.
	 *
	 * @since 1.5.41
	 */
	public function boot(): void {
		// add_filter( 'masteriyo_payment_gateways_instances', array( $this, 'filter_payment_gateways_in_checkout_page' ) );
	}

	/**
	 * Filter payment gateways on the basis of items in account page.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Abstracts\PaymentGateway[] $gateways
	 */
	public function filter_payment_gateways_in_checkout_page( $gateways ) {
		if ( ! masteriyo_is_checkout_page() ) {
			return $gateways;
		}

		$items = array_map(
			function( $item ) {
				return isset( $item['data'] ) ? $item['data'] : null;
			},
			masteriyo( 'cart' )->get_cart_contents()
		);

		$courses = array_filter(
			$items,
			function( $item ) {
				return isset( $item ) && is_a( $item, Course::class );
			}
		);

		$recurring_course_exists = false;
		foreach ( $courses as $course ) {
			if ( CourseAccessMode::RECURRING === $course->get_access_mode() ) {
				$recurring_course_exists = true;
				break;
			}
		}

		if ( $recurring_course_exists ) {
			$gateways = array_filter(
				$gateways,
				function( $gateway ) {
					return $gateway->supports( 'subscription' );
				}
			);
		}

		return $gateways;
	}
}
