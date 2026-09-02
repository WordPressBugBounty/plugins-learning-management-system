<?php
/**
 * Tracking service provider.
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Constants;
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
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * Check if the service provider provides a specific service.
	 *
	 * @since 1.6.0
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
	 * Register any bindings. None required for tracking.
	 *
	 * @since 2.30.0
	 */
	public function register(): void {}

	/**
	 * Bootstraps the tracking system by registering SDK logger filter.
	 *
	 *
	 * @since 2.30.0
	 */
	public function boot(): void {

		// The ThemeIsle SDK derives every one of its hook names from the product
		// key, which is the plugin directory with hyphens replaced by underscores
		// (`ThemeisleSDK\Product::setup_from_path()`). That differs between the two
		// products, so the name has to be derived rather than written out — the same
		// idiom `bootstrap/plugin.php` already uses for `_sdk_should_review`.
		$sdk_key = str_replace( '-', '_', basename( Constants::get( 'MASTERIYO_PLUGIN_DIR' ) ) );

		add_filter( $sdk_key . '_logger_data', array( $this, 'provide_tracking_data' ) );

		add_filter(
			'pre_option_' . $sdk_key . '_sdk_enable_logger',
			function( $enabled ) {
				return \masteriyo_get_setting( 'advance.tracking.allow_usage' ) === true ? 'yes' : 'no';
			}
		);

		add_action(
			'update_option_' . $sdk_key . '_sdk_enable_logger',
			function( $old_value, $value ) {
				// Sparse writes — the SDK can flip this before onboarding; see masteriyo_set_raw_setting().
				if ( 'yes' === $value ) {
					\masteriyo_set_raw_setting( 'advance.tracking.allow_usage', true );
				} elseif ( 'no' === $value ) {
					\masteriyo_set_raw_setting( 'advance.tracking.allow_usage', false );
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
	 * @since 2.30.0
	 */
	public function provide_tracking_data() {

		$last_error_report = get_option( 'masteriyo_last_error_report' );

		if ( is_string( $last_error_report ) ) {
			$decoded = json_decode( $last_error_report, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$last_error_report = $decoded;
			}
		}

		if ( is_array( $last_error_report ) ) {

			$nested_json_fields = array(
				'debug_context',
				'activated_addons',
				'current_settings',
				'additional_infos',
				'activated_plugins',
			);

			foreach ( $nested_json_fields as $field ) {
				if ( isset( $last_error_report[ $field ] ) && is_string( $last_error_report[ $field ] ) ) {
					$decoded_field = json_decode( $last_error_report[ $field ], true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$last_error_report[ $field ] = $decoded_field;
					}
				}
			}

			$allowed_keys = array(
				'page_url',
				'error_code',
				'error_message',
				'stack_trace',
				'user_agent',
				'occurrence_time',
				'http_method',
				'request_data',
				'error_origin',
				'error_category',
				'debug_context',
			);

			$last_error_report = array_intersect_key(
				$last_error_report,
				array_flip( $allowed_keys )
			);

			if ( empty( $last_error_report ) ) {
				$last_error_report = null;
			}
		} else {
			$last_error_report = null;
		}

		if ( ! MasteriyoTrackingInfo::is_usage_allowed() ) {
			return $last_error_report ? array( 'last_error_report' => $last_error_report ) : array();
		}

		$data = array_merge(
			WPTrackingInfo::all(),
			ServerTrackingInfo::all()
		);

		$data['onboarding_data'] = function_exists( 'masteriyo_redact_onboarding_secrets' )
			? masteriyo_redact_onboarding_secrets( get_option( 'masteriyo_onboarding_data' ) )
			: null;
		$data['addons']          = MasteriyoTrackingInfo::get_addons_data();

		$masteriyo_slug    = MasteriyoTrackingInfo::get_slug();
		$masteriyo_product = MasteriyoTrackingInfo::all();

		$other_plugins_titles = array();
		if ( ! empty( $data['product_data'] ) && is_array( $data['product_data'] ) ) {
			foreach ( $data['product_data'] as $slug => $prod ) {
				if ( $slug === $masteriyo_slug ) {
					continue;
				}
				if ( isset( $prod['product_type'] ) && 'plugin' === $prod['product_type'] ) {
					$other_plugins_titles[] = isset( $prod['product_name'] ) && $prod['product_name']
					? $prod['product_name']
					: $slug;
				}
			}
		}

		$active_theme_title = isset( $data['active_theme'] ) ? $data['active_theme'] : null;

		$trimmed = array(
			'base_product'           => MasteriyoTrackingInfo::get_name() ?? 'Masteriyo',
			'product_data'           => array(
				$masteriyo_slug => $masteriyo_product,
			),
			'plugin_activation_date' => get_option( 'masteriyo_install_date', time() ) ?? null,
			'publish_course_count'   => MasteriyoTrackingInfo::get_publish_course_count() ?? null,
			'enrolled_users_count'   => MasteriyoTrackingInfo::masteriyo_count_total_enrolled_users() ?? null,
			'masteriyo_install_days' => MasteriyoTrackingInfo::get_install_days() ?? null,
			'users'                  => MasteriyoTrackingInfo::get_users_data(),
			'content_counts'         => MasteriyoTrackingInfo::get_content_counts(),
			'payments'               => MasteriyoTrackingInfo::get_payments_data(),
			'onboarding_data'        => $data['onboarding_data'] ?? null,
			'addons'                 => $data['addons'] ?? null,
			'wp_version'             => $data['wp_version'] ?? null,
			'php_version'            => $data['php_version'] ?? null,
			'mysql_version'          => $data['mysql_version'] ?? null,
			'timezone'               => $data['timezone'] ?? null,
			'locale'                 => $data['locale'] ?? null,
			'admin_email'            => $data['admin_email'] ?? null,
			'is_multisite'           => $data['is_multisite'] ?? null,
			'active_theme'           => $active_theme_title,
			'other_plugins'          => array(
				'count'  => count( $other_plugins_titles ),
				'titles' => array_values( array_unique( $other_plugins_titles ) ),
			),
			'last_error_report'      => $last_error_report,
		);

		$trimmed = array_filter(
			$trimmed,
			function( $v ) {
				if ( is_array( $v ) ) {
					return ! empty( $v );
				}
				return null !== $v && '' !== $v;
			}
		);

		return $trimmed;
	}
}
