<?php
/**
 * Request class
 *
 * @since 2.5.19
 *
 * @package Masteriyo\packages\Zoom
 */

namespace ThemeGrill\Zoom;

defined( 'ABSPATH' ) || exit;


class Request {

	protected $base_url = '';

	/**
	 * Make a request.
	 *
	 * @since 2.5.19
	 *
	 * @return array
	 */
	public function fetch( $path = '', $method = 'POST', $data = array(), $headers = array(), $id = '' ) {

		$response = wp_remote_request(
			$this->base_url . $path . $id,
			array(
				'method'  => $method,
				'headers' => $headers,
				'body'    => $data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( null === $data ) {
			return new \WP_Error(
				'zoom_invalid_data',
				'Zoom invalid data'
			);
		}

		return $data;
	}

	/**
	 * Returns the base url.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_base_url() {
		return $this->base_url;
	}

	/**
	 * Set base url.
	 *
	 * @since 2.5.19
	 *
	 * @param string $client_id
	*/
	public function set_base_url( $base_url ) {
		$this->base_url = $base_url;
	}
}
