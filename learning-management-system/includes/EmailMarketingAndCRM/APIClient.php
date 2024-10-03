<?php

/**
 * Abstract class for API clients.
 *
 * @package Masteriyo\EmailMarketingAndCRM
 *
 * @since 1.13.3
 */

namespace Masteriyo\EmailMarketingAndCRM;

/**
 * Abstract class for API clients.
 *
 * @since 1.13.3
 */
class APIClient {

	/**
	 * Base URL for the API.
	 *
	 * @since 1.13.3
	 *
	 * @var string
	 */
	protected $base_url;

	/**
	 * Headers for the API requests.
	 *
	 * @since 1.13.3
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Whether to verify SSL certificates.
	 *
	 * @since 1.13.3
	 *
	 * @var boolean
	 */
	protected $verify_ssl = true;

	/**
	 * Request timeout in seconds.
	 *
	 *  @since 1.13.3
	 *
	 * @var integer
	 */
	protected $timeout = 30;

	/**
	 * Constructor for APIClient.
	 *
	 * @since 1.13.3
	 *
	 * @param string $base_url The base URL for the API.
	 */
	public function __construct( string $base_url ) {
		$this->base_url = rtrim( $base_url, '/' ) . '/';
	}

	/**
	 * Sets a header for the API requests.
	 *
	 * @since 1.13.3
	 *
	 * @param string $key The header key.
	 * @param string $value The header value.
	 */
	public function set_header( string $key, string $value ) {
		$this->headers[ $key ] = $value;
	}

	/**
	 * Sets SSL verification.
	 *
	 * @since 1.13.3
	 *
	 * @param bool $verify_ssl Whether to verify SSL certificates.
	 */
	public function set_verify_ssl( bool $verify_ssl ) {
		$this->verify_ssl = $verify_ssl;
	}

	/**
	 * Sets request timeout.
	 *
	 * @since 1.13.3
	 *
	 * @param int $timeout The request timeout in seconds.
	 */
	public function set_timeout( int $timeout ) {
		$this->timeout = $timeout;
	}

	/**
	 * Sets the bearer token for the API requests.
	 *
	 * @since 1.13.3
	 *
	 * @param string $token The bearer token.
	 */
	public function set_bearer_token( string $token ) {
		$this->set_header( 'Authorization', 'Bearer ' . $token );
	}

	/**
	 * Performs a GET request.
	 *
	 * @since 1.13.3
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array $params Optional. Query parameters.
	 * @param array $headers Optional. Headers specific to this request.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function get( string $endpoint, array $params = array(), array $headers = array() ) {
		return $this->make_request( 'GET', $endpoint, $params, array(), $headers );
	}

	/**
	 * Performs a POST request.
	 *
	 * @since 1.13.3
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array $body Optional. Request body.
	 * @param array $headers Optional. Headers specific to this request.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function post( string $endpoint, array $body = array(), array $headers = array() ) {
		return $this->make_request( 'POST', $endpoint, array(), $body, $headers );
	}

	/**
	 * Performs a PUT request.
	 *
	 * @since 1.13.3
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array $body Optional. Request body.
	 * @param array $headers Optional. Headers specific to this request.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function put( string $endpoint, array $body = array(), array $headers = array() ) {
		return $this->make_request( 'PUT', $endpoint, array(), $body, $headers );
	}

	/**
	 * Performs a DELETE request.
	 *
	 * @since 1.13.3
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array $body Optional. Request body.
	 * @param array $headers Optional. Headers specific to this request.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function delete( string $endpoint, array $body = array(), array $headers = array() ) {
		return $this->make_request( 'DELETE', $endpoint, array(), $body, $headers );
	}

	/**
	 * Makes an HTTP request.
	 *
	 * @since 1.13.3
	 *
	 * @param string $method The HTTP method to use.
	 * @param string $endpoint The API endpoint.
	 * @param array $params Optional. Query parameters.
	 * @param array $body Optional. Request body.
	 * @param array $headers Optional. Headers specific to this request.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	protected function make_request( string $method, string $endpoint, array $params = array(), array $body = array(), array $headers = array() ) {
		$url = $this->base_url . ltrim( $endpoint, '/' );

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$args = array(
			'method'    => strtoupper( $method ),
			'headers'   => array_merge( $this->get_default_headers(), $headers ),
			'body'      => ! empty( $body ) ? wp_json_encode( $body ) : '',
			'timeout'   => $this->timeout,
			'sslverify' => $this->verify_ssl,
		);

		$response = wp_remote_request( $url, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Gets default headers for requests.
	 *
	 * @since 1.13.3
	 *
	 * @return array Default headers.
	 */
	protected function get_default_headers() {
		return array_merge(
			array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			$this->headers
		);

	}

	/**
	 * Handles the HTTP response.
	 *
	 * @since 1.13.3
	 *
	 * @param mixed $response The HTTP response.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	protected function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = $body;
			return new \WP_Error( $status_code, $error_message, $response_data );
		}

		return $response_data;
	}
}
