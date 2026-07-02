<?php
/**
 * Clear Masteriyo Session Ajax handler.
 *
 * @since 1.9.3
 *
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\AjaxHandler;
use WP_Session_Tokens;

/**
 * Clear Masteriyo Session ajax handler.
 */
class ClearMasteriyoUserSessionAjaxHandler extends AjaxHandler {

	/**
	 * Clear Masteriyo Session ajax action.
	 *
	 * @since 1.9.3
	 * @var string
	 */
	public $action = 'masteriyo_clear_sessions';

	/**
	 * Register ajax handler.
	 *
	 * @since 1.9.3
	 */
	public function register() {
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'clear_user_session' ) );
	}

	/**
	 * Clears all user sessions.
	 *
	 * @since 1.9.3
	 */
	public function clear_user_session() {
		$user_info = isset( $_POST['user_info'] ) && is_array( $_POST['user_info'] ) ? $_POST['user_info'] : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! isset( $user_info['_wpnonce'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce is required.', 'learning-management-system' ),
				)
			);
		}

		try {
			$user_id = absint( isset( $user_info['user_id'] ) ? $user_info['user_id'] : 0 );

			if ( ! $user_id ) {
				throw new \Exception( __( 'Invalid user.', 'learning-management-system' ) );
			}

			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $user_info['_wpnonce'] ) ), 'masteriyo_clear_sessions' ) ) {
				throw new \Exception( __( 'Invalid nonce. Maybe you should reload the page.', 'learning-management-system' ) );
			}

			$stored_token   = (string) get_user_meta( $user_id, 'mas_session_token', true );
			$supplied_token = (string) ( isset( $user_info['mas_session_token'] ) ? $user_info['mas_session_token'] : '' );

			// Ensure the token matches to verify the clearance request belongs to the authenticated flow.
			if ( empty( $stored_token ) || ! hash_equals( $stored_token, $supplied_token ) ) {
				throw new \Exception( __( 'Invalid session token.', 'learning-management-system' ) );
			}

			$sessions = WP_Session_Tokens::get_instance( $user_id );
			$sessions->destroy_all();

			$all_sessions = masteriyo_get_all_session_data( $user_id );
			if ( ! empty( $all_sessions ) ) {
				foreach ( $all_sessions as $session ) {
					masteriyo_delete_session_info( $session );
				}
			}

			wp_send_json_success(
				array(
					'success' => true,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
}
