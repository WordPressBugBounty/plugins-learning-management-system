<?php

namespace Masteriyo\Addons\Stripe\Client\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for Stripe service implementations.
 */
interface StripeServiceInterface {

	/**
	 * Make a request through the service.
	 *
	 * @param string $method HTTP method.
	 * @param string $endpoint API endpoint.
	 * @param array $data Request data.
	 * @param array $headers Additional headers.
	 * @return array|\WP_Error Response data or error object.
	 */
	public function request( $method, $endpoint, $data = array(), $headers = array() );
}
