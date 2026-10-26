<?php
/**
 * Abstract ajax handler class.
 *
 * @since 1.4.3
 * @package Masteriyo\Abstracts
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;


/**
 * Abstract ajax handler class.
 */
abstract class AjaxHandler {

	/**
	 * Register ajax handlers.
	 *
	 * @since 1.4.3
	 */
	abstract public function register();

	/**
	 * Send an error JSON response.
	 *
	 * @param string $message The error message.
	 * @param int    $status_code The HTTP status code.
	 */
	protected function send_error_response( $message, $status_code = 400 ) {
		wp_send_json_error( array( 'message' => $message ), $status_code );
	}
}
