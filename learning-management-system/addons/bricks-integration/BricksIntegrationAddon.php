<?php
/**
 * Masteriyo bricks integration addon setup.
 *
 * @package Masteriyo\Addons\BricksIntegration
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\BricksIntegration;

use \Bricks\Templates as Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo bricks integration class.
 *
 * @class Masteriyo\Addons\BricksIntegration\BricksIntegrationAddon
 */
class BricksIntegrationAddon {
	/**
	 * Initialize module.
	 *
	 * @since 1.9.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.9.0
	 */
	public function init_hooks() {
		add_action(
			'init',
			array( $this, 'register_bricks_elements' ),
			11
		);
		add_filter(
			'bricks/builder/i18n',
			function( $i18n ) {
				$i18n['masteriyo'] = esc_html__( 'Masteriyo', 'learning-management-system' );

				return $i18n;
			}
		);
		add_filter(
			'bricks/setup/control_options',
			array( $this, 'add_masteriyo_course_template_type' )
		);
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'add_backend_script_data' ) );
		add_action( 'masteriyo_single_course_page_custom_template_render', array( $this, 'render_single_course_page_template' ), 10, 2 );
		add_action( 'init', array( $this, 'create_template_on_bricks_page' ) );
		add_action( 'masteriyo_course_archive_page_custom_template_render', array( $this, 'render_course_archive_page_template' ), 10, 2 );
		add_action( 'masteriyo_single_course_after_template_content_bricks', 'masteriyo_single_course_modals', 10 );
		add_action( 'masteriyo_course_archive_after_template_content_bricks', 'masteriyo_single_course_modals', 10 );
	}

		/**
	 * Render custom template for the Course Archive page.
	 *
	 * @since 1.11.3
	 *
	 * @param string $template_source
	 * @param integer $template_id
	 */
	public function render_course_archive_page_template( $template_source, $template_id ) {
		if ( 'bricks' !== $template_source ) {
			return;
		}

		echo ( new Templates() )->render_shortcode( array( 'id' => $template_id ) );
		do_action( 'masteriyo_course_archive_after_template_content_bricks' );
	}
	/**
	 * create a template on bricks page
	 *
	 * @since 1.11.3
	 */
	public function create_template_on_bricks_page() {

		// Check if the current page is one of the Bricks Builder pages
		if ( isset( $_SERVER['REQUEST_URI'] ) && ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) ) {
			$url             = $_SERVER['REQUEST_URI'];
			$bricks_keywords = array(
				'?page=bricks',
				'edit.php?post_type=bricks_template',
				'?page=bricks-settings',
				'bricks-custom-fonts',
				'?post_type=bricks_fonts',
				'?page=bricks-sidebars',
				'?page=bricks-system-information',
				'?page=bricks-license',
			);

			foreach ( $bricks_keywords as $keyword ) {
				if ( strpos( $url, $keyword ) !== false ) {
					// Create the template
					Helper::create_single_course_bricks_template_if_not_exists();
					Helper::create_course_archive_bricks_template_if_not_exists();
					break;
				}
			}
		}
	}

	/**
	 * Render custom template for the Single Course page.
	 *
	 * @since 1.11.3
	 *
	 * @param string $template_source
	 * @param integer $template_id
	 */
	public function render_single_course_page_template( $template_source, $template_id ) {
		if ( 'bricks' !== $template_source ) {
			return;
		}
		echo ( new Templates() )->render_shortcode( array( 'id' => $template_id ) );
		do_action( 'masteriyo_single_course_after_template_content_bricks' );
	}

	/**
	 * Add custom template for the Course page Type.
	 *
	 * @since 1.11.3
	 *
	 * @param string $template_source
	 * @param integer $template_id
	 */
	public function add_masteriyo_course_template_type( $control_options ) {
		$control_options['templateTypes']['masteriyo-single-course']  = esc_html__( 'Masteriyo Single Course', 'learning-management-system' );
		$control_options['templateTypes']['masteriyo-course-archive'] = esc_html__( 'Masteriyo Course Archive', 'learning-management-system' );
		return $control_options;

	}


	/**
	 * Localize more data to the backend script.
	 *
	 * @since 1.11.3
	 *
	 * @param array $script_data
	 *
	 * @return array
	 */
	public function add_backend_script_data( $script_data ) {
		$script_data['backend']['data']['singleCourseTemplates']['bricks']  = Helper::masteriyo_single_course_listing_template();
		$script_data['backend']['data']['courseArchiveTemplates']['bricks'] = Helper::masteriyo_course_archive_template();
		return $script_data;
	}


	public function register_bricks_elements() {
		$element_files = array(
			__DIR__ . '/Elements/CourseCategoriesElement.php',
			__DIR__ . '/Elements/CoursesElement.php',
			__DIR__ . '/Elements/CourseFeaturedImageElement.php',
			__DIR__ . '/Elements/CourseTitleElement.php',
			__DIR__ . '/Elements/CategoriesOfCourseElement.php',
			__DIR__ . '/Elements/CourseButtonElement.php',
			__DIR__ . '/Elements/CourseHighlightsElement.php',
			__DIR__ . '/Elements/CourseRatingElement.php',
			__DIR__ . '/Elements/CourseContentElement.php',
			__DIR__ . '/Elements/CourseOverviewElement.php',
			__DIR__ . '/Elements/CourseCurriculumElement.php',
			__DIR__ . '/Elements/CourseReviewsElement.php',
			__DIR__ . '/Elements/CoursesSearchElement.php',
			__DIR__ . '/Elements/CourseAvatarElement.php',
			__DIR__ . '/Elements/CourseStatsElement.php',
			__DIR__ . '/Elements/CoursePriceElement.php',
			__DIR__ . '/Elements/CourseArchiveViewModeElement.php',
			__DIR__ . '/Elements/CourseRetakeElement.php',
			__DIR__ . '/Elements/CourseGoogleClassroomCodeNButton.php',
		);
		foreach ( $element_files as $file ) {
			\Bricks\Elements::register_element( $file );
		}

	}

}
