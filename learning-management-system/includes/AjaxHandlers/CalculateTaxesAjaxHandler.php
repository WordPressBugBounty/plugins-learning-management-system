<?php
/**
 * Checkout Ajax handler.
 *
 * @since 2.21.0
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\AjaxHandler;
use Masteriyo\Notice;
use Masteriyo\Tax;

/**
 * Checkout ajax handler.
 */
class CalculateTaxesAjaxHandler extends AjaxHandler {

	/**
	 * Checkout ajax action.
	 *
	 * @since 2.21.0
	 * @var string
	 */
	public $action = 'masteriyo_calculate_taxes';

	/**
	 * Process checkout ajax request.
	 *
	 * @since 2.21.0
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'calculate_taxes' ) );
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'calculate_taxes' ) );
	}

	/**
	 * Process ajax calculate_taxes form.
	 *
	 * @since 2.21.0
	 */
	public function calculate_taxes() {
		try {
			if ( ! isset( $_POST['_wpnonce'] ) ||
				! wp_verify_nonce( $_POST['_wpnonce'], 'masteriyo-process-checkout-nonce' ) ) {
				throw new \Exception( __( 'Invalid request.', 'learning-management-system' ) );
			}

			$country = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
			$state   = isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';

			if ( empty( $country ) ) {
				throw new \Exception( __( 'Country is required.', 'learning-management-system' ) );
			}

			$formatted = $country . ( ! empty( $state ) ? '|' . $state : '' );
			masteriyo_create_session_object()->put( 'selected_country_state', $formatted );
			masteriyo_create_session_object()->save_data();
			masteriyo_create_cart_object()->calculate_totals();

			$this->send_success_response();
		} catch ( \Exception $e ) {
			masteriyo_add_notice( $e->getMessage(), Notice::ERROR );
			$this->send_failure_response();
		}
	}

	/**
	 * If the AJAX operation failed, send failure response.
	 *
	 * @since 2.21.0
	 */
	protected function send_success_response() {
		// Bail early if not ajax.
		if ( ! masteriyo_is_ajax() ) {
			return;
		}

		// Only print notices if not reloading the checkout, otherwise they're lost in the page reload.
		$messages = masteriyo_display_all_notices( true );

		$response = array(
			'messages'  => isset( $messages ) ? $messages : '',
			'fragments' => masteriyo_get_checkout_fragments(),
		);

		masteriyo_clear_notices( Notice::ERROR );
		wp_send_json_success( $response );
	}

	/**
	 * If the AJAX operation failed, send failure response.
	 *
	 * @since 2.21.0
	 */
	protected function send_failure_response() {
		// Bail early if not ajax.
		if ( ! masteriyo_is_ajax() ) {
			return;
		}

		// Only print notices if not reloading the checkout, otherwise they're lost in the page reload.
		$messages = masteriyo_display_all_notices( true );

		$response = array(
			'messages' => isset( $messages ) ? $messages : '',
		);

		masteriyo_clear_notices( Notice::ERROR );
		wp_send_json_error( $response, 400 );
	}
}
