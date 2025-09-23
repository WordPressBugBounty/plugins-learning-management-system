<?php

namespace Masteriyo\Addons\Stripe\Client;

use Masteriyo\Addons\Stripe\Client\Contracts\StripeServiceInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe service for API calls through cloud function.
 */
final class StripeService implements StripeServiceInterface {

	/**
	 * @var StripeServiceConfiguration
	 */
	private $config;

	/**
	 * @var callable
	 */
	private $request_id_generator;

	/**
	 * Constructor.
	 *
	 * @param string $account_id Stripe account ID (required).
	 * @param StripeServiceConfiguration|null $config Configuration instance.
	 */
	public function __construct( $config = null ) {
		$this->config               = $config ?? new StripeServiceConfiguration();
		$this->request_id_generator = function () {
			return 'masteriyo_' . uniqid() . '_' . wp_generate_password( 8, false );
		};
	}

	/**
	 * Make a request through the Stripe service.
	 *
	 * @param string $method HTTP method.
	 * @param string $endpoint API endpoint.
	 * @param array $data Request data.
	 * @param array $headers Additional headers.
	 * @return array|\WP_Error Response data or error object.
	 */
	public function request( $method, $endpoint, $data = array(), $headers = array() ) {
		$query_params = array(
			'mode' => $this->config->get_mode(),
		);

		if ( $this->config->get_account_id() ) {
			$query_params['accountId'] = $this->config->get_account_id();
		}

		$url = add_query_arg(
			$query_params,
			$this->config->get_url() . '/' . ltrim( $endpoint, '/' )
		);

		$request_headers = array_merge(
			array(
				'Content-Type' => 'application/json',
				'X-Request-ID' => call_user_func( $this->request_id_generator ),
				'X-Timestamp'  => time(),
			),
			$headers
		);

		$method = strtoupper( $method );
		$args   = array(
			'method'    => $method,
			'timeout'   => $this->config->get_timeout(),
			'headers'   => $request_headers,
			'sslverify' => $this->config->get_ssl_verify(),
			'body'      => 'POST' === $method ? wp_json_encode( $data ) : null,
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->parse_response( $response );
	}

	/**
	 * Parse the response from Stripe service.
	 *
	 * @param array $response HTTP response.
	 * @return array|\WP_Error Parsed response or error object.
	 */
	private function parse_response( $response ) {
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			$decoded_response = json_decode( $response_body, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $decoded_response;
			}
			return new \WP_Error( 'invalid_response', __( 'Invalid JSON response from Stripe service', 'learning-management-system' ), $response_body );
		}

		return $this->handle_error_response( $response_code, $response_body );
	}

	/**
	 * Handle error responses.
	 *
	 * @param int $response_code HTTP response code.
	 * @param string $response_body Response body.
	 * @return \WP_Error Error object.
	 */
	private function handle_error_response( $response_code, $response_body ) {
		$error_data = json_decode( $response_body, true );

		if ( $response_code >= 400 && $response_code < 500 ) {
			return new \WP_Error(
				'client_error',
				$error_data['message'] ?? 'Client error occurred',
				array(
					'response_code' => $response_code,
					'error_data'    => $error_data,
				)
			);
		}

		if ( $response_code >= 500 ) {
			return new \WP_Error( 'server_error', __( 'Server error occurred', 'learning-management-system' ), array( 'response_code' => $response_code ) );
		}

		return new \WP_Error( 'unexpected_response', __( 'Unexpected response from Stripe service', 'learning-management-system' ), array( 'response_code' => $response_code ) );
	}
}
