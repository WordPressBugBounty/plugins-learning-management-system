<?php
/**
 * Setup Pages Ajax Handler.
 *
 * @since 1.15.0
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

use Masteriyo\Abstracts\AjaxHandler;

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
	 * Register the ajax action for the Setup Pages.
	 *
	 * @since 1.15.0
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'masteriyo_setup_pages' ) );
	}

	/**
	 * Sets up the Learn page.
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public function masteriyo_setup_pages() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'masteriyo-setup-pages' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'learning-management-system' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to perform this action.', 'learning-management-system' ) );
		}

		$page_slugs = isset( $_POST['pages'] ) ? array_map( 'sanitize_text_field', (array) $_POST['pages'] ) : array();

		if ( empty( $page_slugs ) ) {
			wp_send_json_error( __( 'No pages specified.', 'learning-management-system' ) );
		}

		foreach ( $page_slugs as $page_slug ) {
			$post_page = get_page_by_path( $page_slug, OBJECT, 'page' );

			if ( $post_page instanceof \WP_Post ) {
				masteriyo_set_setting( "general.pages.{$page_slug}_page_id", $post_page->ID );
			} else {
				$content = '';

				if ( 'account' === $page_slug ) {
					$content = '<!-- wp:shortcode -->[masteriyo_account]<!-- /wp:shortcode -->';
				} elseif ( 'checkout' === $page_slug ) {
					$content = '<!-- wp:shortcode -->[masteriyo_checkout]<!-- /wp:shortcode -->';
				}

				$page_id = masteriyo_create_page( $page_slug, "{$page_slug}_page_id", ucfirst( $page_slug ), $content );

				if ( $page_id ) {
					masteriyo_set_setting( "general.pages.{$page_slug}_page_id", $page_id );
				}
			}
		}

		wp_send_json_success( __( 'Pages set up successfully.', 'learning-management-system' ) );
	}
}
