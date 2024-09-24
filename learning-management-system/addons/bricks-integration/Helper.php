<?php
/**
 * Bricks Integration helper functions.
 *
 * @package Masteriyo\Addons\BricksIntegration
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\BricksIntegration;

use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseCategoryQuery;
use Masteriyo\Roles;
use WP_Query;

/**
 * Bricks Integration helper functions.
 *
 * @package Masteriyo\Addons\BricksIntegration
 *
 * @since 1.9.0
 */
class Helper {

	/**
	 * Return if Bricks is active.
	 *
	 * @since 1.9.0
	 *
	 * @return boolean
	 */
	public static function is_bricks_active() {
		$theme = wp_get_theme();

		if ( 'bricks' === $theme->get( 'Template' ) ) {
			return true;
		} elseif ( 'Bricks' === $theme->name ) {
			return true;
		}

			return false;
	}

	/**
	 * Returns the courses categories.
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	public static function get_categories_options() {
		$args       = array(
			'order'   => 'ASC',
			'orderby' => 'name',
			'number'  => '',
		);
		$query      = new CourseCategoryQuery( $args );
		$categories = $query->get_categories();

		return array_reduce(
			$categories,
			function( $options, $category ) {
				$options[ $category->get_id() ] = $category->get_name();
				return $options;
			},
			array()
		);
	}

	/**
	 * Get instructors options.
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	public static function get_instructors_options() {
		$args          = array(
			'role__in' => array( Roles::INSTRUCTOR, Roles::ADMIN ),
			'order'    => 'ASC',
			'orderby'  => 'display_name',
			'number'   => '',
		);
		$wp_user_query = new \WP_User_Query( $args );
		$authors       = $wp_user_query->get_results();

		return array_reduce(
			$authors,
			function( $options, $author ) {
				$options[ $author->ID ] = $author->display_name;
				return $options;
			},
			array()
		);
	}

	/**
	 * Retrieves the page number from a given URL.
	 *
	 * @since 1.9.0
	 *
	 * @param string $url The URL to extract the page number from.
	 *
	 * @return int The extracted page number. Defaults to 1 if not found.
	 */
	public static function get_page_from_url( $url ) {
		$page_number = 1; // Default page number.

		// Parse the URL path.
		$url_parts = wp_parse_url( $url );
		$path      = $url_parts['path'];

		// Split the path by '/'.
		$path_parts = explode( '/', trim( $path, '/' ) );

		// Find the index of 'page' in the path.
		$page_index = array_search( 'page', $path_parts, true );

		if ( false !== $page_index && isset( $path_parts[ $page_index + 1 ] ) ) {
			// Get the page number.
			$page_number = absint( $path_parts[ $page_index + 1 ] );
		}

		// Page number not found.
		return $page_number;
	}

	/**
	 * Get all bricks templates
	 *
	 * @since 1.11.3
	 *
	 * @return array
	 */
	public static function get_all_bricks_templates() {
		$args = array(
			'post_type'      => 'bricks_template',
			'posts_per_page' => -1, // -1 to get all templates
		);

		$templates = new WP_Query( $args );

		if ( $templates->have_posts() ) {
			$all_templates = array();
			while ( $templates->the_post() ) {
				$all_templates[] = get_post();
			}
			return $all_templates;
		} else {
			return array();
		}

		wp_reset_postdata(); // Reset post data after the loop
	}

	/**
	 * Get a course to use for preview in brick editor.
	 *
	 * @since 1.11.3
	 *
	 * @return \Masteriyo\Models\Course|null
	 */
	public static function get_bricks_preview_course() {
		global $course;

		if ( empty( $course ) ) {
			$posts = get_posts(
				array(
					'posts_per_page' => 1,
					'post_type'      => PostType::COURSE,
					'post_status'    => array( PostStatus::PUBLISH, PostStatus::DRAFT ),
					'author'         => masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ? null : get_current_user_id(),
				)
			);

			$course = empty( $posts ) ? null : masteriyo_get_course( $posts[0] );
		}

		return $course;
	}

	/**
	 * Single course listing templates with masteriyo-single-course template type.
	 *
	 * @since 1.11.3
	 *
	 * @return array
	 */
	public static function masteriyo_single_course_listing_template() {
		$all_templates = \Bricks\Database::get_all_templates_by_type();
		if ( $all_templates && ! empty( $all_templates['masteriyo-single-course'] ) && $all_templates['masteriyo-single-course'] ) {
			$templates                              = array_values( $all_templates['masteriyo-single-course'] );
			$args                                   = array(
				'post_type'      => 'bricks_template',
				'post_name'      => 'masteriyo-single-course',
				'posts_per_page' => -1,
				'post__in'       => $templates,
				'meta_query'     => array(
					array(
						'key'     => '_bricks_template_type',
						'compare' => 'EXISTS',
					),
				),
				'post_status'    => 'publish',
			);
			$args                                   = apply_filters( 'bricks/database/bricks_get_all_templates_by_type_args', $args );
			$masteriyo_single_course_templates_data = get_posts( $args );
			$bricks_options                         = array();
			foreach ( $masteriyo_single_course_templates_data as $masteriyo_single_course_template ) {
				$bricks_options[] = array(
					'id'    => $masteriyo_single_course_template->ID,
					'title' => $masteriyo_single_course_template->post_title,
				);
			}
			return $bricks_options;
		}
	}

