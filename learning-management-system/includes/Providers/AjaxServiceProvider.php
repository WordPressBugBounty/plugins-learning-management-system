<?php
/**
 * Ajax service provider.
 *
 * @since 1.4.3
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\AjaxHandlers\CheckoutAjaxHandler;
use Masteriyo\Addons\GoogleClassroomIntegration\AjaxHandlers\CourseCompletionAjaxHandler;
use Masteriyo\AjaxHandlers\CalculateTaxesAjaxHandler;
use Masteriyo\AjaxHandlers\CourseFilterAndSortingAjaxHandler;
use Masteriyo\AjaxHandlers\CoursePasswordProtectionHandler;
use Masteriyo\AjaxHandlers\CourseReviewsInfiniteLoadingAjaxHandler;
use Masteriyo\AjaxHandlers\LoginAjaxHandler;
use Masteriyo\AjaxHandlers\ReviewNoticeAjaxHandler;
use Masteriyo\AjaxHandlers\UsageTrackingNoticeHandler;
use Masteriyo\AjaxHandlers\DeactivationFeedbackAjaxHandler;
use Masteriyo\AjaxHandlers\ClearMasteriyoUserSessionAjaxHandler;
use Masteriyo\AjaxHandlers\CohortAjaxHandler;
use Masteriyo\AjaxHandlers\ProtectedMaterialDownloadHandler;
use Masteriyo\AjaxHandlers\SetupPagesAjaxHandler;

/**
 * Ajax service provider.
 *
 * @since 1.4.3
 */
class AjaxServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * Check if the service provider provides a specific service.
	 *
	 * @since 1.4.3
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(),
			true
		);
	}

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.4.3
	 */
	public function register(): void {
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
	 * @since 1.5.43
	 */
	public function boot(): void {
		$handlers = array_unique(
			/**
			 * Filters ajax handler classes.
			 *
			 * @since 1.4.3
			 *
			 * @param string[] $ajax_handlers Ajax handler classes.
			 */
			apply_filters(
				'masteriyo_ajax_handlers',
				array(
					LoginAjaxHandler::class,
					CheckoutAjaxHandler::class,
					ReviewNoticeAjaxHandler::class,
					DeactivationFeedbackAjaxHandler::class,
					CourseReviewsInfiniteLoadingAjaxHandler::class,
					UsageTrackingNoticeHandler::class,
					CoursePasswordProtectionHandler::class,
					CourseCompletionAjaxHandler::class,
					ClearMasteriyoUserSessionAjaxHandler::class,
					SetupPagesAjaxHandler::class,
					CalculateTaxesAjaxHandler::class,
					// Pro ajax handlers
					CourseFilterAndSortingAjaxHandler::class,
					CohortAjaxHandler::class,
					ProtectedMaterialDownloadHandler::class,
				)
			)
		);

		$handlers = array_filter(
			$handlers,
			function( $handler ) {
				return class_exists( $handler );
			}
		);

		foreach ( $handlers as $handler ) {
			$object = new $handler();

			if ( is_callable( array( $object, 'register' ) ) ) {
				$object->register();
			}
		}
	}
}
