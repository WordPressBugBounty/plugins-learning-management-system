<?php
/**
 * Setup Pages Ajax Handler.
 *
 * @since 1.15.0
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\AjaxHandler;
use Masteriyo\Activation;

/**
 * Setup Pages Ajax Handler.
 *
 * @since 1.15.0
 */
class SetupPagesAjaxHandler extends AjaxHandler {

	/**
	 * The ajax action.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	public $action = 'masteriyo_setup_pages';


	/**
	 * Register the AJAX action for setting up pages.
	 *
	 * @since 1.15.0
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'masteriyo_setup_pages' ) );
	}

	/**
	 * Sets up the specified pages via an AJAX request.
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public function masteriyo_setup_pages() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash($_POST['nonce'])), 'masteriyo-setup-pages' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'learning-management-system' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to perform this action.', 'learning-management-system' ) );
		}

		// Get the list of page slugs from the AJAX request.
		$page_slugs = isset( $_POST['pages'] ) ? array_map( 'sanitize_text_field', (array) $_POST['pages'] ) : array();

		// Ensure at least one page slug is provided in the request.
		if ( empty( $page_slugs ) ) {
			wp_send_json_error( __( 'No pages specified.', 'learning-management-system' ) );
		}

		// Add a filter to limit page creation to only the slugs specified in the request.
		add_filter(
			'masteriyo_create_pages',
			function( $pages ) use ( $page_slugs ) {
				// Intersect the default pages array with the requested slugs to filter out unwanted pages.
				$pages = array_intersect_key( $pages, array_flip( $page_slugs ) );

				return $pages;
			}
		);

		// Trigger the page creation process using the Activation class.
		Activation::create_pages();

		wp_send_json_success( __( 'Pages set up successfully.', 'learning-management-system' ) );
	}
}
