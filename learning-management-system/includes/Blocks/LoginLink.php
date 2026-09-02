<?php
/**
 * Login | Logout navigation block.
 *
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\NavMenu\NavMenu;

/**
 * Block-theme counterpart of the classic `#masteriyo-loginout` menu item:
 * a core/navigation child that switches label and URL with the visitor's
 * login state, resolving through the same NavMenu filters.
 */
class LoginLink extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'login-link';

	/**
	 * Build HTML output for the block.
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$label                              = isset( $this->attributes['label'] ) ? (string) $this->attributes['label'] : '';
		list( $login_label, $logout_label ) = NavMenu::split_loginout_label( $label );

		if ( $this->is_block_editor() ) {
			$text = $login_label . ' | ' . $logout_label;
			$url  = '#';
		} else {
			$logged_in = is_user_logged_in();
			$text      = $logged_in ? $logout_label : $login_label;
			$url       = NavMenu::resolve_url( '#masteriyo-loginout' );
		}

		// Core navigation-item markup so the link inherits the navigation
		// block's typography, colors and hover styles.
		return sprintf(
			'<li class="wp-block-navigation-item wp-block-navigation-link masteriyo-nav-loginout"><a class="wp-block-navigation-item__content" href="%s"><span class="wp-block-navigation-item__label">%s</span></a></li>',
			esc_url( $url ),
			esc_html( $text )
		);
	}
}
