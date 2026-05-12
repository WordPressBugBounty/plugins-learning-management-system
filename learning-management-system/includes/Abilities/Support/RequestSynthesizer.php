<?php
/**
 * Request synthesizer.
 *
 * Builds WP_REST_Request objects from ability verb + input so both the
 * permission resolver and execute path use an identical request representation.
 *
 * @package Masteriyo\Abilities\Support
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Synthesizes WP_REST_Request instances from ability invocation data.
 *
 * @since x.x.x
 */
class RequestSynthesizer {

	/**
	 * HTTP method to use for each verb.
	 *
	 * @since x.x.x
	 * @var array
	 */
	private static $verb_methods = array(
		'list'    => 'GET',
		'get'     => 'GET',
		'create'  => 'POST',
		'update'  => 'PUT',
		'delete'  => 'DELETE',
		'restore' => 'POST',
		'clone'   => 'POST',
	);

	/**
	 * Build a request suitable for calling a controller's *_permissions_check() method.
	 *
	 * Sets url_params so $request['id'] resolves correctly for item-level checks.
	 *
	 * @since x.x.x
	 *
	 * @param string $rest_ns      REST namespace, e.g. "masteriyo/v1".
	 * @param string $rest_base    REST base, e.g. "courses".
	 * @param string $verb         list|get|create|update|delete|restore|clone.
	 * @param array  $input        Ability input (may contain 'id').
	 * @param string $route_suffix Optional path segment appended after the id, e.g. "/children".
	 * @return \WP_REST_Request
	 */
	public static function for_permission( string $rest_ns, string $rest_base, string $verb, array $input, string $route_suffix = '' ): \WP_REST_Request {
		$id     = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$method = isset( self::$verb_methods[ $verb ] ) ? self::$verb_methods[ $verb ] : 'GET';
		$route  = self::build_route( $rest_ns, $rest_base, $verb, $id, $route_suffix );

		$request = new \WP_REST_Request( $method, $route );
		if ( $id > 0 ) {
			$request->set_url_params( array( 'id' => $id ) );
		}
		// Provide query params so any permission check can inspect them.
		$request->set_query_params( $input );

		return $request;
	}

	/**
	 * Build a request suitable for dispatching via rest_do_request().
	 *
	 * Routes params to the correct bucket (query vs body) based on HTTP method.
	 *
	 * @since x.x.x
	 *
	 * @param string $rest_ns      REST namespace.
	 * @param string $rest_base    REST base.
	 * @param string $verb         list|get|create|update|delete|restore|clone.
	 * @param array  $input        Ability input.
	 * @param string $route_suffix Optional path segment appended after the id, e.g. "/children".
	 * @return \WP_REST_Request
	 */
	public static function for_execute( string $rest_ns, string $rest_base, string $verb, array $input, string $route_suffix = '' ): \WP_REST_Request {
		$id     = isset( $input['id'] ) ? (int) $input['id'] : 0;
		$method = isset( self::$verb_methods[ $verb ] ) ? self::$verb_methods[ $verb ] : 'GET';
		$route  = self::build_route( $rest_ns, $rest_base, $verb, $id, $route_suffix );

		$request = new \WP_REST_Request( $method, $route );

		// Remove 'id' from params: it is encoded in the route, not the body/query.
		$params = $id > 0 ? array_diff_key( $input, array( 'id' => true ) ) : $input;

		if ( 'GET' === $method || 'DELETE' === $method ) {
			$request->set_query_params( $params );
		} else {
			$request->set_body_params( $params );
		}

		return $request;
	}

	/**
	 * Build the absolute REST route string for a given verb.
	 *
	 * @since x.x.x
	 *
	 * @param string $rest_ns      REST namespace, e.g. "masteriyo/v1".
	 * @param string $rest_base    REST base, e.g. "courses".
	 * @param string $verb         The ability verb (list, get, create, etc.).
	 * @param int    $id           Resource ID (0 for collection-level verbs).
	 * @param string $route_suffix Optional path segment appended after the id, e.g. "/children".
	 * @return string Absolute REST route string.
	 */
	private static function build_route( string $rest_ns, string $rest_base, string $verb, int $id, string $route_suffix = '' ): string {
		$base = '/' . trim( $rest_ns, '/' ) . '/' . trim( $rest_base, '/' );

		if ( '' !== $route_suffix ) {
			// Suffix routes always require an id segment: /base/{id}/suffix.
			return $base . '/' . $id . '/' . ltrim( $route_suffix, '/' );
		}

		switch ( $verb ) {
			case 'list':
			case 'create':
				return $base;
			case 'restore':
				return $base . '/' . $id . '/restore';
			case 'clone':
				return $base . '/' . $id . '/clone';
			default:
				// get, update, delete — item-level route.
				return $id > 0 ? $base . '/' . $id : $base;
		}
	}
}
