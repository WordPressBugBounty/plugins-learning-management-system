<?php
/**
 *
 *
 * @since 1.9.3
 * @package \Masteriyo\Addons\LemonSqueezyIntegration
 */

namespace Masteriyo\Addons\LemonSqueezyIntegration;

use WP_Error;

class Request {
	/**
	 * Lemon Squeezy API base URL.
	 *
	 * @since 1.9.3
	 * @var string
	 */
	const URL = 'https://api.lemonsqueezy.com/v1/';

	/**
	 * Default headers for API requests.
	 *
	 * @since 1.9.3
	 * @var array
	 */
	private $headers = array(
		'Accept'        => 'application/vnd.api+json',
		'Content-Type'  => 'application/vnd.api+json',
		'Cache-Control' => 'no-cache',
	);

	/**
	 * Make a request to the Lemon Squeezy API.
	 *
	 * @since 1.9.3
	 *
	 * @param string $endpoint The API endpoint to call (e.g., 'checkouts').
	 * @param string $method  The HTTP method (e.g., 'POST').
	 * @param array  $data     (Optional) Data to send in the request body.
	 *
	 * @return array|WP_Error The response data or a WP_Error object on failure.
	 */
	public function make_request( $endpoint, $method = 'GET', $data = array() ) {
		$url = self::URL . $endpoint;

		$args = array(
			'headers' => $this->headers,
			'method'  => $method,
			'body'    => wp_json_encode( $data ),
		);

		$api_key = Setting::get( 'api_key' );

		if ( ! $api_key ) {
			return new WP_Error( 'lemon_squeezy_api_key_not_set', __( 'Lemon Squeezy API key is not set.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$store_id = Setting::get( 'store_id' );

		if ( ! $store_id ) {
			return new WP_Error( 'lemon_squeezy_store_id_not_set', __( 'Lemon Squeezy store ID is not set.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		if ( $api_key && 'GET' !== $method ) {
			$args['headers']['authorization'] = 'Bearer ' . $api_key;
		}

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['errors'] ) ) {
			return new WP_Error( 'lemon_squeezy_api_error', $this->get_error_message( $data['errors'] ), $data );
		}

		return $data;
	}

	/**
	 * Get error message from Lemon Squeezy API response.
	 *
	 * @since 1.9.3
	 *
	 * @param array $errors The error data from the API response.
	 *
	 * @return string The error message or a default message.
	 */
	private function get_error_message( $errors ) {
		if ( empty( $errors ) ) {
			return __( 'An unknown error occurred.', 'learning-management-system' );
		}

		$error_message = '';
		foreach ( $errors as $error ) {
			if ( isset( $error['detail'] ) ) {
				$error_message .= $error['detail'] . ' ';
			}
		}

		return trim( $error_message );
	}

	/**
	 * Create a custom checkout URL.
	 *
	 * @since 1.9.3
	 *
	 * @param \Masteriyo\Models\Course|int $course The Masteriyo course ID.
	 * @param array  $options    (Optional) Checkout URL options (e.g., 'redirect_url', 'failure_url').
	 *
	 * @return string|WP_Error The checkout URL or a WP_Error object on failure.
	 */
	public function create_checkout_url( $course, $checkout_data, $options = array() ) {

		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return new WP_Error( 'invalid_course_data', __( 'Course does not exist.', 'learning-management-system' ) );
		}

		$lemon_squeezy_product_id = get_post_meta( $course->get_id(), '_lemon_squeezy_product_id', true );

		if ( ! $lemon_squeezy_product_id ) {
			return new WP_Error( 'invalid_course_data', __( 'Course does not have a linked Lemon Squeezy product.', 'learning-management-system' ) );
		}

		if ( isset( $checkout_data['custom'] ) ) {
			$checkout_data['custom']['course_name'] = $course->get_name();
		}

		$checkout_data['variant_quantities'] = array();

		$product_options = array(
			'name'             => $course->get_name(),
			'enabled_variants' => array( $lemon_squeezy_product_id ),
		);

		if ( ! empty( trim( $course->get_description() ) ) ) {
			$product_options['description'] = $course->get_description();
		}

		if ( isset( $options['redirect_url'] ) && ! empty( $options['redirect_url'] ) ) {
			$product_options['redirect_url'] = $options['redirect_url'];
		}

		$data = array(
			'data' => array(
				'type'          => 'checkouts',
				'attributes'    => array(
					'product_options' => $product_options,
					'checkout_data'   => $checkout_data,
					'expires_at'      => null,
					'preview'         => false,
				),
				'relationships' => array(
					'store'   => array(
						'data' => array(
							'type' => 'stores',
							'id'   => Setting::get( 'store_id' ),
						),
					),
					'variant' => array(
						'data' => array(
							'type' => 'variants',
							'id'   => $lemon_squeezy_product_id,
						),
					),
				),
			),
		);

		$response = $this->make_request( 'checkouts', 'POST', $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['data']['attributes']['url'] ) ) {
			return $response['data']['attributes']['url'];
		}

		return new WP_Error( 'invalid_checkout_data', __( 'Failed to create a custom checkout URL.', 'learning-management-system' ) );
	}
}