	/**
	 * Courses listing templates with masteriyo-single-course template type.
	 *
	 * @since 1.11.3
	 *
	 * @return array
	 */
	public static function masteriyo_course_archive_template() {
		$all_template = \Bricks\Database::get_all_templates_by_type();
		if ( $all_template && ! empty( $all_template['masteriyo-course-archive'] ) && $all_template['masteriyo-course-archive'] ) {
			$template = array_values( $all_template['masteriyo-course-archive'] );

			$args                      = array(
				'post_type'      => 'bricks_template',
				'post_name'      => 'masteriyo-course-archive',
				'posts_per_page' => -1,
				'post__in'       => $template,
				'meta_query'     => array(
					array(
						'key'     => '_bricks_template_type',
						'compare' => 'EXISTS',
					),
				),
				'post_status'    => 'publish',
			);
			$args                      = apply_filters( 'bricks/database/bricks_get_all_templates_by_type_args', $args );
			$masteriyo_course_archives = get_posts( $args );
			$bricks_options            = array();
			foreach ( $masteriyo_course_archives as $masteriyo_course_archive ) {
				$bricks_options[] = array(
					'id'    => $masteriyo_course_archive->ID,
					'title' => $masteriyo_course_archive->post_title,
				);
			}
			return $bricks_options;
		}
	}

	/**
	 * Creates the new single course bricks template if it does not exists.
	 *
	 * @since 1.11.3
	 *
	 * @return void
	 */
	public static function create_single_course_bricks_template_if_not_exists() {
		$json_file_path = __DIR__ . '/json/template-masteriyo-single-course.json';

		if ( ! file_exists( $json_file_path ) ) {
			echo '<div>No JSON found for Masteriyo Single Course Template</div>';
			return;
		}

		$existing_template = get_posts(
			array(
				'post_type'   => 'bricks_template',
				'post_name'   => 'masteriyo-single-course',
				'title'       => 'Masteriyo Single Course (Default)',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		if ( empty( $existing_template ) ) {
			$json_string = file_get_contents( $json_file_path );
			// Decode the JSON string into a PHP array
			$template_name    = 'Masteriyo Single Course (Default)';
			$template_content = json_decode(
				$json_string,
				true
			);

			self::create_bricks_template( $template_name, $template_content );
		}
	}

	/**
	 * Creates the new courses archive bricks template if it does not exists.
	 *
	 * @since 1.11.3
	 *
	 * @return void
	 */
	public static function create_course_archive_bricks_template_if_not_exists() {
		$json_file_path = __DIR__ . '/json/template-masteriyo-course-archive.json';

		if ( ! file_exists( $json_file_path ) ) {
			echo '<div>No JSON found for Masteriyo Course Archive Template</div>';
			return;
		}

		$existing_template = get_posts(
			array(
				'post_type'   => 'bricks_template',
				'post_name'   => 'masteriyo-course-archive',
				'title'       => 'Masteriyo Course Archive (Default)',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		if ( empty( $existing_template ) ) {
			$json_string = file_get_contents( $json_file_path );
			// Decode the JSON string into a PHP array
			$template_name    = 'Masteriyo Course Archive (Default)';
			$template_content = json_decode(
				$json_string,
				true
			);

			self::create_bricks_template( $template_name, $template_content );
		}
	}

	/**
	 * Creates the new course bricks template for first time.
	 *
	 * @since 1.11.3
	 *
	 * @return number|string
	 */
	public static function create_bricks_template( $template_name, $template_content ) {
		if ( get_option( $template_content['type'] ) ) {
			return;
		};
		$template_data = array(
			'post_title'  => $template_name,
			'post_status' => 'publish',
			'post_type'   => 'bricks_template',
		);

		$template_id = wp_insert_post( $template_data );
		if ( is_wp_error( $template_id ) ) {
				return $template_id->get_error_message();
		}

		// Extract the 'content' array
		$content = $template_content['content'];

		// Prepare the associative array in the desired format
		$associative_array = array();

		foreach ( $content as $item ) {
			$associative_array[] = array(
				'id'       => $item['id'],
				'name'     => $item['name'],
				'parent'   => $item['parent'],
				'children' => $item['children'],
				'settings' => $item['settings'],
			);
		}

		add_option( $template_content['type'], true );
		update_post_meta( $template_id, '_bricks_template_type', $template_content['type'] );
		update_post_meta( $template_id, '_bricks_editor_mode', 'bricks' );
		update_post_meta( $template_id, '_bricks_page_content_2', $associative_array );
		update_post_meta( $template_id, '_masteriyo_course_archive_template_meta', true );
		return $template_id;
	}


}


