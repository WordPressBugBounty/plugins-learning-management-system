<?php
/**
 * Beaver Integration Helper Class.
 *
 * @package Masteriyo\Addons\BeaverIntegration
 *
 * @since 1.10.0
 */


namespace Masteriyo\Addons\BeaverIntegration;

use Masteriyo\Query\CourseCategoryQuery;
use Masteriyo\Roles;

/**
 * Beaver Integration Helper Class.
 *
 * @package Masteriyo\Addons\BeaverIntegration
 *
 * @since 1.10.0
 */
class Helper {

	/**
	 * Return if Beaver is active.
	 *
	 * @since 1.10.0
	 *
	 * @return boolean
	 */
	public static function is_beaver_active() {
		if ( masteriyo_check_plugin_active_in_network( 'beaver-builder/fl-builder.php' ) || masteriyo_check_plugin_active_in_network( 'beaver-builder-lite-version/fl-builder.php' ) || masteriyo_check_plugin_active_in_network( 'bb-plugin/fl-builder.php' ) ) {
			return true;
		}

		$active_plugins = get_option( 'active_plugins', array() );

		return in_array( 'beaver-builder/fl-builder.php', $active_plugins, true ) || in_array( 'beaver-builder-lite-version/fl-builder.php', $active_plugins, true ) || in_array( 'bb-plugin/fl-builder.php', $active_plugins, true );
	}

