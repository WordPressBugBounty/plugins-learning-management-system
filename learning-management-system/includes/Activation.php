<?php
/**
 * Activation class.
 *
 * @since 1.0.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


class Activation {

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		register_activation_hook( Constants::get( 'MASTERIYO_PLUGIN_FILE' ), array( __CLASS__, 'on_activate' ) );
	}

	/**
	 * Callback for plugin activation hook.
	 *
	 * @since 1.0.0
	 */
	public static function on_activate() {
		self::create_roles();
		self::assign_core_capabilities_to_admin();
		self::attach_placeholder_image();
		self::create_log_files();

		/**
		 * Fire after masteriyo is activated.
		 *
		 * @since 1.5.37
		 */
		do_action( 'masteriyo_activation' );
	}

	/**
	 * Create roles.
	 *
	 * @since 1.5.37
	 */
	private static function create_roles() {
		foreach ( Roles::get_all() as $role_slug => $role ) {
			add_role( $role_slug, $role['display_name'], $role['capabilities'] );
		}
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 *
	 * @since 1.0.0
	 */
	public static function create_pages() {
		/**
		 * Filters the list of pages that will be created on plugin activation.
		 *
		 * @since 1.0.0
		 *
		 * @param array[] $pages List of pages.
		 */
		$pages = apply_filters(
			'masteriyo_create_pages',
			array(
				'courses'                 => array(
					'name'         => 'courses',
					'title'        => 'Courses',
					'content'      => '',
					'setting_name' => 'courses_page_id',
				),
				'account'                 => array(
					'name'         => 'account',
					'title'        => 'Account',
					'content'      => '<!-- wp:shortcode -->[masteriyo_account]<!-- /wp:shortcode -->',
					'setting_name' => 'account_page_id',
				),
				'checkout'                => array(
					'name'         => 'checkout',
					'title'        => 'Checkout',
					'content'      => '<!-- wp:shortcode -->[masteriyo_checkout]<!-- /wp:shortcode -->',
					'setting_name' => 'checkout_page_id',
				),
				'learn'                   => array(
					'name'         => 'learn',
					'title'        => 'Learn',
					'content'      => '',
					'setting_name' => 'learn_page_id',
				),
				'instructor-registration' => array(
					'name'         => 'instructor-registration',
					'title'        => 'Instructor Registration',
					'content'      => '<!-- wp:shortcode -->[masteriyo_instructor_registration]<!-- /wp:shortcode -->',
					'setting_name' => 'instructor_registration_page_id',
				),
				'instructors-list'        => array(
					'name'         => 'instructors-list',
					'title'        => 'Instructors List',
					'content'      => '<!-- wp:shortcode -->[masteriyo_instructors_list]<!-- /wp:shortcode -->',
					'setting_name' => 'instructors_list_page_id',
				),
			)
		);

		foreach ( $pages as $key => $page ) {
			$setting_name = $page['setting_name'];
			$post_id      = masteriyo_get_setting( "general.pages.{$setting_name}" );
			$post         = get_post( $post_id );

			if ( $post && 'page' === $post->post_type ) {
				// If page is already published, use it and skip creation.
				if ( 'publish' === $post->post_status ) {
					continue;
				} else {
					// Try to publish the existing page.
					$result = wp_update_post(
						array(
							'ID'          => $post->ID,
							'post_status' => 'publish',
						)
					);

					if ( ! is_wp_error( $result ) ) {
						// Successfully published, use this page.
						continue;
					}
				}
			}

			$page_id       = 0;
			$slug          = esc_sql( $page['name'] );
			$original_slug = $slug;
			$count         = 1;

			// Ensure the slug is unique.
			$existing_page = get_page_by_path( $slug );

			while ( $existing_page ) {
				// Check if the existing page has no content OR it was created by Masteriyo.
				$page_content = trim( $existing_page->post_content );

				if ( empty( $page_content ) || false !== strpos( $page_content, $page['content'] ) ) {
					// If the page is empty or has the expected shortcode, reuse it.
					$page_id = $existing_page->ID;
					break;
				}

				// Otherwise, increment the slug and check again.
				$slug = $original_slug . '-' . $count;
				++$count;

				// Fetch the next potential existing page.
				$existing_page = get_page_by_path( $slug );
			}

			// Only create a new page if an existing one wasn't found.
			if ( ! $page_id ) {
				$page_id = masteriyo_create_page( $slug, $setting_name, $page['title'], $page['content'], ! empty( $page['parent'] ) ? masteriyo_get_page_id( $page['parent'] ) : '' );
			}

			masteriyo_set_setting( "general.pages.{$setting_name}", $page_id );
		}
	}


	/**
	 * Assign core capabilities to admin role.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function assign_core_capabilities_to_admin() {
		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		$capabilities = Capabilities::get_admin_capabilities();

		foreach ( $capabilities as $cap => $bool ) {
			wp_roles()->add_cap( 'administrator', $cap );
		}
	}

	/**
	 * Insert masteriyo placeholder image to WP Media library.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function attach_placeholder_image() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			return false;
		}

		$wp_filesystem = new \WP_Filesystem_Direct( null );

		// Get upload directory.
		$upload_dir = wp_upload_dir();
		// Making masteriyo directory on uploads folder.
		$upload_masteriyo_dir = $upload_dir['basedir'] . '/masteriyo';

		$img_file           = masteriyo_get_plugin_dir() . '/assets/img/placeholder.jpg';
		$filename           = basename( $img_file );
		$prev_attachment_id = get_option( 'masteriyo_placeholder_image', 0 );
		$attach_file        = $upload_masteriyo_dir . '/' . sanitize_file_name( $filename );

		// Return if image already exists.
		if ( $wp_filesystem->exists( $attach_file ) && wp_attachment_is_image( $prev_attachment_id ) ) {
			return;
		}

		if ( ! file_exists( $upload_masteriyo_dir ) ) {
			wp_mkdir_p( $upload_masteriyo_dir );
		}

		$upload = $wp_filesystem->copy( $img_file, $attach_file, true );

		if ( $upload ) {
			$wp_filetype = wp_check_filetype( $filename, null );

			$attachment    = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_file_name( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$attachment_id = wp_insert_attachment( $attachment, $attach_file );

			// Update attachment ID.
			update_option( 'masteriyo_placeholder_image', $attachment_id );

			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $attach_file );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}
		}
	}

	/**
	 * Create log files.
	 *
	 * @since 1.12.2
	 */
	private static function create_log_files() {
		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( apply_filters( 'masteriyo_install_skip_create_files', false ) ) {
			return;
		}

		$files = array(
			array(
				'base'    => MASTERIYO_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => MASTERIYO_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // @codingStandardsIgnoreLine.

				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // @codingStandardsIgnoreLine.
					fclose( $file_handle ); // @codingStandardsIgnoreLine.
				}
			}
		}
	}
}
