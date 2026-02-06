<?php
/**
 * Error Reports Controller.
 *
 * Handles requests to the error-reports endpoint.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since 1.14.3
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\DateTime;
use Masteriyo\Helper\Permission;
use Masteriyo\Pro\Addons;
use Masteriyo\Tracking\ServerTrackingInfo;
use Masteriyo\Tracking\WPTrackingInfo;
use Masteriyo\Tracking\MasteriyoTrackingInfo;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Error Reports Controller Class.
 *
 * @package Masteriyo\RestApi
 */
class ErrorReportsController extends WP_REST_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'error-reports';

	/**
	 * Permission class instance.
	 *
	 * @since 1.14.3
	 * @var Permission
	 */
	protected $permission;

	/**
	 * Constructor.
	 *
	 * Sets up the utilities controller.
	 *
	 * @since 1.14.3
	 * @param Permission|null $permission The permission handler instance.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.14.3
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'report_error' ),
				'permission_callback' => array( $this, 'report_error_permissions_check' ),
			)
		);
	}

	/**
	 * Checks if the current user has permissions to perform error report.
	 *
	 * @since 1.14.3
	 * @param WP_REST_Request $request The request.
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function report_error_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new WP_Error( 'masteriyo_null_permission', __( 'Sorry, the permission object for this resource is null.', 'learning-management-system' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'masteriyo_permission_denied',
				__( 'You are not allowed to perform this action.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Reports an error.
	 *
	 * @since 1.14.3
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_Error|WP_REST_Response WP_Error on failure, WP_REST_Response on success.
	 */
	public function report_error( $request ) {
		$url             = isset( $request['url'] ) ? esc_url( $request['url'] ) : '';
		$error_code      = isset( $request['error_code'] ) ? sanitize_text_field( $request['error_code'] ) : '';
		$error_message   = isset( $request['error_message'] ) ? sanitize_textarea_field( $request['error_message'] ) : '';
		$stack_trace     = isset( $request['stack_trace'] ) ? sanitize_textarea_field( $request['stack_trace'] ) : '';
		$user_agent      = isset( $request['user_agent'] ) ? sanitize_text_field( $request['user_agent'] ) : '';
		$occurrence_time = isset( $request['occurrence_time'] ) ? ( new DateTime( $request['occurrence_time'] ) )->format( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
		$additional_info = isset( $request['additional_info'] ) ? sanitize_text_field( $request['additional_info'] ) : '';
		$http_method     = isset( $request['http_method'] ) ? sanitize_text_field( $request['http_method'] ) : '';
		$request_data    = isset( $request['request_data'] ) ? sanitize_textarea_field( $request['request_data'] ) : '';
		$error_origin    = isset( $request['error_origin'] ) ? sanitize_text_field( $request['error_origin'] ) : '';
		$error_category  = isset( $request['error_category'] ) ? sanitize_text_field( $request['error_category'] ) : '';

		if ( empty( $error_code ) || empty( $error_message ) ) {
			return new WP_Error( 'missing_error_data', __( 'Error code and message are required.', 'learning-management-system' ) );
		}

		$error_data = array(
			'page_url'        => $url,
			'error_code'      => $error_code,
			'error_message'   => $error_message,
			'stack_trace'     => $stack_trace,
			'user_agent'      => $user_agent,
			'occurrence_time' => $occurrence_time,
			'http_method'     => $http_method,
			'request_data'    => $request_data,
			'error_origin'    => $error_origin,
			'error_category'  => $error_category,
			'debug_context'   => wp_json_encode(
				array(
					'user_browser_info'            => $user_agent,
					'user_defined_additional_info' => $additional_info,
				)
			),
		);

		$data             = $this->get_error_reports_data( $error_data );
		$error_report_key = 'masteriyo_last_error_report';

		$has_previous_report = get_option( $error_report_key, null ) !== null;

		if ( $has_previous_report ) {
			delete_option( $error_report_key );
		}

		update_option( $error_report_key, $data, false );

		$redirect_url = home_url( '/wp-admin/admin.php?page=masteriyo#/dashboard' );

		return rest_ensure_response(
			array(
				'success'      => true,
				'message'      => __( 'Error reported successfully.', 'learning-management-system' ),
				'redirect_url' => $redirect_url,
			)
		);
	}

	/**
	 * Collects error reports data.
	 *
	 * This method collects data necessary for error reports, such as plugin and WordPress version, PHP version, MySQL version, server OS, etc.
	 *
	 * @since 1.14.3
	 *
	 * @param array $error_data The error data.
	 *
	 * @return array The collected data.
	 */
	private function get_error_reports_data( $error_data ) {
		$addons        = new Addons();
		$active_addons = $addons->get_active_addons();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active_plugins = get_option( 'active_plugins', array() );
		$all_plugins    = get_plugins();

		$masteriyo_slug = method_exists( \Masteriyo\Tracking\MasteriyoTrackingInfo::class, 'get_slug' )
		? MasteriyoTrackingInfo::get_slug()
		: 'learning-management-system/lms.php';

		$other_plugin_titles = array();
		foreach ( $active_plugins as $plugin_file ) {
			if ( $plugin_file === $masteriyo_slug ) {
				continue;
			}

			$title = ( isset( $all_plugins[ $plugin_file ]['Name'] ) && $all_plugins[ $plugin_file ]['Name'] )
			? $all_plugins[ $plugin_file ]['Name']
			: preg_replace( '/\.php$/', '', basename( $plugin_file ) );

			$other_plugin_titles[] = $title;
		}

		$themes             = function_exists( 'wp_get_themes' ) ? wp_get_themes() : array();
		$active_theme       = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		$active_stylesheet  = $active_theme ? $active_theme->get_stylesheet() : '';
		$other_theme_titles = array();

		if ( ! empty( $themes ) ) {
			foreach ( $themes as $stylesheet => $theme_obj ) {
				if ( $stylesheet === $active_stylesheet ) {
					continue;
				}
				$other_theme_titles[] = $theme_obj->get( 'Name' );
			}
		}

		$other_plugin_titles = array_values( array_unique( $other_plugin_titles ) );
		sort( $other_plugin_titles );

		$other_theme_titles = array_values( array_unique( $other_theme_titles ) );
		sort( $other_theme_titles );

		$current_settings = masteriyo_get_settings()->get_data();
		$advance          = masteriyo_array_only( $current_settings, array( 'advance' ) );
		$filtered_advance = masteriyo_array_except( $advance['advance'], array( 'openai' ) );
		$advance          = array( 'advance' => $filtered_advance );

		$exclude_keys      = array( 'payments', 'emails', 'advance' );
		$filtered_data     = masteriyo_array_except( $current_settings, $exclude_keys );
		$filtered_settings = array_merge( $filtered_data, $advance );

		$memory_limit = WP_MEMORY_LIMIT;
		if ( function_exists( 'memory_get_usage' ) ) {
			$memory_limit = max( $memory_limit, @ini_get( 'memory_limit' ) );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$additional_data = array(
			'plugin_name'            => 'Masteriyo',
			'plugin_version'         => masteriyo_get_version(),
			'wp_version'             => get_bloginfo( 'version' ),
			'php_version'            => phpversion(),
			'mysql_version'          => ServerTrackingInfo::get_database_version(),
			'server_os'              => php_uname(),
			'is_pro_version'         => defined( 'MASTERIYO_PRO_VERSION' ) ? 1 : 0,
			'is_multisite'           => is_multisite() ? 1 : 0,
			'activated_addons'       => wp_json_encode( $active_addons ),
			'current_settings'       => wp_json_encode( $filtered_settings ),
			'php_memory_limit'       => $memory_limit,
			'php_max_execution_time' => @ini_get( 'max_execution_time' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,
		);

		$additional_infos = array(
			'is_wp_com'                => WPTrackingInfo::is_wp_com(),
			'is_wp_com_vip'            => WPTrackingInfo::is_wp_com_vip(),
			'is_wp_cache'              => WPTrackingInfo::is_wp_cache_enabled(),
			'is_external_object_cache' => wp_using_ext_object_cache(),
			'debug_mode'               => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'cron'                     => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'                 => get_locale(),
			'multi_site_count'         => WPTrackingInfo::get_sites_total(),
			'timezone'                 => masteriyo_timezone_string(),
			'server_info'              => isset( $_SERVER['SERVER_SOFTWARE'] ) ? wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'curl_version'             => function_exists( 'curl_version' ) ? curl_version()['version'] : '',
			'max_upload_size'          => wp_max_upload_size(),
			'enable_fsockopen_or_curl' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'enable_soapclient'        => class_exists( 'SoapClient' ),
			'enable_domdocument'       => class_exists( 'DOMDocument' ),
			'enable_gzip'              => is_callable( 'gzopen' ),
			'enable_mbstring'          => extension_loaded( 'mbstring' ),
			'suhosin_installed'        => extension_loaded( 'suhosin' ),
			'php_post_max_size'        => @ini_get( 'post_max_size' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			'php_max_input_vars'       => @ini_get( 'max_input_vars' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		);

		$additional_data['additional_infos'] = wp_json_encode( $additional_infos );

		$data = array_merge( $error_data, $additional_data );

		$data['activated_plugins'] = wp_json_encode(
			array(
				'masteriyo_plugin_slug' => $masteriyo_slug,
				'other_plugins'         => array(
					'titles' => $other_plugin_titles,
					'count'  => count( $other_plugin_titles ),
				),
				'other_themes'          => array(
					'titles'       => $other_theme_titles,
					'count'        => count( $other_theme_titles ),
					'active_theme' => $active_theme ? $active_theme->get( 'Name' ) : '',
				),
			)
		);

		return $data;
	}
}
