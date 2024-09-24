<?php

/**
 * Masteriyo category carousel elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Constants;
use Masteriyo\Taxonomy\Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo category carousel elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.13.0
 */
class CategoryCarouselWidget extends CourseCategoriesWidget {

	/**
	 * Get widget script dependencies.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'masteriyo-widget-carousel' );
	}

	/**
	 * Get widget style dependencies.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'masteriyo-widget-swiper' );
	}

	/**
	 * Get widget name.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-category-carousel';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Category Carousel', 'learning-management-system' );
	}

	/**
	 * Get icon class for the widget.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'masteriyo-category-carousel-widget-icon';
	}

	/**
	 * Register controls for configuring widget content.
	 *
	 * @since 1.13.0
	 */
	protected function register_content_controls() {
		$this->register_general_content_controls_section();
		$this->register_sorting_controls_section();
		$this->register_carousel_settings_controls_section();
	}

	/**
	 * Register general content controls section.
	 *
	 * @since 1.13.0
	 */
	protected function register_general_content_controls_section() {
		$this->start_controls_section(
			'general',
			array(
				'label' => __( 'General', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Limit', 'learning-management-system' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => -1,
				'max'     => 100,
				'step'    => 1,
				'default' => 12,
			)
		);

		$this->add_control(
			'include_sub_categories',
			array(
				'label'        => __( 'Include Sub-Categories', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Include', 'learning-management-system' ),
				'label_off'    => __( 'Exclude', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'divider_1',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$this->add_on_off_switch_control(
			'show_thumbnail',
			__( 'Thumbnail', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-category-card__image' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_category_details',
			__( 'Details', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-category-card__detail' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_category_title',
			__( 'Title', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_category_details' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-category-card__title' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_courses_count',
			__( 'Courses Count', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_category_details' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-category-card__courses' => 'display: none !important;',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Registers the carousel settings controls section for the Category Carousel widget.
	 *
	 * This method adds various controls for configuring the carousel behavior, such as
	 * enabling/disabling arrows and dots, setting the transition duration, enabling
	 * centered slides, smooth scrolling, reverse direction, autoplay, and more.
	 *
	 * @since 1.13.0
	 *
	 * The controls are added to the 'Category Carousel' section in the Elementor widget
	 * settings panel.
	 */
	protected function register_carousel_settings_controls_section() {
		$this->start_controls_section(
			'category_carousel_section',
			array(
				'label' => __( 'Carousel Settings' ),
				'type'  => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'category_carousel_arrows',
			array(
				'label'        => __( 'Arrows', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'learning-management-system' ),
				'label_off'    => __( 'Hide', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'category_carousel_dots',
			array(
				'label'        => __( 'Dots', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'learning-management-system' ),
				'label_off'    => __( 'Hide', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'category_carousel_transition',
			array(
				'label'   => __( 'Transition Duration', 'learning-management-system' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => '600',
			)
		);

		$this->add_control(
			'category_carousel_center_slides',
			array(
				'label'        => __( 'Centered Slides', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'category_carousel_scroll',
			array(
				'label'        => __( 'Smooth Scrolling', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'category_carousel_autoplay',
			array(
				'label'        => __( 'Auto Play', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'category_carousel_autoplay_speed',
			array(
				'label'   => __( 'Auto Play Speed', 'learning-management-system' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2500,
			)
		);

		$this->add_control(
			'category_carousel_reverse_direction',
			array(
				'label'        => __( 'Reserve Direction', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'category_carousel_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'category_carousel_infinite_loop',
			array(
				'label'        => __( 'Infinite Loop', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'category_carousel_pause_onhover',
			array(
				'label'        => __( 'Pause on Hover', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'category_carousel_rewind',
			array(
				'label'        => __( 'Rewind', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_responsive_control(
			'slides_per_view',
			array(
				'label'   => __( 'Slides Per View', 'learning-management-system' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
				'min'     => 1,
				'max'     => 3,
				'step'    => 1,
				'default' => 3,
			)
		);

		$this->add_responsive_control(
			'space_between',
			array(
				'label'   => __( 'Space Between Slides', 'learning-management-system' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render HTML for frontend.
	 *
	 * @since 1.13.0
	 */
	protected function render() {
		$settings = $this->get_settings();

		$limit   = max( absint( $settings['limit'] ), 1 );
		$columns = max( absint( $settings['slides_per_view'] ), 1 );

		$attrs                  = array();
		$include_sub_categories = masteriyo_string_to_bool( $settings['include_sub_categories'] );
		$hide_courses_count     = ! masteriyo_string_to_bool( isset( $settings['show_courses_count'] ) ? $settings['show_courses_count'] : true );
		$args                   = array(
			'taxonomy'   => Taxonomy::COURSE_CATEGORY,
			'order'      => masteriyo_array_get( $settings, 'order', 'ASC' ),
			'orderby'    => masteriyo_array_get( $settings, 'order_by', 'name' ),
			'number'     => $limit,
			'hide_empty' => false,
		);

		if ( ! masteriyo_string_to_bool( $include_sub_categories ) ) {
			$args['parent'] = 0;
		}

		$query      = new \WP_Term_Query();
		$result     = $query->query( $args );
		$categories = array_filter( array_map( 'masteriyo_get_course_cat', $result ) );

		$attrs['count']                  = $limit;
		$attrs['columns']                = $columns;
		$attrs['categories']             = $categories;
		$attrs['hide_courses_count']     = $hide_courses_count;
		$attrs['include_sub_categories'] = $include_sub_categories;

		if ( ! empty( $settings['card_hover_animation'] ) ) {
			$attrs['card_class'] = sprintf( 'elementor-animation-%s', $settings['card_hover_animation'] );
		}

		$show_carousel_arrows    = 'yes' === $settings['category_carousel_arrows'] ? true : false;
		$show_carousel_dots      = 'yes' === $settings['category_carousel_dots'] ? true : false;
		$show_carousel_scrollbar = 'yes' === $settings['category_carousel_scroll'] ? true : false;

		$attrs['swiper_enabled'] = true;

		$slider_data = array(
			'columns'           => $columns,
			'space_between'     => isset( $settings['space_between'] ) ? absint( $settings['space_between'] ) : 0,
			'reverse_direction' => 'yes' === $settings['category_carousel_reverse_direction'],
			'delay'             => $settings['category_carousel_autoplay_speed'],
			'infinite_loop'     => 'yes' === $settings['category_carousel_infinite_loop'],
			'autoplay'          => 'yes' === $settings['category_carousel_autoplay'],
			'speed'             => $settings['category_carousel_transition'],
			'navigation'        => 'yes' === $settings['category_carousel_arrows'],
			'pagination'        => 'yes' === $settings['category_carousel_dots'],
			'centeredSlides'    => 'yes' === $settings['category_carousel_center_slides'],
			'pauseOnHover'      => 'yes' === $settings['category_carousel_pause_onhover'],
			'scrollbar'         => 'yes' === $settings['category_carousel_scroll'],
			'rewind'            => 'yes' === $settings['category_carousel_rewind'],
			'breakpoints'       => array(
				320  => array(
					'slidesPerView' => isset( $settings['slides_per_view_mobile'] ) ? absint( $settings['slides_per_view_mobile'] ) : 1,
					'spaceBetween'  => isset( $settings['space_between_mobile'] ) ? absint( $settings['space_between_mobile'] ) : 0,
				),
				768  => array(
					'slidesPerView' => isset( $settings['slides_per_view_tablet'] ) ? absint( $settings['slides_per_view_tablet'] ) : 2,
					'spaceBetween'  => isset( $settings['space_between_tablet'] ) ? absint( $settings['space_between_tablet'] ) : 0,
				),
				1024 => array(
					'slidesPerView' => isset( $settings['slides_per_view'] ) ? absint( $settings['slides_per_view'] ) : 3,
					'spaceBetween'  => isset( $settings['space_between'] ) ? absint( $settings['space_between'] ) : 0,
				),
			),
		);

		printf( '<div class="masteriyo masteriyo-category-carousel" data-settings="%s">', esc_attr( wp_json_encode( $slider_data ) ) );
		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attrs );

		if ( $show_carousel_scrollbar ) :
			?>
		<div class="swiper-scrollbar"></div>
			<?php
		endif;

		if ( $show_carousel_arrows ) :
			?>
			<div class="swiper-button-next"></div>
			<div class="swiper-button-prev"></div>
			<?php
		endif;

		if ( $show_carousel_dots ) :
			?>
			<div class="swiper-pagination"></div>
			<?php
		endif;

		echo '</div>';
	}
}
