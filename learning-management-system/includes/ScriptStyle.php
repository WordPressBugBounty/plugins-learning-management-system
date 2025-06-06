<?php

/**
 * Manages scripts and styles.
 *
 * @package Masteriyo
 *
 * @since 1.0.0
 */

namespace Masteriyo;

use Masteriyo\Constants;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseCategoryQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Manages scripts and styles.
 *
 * @class Masteriyo\ScriptStyle
 */

class ScriptStyle {

	/**
	 * Scripts.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $scripts = array();

	/**
	 * Styles.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $styles = array();

	/**
	 * Localized scripts.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $localized_scripts = array();

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::init_hooks();
		self::init_scripts();
		self::init_styles();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function init_hooks() {
		add_action( 'init', array( __CLASS__, 'after_wp_init' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'load_editor_styles' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_public_scripts_styles' ), PHP_INT_MAX - 10 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_scripts_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_public_localized_scripts' ), PHP_INT_MAX - 9 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_localized_scripts' ) );
		add_action( 'current_screen', array( __CLASS__, 'override_wp_private_apis_script' ) );

		// Remove third party styles from learn page.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_styles_from_learn_page' ), PHP_INT_MAX );
		// Remove third party styles from account page.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_styles_from_account_page' ), PHP_INT_MAX );

		// Remove third party scripts from admin page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'remove_scripts_from_admin_page' ), 9999 );
	}




	/**
	 * load editor styles for blocks
	 *
	 * @since 1.12.2
	 */
	public static function load_editor_styles() {
		$public_style = self::get_styles( 'public' )['public'] ?? array();
		if ( ! $public_style ) {
			return;
		}
		self::enqueue_style( 'public', $public_style['src'], $public_style['deps'], $public_style['version'], $public_style['media'], $public_style['has_rtl'] );

		self::load_custom_inline_styles();
	}

	/**
	 * Override wp private apis script.
	 *
	 * @since 1.7.3
	 */
	public static function override_wp_private_apis_script() {
		if ( 'toplevel_page_masteriyo' === get_current_screen()->id && ( version_compare( get_bloginfo( 'version' ), '6.4', '<' ) || version_compare( get_bloginfo( 'version' ), '6.6', '>=' ) ) ) {
			$suffix = SCRIPT_DEBUG ? '.js' : '.min.js';
			wp_deregister_script( 'wp-private-apis' );
			wp_register_script( 'wp-private-apis', plugins_url( 'libs/private-apis' . $suffix, MASTERIYO_PLUGIN_FILE ), array(), MASTERIYO_VERSION, true );
		}
	}

	/**
	 * Initialization after WordPress is initialized.
	 *
	 * @since 1.3.0
	 */
	public static function after_wp_init() {
		self::register_block_scripts_and_styles();
		self::localize_block_scripts();
	}

	/**
	 * Get application version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function get_version() {
		return Constants::get( 'MASTERIYO_VERSION' );
	}

	/**
	 * Get asset name suffix.
	 *
	 * @since 1.0.0
	 * @deprecated 1.5.35
	 *
	 * @return array
	 */
	public static function get_asset_suffix() {
		$version = Constants::get( 'MASTERIYO_VERSION' );

		if ( Constants::is_true( 'SCRIPT_DEBUG' ) ) {
			return ".{$version}";
		}
		return ".{$version}.min";
	}

	/**
	 * Get asset dependencies.
	 *
	 * @since 1.4.1
	 *
	 * @param string $asset_name
	 *
	 * @return array
	 */
	public static function get_asset_deps( $asset_name ) {
		$asset_filepath = Constants::get( 'MASTERIYO_PLUGIN_DIR' ) . "/assets/js/build/{$asset_name}.asset.php";

		if ( ! file_exists( $asset_filepath ) || ! is_readable( $asset_filepath ) ) {
			return array();
		}
		$asset = (array) require $asset_filepath;

		return masteriyo_array_get( $asset, 'dependencies', array() );
	}

