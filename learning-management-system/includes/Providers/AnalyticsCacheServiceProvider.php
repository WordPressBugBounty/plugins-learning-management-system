<?php
/**
 * Analytics cache invalidation service provider.
 *
 * Registers the analytics cache-invalidation hooks on every request. These live
 * here (instead of in AnalyticsController::__construct) because the controller is
 * only constructed during analytics REST requests, whereas order/course events —
 * e.g. an offline-payment order placed via the ajax checkout — happen on non-REST
 * requests. Registering globally keeps Total Orders / Total Courses in sync.
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\RestApi\Controllers\Version1\AnalyticsController;

class AnalyticsCacheServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Cached controller instance.
	 *
	 * @var AnalyticsController|null
	 */
	private $controller = null;

	/**
	 * This provider does not bind any service into the container.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return false;
	}

	/**
	 * No services to register.
	 */
	public function register(): void {}

	/**
	 * Register analytics cache-invalidation hooks (runs on every request).
	 */
	public function boot(): void {
		add_action( 'masteriyo_new_order', array( $this, 'invalidate_sales' ), 10, 2 );
		add_action( 'masteriyo_update_order', array( $this, 'invalidate_sales' ), 10, 2 );
		add_action( 'masteriyo_trash_order', array( $this, 'invalidate_sales' ), 10, 2 );
		add_action( 'masteriyo_order_status_changed', array( $this, 'invalidate_sales_on_status' ), 10, 4 );

		add_action( 'masteriyo_new_user_course', array( $this, 'invalidate_user_courses' ), 10, 2 );
		add_action( 'masteriyo_update_user_course', array( $this, 'invalidate_user_courses' ), 10, 2 );
		add_action( 'masteriyo_delete_user_course', array( $this, 'invalidate_user_courses' ), 10, 2 );

		// Student/instructor counts come from WP_User_Query, so they must be
		// invalidated when a user is added, deleted, or has their role changed —
		// otherwise the cached "Total Students"/instructors count goes stale.
		add_action( 'deleted_user', array( $this, 'invalidate_user_counts' ) );
		add_action( 'user_register', array( $this, 'invalidate_user_counts' ) );
		add_action( 'set_user_role', array( $this, 'invalidate_user_counts' ) );

		add_action( 'masteriyo_new_course', array( $this, 'invalidate_courses_list' ) );
		add_action( 'masteriyo_update_course', array( $this, 'invalidate_courses_list' ) );
		add_action( 'masteriyo_trash_course', array( $this, 'invalidate_courses_list' ) );
		add_action( 'masteriyo_after_delete_course', array( $this, 'invalidate_courses_list' ) );
		add_action( 'before_delete_post', array( $this, 'maybe_invalidate_courses_list' ), 10, 2 );
		add_action( 'untrashed_post', array( $this, 'maybe_invalidate_courses_list' ), 10, 2 );
	}

	/**
	 * Lazily resolve the analytics controller (only when an event actually fires,
	 * never on a plain frontend pageview).
	 *
	 * @return AnalyticsController
	 */
	private function controller(): AnalyticsController {
		if ( null === $this->controller ) {
			$this->controller = masteriyo( AnalyticsController::class );
		}

		return $this->controller;
	}

	/**
	 * @param int   $id    Order ID.
	 * @param mixed $order Order object.
	 */
	public function invalidate_sales( $id, $order ) {
		$this->controller()->delete_sales_related_cache_keys( $id, $order );
	}

	/**
	 * @param int    $id    Order ID.
	 * @param string $from  Previous status.
	 * @param string $to    New status.
	 * @param mixed  $order Order object.
	 */
	public function invalidate_sales_on_status( $id, $from, $to, $order ) {
		$this->controller()->delete_sales_related_cache_keys_on_status( $id, $from, $to, $order );
	}

	/**
	 * @param int   $id          User course ID.
	 * @param mixed $user_course User course object.
	 */
	public function invalidate_user_courses( $id, $user_course ) {
		$this->controller()->delete_user_courses_related_cache_keys( $id, $user_course );
	}

	/**
	 * Clear cached student/instructor counts after a user is added, deleted, or
	 * has a role change.
	 */
	public function invalidate_user_counts() {
		masteriyo_transient_cache()->clear_caches( 'analytics_user_courses_group' );
	}

	public function invalidate_courses_list() {
		$this->controller()->delete_courses_list_cache();
	}

	/**
	 * @param int           $post_id Post ID.
	 * @param \WP_Post|null $post    Post object.
	 */
	public function maybe_invalidate_courses_list( $post_id, $post = null ) {
		$this->controller()->maybe_delete_courses_list_cache( $post_id, $post );
	}
}
