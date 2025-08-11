<?php
/**
 * Tracking service provider.
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Tracking\WPTrackingInfo;
use Masteriyo\Tracking\ServerTrackingInfo;
use Masteriyo\Tracking\MasteriyoTrackingInfo;

/**
 * Service provider for registering tracking integration with ThemeIsle SDK.
 *
 * @since 1.6.0
 */
class TrackingServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * Register any bindings. None required for tracking.
	 *
	 * @since 1.6.0
	 */
	public function register() {}

	/**
	 * Bootstraps the tracking system by registering SDK logger filter.
	 *
	 *
	 * @since 1.18.1
	 */
	public function boot() {
		add_filter( 'learning_management_system_logger_data', array( $this, 'provide_tracking_data' ) );

		add_filter(
			'pre_option_learning_management_system_sdk_enable_logger',
			function( $enabled ) {
				return \masteriyo_get_setting( 'advance.tracking.allow_usage' ) === true ? 'yes' : 'no';
			}
		);

		add_action(
			'update_option_learning_management_system_sdk_enable_logger',
			function( $old_value, $value ) {
				if ( 'yes' === $value ) {
					\masteriyo_set_setting( 'advance.tracking.allow_usage', true );
				} elseif ( 'no' === $value ) {
					\masteriyo_set_setting( 'advance.tracking.allow_usage', false );
				}
			},
			10,
			2
		);

	}

	/**
	 * Callback for SDK tracking filter.
	 *
	 * @return array Tracking data payload.
	 * @since 1.18.1
	 */
	public function provide_tracking_data() {
		if ( ! MasteriyoTrackingInfo::is_usage_allowed() ) {
			return array();
		}

		$data = array_merge(
			WPTrackingInfo::all(),
			ServerTrackingInfo::all()
		);

		$data['product_data'][ MasteriyoTrackingInfo::get_slug() ] = MasteriyoTrackingInfo::all();
		$data['base_product']                                      = MasteriyoTrackingInfo::get_name();
		$data['plugin_activation_date']                            = get_option( 'masteriyo_install_date', time() );
		$data['publish_course_count']                              = MasteriyoTrackingInfo::get_publish_course_count();
		$data['enrolled_users_count']                              = MasteriyoTrackingInfo::masteriyo_count_total_enrolled_users();
		$data['masteriyo_install_days']                            = MasteriyoTrackingInfo::get_install_days();
		return $data;
	}
}