	/**
	 * Returns the courses categories.
	 *
	 * @since 1.10.0
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
	 * Retrieves the page number from a given URL.
	 *
	 * @since 1.10.0
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

		$path_parts = explode( '/', trim( $path, '/' ) );

		$page_index = array_search( 'page', $path_parts, true );

		if ( false !== $page_index && isset( $path_parts[ $page_index + 1 ] ) ) {
			// Get the page number.
			$page_number = absint( $path_parts[ $page_index + 1 ] );
		}

		return $page_number;
	}


	/**
	 * Get instructors options.
	 *
	 * @since 1.10.0
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
	 * Return all the array data for courses settings.
	 *
	 * @since 1.10.0
	 *
	 * @return array
	 */
	public static function get_courses_setting() {
		return array(
			'tab-01' => array(
				'title'    => __( 'Course List', 'learning-management-system' ),
				'sections' => array(
					'courses_general'               => array(
						'title'  => __( 'General', 'learning-management-system' ),
						'fields' => array(
							'courses_order'    => array(
								'type'    => 'select',
								'label'   => __( 'Order', 'learning-management-system' ),
								'options' => array(
									'ASC'  => __( 'ASC', 'learning-management-system' ),
									'DESC' => __( 'DESC', 'learning-management-system' ),
								),
							),
							'courses_order_by' => array(
								'type'    => 'select',
								'label'   => __( 'Order By', 'learning-management-system' ),
								'options' => array(
									'date'   => esc_html__( 'Date', 'learning-management-system' ),
									'title'  => esc_html__( 'Title', 'learning-management-system' ),
									'price'  => esc_html__( 'Price', 'learning-management-system' ),
									'rating' => esc_html__( 'Rating', 'learning-management-system' ),
								),
							),
							'courses_columns'  => array(
								'type'        => 'unit',
								'label'       => 'Course Columns',
								'default'     => 3,
								'description' => 'Min 1 and Max 4 are only supported.',
								'slider'      => array(
									'min'  => 1,
									'max'  => 4,
									'step' => 1,
								),
							),
							'courses_per_page' => array(
								'type'    => 'unit',
								'label'   => 'Per Page',
								'default' => 12,
								'slider'  => array(
									'min'  => 1,
									'max'  => 24,
									'step' => 2,
								),

							),
							'courses_gap'      => array(
								'type'    => 'dimension',
								'units'   => array( 'px', 'em' ),
								'label'   => esc_html__( 'Course Gap', 'learning-management-system' ),
								'unit'    => 'px',
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'selector'  => '.masteriyo-col',
									'property'  => 'padding',
								),
							),
						),
					),
					'courses_components_visibility' => array(
						'title'  => __( 'Visibility', 'learning-management-system' ),
						'fields' => array(
							'show_thumbnail'          => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Thumbnail', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--img-wrap',
								),
								'toggle'  => array(
									'inline' => array(
										'fields' => array( 'difficulty_badge_vertical_position', 'difficulty_badge_horizontal_position' ),
									),
									'none'   => array(),
								),
							),
							'show_categories'         => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Categories', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--content__category',
								),
							),
							'show_title'              => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Title', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--content__title a',
								),
							),
							'show_author'             => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Author', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'toggle'  => array(
									'inline' => array(
										'fields' => array( 'show_author_avatar', 'show_author_name' ),
									),
									'none'   => array(),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-author',
								),
							),
							'show_author_avatar'      => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Avatar of Author', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-author .img',
								),
							),
							'show_author_name'        => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Name of Author', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-author .masteriyo-course-author--name',
								),
							),
							'show_course_description' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Highlights / Description', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--content__description',
								),
							),
							'show_metadata'           => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Meta Data', 'learning-management-system' ),
								'options' => array(
									'flex' => esc_html__( 'Visible', 'learning-management-system' ),
									'none' => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--content__stats',
								),
								'toggle'  => array(
									'inline' => array(
										'fields' => array( 'show_students_count', 'show_course_duration', 'show_lessons_count' ),
									),
									'none'   => array(),
								),
							),
							'show_students_count'     => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Students Count', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-stats-students',
								),
							),
							'show_course_duration'    => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Course Duration', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-stats-duration',
								),
							),
							'show_lessons_count'      => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Lessons Count', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-stats-curriculum',
								),
							),
							'show_card_footer'        => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Course Footer', 'learning-management-system' ),
								'options' => array(
									'flex' => esc_html__( 'Visible', 'learning-management-system' ),
									'none' => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-card-footer',
								),
								'toggle'  => array(
									'inline' => array(
										'fields' => array( 'show_price', 'show_enroll_button' ),
									),
									'none'   => array(),
								),
							),
							'show_price'              => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Course Price', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course-price',
								),
							),
							'show_enroll_button'      => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Course Enroll Button', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-enroll-btn',
								),
							),
						),
					),
					'courses_components_styles'     => array(
						'title'  => __( 'Styles', 'learning-management-system' ),
						'fields' => array(
							//card styles
							'course_title_color'        => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Title Color', 'learning-management-system' ),
								'options' => array(
									'inline' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-course--content__title a',
								),
							),
							'card_border_style'         => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Border Style', 'learning-management-system' ),
								'default' => 'solid',
								'options' => array(
									'dashed'  => esc_html__( 'Dashes', 'learning-management-system' ),
									'dotted'  => esc_html__( 'Dotted', 'learning-management-system' ),
									'solid'   => esc_html__( 'Solid', 'learning-management-system' ),
									'doubled' => esc_html__( 'Doubled', 'learning-management-system' ),
									'groove'  => esc_html__( 'Groove', 'learning-management-system' ),
									'ridge'   => esc_html__( 'Ridge', 'learning-management-system' ),
									'none'    => esc_html__( 'None', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-style',
									'selector'  => '.masteriyo-course--card',
								),
							),
							'card_border'               => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Card Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-color',
									'selector'  => '.masteriyo-course--card',
								),
							),
							'card_border_width'         => array(
								'type'    => 'unit',
								'units'   => array(
									'em',
									'px',
								),
								'label'   => esc_html__( 'Card Border Width', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-width',
									'selector'  => '.masteriyo-course--card',
								),
							),
							'card_box_shadow'           => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Card BoxShadow', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'box-shadow',
									'selector'  => '.masteriyo-course--card',
								),
							),
							'difficulty_badge_border_style' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Difficulty Badge Border Style', 'learning-management-system' ),
								'default' => 'solid',
								'options' => array(
									'dashed'  => esc_html__( 'Dashes', 'learning-management-system' ),
									'dotted'  => esc_html__( 'Dotted', 'learning-management-system' ),
									'solid'   => esc_html__( 'Solid', 'learning-management-system' ),
									'doubled' => esc_html__( 'Doubled', 'learning-management-system' ),
									'groove'  => esc_html__( 'Groove', 'learning-management-system' ),
									'ridge'   => esc_html__( 'Ridge', 'learning-management-system' ),
									'none'    => esc_html__( 'None', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-style',
									'selector'  => '.difficulty-badge .masteriyo-badge',
								),
							),
							'difficulty_badge_border'   => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Difficulty Badge Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-color',
									'selector'  => '.masteriyo-badge',
								),
							),
							'difficulty_badge_border_shadow' => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Difficulty Badge BoxShadow', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'box-shadow',
									'selector'  => '.difficulty-badge .masteriyo-badge',
								),
							),
							'difficulty_badge_padding'  => array(
								'type'    => 'dimension',
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'min'     => '0',
								'label'   => esc_html__( 'Difficulty Badge Padding', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'padding',
									'selector'  => '.difficulty-badge .masteriyo-badge',
								),
							),
							'difficulty_badge_vertical_position' => array(
								'type'    => 'unit',
								'units'   => array( 'px' ),
								'label'   => esc_html__( 'Vertical Badge Position', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'top',
									'selector'  => '.masteriyo-course--img-wrap .difficulty-badge',
								),
							),
							'difficulty_badge_horizontal_position' => array(
								'type'    => 'unit',
								'units'   => array( 'px' ),
								'label'   => esc_html__( 'Horizontal Badge Position', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'left',
									'selector'  => '.masteriyo-course--img-wrap .difficulty-badge',
								),
							),
							//categories
							'categories_gap'            => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
								),
								'label'   => esc_html__( 'Categories Margin Left', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'margin-left',
									'selector'  => '.masteriyo-course--content__category',
								),
							),

							//author
							'author_color'              => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Author Text Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-course-author--name',
								),
							),
							'author_margin'             => array(
								'type'    => 'unit',
								'units'   => array(
									'em',
									'px',
								),
								'label'   => esc_html__( 'Author Margin ', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'margin-left',
									'selector'  => '.masteriyo-course-author--name',
								),
							),
							'author_font_size'          => array(
								'type'    => 'unit',
								'units'   => array(
									'em',
									'px',
								),
								'min'     => '5',
								'label'   => esc_html__( 'Author Font Size ', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'font-size',
									'selector'  => '.masteriyo-course-author--name',
								),
							),
							'author_text shadow'        => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Author Text BoxShadow', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'box-shadow',
									'selector'  => '.masteriyo-course-author--name',
								),
							),

							//rating
							'rating_visibility'         => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Rating', 'learning-management-system' ),
								'options' => array(
									'flex' => esc_html__( 'Visible', 'learning-management-system' ),
									'none' => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'default' => 'flex',
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-course--content__rt .masteriyo-rating',
								),
								'toggle'  => array(
									'flex' => array(
										'fields' => array( 'rating_typography', 'rating_height', 'rating_width', 'rating_color', 'rating_color', 'rating_gap' ),
									),
									'none' => array(),
								),
							),
							'rating_color'              => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Rating Count Color', 'learning-management-system' ),
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'fill',
									'selector'  => '.masteriyo-course--content__rt .masteriyo-rating svg',
								),
							),
							'rating_bg_color'           => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Rating Background Color', 'learning-management-system' ),
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.masteriyo-course--content__rt .masteriyo-rating svg',
								),
							),
							'rating_gap'                => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
								),
								'label'   => esc_html__( 'Rating Gap', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'width',
									'selector'  => '.masteriyo-rating > svg',
								),
							),
							'rating_height'             => array(
								'type'        => 'unit',
								'units'       => array(
									'px',
								),
								'label'       => esc_html__( 'Rating Height', 'learning-management-system' ),
								'preview'     => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'height',
									'selector'  => '.masteriyo-rating > svg',
								),
								'description' => 'px',
							),
							//descriptions or highlights
							'highlight_gap'             => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
								),
								'label'   => esc_html__( 'Highlight Margin Top', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'margin-top',
									'selector'  => '.masteriyo-course--content .masteriyo-course--content__description ul, .masteriyo-course--content .masteriyo-course--content__description ol',
								),
							),
							'highlight_color'           => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Highlight Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-course--content__description',
								),
							),
							//metadata
							'metadata_font_size'        => array(
								'type'    => 'unit',
								'label'   => esc_html__( 'MetaData Font Size', 'learning-management-system' ),
								'units'   => array( 'px' ),
								'min'     => '4',
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'font-size',
									'selector'  => '.masteriyo-course--content__stats span',
								),
							),
							'metadata_top_border_style' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'MetaData Top Border Style', 'learning-management-system' ),
								'default' => 'solid',
								'options' => array(
									'dashed'  => esc_html__( 'Dashes', 'learning-management-system' ),
									'dotted'  => esc_html__( 'Dotted', 'learning-management-system' ),
									'solid'   => esc_html__( 'Solid', 'learning-management-system' ),
									'doubled' => esc_html__( 'Doubled', 'learning-management-system' ),
									'groove'  => esc_html__( 'Groove', 'learning-management-system' ),
									'ridge'   => esc_html__( 'Ridge', 'learning-management-system' ),
									'none'    => esc_html__( 'None', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-style',
									'selector'  => '.masteriyo-course--content__stats',
								),
							),
							'metadata_top_border'       => array(
								'type'    => 'color',
								'label'   => esc_html__( 'MetaData Top Border', 'learning-management-system' ),
								'min'     => '0',
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-color',
									'selector'  => '.masteriyo-course--content__stats',
								),
							),
							'metadata_top_margin'       => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'min'     => '0',
								'label'   => esc_html__( 'MetaData Top Margin', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'margin-top',
									'selector'  => '.masteriyo-course--content__stats',
								),
							),
							'metadata_top_width'        => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'min'     => '0',
								'label'   => esc_html__( 'MetaData Top Width', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-width',
									'selector'  => '.masteriyo-course--content__stats',
								),
							),
							'metadata_icon_color'       => array(
								'type'    => 'color',
								'label'   => esc_html__( 'MetaData Icon Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'fill',
									'selector'  => '.masteriyo-course--content__stats svg',
								),
							),
							'metadata_color'            => array(
								'type'    => 'color',
								'label'   => esc_html__( 'MetaData Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-course--content__stats > div span',
								),
							),
							//price
							'text_price_size'           => array(
								'type'    => 'unit',
								'units'   => array( 'px', 'em' ),
								'label'   => esc_html__( 'Price Size', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'font-size',
									'selector'  => '.masteriyo-course--content .masteriyo-course-price .current-amount',
								),
							),
							'text_price'                => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Price Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-course--content .masteriyo-course-price .current-amount',
								),
							),
							'text_price_shadow'         => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Price BoxShadow', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'text-shadow',
									'selector'  => '.masteriyo-course--content .masteriyo-course-price .current-amount',
								),
							),
							'price_button_border_style' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Price Button Content Border Style', 'learning-management-system' ),
								'default' => 'solid',
								'options' => array(
									'dashed'  => esc_html__( 'Dashes', 'learning-management-system' ),
									'dotted'  => esc_html__( 'Dotted', 'learning-management-system' ),
									'solid'   => esc_html__( 'Solid', 'learning-management-system' ),
									'doubled' => esc_html__( 'Doubled', 'learning-management-system' ),
									'groove'  => esc_html__( 'Groove', 'learning-management-system' ),
									'ridge'   => esc_html__( 'Ridge', 'learning-management-system' ),
									'none'    => esc_html__( 'None', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-style',
									'selector'  => '.masteriyo-course--content .masteriyo-time-btn',
								),
							),
							'price_button_border'       => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Price Button Content Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-color',
									'selector'  => '.masteriyo-course--content .masteriyo-time-btn',
								),
							),
							'price_button_width_top'    => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'min'     => '0',
								'label'   => esc_html__( 'Price Button Top Border Width', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-top-width',
									'selector'  => '.masteriyo-course--content .masteriyo-time-btn',
								),
							),
							//enroll button styles
							'button'                    => array(
								'type'    => 'unit',
								'units'   => array(
									'px',
									'em',
									'%',
								),
								'label'   => esc_html__( 'Enroll Button Radius', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-radius',
									'selector'  => '.masteriyo-enroll-btn',
								),
							),
							'button_color'              => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Enroll Button Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.masteriyo-btn.masteriyo-btn-primary',
								),
							),
							'button_background'         => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Enroll Button Background Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.masteriyo-btn.masteriyo-btn-primary',
								),
							),

						),
					),
					'courses_pagination'            => array(
						'title'  => esc_html__( 'Pagination', 'learning-management-system' ),
						'fields' => array(
							'show_pagination'           => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Pagination', 'learning-management-system' ),
								'options' => array(
									'flex' => esc_html__( 'Visible', 'learning-management-system' ),
									'none' => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.page-numbers',
								),
								'toggle'  => array(
									'flex' => array(
										'fields' => array( 'pagination_bg', 'pagination_color', 'pagination_border', 'pagination_list_current_border', 'pagination_list_current_active_color' ),
									),
									'none' => array(),
								),
							),
							'pagination_bg'             => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Background Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.page-numbers',
								),
							),
							'pagination_color'          => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.page-numbers',
								),
							),
							'pagination_list_current_active_color' => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Current Active Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.page-numbers li .current',
								),
							),
							'pagination_current_active' => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Current Active Background Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.page-numbers li .current',
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Return all the array data for courses categories settings.
	 *
	 * @since 1.10.0
	 *
	 * @return array
	 */
	public static function get_courses_categories_setting() {
		return array(
			'tab-01' => array(
				'title'    => __( 'Course Categories', 'learning-management-system' ),
				'sections' => array(
					'courses_categories_general'       => array(
						'title'  => __( 'General', 'learning-management-system' ),
						'fields' => array(
							'categories_order'       => array(
								'type'    => 'select',
								'label'   => __( 'Order', 'learning-management-system' ),
								'options' => array(
									'ASC'  => __( 'ASC', 'learning-management-system' ),
									'DESC' => __( 'DESC', 'learning-management-system' ),
								),
							),
							'categories_order_by'    => array(
								'type'    => 'select',
								'label'   => __( 'Order By', 'learning-management-system' ),
								'options' => array(
									'date'   => esc_html__( 'Date', 'learning-management-system' ),
									'title'  => esc_html__( 'Title', 'learning-management-system' ),
									'price'  => esc_html__( 'Price', 'learning-management-system' ),
									'rating' => esc_html__( 'Rating', 'learning-management-system' ),
								),
							),
							'categories_columns'     => array(
								'type'    => 'unit',
								'label'   => 'Categories Columns',
								'default' => 3,
								'slider'  => array(
									'min'  => 1,
									'max'  => 4,
									'step' => 1,
								),
							),
							'categories_per_page'    => array(
								'type'    => 'unit',
								'label'   => 'Per Page',
								'default' => 12,
								'slider'  => array(
									'min'  => 1,
									'max'  => 24,
									'step' => 2,
								),
							),
							'categories_columns_gap' => array(
								'type'         => 'dimension',
								'units'        => array( 'px', 'em' ),
								'default_unit' => 'px',
								'label'        => esc_html__( 'Categories Gap', 'learning-management-system' ),
								'preview'      => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'selector'  => '.masteriyo-col',
									'property'  => 'padding',
								),
							),
						),
					),
					//visibility
					'categories_components_visibility' => array(
						'title'  => __( 'Visibility', 'learning-management-system' ),
						'fields' => array(
							'categories_show_thumbnail' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Thumbnail', 'learning-management-system' ),
								'options' => array(
									'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-category-card__image',
								),

							),
							'categories_include_sub_categories' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Include Sub-Categories', 'learning-management-system' ),
								'options' => array(
									'yes' => esc_html__( 'Yes', 'learning-management-system' ),
									'no'  => esc_html__( 'No', 'learning-management-system' ),
								),
								'default' => 'yes',
							),
							'categories_show_details'   => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Details', 'learning-management-system' ),
								'options' => array(
									'block' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-category-card__detail',
								),
								'toggle'  => array(
									'block' => array(
										'fields' => array( 'categories_show_title', 'categories_show_courses_count', 'categories_show_details_section' ),
									),
									'none'  => array(),
								),
							),
							'categories_show_title'     => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Title', 'learning-management-system' ),
								'options' => array(
									'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-category-card__title',
								),
							),
							'categories_show_courses_count' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Courses Count', 'learning-management-system' ),
								'options' => array(
									'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.masteriyo-category-card__courses',
								),
							),
						),
					),
					'categories_components_styles'     => array(
						'title'  => __( 'Categories Elements Styles', 'learning-management-system' ),
						'fields' => array(
							'categories_card_border_style' => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Border Style', 'learning-management-system' ),
								'default' => 'solid',
								'options' => array(
									'dashed'  => esc_html__( 'Dashes', 'learning-management-system' ),
									'dotted'  => esc_html__( 'Dotted', 'learning-management-system' ),
									'solid'   => esc_html__( 'Solid', 'learning-management-system' ),
									'doubled' => esc_html__( 'Doubled', 'learning-management-system' ),
									'groove'  => esc_html__( 'Groove', 'learning-management-system' ),
									'ridge'   => esc_html__( 'Ridge', 'learning-management-system' ),
									'none'    => esc_html__( 'None', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-style',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_card_border'       => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Card Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-color',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_card_border_width' => array(
								'type'    => 'unit',
								'units'   => array(
									'em',
									'px',
								),
								'label'   => esc_html__( 'Card Border Width', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'border-width',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_card_border_box_shadow' => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Card BoxShadow', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'box-shadow',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_card_color'        => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Background Color', 'learning-management-system' ),
								'options' => array(
									'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.masteriyo-category-card',
								),

							),
							'categories_cardbox_shadow'    => array(
								'type'    => 'shadow',
								'label'   => esc_html__( 'Card BoxShadow', 'learning-management-system' ),
								'options' => array(
									'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
									'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'box-shadow',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_card_padding'      => array(
								'type'    => 'dimension',
								'units'   => array( 'px', 'em' ),
								'label'   => esc_html__( 'Padding', 'learning-management-system' ),
								'unit'    => 'px',
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'padding',
									'selector'  => '.masteriyo-category-card',
								),
							),
							'categories_details_section_styles' => array(
								'type'    => 'unit',
								'units'   => array( 'px', 'em' ),
								'label'   => esc_html__( 'Details Section Padding Top', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'padding-top',
									'selector'  => '.masteriyo-category-card__detail',
								),
							),
							'categories_courses_count_margin' => array(
								'type'    => 'unit',
								'units'   => array( 'px', 'em' ),
								'label'   => esc_html__( 'Courses Count Margin', 'learning-management-system' ),

								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'margin-left',
									'selector'  => '.masteriyo-category-card__courses',
								),
							),

						),
					),
					'categories_pagination'            => array(
						'title'  => esc_html__( 'Pagination', 'learning-management-system' ),
						'fields' => array(
							'show_pagination'           => array(
								'type'    => 'select',
								'label'   => esc_html__( 'Show Pagination', 'learning-management-system' ),
								'options' => array(
									'flex' => esc_html__( 'Visible', 'learning-management-system' ),
									'none' => esc_html__( 'Invisible', 'learning-management-system' ),
								),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'display',
									'selector'  => '.page-numbers',
								),
								'toggle'  => array(
									'flex' => array(
										'fields' => array( 'pagination_bg', 'pagination_color', 'pagination_border', 'pagination_list_current_border', 'pagination_list_current_active_color' ),
									),
									'none' => array(),
								),
							),
							'pagination_bg'             => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Background Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.page-numbers',
								),
							),
							'pagination_color'          => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.page-numbers',
								),
							),
							'pagination_list_current_active_color' => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Current Active Border', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'color',
									'selector'  => '.page-numbers li .current',
								),
							),
							'pagination_current_active' => array(
								'type'    => 'color',
								'label'   => esc_html__( 'Current Active Background Color', 'learning-management-system' ),
								'preview' => array(
									'type'      => 'css',
									'auto'      => true,
									'important' => true,
									'property'  => 'background-color',
									'selector'  => '.page-numbers li .current',
								),
							),
						),
					),
				),
			),
		);
	}

}
