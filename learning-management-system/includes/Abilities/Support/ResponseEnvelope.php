<?php
/**
 * Response envelope.
 *
 * Wraps list-style WP_REST_Response objects with pagination metadata that
 * WP normally returns as HTTP headers (X-WP-Total, X-WP-TotalPages).
 *
 * @package Masteriyo\Abilities\Support
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Envelopes a list response so pagination is available to ability callers.
 *
 * @since x.x.x
 */
class ResponseEnvelope {

	/**
	 * Wrap a REST response into an envelope with data + pagination fields.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Response $response   The raw REST response.
	 * @param array             $input      Ability input (for page/per_page extraction).
	 * @return array { data: array, pagination: { total, total_pages, page, per_page } }
	 */
	public static function wrap( \WP_REST_Response $response, array $input = array() ): array {
		$data    = $response->get_data();
		$headers = $response->get_headers();

		// WP REST list endpoints return numerically-indexed arrays. Guard against
		// non-arrays and associative arrays, which JSON-encode as objects rather
		// than arrays, causing output[data] type:array validation failures.
		if ( ! is_array( $data ) ) {
			$data = array();
		} elseif ( ! self::is_list( $data ) ) {
			$data = array_values( $data );
		}

		$total       = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $data );
		$total_pages = isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1;
		$page        = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$per_page    = isset( $input['per_page'] ) ? (int) $input['per_page'] : count( $data );

		return array(
			'data'       => $data,
			'pagination' => array(
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => $page,
				'per_page'    => $per_page,
			),
		);
	}

	/**
	 * Return true when $data is a sequential (list-style) array.
	 *
	 * PHP 7.4-compatible alternative to array_is_list() (PHP 8.1+).
	 * Handles sparse arrays correctly — `!isset($data[0])` would misclassify
	 * an array whose first element was unset (gap at index 0).
	 *
	 * @since x.x.x
	 * @param array $data The array to test.
	 * @return bool
	 */
	private static function is_list( array $data ): bool {
		if ( empty( $data ) ) {
			return true;
		}
		return array_keys( $data ) === range( 0, count( $data ) - 1 );
	}
}