	/**
	 * Initialize the scripts.`
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function init_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$account_src               = self::get_asset_url( '/assets/js/build/masteriyo-account.js' );
		$backend_src               = self::get_asset_url( '/assets/js/build/masteriyo-backend.js' );
		$learn_src                 = self::get_asset_url( '/assets/js/build/masteriyo-interactive.js' );
		$single_course_src         = self::get_asset_url( '/assets/js/build/single-course' . $suffix . '.js' );
		$courses_src               = self::get_asset_url( '/assets/js/build/courses' . $suffix . '.js' );
		$admin_src                 = self::get_asset_url( '/assets/js/build/admin' . $suffix . '.js' );
		$login_form_src            = self::get_asset_url( '/assets/js/build/login-form' . $suffix . '.js' );
		$checkout_src              = self::get_asset_url( '/assets/js/build/checkout' . $suffix . '.js' );
		$ask_review_src            = self::get_asset_url( '/assets/js/build/ask-review' . $suffix . '.js' );
		$jquery_block_ui_src       = self::get_asset_url( '/assets/js/build/jquery-block-ui' . $suffix . '.js' );
		$ask_usage_tracking_src    = self::get_asset_url( '/assets/js/build/usage-tracking' . $suffix . '.js' );
		$swiper_src                = plugins_url( 'libs/swiper/swiper-bundle.min.js', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) );
		$categories_slider_src     = self::get_asset_url( '/assets/js/build/categories-slider' . $suffix . '.js' );
		$custom_field_src          = self::get_asset_url( '/assets/js/build/masteriyo-builder-custom-fields' . $suffix . '.js' );

		if ( masteriyo_is_development() ) {
			$account_src               = 'http://localhost:3000/dist/account.js';
			$backend_src               = 'http://localhost:3000/dist/backend.js';
			$learn_src                 = 'http://localhost:3000/dist/interactive.js';
			$single_course_src         = self::get_asset_url( '/assets/js/frontend/single-course.js' );
			$courses_src               = self::get_asset_url( '/assets/js/frontend/courses.js' );
			$admin_src                 = self::get_asset_url( '/assets/js/admin/admin.js' );
			$login_form_src            = self::get_asset_url( '/assets/js/frontend/login-form.js' );
			$checkout_src              = self::get_asset_url( '/assets/js/frontend/checkout.js' );
			$ask_review_src            = self::get_asset_url( '/assets/js/frontend/ask-review.js' );
			$jquery_block_ui_src       = self::get_asset_url( '/assets/js/frontend/jquery-block-ui.js' );
			$ask_usage_tracking_src    = self::get_asset_url( '/assets/js/frontend/usage-tracking.js' );
			$categories_slider_src     = self::get_asset_url( '/assets/js/frontend/categories-slider.js' );
			$custom_field_src          = self::get_asset_url( '/assets/js/admin/masteriyo-builder-custom-fields.js' );
		}

		/**
		 * Filters the scripts.
		 *
		 * @since 1.0.0
		 *
		 * @param array $scripts List of scripts.
		 */
		self::$scripts = apply_filters(
			'masteriyo_enqueue_scripts',
			array(
				'dependencies'            => array(
					'src'      => self::get_asset_url( '/assets/js/build/masteriyo-dependencies.js' ),
					'context'  => array( 'admin', 'public' ),
					'callback' => function () {
						return masteriyo_is_production() && ( masteriyo_is_admin_page() || masteriyo_is_learn_page() || ( is_user_logged_in() && masteriyo_is_account_page() ) );},
				),
				'blocks'                  => array(
					'src'           => self::get_asset_url( '/assets/js/build/blocks.js' ),
					'context'       => 'blocks',
					'deps'          => array_merge( self::get_asset_deps( 'blocks' ), array( 'jquery', 'wp-dom-ready', 'wp-hooks', 'wp-keyboard-shortcuts' ) ),
					'register_only' => true,
				),
				'admin'                   => array(
					'src'      => $admin_src,
					'deps'     => array( 'jquery' ),
					'context'  => 'admin',
					'callback' => 'masteriyo_is_admin_page',
				),
				'masteriyo-custom'        => array(
					'src'     => $custom_field_src,
					'deps'    => array( 'jquery' ),
					'context' => array( 'admin', 'public' ),
					'type'    => 'module',
					function () {
						return masteriyo_is_production() && ( masteriyo_is_admin_page() || masteriyo_is_learn_page() || ( is_user_logged_in() && masteriyo_is_account_page() ) );},
				),
				'backend'                 => array(
					'src'      => $backend_src,
					'deps'     => array_merge( self::get_asset_deps( 'masteriyo-backend' ), array( 'wp-core-data', 'wp-components', 'wp-element', 'wp-editor', 'wp-rich-text', 'wp-format-library' ) ),
					'context'  => 'admin',
					'callback' => 'masteriyo_is_admin_page',
				),
				'single-course'           => array(
					'src'      => $single_course_src,
					'deps'     => array( 'jquery' ),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_single_course_page() || isset( $_GET['masteriyo-load-single-course-js'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					},
				),
				'courses'                 => array(
					'src'      => $courses_src,
					'deps'     => array( 'jquery' ),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_courses_page() || isset( $_GET['masteriyo-load-courses-js'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					},
				),
				'account'                 => array(
					'src'      => $account_src,
					'deps'     => array_merge( self::get_asset_deps( 'masteriyo-backend' ), array( 'wp-core-data', 'wp-components', 'wp-element' ) ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return is_user_logged_in() && masteriyo_is_account_page();
					},
				),
				'login-form'              => array(
					'src'      => $login_form_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_load_login_form_assets() ||
						( ! is_user_logged_in() && masteriyo_get_setting( 'single_course.display.course_visibility' ) );
					},
				),
				'checkout'                => array(
					'src'      => $checkout_src,
					'deps'     => array( 'jquery', 'masteriyo-jquery-block-ui' ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => 'masteriyo_is_checkout_page',
				),
				'ask-review'              => array(
					'src'      => $ask_review_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'admin',
					'callback' => 'masteriyo_is_show_review_notice',
				),
				'learn'                   => array(
					'src'      => $learn_src,
					'deps'     => array_merge( self::get_asset_deps( 'masteriyo-interactive' ), array( 'wp-data', 'wp-core-data', 'wp-components', 'wp-element' ) ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => 'masteriyo_is_learn_page',
				),
				'jquery-block-ui'         => array(
					'src'      => $jquery_block_ui_src,
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_checkout_page() || is_post_type_archive( PostType::COURSE );
					},
				),
				'ask-usage-tracking'      => array(
					'src'      => $ask_usage_tracking_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'admin',
					'callback' => function () {
						return masteriyo_show_usage_tracking_notice();
					},
				),
				'swiper'                  => array(
					'src'      => $swiper_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_slider_enabled();
					},
				),
				'categories-slider'       => array(
					'src'      => $categories_slider_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_categories_slider_enabled();
					},
				),
				'masteriyo-single-course' => array(
					'src'      => $single_course_src,
					'deps'     => array( 'jquery' ),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_single_page_contains_block();
					},
				),
				'masteriyo-ask-review'    => array(
					'src'      => $ask_review_src,
					'deps'     => array( 'jquery' ),
					'version'  => self::get_version(),
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_single_page_contains_block();
					},
				),
				'masteriyo-courses'       => array(
					'src'      => $courses_src,
					'deps'     => array( 'jquery' ),
					'context'  => 'public',
					'callback' => function () {
							return masteriyo_is_single_page_contains_block();
					},
				),
			)
		);
	}

	/**
	 * Initialize the styles.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function init_styles() {
		/**
		 * Filters the styles.
		 *
		 * @since 1.0.0
		 *
		 * @param array $styles List of styles.
		 */
		self::$styles = apply_filters(
			'masteriyo_enqueue_styles',
			array(
				'public'                => array(
					'src'     => self::get_asset_url( '/assets/css/public.css' ),
					'has_rtl' => false,
					'context' => 'public',
				),
				'dependencies'          => array(
					'src'      => self::get_asset_url( '/assets/js/build/masteriyo-dependencies.css' ),
					'has_rtl'  => false,
					'context'  => array( 'admin', 'public' ),
					'callback' => function () {
						return masteriyo_is_production() && ( masteriyo_is_admin_page() || ( is_user_logged_in() && masteriyo_is_account_page() ) || masteriyo_is_learn_page() );                   },
				),
				'block'                 => array(
					'src'      => self::get_asset_url( '/assets/css/block.css' ),
					'has_rtl'  => false,
					'context'  => 'admin',
					'callback' => function () {
						$screen = get_current_screen();

						return $screen && ( $screen->is_block_editor() || 'customize' === $screen->id );
					},
				),
				'review-notice'         => array(
					'src'      => self::get_asset_url( '/assets/css/review-notice.css' ),
					'has_rtl'  => false,
					'context'  => 'admin',
					'callback' => 'masteriyo_is_show_review_notice',
				),
				'allow-usage-notice'    => array(
					'src'      => self::get_asset_url( '/assets/css/allow-usage-notice.css' ),
					'has_rtl'  => false,
					'context'  => 'admin',
					'callback' => function () {
						return masteriyo_show_usage_tracking_notice();
					},
				),
				'swiper'                => array(
					'src'      => plugins_url( 'libs/swiper/swiper-bundle.min.css', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ),
					'has_rtl'  => false,
					'context'  => 'public',
					'callback' => function () {
						return masteriyo_is_slider_enabled();
					},
				),
			)
		);
	}

	/**
	 * Get styles according to context.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Style/Script context (admin, public  none, etc.)
	 *
	 * @return array
	 */
	public static function get_styles( $context ) {
		// Set default values.
		$styles = array_map(
			function ( $style ) {
				return array_replace_recursive( self::get_default_style_options(), $style );
			},
			self::$styles
		);

		// Filter according to admin or public static context.
		$styles = array_filter(
			$styles,
			function ( $style ) use ( $context ) {
				return in_array( $context, (array) $style['context'], true );
			}
		);

		return $styles;
	}

	/**
	 * Get scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Script context. (admin, public,static  none).
	 *
	 * @return array
	 */
	public static function get_scripts( $context ) {
		// Set default values.
		$scripts = array_map(
			function ( $script ) {
				return array_replace_recursive( self::get_default_script_options(), $script );
			},
			self::$scripts
		);

		// Filter according to admin or public static context.
		$scripts = array_filter(
			$scripts,
			function ( $script ) use ( $context ) {
				return in_array( $context, (array) $script['context'], true );
			}
		);

		return $scripts;
	}

	/**
	 * Default script options.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_default_script_options() {
		/**
		 * Filters the default options for a script.
		 *
		 * @since 1.0.0
		 *
		 * @param array $options The default options.
		 */
		return apply_filters(
			'masteriyo_get_default_script_options',
			array(
				'src'           => '',
				'deps'          => array( 'jquery' ),
				'version'       => self::get_version(),
				'context'       => 'none',
				'in_footer'     => true,
				'register_only' => false,
				'callback'      => '',
				'type'          => '',
			)
		);
	}

	/**
	 * Default style options.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_default_style_options() {
		/**
		 * Filters the default options for a style.
		 *
		 * @since 1.0.0
		 *
		 * @param array $options The default options.
		 */
		return apply_filters(
			'masteriyo_get_default_style_options',
			array(
				'src'           => '',
				'deps'          => array(),
				'version'       => self::get_version(),
				'media'         => 'all',
				'has_rtl'       => false,
				'context'       => array( 'none' ),
				'in_footer'     => true,
				'register_only' => false,
				'callback'      => '',
			)
		);
	}

	/**
	 * Return asset URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Assets path.
	 *
	 * @return string
	 */
	private static function get_asset_url( $path ) {
		/**
		 * Filters asset URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The asset URL.
		 * @param string $path The relative path to the plugin directory.
		 */
		return apply_filters( 'masteriyo_get_asset_url', plugins_url( $path, Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ), $path );
	}

	/**
	 * Register a script for use.
	 *
	 * @since 1.0.0
	 *
	 * @uses   wp_register_script()
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = '', $in_footer = true ) {
		wp_register_script( "masteriyo-{$handle}", $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @since 1.0.0
	 *
	 * @uses   wp_enqueue_script()
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = '', $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
			wp_register_script( "masteriyo-{$handle}", $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( "masteriyo-{$handle}" );
	}

	/**
	 * Register a style for use.
	 *
	 *
	 * @since 1.0.0
	 *
	 * @uses   wp_register_style()
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '( orientation: portrait )' and '( max-width: 640px )'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = '', $media = 'all', $has_rtl = false ) {
		if ( ! isset( self::$styles[ $handle ] ) ) {
			self::$styles[ $handle ] = array(
				'src'     => $path,
				'deps'    => $deps,
				'version' => $version,
				'media'   => $media,
				'has_rtl' => $has_rtl,
			);
		}
		wp_register_style( "masteriyo-{$handle}", $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( "masteriyo-{$handle}", 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @since 1.0.0
	 *
	 * @uses   wp_enqueue_style()
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '( orientation: portrait )' and '( max-width: 640px )'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = '', $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( "masteriyo-{$handle}" );
	}

	/**
	 * Load public static scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public static function load_public_scripts_styles() {
		$scripts = self::get_scripts( 'public' );
		$styles  = self::get_styles( 'public' );

		foreach ( $scripts as $handle => $script ) {
			if ( true === (bool) $script['register_only'] ) {
				self::register_script( $handle, $script['src'], $script['deps'], $script['version'] );
				continue;
			}

			if ( empty( $script['callback'] ) ) {
				self::enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );
			} elseif ( is_callable( $script['callback'] ) && call_user_func_array( $script['callback'], array() ) ) {
				self::enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );
			}
		}

		foreach ( $styles as $handle => $style ) {
			if ( true === (bool) $style['register_only'] ) {
				self::register_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
				continue;
			}

			if ( empty( $style['callback'] ) ) {
				self::enqueue_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
			} elseif ( is_callable( $style['callback'] ) && call_user_func_array( $style['callback'], array() ) ) {
				self::enqueue_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
			}

			if ( isset( $style['type'] ) && 'module' === $style['type'] ) {
				add_filter(
					'script_loader_tag',
					function ( $tag, $tag_handle ) use ( $handle ) {
						if ( $tag_handle === $handle ) {
							return str_replace( 'src', 'type="module" src', $tag );
						}
						return $tag;
					},
					10,
					2
				);
			}
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'masteriyo-learn', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
			wp_set_script_translations( 'masteriyo-account', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
		}

		self::load_custom_inline_styles();
		self::load_block_styles();

		// Load dashicons in frontend.
		wp_enqueue_style( 'dashicons' );
	}

	/**
	 * Load inline custom colors.
	 *
	 * @since 1.0.4
	 */
	public static function load_custom_inline_styles() {
		$primary_color = masteriyo_get_setting( 'general.styling.primary_color' );

		// Bail early if the primary color is not set.
		if ( empty( trim( $primary_color ) ) ) {
			return;
		}

		$primary_light = masteriyo_color_luminance( $primary_color, 0.3 );
		$primary_dark  = masteriyo_color_luminance( $primary_color, -0.05 );

		$custom_css = "
      :root {
		--masteriyo-color-primary: $primary_color ;
        --masteriyo-color-primary-light: $primary_light;
        --masteriyo-color-primary-dark: $primary_dark;
        --masteriyo-color-btn-blue-hover: $primary_light;
      }
    ";
		wp_add_inline_style( 'masteriyo-public', $custom_css );

		// Fixes adminbar issue on learn page. @see https://wordpress.org/support/topic/course-lesson-page-mobile-responsiveness/
		$custom_css = '
      @media screen and (max-width: 600px){
        .masteriyo-interactive-page #wpadminbar {
          position: fixed;
        }
      }
    ';
		wp_add_inline_style( 'admin-bar', $custom_css );

		$button_color = masteriyo_get_setting( 'general.styling.button_color' ) ?? '';

		if ( trim( $button_color ) ) {
			$button_color_light = masteriyo_color_luminance( $button_color, 0.3 );

			$custom_css = "
				.masteriyo-btn.masteriyo-btn-primary {
					background-color: {$button_color};
					border-color: {$button_color}
				}

				:root {
					--masteriyo-color-btn-blue-hover: $button_color_light;
				}
			";
			wp_add_inline_style( 'masteriyo-public', $custom_css );
		}

		// Fixes adminbar issue on user account page.
		$custom_css = '
      @media screen and (max-width: 600px){
        .masteriyo-account-page #wpadminbar {
          position: fixed;
        }
      }
    ';
		wp_add_inline_style( 'admin-bar', $custom_css );
	}

	/**
	 * Load custom inline styles on admin page.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public static function load_custom_admin_inline_styles() {
		if ( ! current_user_can( 'manage_masteriyo_settings' ) ) {
			return;
		}

		$custom_css = '
        #toplevel_page_masteriyo ul.wp-submenu li:last-child a {
            color: #27e527 !important;
        }';

		wp_add_inline_style( 'masteriyo-dependencies', $custom_css );
	}

	/**
	 * Load block styles.
	 *
	 * @since 1.3.0
	 */
	public static function load_block_styles() {
		$post_id = get_the_ID();

		if ( empty( $post_id ) ) {
			return;
		}

		wp_add_inline_style( 'masteriyo-public', masteriyo_get_setting( 'general.widgets_css' ) );
		$css = get_post_meta( $post_id, '_masteriyo_css', true );

		if ( empty( $css ) ) {
			return;
		}
		wp_add_inline_style( 'masteriyo-public', $css );
	}

	/**
	 * Register block scripts and styles.
	 *
	 * @since 1.3.0
	 */
	public static function register_block_scripts_and_styles() {
		global $pagenow;

		if ( ( is_admin() && 'widgets.php' === $pagenow ) ) {
			return;
		}

		$scripts = self::get_scripts( 'blocks' );
		$styles  = self::get_styles( 'blocks' );

		foreach ( $scripts as $handle => $script ) {
			if ( true === (bool) $script['register_only'] ) {
				self::register_script( $handle, $script['src'], $script['deps'], $script['version'] );
				continue;
			}

			if ( empty( $script['callback'] ) ) {
				self::enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );
			} elseif ( is_callable( $script['callback'] ) && call_user_func_array( $script['callback'], array() ) ) {
				self::enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );
			}
		}

		foreach ( $styles as $handle => $style ) {
			if ( true === (bool) $style['register_only'] ) {
				self::register_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
				continue;
			}

			if ( empty( $style['callback'] ) ) {
				self::enqueue_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
			} elseif ( is_callable( $style['callback'] ) && call_user_func_array( $style['callback'], array() ) ) {
				self::enqueue_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
			}
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'masteriyo-blocks', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
		}
	}

	/**
	 * Localize block scripts.
	 *
	 * @since 1.3.0
	 */
	public static function localize_block_scripts() {
		global $pagenow;
		$args       = array(
			'order'   => 'ASC',
			'orderby' => 'name',
			'number'  => '',
		);
		$query      = new CourseCategoryQuery( $args );
		$categories = $query->get_categories();

		/**
		 * Filters the localized gutenberg block scripts.
		 *
		 * @since 1.3.0
		 *
		 * @param array $scripts The localized scripts.
		 */
		self::$localized_scripts = apply_filters(
			'masteriyo_localized_block_scripts',
			array(
				'blocks' => array(
					'name' => '_MASTERIYO_BLOCKS_DATA_',
					'data' => array(
						'categories'      => array_map(
							function ( $category ) {
								return $category->get_data();
							},
							$categories
						),
						'isWidgetsEditor' => 'widgets.php' === $pagenow ? 'yes' : 'no',
						'isCustomizer'    => 'customize.php' === $pagenow ? 'yes' : 'no',
					),
				),
			)
		);

		foreach ( self::$localized_scripts as $handle => $script ) {
			\wp_localize_script( "masteriyo-{$handle}", $script['name'], $script['data'] );
		}
	}

	/**
	 * Load public static scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public static function load_admin_scripts_styles() {
		$scripts = self::get_scripts( 'admin' );
		$styles  = self::get_styles( 'admin' );

		global $post;

		if ( masteriyo_is_admin_page() ) {
			wp_enqueue_style( 'wp-edit-post' );
			wp_enqueue_style( 'wp-format-library' );
			wp_tinymce_inline_scripts();

			wp_add_inline_style( 'wp-edit-post', 'html.wp-toolbar { background-color: #F7FAFC; }' );

			if ( isset( $post ) ) {
				wp_add_inline_script(
					'wp-blocks',
					sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $post ) ) ),
					'after'
				);
			}
		}

		foreach ( $scripts as $handle => $script ) {
			if ( true === (bool) $script['register_only'] ) {
				self::register_script( $handle, $script['src'], $script['deps'], $script['version'] );
				continue;
			}

			if ( empty( $script['callback'] ) || ( is_callable( $script['callback'] ) && call_user_func( $script['callback'] ) ) ) {
				self::enqueue_script( $handle, $script['src'], $script['deps'], $script['version'] );
			}
		}

		foreach ( $styles as $handle => $style ) {
			if ( true === (bool) $style['register_only'] ) {
				self::register_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
				continue;
			}

			if ( empty( $style['callback'] ) || ( is_callable( $style['callback'] ) && call_user_func( $style['callback'] ) ) ) {
				self::enqueue_style( $handle, $style['src'], $style['deps'], $style['version'], $style['media'], $style['has_rtl'] );
			}
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'masteriyo-admin', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
			wp_set_script_translations( 'masteriyo-backend', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
		}
	}


	/**
	 * Load admin localized scripts.
	 *
	 * @since 1.0.0
	 */


	public static function load_admin_localized_scripts() {
		$courses_page = get_post( masteriyo_get_page_id( 'courses' ) );
		$courses_slug = ! is_null( $courses_page ) ? $courses_page->post_name : '';

		$account_page = get_post( masteriyo_get_page_id( 'account' ) );
		$account_slug = ! is_null( $account_page ) ? $account_page->post_name : '';

		$checkout_page = get_post( masteriyo_get_page_id( 'checkout' ) );
		$checkout_slug = ! is_null( $checkout_page ) ? $checkout_page->post_name : '';

		$user = masteriyo_get_current_user();

		$allowed_plugin_slugs   = array(
			'everest-forms/everest-forms.php',
			'blockart-blocks/blockart.php',
			'user-registration/user-registration.php',
			'magazine-blocks/magazine-blocks.php',
		);
		$installed_plugin_slugs = array_keys( get_plugins() );
		$installed_theme_slugs  = array_keys( wp_get_themes() );
		$current_theme          = get_stylesheet();

		/**
		 * Filters the localized admin scripts.
		 *
		 * @since 1.0.0
		 *
		 * @param array $scripts The localized scripts.
		 */
		self::$localized_scripts = apply_filters(
			'masteriyo_localized_admin_scripts',
			array(
				'backend'               => array(
					'name' => '_MASTERIYO_',
					'data' => array(
						'version'                   => masteriyo_get_version(),
						'adminUrl'                  => get_admin_url(),
						'rootApiUrl'                => esc_url_raw( untrailingslashit( rest_url() ) ),
						'current_user_id'           => get_current_user_id(),
						'nonce'                     => wp_create_nonce( 'wp_rest' ),
						'review_notice_nonce'       => wp_create_nonce( 'masteriyo_review_notice_nonce' ),
						'allow_usage_notice_nonce'  => wp_create_nonce( 'masteriyo_allow_usage_notice_nonce' ),
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
						'home_url'                  => home_url(),
						'pageSlugs'                 => array(
							'courses'  => $courses_slug,
							'account'  => $account_slug,
							'checkout' => $checkout_slug,
						),
						'permalinkStructure'        => get_option( 'permalink_structure' ),
						'currency'                  => array(
							'code'               => masteriyo_get_currency(),
							'symbol'             => html_entity_decode( masteriyo_get_currency_symbol( masteriyo_get_currency() ) ),
							'position'           => masteriyo_get_setting( 'payments.currency.currency_position' ),
							'thousandsSeparator' => masteriyo_get_price_thousand_separator(),
						),
						'currencies'                => masteriyo_get_currencies_array(),
						'allPages'                  => masteriyo_get_all_pages(),
						'imageSizes'                => get_intermediate_image_sizes(),
						'countries'                 => masteriyo_get_countries(),
						'states'                    => masteriyo_get_states(),
						'show_review_notice'        => masteriyo_bool_to_string( masteriyo_is_show_review_notice() ),
						'show_allow_usage_notice'   => masteriyo_bool_to_string( masteriyo_show_usage_tracking_notice() ),
						'total_posts'               => count_user_posts( get_current_user_id() ),
						'settings'                  => masteriyo_get_setting( 'general' ),
						'current_user'              => $user ? masteriyo_array_except( $user->get_data(), array( 'password' ) ) : null,
						'canDeleteCourseCategories' => masteriyo_bool_to_string( current_user_can( 'delete_course_categories' ) ),
						'isOpenAIKeyFound'          => masteriyo_bool_to_string( masteriyo_get_setting( 'advance.openai.api_key' ) ? true : false ),
						'isOpenAIEnabled'           => masteriyo_bool_to_string( masteriyo_get_setting( 'advance.openai.enable' ) ? true : false ),
						'singleCourseTemplates'     => array(),
						'courseArchiveTemplates'    => array(),
						'onBoardingPageUrl'         => admin_url( 'index.php?page=masteriyo-onboard' ),
						'isCurrentUserAdmin'        => masteriyo_bool_to_string( masteriyo_is_current_user_admin() ),
						'editorStyles'              => function_exists( 'get_block_editor_theme_styles' ) && masteriyo_is_admin_page() ? get_block_editor_theme_styles() : (object) array(),
						'editorSettings'            => function_exists( 'get_block_editor_settings' ) && masteriyo_is_admin_page() ? get_block_editor_settings( array(), new \WP_Block_Editor_Context() ) : (object) array(),
						'defaultEditor'             => masteriyo_get_setting( 'advance.editor.default_editor' ),
						'dashboard'                 => array(
							'plugins' => array_reduce(
								$allowed_plugin_slugs,
								function ( $acc, $curr ) use ( $installed_plugin_slugs ) {
									if ( in_array( $curr, $installed_plugin_slugs, true ) ) {

										if ( is_plugin_active( $curr ) ) {
											$acc[ $curr ] = 'active';
										} else {
											$acc[ $curr ] = 'inactive';
										}
									} else {
										$acc[ $curr ] = 'not-installed';
									}
									return $acc;
								},
								array()
							),
							'themes'  => array(
								'elearning'        => strpos( $current_theme, 'elearning' ) !== false ? 'active' : (
								in_array( 'elearning', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
									),
								'online-education' => strpos( $current_theme, 'online-education' ) !== false ? 'active' : (
								in_array( 'online-education', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
								),
							),

						),
						'logo'                      => plugins_url( 'assets/img/logo.png', MASTERIYO_PLUGIN_FILE ),

					),
				),
				'ask-review'            => array(
					'name' => '_MASTERIYO_ASK_REVIEW_DATA_',
					'data' => array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'masteriyo_review_notice_nonce' ),
					),
				),
				'ask-usage-tracking'    => array(
					'name' => '_MASTERIYO_ASK_ALLOW_USAGE_DATA_',
					'data' => array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'masteriyo_allow_usage_notice_nonce' ),
					),
				),
			)
		);

		foreach ( self::$localized_scripts as $handle => $script ) {
			\wp_localize_script( "masteriyo-{$handle}", $script['name'], $script['data'] );
		}
	}

	/**
	 * Load public static localized scripts.
	 *
	 * @since 1.0.0
	 */
	public static function load_public_localized_scripts() {
		/**
		 * Filters the localized public scripts.
		 *
		 * @since 1.0.0
		 *
		 * @param array $scripts The localized scripts.
		 */
		self::$localized_scripts = apply_filters(
			'masteriyo_localized_public_scripts',
			array(
				'account'                 => array(
					'name' => '_MASTERIYO_',
					'data' => array(
						'rootApiUrl'              => esc_url_raw( untrailingslashit( rest_url() ) ),
						'current_user_id'         => get_current_user_id(),
						'nonce'                   => wp_create_nonce( 'wp_rest' ),
						'labels'                  => array(
							'save'                   => __( 'Save', 'learning-management-system' ),
							'saving'                 => __( 'Saving...', 'learning-management-system' ),
							'profile_update_success' => __( 'Your profile was updated successfully.', 'learning-management-system' ),
						),
						'currency'                => array(
							'code'     => masteriyo_get_currency(),
							'symbol'   => html_entity_decode( masteriyo_get_currency_symbol( masteriyo_get_currency() ) ),
							'position' => masteriyo_get_setting( 'payments.currency.currency_position' ),
						),
						'urls'                    => array(
							'logout'       => wp_logout_url( get_home_url() ),
							'account'      => masteriyo_get_page_permalink( 'account' ),
							'courses'      => masteriyo_get_page_permalink( 'courses' ),
							'home'         => home_url(),
							'myCourses'    => admin_url( 'admin.php?page=masteriyo#/courses' ),
							'addNewCourse' => admin_url( 'admin.php?page=masteriyo#/courses/add-new-course' ),
							'webhooks'     => admin_url( 'admin.php?page=masteriyo#/webhooks' ),

						),
						'isCurrentUserStudent'    => masteriyo_bool_to_string( masteriyo_is_current_user_student() ),
						'isCurrentUserInstructor' => masteriyo_bool_to_string( masteriyo_is_current_user_instructor() ),
						'isInstructorActive'      => masteriyo_bool_to_string( masteriyo_is_instructor_active() ),
						'isUserEmailVerified'     => masteriyo_bool_to_string( masteriyo_is_user_email_verified() ),
						'settings'                => masteriyo_get_setting( 'general' ),
						'pagesVisibility'         => masteriyo_get_setting( 'accounts_page' ),
						'isCurrentUserAdmin'      => masteriyo_bool_to_string( masteriyo_is_current_user_admin() ),
						'PasswordProtectedNonce'  => wp_create_nonce( 'masteriyo_course_password_protected_nonce' ),
						'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
						'isQRLoginEnabled'        => masteriyo_bool_to_string( masteriyo_is_qr_login_enabled() ),
						'QRLoginAttentionMessage' => masteriyo_get_setting( 'authorization.qr_login.attention_message' ),
						'showHeaderFooter'        => masteriyo_bool_to_string( masteriyo_get_setting( 'accounts_page.display.layout.enable_header_footer' ) ),
					),
				),
				'login-form'              => array(
					'name' => '_MASTERIYO_',
					'data' => array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'masteriyo_login_nonce' ),
						'labels'   => array(
							'sign_in'    => __( 'Sign In', 'learning-management-system' ),
							'signing_in' => __( 'Signing In...', 'learning-management-system' ),
						),
					),
				),
				'single-course'           => array(
					'name' => 'masteriyo_data',
					'data' => array(
						'rootApiUrl'               => esc_url_raw( rest_url() ),
						'nonce'                    => wp_create_nonce( 'wp_rest' ),
						'password_protected_nonce' => wp_create_nonce( 'masteriyo_course_password_protected_nonce' ),
						'reviews_listing_nonce'    => wp_create_nonce( 'masteriyo_course_reviews_infinite_loading_nonce' ),
						'rating_indicator_markup'  => masteriyo_get_rating_indicators_markup( 'masteriyo-rating-input-icon' ),
						'max_course_rating'        => masteriyo_get_max_course_rating(),
						'review_deleted_notice'    => masteriyo_get_template_html( 'notices/review-deleted.php' ),
						'retake_url'               => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_retake_url() : '',
						'labels'                   => array(
							'type_confirm'             => __( 'Type CONFIRM to proceed.', 'learning-management-system' ),
							'try_again'                => __( 'Try again', 'learning-management-system' ),
							'submit'                   => __( 'Submit', 'learning-management-system' ),
							'update'                   => __( 'Update', 'learning-management-system' ),
							'delete'                   => __( 'Delete', 'learning-management-system' ),
							'submitting'               => __( 'Submitting...', 'learning-management-system' ),
							'deleting'                 => __( 'Deleting...', 'learning-management-system' ),
							'reply_to'                 => __( 'Reply to', 'learning-management-system' ),
							'edit_reply'               => __( 'Edit reply', 'learning-management-system' ),
							'edit_review'              => __( 'Edit review', 'learning-management-system' ),
							'submit_success'           => masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ? __( 'Your review has been submitted successfully.', 'learning-management-system' ) : __( 'Your review has been submitted and is awaiting approval by the instructor.', 'learning-management-system' ),
							'update_success'           => __( 'Updated successfully.', 'learning-management-system' ),
							'delete_success'           => __( 'Deleted successfully.', 'learning-management-system' ),
							'expand_all'               => __( 'Expand All', 'learning-management-system' ),
							'collapse_all'             => __( 'Collapse All', 'learning-management-system' ),
							'loading'                  => __( 'Loading...', 'learning-management-system' ),
							'load_more_reviews_failed' => __( 'Failed to load more reviews', 'learning-management-system' ),
							'see_more_reviews'         => __( 'See more reviews', 'learning-management-system' ),
							'password_not_empty'       => __( 'Please enter a password.', 'learning-management-system' ),
						),
						'ajaxURL'                  => admin_url( 'admin-ajax.php' ),
						'course_id'                => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_id() : 0,
						'course_review_pages'      => isset( $GLOBALS['course'] ) ? masteriyo_get_course_reviews_infinite_loading_pages_count( $GLOBALS['course'] ) : 0,
						'review_form_enabled'      => isset( $GLOBALS['course'] ) ? masteriyo_bool_to_string( masteriyo_can_user_review_course( $GLOBALS['course'] ) ) : 0,
						'current_user_logged_in'   => masteriyo_bool_to_string( is_user_logged_in() ),
						'course_reviews_count'     => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_review_count() : 0,
					),
				),
				'masteriyo-single-course' => array(
					'name' => 'masteriyo_data',
					'data' => array(
						'rootApiUrl'               => esc_url_raw( rest_url() ),
						'nonce'                    => wp_create_nonce( 'wp_rest' ),
						'password_protected_nonce' => wp_create_nonce( 'masteriyo_course_password_protected_nonce' ),
						'reviews_listing_nonce'    => wp_create_nonce( 'masteriyo_course_reviews_infinite_loading_nonce' ),
						'rating_indicator_markup'  => masteriyo_get_rating_indicators_markup( 'masteriyo-rating-input-icon' ),
						'max_course_rating'        => masteriyo_get_max_course_rating(),
						'review_deleted_notice'    => masteriyo_get_template_html( 'notices/review-deleted.php' ),
						'retake_url'               => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_retake_url() : '',
						'labels'                   => array(
							'type_confirm'             => __( 'Type CONFIRM to proceed.', 'learning-management-system' ),
							'try_again'                => __( 'Try again', 'learning-management-system' ),
							'submit'                   => __( 'Submit', 'learning-management-system' ),
							'update'                   => __( 'Update', 'learning-management-system' ),
							'delete'                   => __( 'Delete', 'learning-management-system' ),
							'submitting'               => __( 'Submitting...', 'learning-management-system' ),
							'deleting'                 => __( 'Deleting...', 'learning-management-system' ),
							'reply_to'                 => __( 'Reply to', 'learning-management-system' ),
							'edit_reply'               => __( 'Edit reply', 'learning-management-system' ),
							'edit_review'              => __( 'Edit review', 'learning-management-system' ),
							'submit_success'           => masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ? __( 'Your review has been submitted successfully.', 'learning-management-system' ) : __( 'Your review has been submitted and is awaiting approval by the instructor.', 'learning-management-system' ),
							'update_success'           => __( 'Updated successfully.', 'learning-management-system' ),
							'delete_success'           => __( 'Deleted successfully.', 'learning-management-system' ),
							'expand_all'               => __( 'Expand All', 'learning-management-system' ),
							'collapse_all'             => __( 'Collapse All', 'learning-management-system' ),
							'loading'                  => __( 'Loading...', 'learning-management-system' ),
							'load_more_reviews_failed' => __( 'Failed to load more reviews', 'learning-management-system' ),
							'see_more_reviews'         => __( 'See more reviews', 'learning-management-system' ),
							'password_not_empty'       => __( 'Please enter a password.', 'learning-management-system' ),
						),
						'ajaxURL'                  => admin_url( 'admin-ajax.php' ),
						'course_id'                => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_id() : 0,
						'course_review_pages'      => isset( $GLOBALS['course'] ) ? masteriyo_get_course_reviews_infinite_loading_pages_count( $GLOBALS['course'] ) : 0,
						'review_form_enabled'      => isset( $GLOBALS['course'] ) ? masteriyo_bool_to_string( masteriyo_can_user_review_course( $GLOBALS['course'] ) ) : 0,
						'current_user_logged_in'   => masteriyo_bool_to_string( is_user_logged_in() ),
						'course_reviews_count'     => ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course']->get_review_count() : 0,
					),
				),
				'checkout'                => array(
					'name' => '_MASTERIYO_CHECKOUT_',
					'data' => array(
						'ajaxURL'             => admin_url( 'admin-ajax.php' ),
						'checkoutURL'         => add_query_arg( array( 'action' => 'masteriyo_checkout' ), admin_url( 'admin-ajax.php' ) ),
						'i18n_checkout_error' => esc_html__( 'Error processing checkout. Please try again.', 'learning-management-system' ),
						'is_checkout'         => true,
						'mto_ajax_url'        => '/?masteriyo-ajax=%%endpoint%%',
						'countries'           => array_map( 'html_entity_decode', masteriyo( 'countries' )->get_countries() ),
						'states'              => array_filter( masteriyo( 'countries' )->get_states() ),
						'payment_sheet_nonce' => wp_create_nonce( 'masteriyo-upload-payment-sheet' ),
					),
				),
				'learn'                   => array(
					'name' => '_MASTERIYO_',
					'data' => array(
						'rootApiUrl'                      => esc_url_raw( rest_url() ),
						'nonce'                           => wp_create_nonce( 'wp_rest' ),
						'urls'                            => array(
							'logout'  => wp_logout_url( get_home_url() ),
							'account' => masteriyo_get_page_permalink( 'account' ),
							'courses' => masteriyo_get_page_permalink( 'courses' ),
							'home'    => home_url(),
						),
						'logo'                            => masteriyo_get_setting( 'learn_page.general.logo_id' ) ? masteriyo_get_learn_page_logo_data() : masteriyo_get_custom_logo_data(),
						'siteTitle'                       => get_bloginfo( 'name' ),
						'userAvatar'                      => is_user_logged_in() ? masteriyo_get_current_user()->profile_image_url() : '',
						'qaEnable'                        => masteriyo_get_setting( 'learn_page.display.enable_questions_answers' ),
						'isUserLoggedIn'                  => is_user_logged_in(),
						'settings'                        => masteriyo_get_setting( 'general' ),
						'current_user_id'                 => get_current_user_id(),
						'autoLoadNextContent'             => masteriyo_bool_to_string( masteriyo_get_setting( 'learn_page.general.auto_load_next_content' ) ),
						'hideCompleteQuizButtonOnFail'    => masteriyo_bool_to_string( masteriyo_get_setting( 'quiz.display.quiz_completion_button' ) ),
						'isCurrentUserStudent'            => masteriyo_bool_to_string( masteriyo_is_current_user_student() ),
						'quizReviewButtonVisibility'      => masteriyo_get_setting( 'quiz.display.quiz_review_visibility' ),
						'enableQuizPreviouslyVisitedPage' => masteriyo_bool_to_string( masteriyo_get_setting( 'quiz.display.enable_quiz_previously_visited_page' ) ),
						'quizAccess'                      => masteriyo_get_setting( 'quiz.general.quiz_access' ),
						'automaticallySubmitQuiz'         => masteriyo_bool_to_string( masteriyo_get_setting( 'quiz.general.automatically_submit_quiz' ) ),
						'showSidebarInitially'            => masteriyo_bool_to_string( masteriyo_get_setting( 'learn_page.display.show_sidebar_initially' ) ),
						'enableFocusMode'                 => masteriyo_bool_to_string( masteriyo_get_setting( 'learn_page.display.enable_focus_mode' ) ),
						'showSidebar'                     => masteriyo_bool_to_string( masteriyo_get_setting( 'learn_page.display.show_sidebar' ) ),
						'showHeader'                      => masteriyo_bool_to_string( masteriyo_get_setting( 'learn_page.display.show_header' ) ),
						'reviewOptionIsVisible'           => masteriyo_bool_to_string( masteriyo_is_reviews_enabled_in_learn_page() ),
						'isCurrentUserAdmin'              => masteriyo_bool_to_string( masteriyo_is_current_user_admin() ),
						'isAutoApproveReviews'            => masteriyo_bool_to_string( masteriyo_get_setting( 'single_course.display.auto_approve_reviews' ) ),
					),
				),
				'courses'                 => array(
					'name' => 'masteriyo_data',
					'data' => array(
						'ajaxURL'                  => admin_url( 'admin-ajax.php' ),
						'password_protected_nonce' => wp_create_nonce( 'masteriyo_course_password_protected_nonce' ),
						'labels'                   => array(
							'password_not_empty' => __( 'Please enter a password.', 'learning-management-system' ),
						),
					),
				),
			)
		);

		if ( masteriyo_is_categories_slider_enabled() ) {
			$attributes = masteriyo_get_shortcode_attributes( 'masteriyo_course_categories' );

			self::$localized_scripts['categories-slider'] = array(
				'name' => '_MASTERIYO_CATEGORIES_SLIDER_DATA_',
				'data' => $attributes,
			);
		}

		foreach ( self::$localized_scripts as $handle => $script ) {
			\wp_localize_script( "masteriyo-{$handle}", $script['name'], $script['data'] );
		}
	}

	/**
	 * Remove styles in learn page.
	 *
	 * @since 1.0.0
	 * @since 1.5.37 Renamed from 'remove_styles_scripts_in_learn_page' to 'remove_styles_from_learn_page'
	 *
	 * @return void
	 */
	public static function remove_styles_from_learn_page() {
		global $wp_styles;

		// Bail early if the page is not learn.
		if ( ! masteriyo_is_learn_page() ) {
			return;
		}

		$whitelist = self::get_whitelist_styles_in_learn_page();

		// Dequeue blacklist styles
		foreach ( $wp_styles->registered as $style ) {
			if ( ! in_array( $style->handle, $whitelist, true ) ) {
				wp_deregister_style( $style->handle );
			}
		}

		foreach ( $wp_styles->queue as $handle ) {
			if ( ! in_array( $handle, $whitelist, true ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}

	/**
	 * Remove styles in account page.
	 *
	 * @since 1.13.3
	 *
	 * @return void
	 */
	public static function remove_styles_from_account_page() {
		global $wp_styles;

		// Bail early if the page is not learn.
		if ( ! is_user_logged_in() || ! masteriyo_is_account_page() ) {
			return;
		}

		$show_header_footer = masteriyo_get_setting( 'accounts_page.display.layout.enable_header_footer' );

		// Return if header footer is enabled.
		if ( $show_header_footer ) {
			return;
		}

		#TODO make whitelist styles for account page separately.
		$whitelist = self::get_whitelist_styles_in_learn_page();

		// Dequeue blacklist styles
		foreach ( $wp_styles->registered as $style ) {
			if ( ! in_array( $style->handle, $whitelist, true ) ) {
				wp_deregister_style( $style->handle );
			}
		}

		foreach ( $wp_styles->queue as $handle ) {
			if ( ! in_array( $handle, $whitelist, true ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}

	/**
	 * Remove scripts on the masteriyo admin page.
	 *
	 * @since 1.16.0
	 *
	 * @return void
	*/
	public static function remove_scripts_from_admin_page() {

		// Bail early if the user is not logged in or not on the admin page.
		if ( ! is_user_logged_in() || ! masteriyo_is_admin_page() ) {
			return;
		}

		// Deregister and dequeue blacklisted scripts.
		$blacklist_scripts = self::get_blacklist_scripts_in_masteriyo_admin_page();
		foreach ( $blacklist_scripts as $handle ) {
			wp_deregister_script( $handle );
			wp_dequeue_script( $handle );
		}
	}


	/**
	 * Get the list of blacklist scripts in masteriyo admin page.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function get_blacklist_scripts_in_masteriyo_admin_page() {
		return array_unique(
			apply_filters(
				'get_blacklist_scripts_in_admin_page',
				array(
					'mwai',
					'editor-check', // Learnpress conflicting our backend pages.
				)
			)
		);
	}


	/**
	 * Get the list of whitelist styles in learn page.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_whitelist_styles_in_learn_page() {
		/**
			* Filters the whitelisted styles for learn page.
			*
			* @since 1.5.32
			*
			* @param array $styles List of style handles.
			*/
		return array_unique(
			apply_filters(
				'masteriyo_whitelist_styles_learn_page',
				array(
					'masteriyo-learn',
					'masteriyo-dependencies',
					'colors',
					'common',
					'forms',
					'admin-menu',
					'dashboard',
					'list-tables',
					'edit',
					'revisions',
					'media',
					'themes',
					'about',
					'nav-menus',
					'widgets',
					'site-icon',
					'l10n',
					'code-editor',
					'site-health',
					'wp-admin',
					'login',
					'install',
					'wp-color-picker',
					'customize-controls',
					'customize-widgets',
					'customize-nav-menus',
					'buttons',
					'dashicons',
					'admin-bar',
					'wp-auth-check',
					'editor-buttons',
					'mediea-views',
					'wp-pointer',
					'customize-preview',
					'wp-embed-template-ie',
					'imgareaselect',
					'wp-jquery-ui-dialog',
					'mediaelement',
					'wp-mediaelement',
					'thickbox',
					'wp-codemirror',
					'deprecated-media',
					'farbtastic',
					'jcrop',
					'colors-fresh',
					'open-sans',
					'wp-editor-font',
					'wp-block-library-theme',
					'wp-edit-blocks',
					'wp-block-editor',
					'wp-block-library',
					'wp-block-directory',
					'wp-components',
					'wp-editor',
					'wp-format-library',
					'wp-list-resuable-blocks',
					'wp-nux',
					'wp-block-library-theme',
					'wp-block-library',
					// Support translatepress plugin language switch floater.
					'trp-language-switcher-style',
					'trp-floater-language-switcher-style',
					'everest-forms-general',
					'user-registration-general',
					'contact-form-7',
					'query-monitor',

					// Rank Math SEO plugin.
					'rank-math',
					// Elementor plugin.
					'e-popup',
					'elementor-common',
					'elementor-frontend',
					//Font Awesome 5.
					'font-awesome-5-all',
					// Royal Addons for Elementor.
					'wpr-addons-css',
					'wpr-text-animations-css',
					// MonsterInsights.
					'monsterinsights-style-frontend.css',
					'monsterinsights-style-vendor.css',
				)
			)
		);
	}
}
