<?php
/**
 * Ajax.
 *
 * @package Masteriyo
 *
 * @since 1.0.0
 */

namespace Masteriyo;

use Masteriyo\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax class.
 *
 * @class Masteriyo\Ajax
 */

class AdminMenu {

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		self::init_hooks();
	}

	/**
	 * Initialize admin menus.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init_menus() {
		// Bail early if the admin menus is not visible.
		if ( ! masteriyo_is_admin_menus_visible() ) {
			return true;
		}

		// phpcs:disable
		if ( isset( $_GET['page'] ) && 'masteriyo' === $_GET['page'] ) {
			$dashicon = 'data:image/svg+xml;base64,' . base64_encode( masteriyo_get_svg( 'dashicon-white' ) );

			/**
			 * Filter active admin menu icon.
			 *
			 * @since 1.5.7
			 */
			$dashicon = apply_filters( 'masteriyo_active_admin_menu_icon', $dashicon );
		} else {
			$dashicon = 'data:image/svg+xml;base64,' . base64_encode( masteriyo_get_svg( 'dashicon-grey' ) );

			/**
			 * Filter inactive admin menu icon.
			 *
			 * @since 1.5.7
			 */
			$dashicon = apply_filters( 'masteriyo_inactive_admin_menu_icon', $dashicon );
		}
		// phpcs:enable

		/**
		 * Filter admin menu title.
		 *
		 * @since 1.5.7
		 */
		$admin_menu_title = apply_filters( 'masteriyo_admin_menu_title', __( 'Masteriyo', 'learning-management-system' ) );

		add_menu_page(
			$admin_menu_title,
			$admin_menu_title,
			'edit_courses',
			'masteriyo',
			array( __CLASS__, 'display_main_page' ),
			$dashicon,
			3
		);

		self::register_submenus();

		remove_submenu_page( 'masteriyo', 'masteriyo' );
	}

	/**
	 * Register submenus.
	 *
	 * @since 1.5.12
	 *
	 * @return void
	 */
	public static function register_submenus() {
		$submenus = self::get_submenus();

		uasort(
			$submenus,
			function( $a, $b ) {
				if ( $a['position'] === $b['position'] ) {
					return 0;
				}

				return ( $a['position'] < $b['position'] ) ? -1 : 1;
			}
		);

		foreach ( $submenus as $slug => $submenu ) {
			$menu_slug = "masteriyo#/{$slug}";

			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$menu_slug,
				$submenu['callback'],
				$submenu['position']
			);

		}
	}

	/**
	 * Returns an array of submenus.
	 *
	 * @since 1.5.12
	 *
	 * @return array
	 */
	public static function get_submenus() {
		$submenus = array(
			'dashboard'          => array(
				'page_title' => __( 'Dashboard', 'learning-management-system' ),
				'menu_title' => __( 'Dashboard', 'learning-management-system' ),
				'position'   => 0,
			),

			'analytics'          => array(
				'page_title' => __( 'Analytics', 'learning-management-system' ),
				'menu_title' => __( 'Analytics', 'learning-management-system' ),
				'position'   => 5,
			),
			'courses'            => array(
				'page_title' => __( 'Courses', 'learning-management-system' ),
				'menu_title' => __( 'Courses', 'learning-management-system' ),
				'capability' => 'edit_courses',
				'position'   => 10,
			),
			'courses/categories' => array(
				'page_title' => __( 'Categories', 'learning-management-system' ),
				'menu_title' => __( 'Categories', 'learning-management-system' ),
				'capability' => 'manage_course_categories',
				'position'   => 15,
			),
			'orders'             => array(
				'page_title' => __( 'Orders', 'learning-management-system' ),
				'menu_title' => __( 'Orders', 'learning-management-system' ),
				'position'   => 20,
			),
			'quiz-attempts'      => array(
				'page_title' => __( 'Quiz Attempts', 'learning-management-system' ),
				'menu_title' => __( 'Quiz Attempts', 'learning-management-system' ),
				'capability' => 'edit_courses',
				'position'   => 30,
			),
			'users/students'     => array(
				'page_title' => __( 'Users', 'learning-management-system' ),
				'menu_title' => __( 'Users', 'learning-management-system' ),
				'position'   => 35,
			),
			'reviews'            => array(
				'page_title' => __( 'Reviews', 'learning-management-system' ),
				'menu_title' => __( 'Reviews', 'learning-management-system' ),
				'capability' => 'edit_courses',
				'position'   => 40,
			),
			'question-answers'   => array(
				'page_title' => __( 'Question & Answers', 'learning-management-system' ),
				'menu_title' => __( 'Question & Answers', 'learning-management-system' ),
				'capability' => 'edit_courses',
				'position'   => 45,
			),
			'webhooks'           => array(
				'page_title' => __( 'Webhooks', 'learning-management-system' ),
				'menu_title' => __( 'Webhooks', 'learning-management-system' ),
				'position'   => 50,
				'capability' => 'edit_courses',
			),
			'settings'           => array(
				'page_title' => __( 'Settings', 'learning-management-system' ),
				'menu_title' => __( 'Settings', 'learning-management-system' ),
				'position'   => 55,
			),
			'tools'              => array(
				'page_title' => __( 'Tools', 'learning-management-system' ),
				'menu_title' => __( 'Tools', 'learning-management-system' ),
				'capability' => 'edit_courses',
				'position'   => 60,
			),
		);

		/**
		 * Filter admin submenus.
		 *
		 * @since 1.5.12
		 */
		$submenus = apply_filters( 'masteriyo_admin_submenus', $submenus );

		$submenus = array_map(
			function( $submenu ) {
				return wp_parse_args(
					$submenu,
					array(
						'page_title'  => '',
						'menu_title'  => '',
						'parent_slug' => 'masteriyo',
						'capability'  => 'manage_masteriyo_settings',
						'position'    => 1000,
						'callback'    => array( __CLASS__, 'display_main_page' ),
					)
				);
			},
			$submenus
		);

		return $submenus;
	}

	/**
	 * Add some divider css.
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public static function admin_menu_css() {
		echo '<style>
			#toplevel_page_masteriyo li {
				clear: both;
			}

			#toplevel_page_masteriyo li:not(:last-child) a[href^="admin.php?page=masteriyo#/tools"]:after {
				border-bottom: 1px solid hsla(0,0%,100%,.2);
				display: block;
				float: left;
				margin: 13px -15px 8px;
				content: "";
				width: calc(100% + 26px);
			}
			#toplevel_page_masteriyo li:not(:last-child) a[href^="admin.php?page=masteriyo#/tools"]:after {
				border-bottom: 1px solid hsla(0,0%,100%,.2);
				display: block;
				float: left;
				margin: 13px -15px 8px;
				content: "";
				width: calc(100% + 26px);
			}
			#toplevel_page_masteriyo li:not(:last-child) a[href^="admin.php?page=masteriyo#/add-ons"] {
            	color: #27e527 !important;
        	};
		</style>';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function init_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'init_menus' ), 10 );
		add_action( 'admin_head', array( __CLASS__, 'admin_menu_css' ) );
	}

	/**
	 * Display main page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function display_main_page() {
		require_once Constants::get( 'MASTERIYO_PLUGIN_DIR' ) . '/templates/masteriyo.php';
	}
}
