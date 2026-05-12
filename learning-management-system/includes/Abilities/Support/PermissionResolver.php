<?php
/**
 * Permission resolver.
 *
 * Delegates ability permission checks to the underlying REST controller's
 * own *_permissions_check() methods, ensuring perfect parity with the REST API.
 *
 * @package Masteriyo\Abilities\Support
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves ability permissions by calling the controller's existing check methods.
 *
 * @since x.x.x
 */
class PermissionResolver {

	/**
	 * Map each ability verb to the controller method that checks it.
	 *
	 * @since x.x.x
	 * @var array
	 */
	private static $verb_method_map = array(
		'list'   => 'get_items_permissions_check',
		'get'    => 'get_item_permissions_check',
		'create' => 'create_item_permissions_check',
		'update' => 'update_item_permissions_check',
		'delete' => 'delete_item_permissions_check',
		'clone'  => 'clone_item_permissions_check',
	);

	/**
	 * Call the appropriate *_permissions_check() on $controller and return bool.
	 *
	 * WP_Error responses are treated as denial (false). This matches how the REST
	 * API would respond to a client that lacks the necessary capability.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Controller $controller REST controller to check against.
	 * @param string              $verb        The ability verb (list, get, create, etc.).
	 * @param \WP_REST_Request    $request     Synthesized request carrying id/params.
	 * @return bool
	 */
	public function resolve( \WP_REST_Controller $controller, string $verb, \WP_REST_Request $request ): bool {
		if ( 'restore' === $verb ) {
			// Prefer a dedicated restore check if the controller implements one;
			// restore is an edit-class operation, not delete.
			$method = method_exists( $controller, 'restore_item_permissions_check' )
				? 'restore_item_permissions_check'
				: 'update_item_permissions_check';
		} elseif ( isset( self::$verb_method_map[ $verb ] ) ) {
			$method = self::$verb_method_map[ $verb ];
		} else {
			// Unknown verb — deny rather than silently falling back to a weaker check.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf( 'Masteriyo Abilities: unknown verb "%s" for %s — access denied.', esc_html( $verb ), esc_html( get_class( $controller ) ) ),
					E_USER_WARNING
				);
			}
			return false;
		}

		if ( ! method_exists( $controller, $method ) ) {
			// If the controller doesn't implement the check at all, deny.
			return false;
		}

		$result = $controller->{$method}( $request );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return (bool) $result;
	}
}
