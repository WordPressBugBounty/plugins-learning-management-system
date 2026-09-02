<?php
/**
 * Make the theme-wrapped account and checkout pages sit naturally inside
 * third-party themes.
 *
 * Both pages open with their own heading, so the theme's page title above them
 * is a duplicate. It is removed server-side rather than hidden with CSS: when
 * the_title filters to an empty string, WordPress skips the whole heading
 * (wrapper markup included), so no theme-specific empty-heading gap is left
 * behind. Spacing quirks that remain are patched per active theme with a small
 * inline style on the plugin's own public stylesheet.
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

class ThemeCompat {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'the_title', array( __CLASS__, 'remove_page_title' ), 10, 2 );

		// Neve prints the page title outside the loop through its own page-header
		// component, so the the_title filter never reaches it; its hide-title
		// theme mod is the supported seam (same effect as the per-page toggle).
		add_filter( 'theme_mod_neve_page_hide_title', array( __CLASS__, 'hide_neve_page_title' ) );

		// Saving any page under Neve stamps neve_meta_disable_title = 'off' on it,
		// and that meta overrides the theme mod — so the read is intercepted too.
		add_filter( 'get_post_metadata', array( __CLASS__, 'override_neve_title_meta' ), 10, 4 );

		// After ScriptStyle's PHP_INT_MAX - 10 enqueue, so the handle exists.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_inline_styles' ), PHP_INT_MAX - 8 );
	}

	/**
	 * Whether the theme's title for this page should be removed.
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return boolean
	 */
	protected static function should_remove_title( $page_id ) {
		$page_ids = array_filter(
			array(
				masteriyo_get_page_id( 'account' ),
				masteriyo_get_page_id( 'checkout' ),
			),
			function ( $id ) {
				return $id > 0;
			}
		);

		$remove = in_array( (int) $page_id, $page_ids, true );

		/**
		 * Filters whether the theme's page title is removed on the account and
		 * checkout pages.
		 *
		 * @param boolean $remove  Whether to remove the title.
		 * @param integer $page_id The page being rendered.
		 */
		return (bool) apply_filters( 'masteriyo_remove_theme_page_title', $remove, $page_id );
	}

	/**
	 * Blank the title of the account and checkout pages in the main loop.
	 *
	 * @param string  $title   Page title.
	 * @param integer $post_id Post ID.
	 *
	 * @return string
	 */
	public static function remove_page_title( $title, $post_id = 0 ) {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		return self::should_remove_title( $post_id ) ? '' : $title;
	}

	/**
	 * Tell Neve to skip its page-header title on the account and checkout pages.
	 *
	 * @param mixed $value The neve_page_hide_title theme mod.
	 *
	 * @return mixed
	 */
	public static function hide_neve_page_title( $value ) {
		if ( is_admin() || ! did_action( 'wp' ) ) {
			return $value;
		}

		if ( ( masteriyo_is_account_page() || masteriyo_is_checkout_page() ) && self::should_remove_title( get_queried_object_id() ) ) {
			return true;
		}

		return $value;
	}

	/**
	 * Answer Neve's per-page title meta with 'on' for the account/checkout pages.
	 *
	 * Frontend only, so the metabox in the editor still shows the stored value.
	 *
	 * @param mixed   $value     Short-circuit value, null to read from the database.
	 * @param integer $object_id Post ID.
	 * @param string  $meta_key  Meta key.
	 * @param boolean $single    Whether a single value is requested.
	 *
	 * @return mixed
	 */
	public static function override_neve_title_meta( $value, $object_id, $meta_key, $single ) {
		if ( 'neve_meta_disable_title' !== $meta_key || is_admin() || 'neve' !== get_template() ) {
			return $value;
		}

		if ( self::should_remove_title( $object_id ) ) {
			return $single ? 'on' : array( 'on' );
		}

		return $value;
	}

	/**
	 * Attach the active theme's spacing patches to the public stylesheet.
	 */
	public static function add_inline_styles() {
		if ( ! masteriyo_is_account_page() && ! masteriyo_is_checkout_page() ) {
			return;
		}

		$css = self::get_theme_css( get_template() );

		if ( $css ) {
			wp_add_inline_style( 'masteriyo-public', $css );
		}
	}

	/**
	 * Spacing patches for the active theme, on the account/checkout pages only.
	 *
	 * The inline style is printed only on those pages, so selectors need no
	 * page scoping of their own.
	 *
	 * @param string $template Active (parent) theme directory name.
	 *
	 * @return string
	 */
	protected static function get_theme_css( $template ) {
		$css = '';

		if ( 'neve' === $template ) {
			// Neve's content area has no block padding of its own — an ordinary
			// page gets its breathing room from the title wrap removed above, so
			// hand the account containers the checkout container's 60px rhythm.
			// The .nv-content-wrap scope keeps this out of the full-layout mode.
			$css = '
				.nv-content-wrap #masteriyo-account-page,
				.nv-content-wrap .masteriyo-login-form-wrapper,
				.nv-content-wrap .masteriyo-signup,
				.nv-content-wrap .masteriyo-reset {
					margin-block: 60px;
				}
			';
		}

		/**
		 * Filters the per-theme spacing CSS for the account and checkout pages.
		 *
		 * @param string $css      The CSS to inline; empty for no patch.
		 * @param string $template Active (parent) theme directory name.
		 */
		return apply_filters( 'masteriyo_theme_compat_inline_css', $css, $template );
	}
}
