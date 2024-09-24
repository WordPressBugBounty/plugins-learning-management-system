<?php

/**
 * Handles sending tracking information for addons in Masteriyo.
 *
 * @package Masteriyo\Tracking
 * @since  1.8.3
 */

namespace Masteriyo\Tracking;

use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Class SendAddonsTrackingInfo
 *
 * Handles the process of sending addon tracking information.
 *
 * @since 1.8.3
 */
class SendAddonsTrackingInfo {

	/**
	 * The URL to which tracking information is sent.
	 *
	 * @since 1.8.3
	 */
	const REMOTE_URL = 'https://stats.wpeverest.com/wp-json/tgreporting/v1/process-free/';

	/**
	 * Get the base product plugin slug.
	 *
	 * @since 1.8.3
	 *
	 * @return string The base product plugin slug.
	 */
	public static function get_slug() {
		return 'learning-management-system/lms.php';
	}

	/**
	 * Return base product name.
	 *
	 * @since 1.8.3
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Masteriyo', 'learning-management-system' );
	}

	/**
	 * Get all addons.
	 *
	 * @since 1.8.3
	 */
	public function get_addons() {
		$addons      = new Addons();
		$addons_data = $addons->get_addons_data();

		return $addons_data;
	}

	/**
	 * Get all addons List.
	 *
	 * @since 1.8.3
	 */
	public function get_addon_list() {
		$our_addons  = $this->get_addons();
		$addons_list = wp_list_pluck( $our_addons, 'slug' );

		$addons        = new Addons();
		$active_addons = $addons->get_active_addons();

		$addons_data = array(
			self::get_slug() => array(
				'product_name'    => self::get_name(),
				'product_version' => masteriyo_get_version(),
				'product_meta'    => array( 'license_key' => '' ),
				'product_type'    => 'plugin',
				'product_slug'    => self::get_slug(),
				'is_premium'      => 0,
			),
		);

		if ( ! empty( $active_addons ) ) {
			foreach ( $active_addons as  $addon ) {
				if ( in_array( $addon['slug'], $addons_list, true ) ) {
					if ( preg_match( '#/wp-content/plugins/(.*)$#', $addon['slug'], $matches ) ) {

						$addon_name = isset( $addon['Addon Name'] ) ? trim( $addon['Addon Name'] ) . ' (Free)' : '';

						$addons_data[ $matches[1] ] = array(
							'product_name'    => $addon_name,
							'product_version' => masteriyo_get_version(),
							'product_type'    => 'plugin',
							'product_slug'    => $matches[1],
						);
					}
				}
			}
		}

		return $addons_data;
	}

	/**
	 * Call API.
	 *
	 * @since 1.8.3
	 */
	public function call_api() {
		$data                 = array();
		$data['product_data'] = $this->get_addon_list();
		$data['admin_email']  = get_bloginfo( 'admin_email' );
		$data['website_url']  = get_bloginfo( 'url' );
		$data['wp_version']   = get_bloginfo( 'version' );
		$data['base_product'] = self::get_name();

		$this->send_request( self::REMOTE_URL, apply_filters( 'masteriyo_addons_stats_data', $data ) );
	}

	/**
	 * Return headers.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	public function get_headers() {
		return array(
			'user-agent' => 'Masteriyo/' . masteriyo_get_version() . '; ' . get_bloginfo( 'url' ),
		);
	}

	/**
	 * Send Request to API.
	 *
	 * @since 1.8.3
	 *
	 * @param string $url URL.
	 *
	 * @param array  $data Data.
	 */
	public function send_request( $url, $data ) {
		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 10,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $this->get_headers(),
				'body'        => array( 'free_data' => $data ),
			)
		);

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}
