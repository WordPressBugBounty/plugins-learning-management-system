<?php
/**
 * StarterTemplateServiceProvider Class.
 *
 * @since 2.0.0
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Registers and initializes block types and categories for Masteriyo LMS.
 *
 * @since 2.0.0
 */
class StarterTemplateServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Services provided by this service provider.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $provides = array();

	/**
	 * Register services in the container.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register() {
		// No container services to register for now.
	}

	/**
	 * Boot the block service provider.
	 * Registers block types, categories, and editor assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function boot() {
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
	}

	/**
	 * Localize data for admin scripts.
	 *
	 * @param array $localized_data Existing localized data.
	 * @return array Modified localized data.
	 */
	public function localize_admin_scripts( $localized_data ) {
		$demo_data         = $this->get_demo_data_with_cache( 'https://themegrilldemos.com/wp-json/themegrill-demos/v1/sites' );
		$elearning_demos   = $this->filter_elearning_demos( $demo_data );
		$starter_templates = array( 'demo_data' => $elearning_demos );
		$localized_data['backend']['data']['starter_templates'] = $starter_templates;
		return $localized_data;
	}

	/**
	 * Get demo data with transient caching.
	 * Fetches data from cache if available, otherwise fetches from the URL and stores in transient.
	 *
	 * @param string $url The URL to fetch demo data from.
	 * @return array The demo data.
	 */
	public function get_demo_data_with_cache( $url ) {
		$transient_key    = 'masteriyo_demo_data';
		$cached_demo_data = get_transient( $transient_key );

		if ( $cached_demo_data ) {
			return $cached_demo_data;
		}

		$demo_data = $this->fetch_demo_data( $url );
		set_transient( $transient_key, $demo_data, WEEK_IN_SECONDS );
		return $demo_data;
	}

	/**
	 * Fetch demo data from the provided URL.
	 *
	 * @param string $url The URL to fetch demo data from.
	 * @return array The demo data.
	 */
	public function fetch_demo_data( $url ) {
		$data = wp_remote_get(
			$url,
			array(
				'headers'   => array(
					'User-Agent'   => 'Masteriyo/1.0',
					'Content-Type' => 'application/json',
				),
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $data ) ) {
			return array(
				'success' => false,
				'message' => $data->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $data );

		if ( empty( $body ) ) {
			return array(
				'success' => false,
				'message' => 'Empty response body',
			);
		}

		$response_code = wp_remote_retrieve_response_code( $data );
		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'message' => 'Failed to fetch data.',
			);
		}

		$all_demos = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $all_demos ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid JSON',
			);
		}

		return $all_demos;
	}

	/**
	 * Filter demo data to return only demos for the 'elearning' theme.
	 *
	 * @param array $demo_data The full demo data.
	 * @return array Filtered demo data for 'elearning' theme.
	 */
	private function filter_elearning_demos( $demo_data ) {
		$filtered_demos = array_filter(
			$demo_data,
			function( $demo ) {
				return isset( $demo['theme_slug'] ) && $demo['theme_slug'] === 'elearning';
			}
		);
		return array_values( $filtered_demos );
	}

	/**
	 * Check if eLearning theme is installed.
	 *
	 * @return bool
	 */
	private function is_elearning_installed() {
		$installed_themes = array_keys( wp_get_themes() );
		return in_array( 'elearning', $installed_themes, true );
	}

	/**
	 * Get the current theme.
	 *
	 * @return string
	 */
	private function get_theme() {
		$theme = get_option( 'template' );
		return $theme;
	}
}
