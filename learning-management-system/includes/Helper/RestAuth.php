<?php
/**
 * REST Auth helper functions.
 *
 * @since 1.16.0
 * @package Masteriyo\Helper
 */

namespace Masteriyo\Helper;

use WP_Error;

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * REST Auth helper class
 *
 * @since 1.16.0
 */
class RestAuth {

	/**
	 * Keys user meta key.
	 *
	 * @since 1.16.0
	 * @var string
	 */
	const KEYS_USER_META_KEY = 'masteriyo_rest_api_key_secret';

	/**
	 * Checks if the incoming request has valid authentication credentials.
	 *
	 * This function extracts and validates the API key and secret from the
	 * 'Authorization' header of the request. It supports Basic authentication
	 * scheme where the credentials are base64 encoded.
	 *
	 * @since 1.16.0
	 *
	 * @param \WP_REST_Request $request Full details about the incoming REST API request.
	 *
	 * @return boolean True if the request has valid authentication credentials, false otherwise.
	 */

	public static function rest_auth_permissions_check( $request ) {

		$authorization_header = self::get_authorization_header();

		if ( empty( $authorization_header ) ) {
			return new WP_Error( 'rest_invalid_header', 'Missing Authorization header.', array( 'status' => 401 ) );
		}

		$base_64_credentials = str_replace( 'Basic ', '', $authorization_header );
    $credentials         = base64_decode( $base_64_credentials ); //phpcs:ignore

		list($api_key, $api_secret) = explode( ':', $credentials );

		if ( self::validate_api_key_secret( $api_key, $api_secret ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_credentials', 'Invalid API key or secret.', array( 'status' => 401 ) );
	}

	/**
	 * Retrieves the 'Authorization' header from the incoming request.
	 *
	 * This function attempts to fetch the 'Authorization' header from
	 * the server's HTTP headers. It first checks the 'HTTP_AUTHORIZATION'
	 * server variable and then falls back to using the getallheaders()
	 * function if available. Returns an empty string if the header is not found.
	 *
	 * @since 1.16.0
	 *
	 * @return string The value of the 'Authorization' header or an empty string if not found.
	 */

	public static function get_authorization_header() {
		if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			return wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ); // WPCS: sanitization ok.
		}

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
			foreach ( $headers as $key => $value ) {

				if ( 'authorization' === strtolower( $key ) ) {
					return $value;
				}
			}
		}

		return '';
	}


	/**
	 * Validate API key and secret.
	 *
	 * @since 1.16.0
	 *
	 * @param string $api_key     The API key to validate.
	 * @param string $api_secret  The API secret to validate.
	 * @param bool   $return_result If true, returns the record from the user meta table, false otherwise.
	 *
	 * @return bool|array True if the API key and secret are valid, the record from the user meta table if $return_result is true, false otherwise.
	 */
	public static function validate_api_key_secret( $api_key, $api_secret, $return_result = false ) {
		global $wpdb;

		$valid = false;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s",
				self::KEYS_USER_META_KEY
			),
			ARRAY_A
		);

		if ( ! $results || ! is_array( $results ) || ! count( $results ) ) {
			return $valid;
		}

		foreach ( $results as $result ) {
			$item = json_decode( $result['meta_value'], true );

			if ( ! is_array( $item ) || ! isset( $item['apiKey'] ) || ! isset( $item['secret'] ) ) {
				continue;
			}

			if ( $item['apiKey'] === $api_key && $item['secret'] === $api_secret ) {
				$valid = true;

				$item['user_id'] = $result['user_id'];

				if ( $return_result ) {
					$valid = $item;
				}

				break;
			}
		}

		return $valid;
	}
}
