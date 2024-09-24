<?php
/**
 * Masteriyo WishList addon main class.
 *
 * @package Masteriyo\Addons\WishList
 *
 * @since 1.12.2
 */

namespace Masteriyo\Addons\WishList;

use Masteriyo\Addons\WishList\PostType\WishlistItem;
use Masteriyo\Addons\WishList\AjaxHandlers\AddCourseToWishlistAjaxHandler;
use Masteriyo\Addons\WishList\AjaxHandlers\RemoveCourseFromWishlistAjaxHandler;
use Masteriyo\Addons\WishList\RestApi\Controllers\Version1\WishListItemsController;
use Masteriyo\Traits\Singleton;
use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

class WishListAddon {

	use Singleton;

	/**
	 * Initialize the application.
	 *
	 * @since 1.12.2
	 */
	public function init() {
		$this->init_hooks();

		$elementor_integration = new ElementorIntegration();
		$elementor_integration->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.12.2
	 */
	public function init_hooks() {
		add_action( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_routes' ), 10 );
		add_filter( 'masteriyo_ajax_handlers', array( $this, 'register_ajax_handlers' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_post_types' ), 10, 1 );
		add_action( 'masteriyo_after_single_course_title_text', array( $this, 'render_wishlist_toggle_button' ), 10 );
		add_action( 'masteriyo_after_course_archive_title_link', array( $this, 'render_wishlist_toggle_button' ), 10 );
		add_action( 'masteriyo_after_layout_1_course_title', array( $this, 'render_wishlist_toggle_button' ), 10 );
		add_action( 'masteriyo_after_layout_2_course_title', array( $this, 'render_wishlist_toggle_button' ), 10 );
		add_action( 'masteriyo_after_layout_1_single_course_title', array( $this, 'render_wishlist_toggle_button' ), 1 );
		add_action( 'masteriyo_before_delete_course', array( $this, 'on_course_update' ), 10, 2 );
		add_action( 'masteriyo_before_trash_course', array( $this, 'on_course_update' ), 10, 2 );
	}

	/**
	 * Render wishlist toggle button beside the course title.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Models\Course $course
	 */
	public function render_wishlist_toggle_button( $course ) {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$added_to_wishlist = masteriyo_current_user_has_course_in_wishlist( $course->get_id() );

		$class = array(
			'masteriyo-icon-svg',
			'masteriyo-wishlist-toggle',
		);

		$layout = masteriyo_get_setting( 'course_archive.layout' ) ?? 'default';

		if ( 'layout1' === $layout ) {
			$class[] = 'masteriyo-archive-card__image-favorite-icon';
		} elseif ( 'layout2' === $layout ) {
			$class[] = 'masteriyo-course-card__favorite-icon';
		} else {
			$class[] = 'masteriyo-single-course__favorite-icon';
		}

		if ( $added_to_wishlist ) {
			$class[] = 'active';
		}

		$class = join( ' ', $class );

		/**
		 * Template arguments.
		 */
		$title  = $added_to_wishlist ? __( 'Remove from Wishlist', 'learning-management-system' ) : __( 'Add to Wishlist', 'learning-management-system' );
		$action = $added_to_wishlist ? 'masteriyo_remove_course_from_wishlist' : 'masteriyo_add_course_to_wishlist';

		include MASTERIYO_WISHLIST_ADDON_DIR . '/templates/wishlist-toggle.php';
	}

	/**
	 * Register rest routes.
	 *
	 * @since 1.12.2
	 */
	public function register_routes( $namespaces ) {
		$namespaces['masteriyo/v1']['wishlist-items'] = WishListItemsController::class;

		return $namespaces;
	}

	/**
	 * Register ajax handlers.
	 *
	 * @since 1.12.2
	 *
	 * @param string[] $handlers Ajax handler classes.
	 *
	 * @return array
	 */
	public function register_ajax_handlers( $handlers ) {

		$handlers[] = AddCourseToWishlistAjaxHandler::class;
		$handlers[] = RemoveCourseFromWishlistAjaxHandler::class;

		return $handlers;
	}

	/**
	 * Register admin menus.
	 *
	 * @since 1.12.2
	 */
	public function init_admin_menus() {
		// Bail early if the admin menus is not visible.
		if ( ! masteriyo_is_admin_menus_visible() ) {
			return true;
		}

		add_submenu_page(
			'masteriyo',
			esc_html__( 'Wishlists', 'learning-management-system' ),
			esc_html__( 'Wishlists', 'learning-management-system' ),
			'manage_masteriyo_settings',
			'masteriyo#/wishlist-items',
			array( $this, 'display_main_page' )
		);
	}

	/**
	 * Display main page.
	 *
	 * @since 1.12.2
	 */
	public static function display_main_page() {
		masteriyo_get_template( 'masteriyo.php' );
	}

	/**
	 * Enqueue necessary scripts.
	 *
	 * @since 1.12.2
	 *
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		$scripts['wishlist'] = array(
			'src'      => plugin_dir_url( MASTERIYO_WISHLIST_ADDON_FILE ) . '/assets/js/frontend/wishlist.js',
			'deps'     => array( 'jquery', 'wp-i18n' ),
			'context'  => 'public',
			'callback' => function() {
				global $post;

				return masteriyo_is_courses_page() || masteriyo_is_single_course_page() || ( $post && has_shortcode( $post->post_content, 'masteriyo_courses' ) ) || ( new Addons() )->is_active( 'oxygen-integration' );
			},
		);

		return $scripts;
	}

	/**
	 * Localize public scripts.
	 *
	 * @since 1.12.2
	 *
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function localize_public_scripts( $scripts ) {
		$scripts['wishlist'] = array(
			'name' => '_MASTERIYO_WISHLIST_ADDON_',
			'data' => array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonces'   => array(
					'wishlist_toggle' => wp_create_nonce( 'masteriyo_wishlist_toggle_nonce' ),
				),
				'labels'   => array(
					'add_to_wishlist'      => __( 'Add to Wishlist', 'learning-management-system' ),
					'remove_from_wishlist' => __( 'Remove from Wishlist', 'learning-management-system' ),
				),
			),
		);

		return $scripts;
	}

	/**
	 * Handle course update hook.
	 *
	 * Sync wishlist items with the updated course.
	 *
	 * @since 1.12.2
	 *
	 * @param integer $course_id Course ID.
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public function on_course_update( $course_id, $course ) {
		masteriyo_sync_wishlist_items_with_course( $course );
	}

	/**
	 * Register post types.
	 *
	 * @since 1.12.2
	 *
	 * @param string[] $post_types Post type classes.
	 *
	 * @return string[]
	 */
	public function register_post_types( $post_types ) {
		$post_types['wishlist-item'] = WishlistItem::class;
		return $post_types;
	}
}
