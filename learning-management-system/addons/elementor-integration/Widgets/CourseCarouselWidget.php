<?php

/**
 * Masteriyo course carousel elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course carousel elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.13.0
 */
class CourseCarouselWidget extends CourseListWidget {

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
		return 'masteriyo-course-carousel';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Carousel', 'learning-management-system' );
	}

	/**
	 * Get icon class for the widget.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'masteriyo-course-carousel-widget-icon';
	}

	/**
	 * Register controls for configuring widget content.
	 *
	 * @since 1.13.0
	 */
	protected function register_content_controls() {
		$this->register_general_content_controls_section();
		$this->register_filter_controls_section();
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
			'source',
			array(
				'label'    => __( 'Source', 'learning-management-system' ),
				'type'     => Controls_Manager::SELECT,
				'multiple' => true,
				'options'  => array(
					''        => 'All Courses',
					'related' => 'Related Courses',
				),
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
				'{{WRAPPER}} .masteriyo-course--img-wrap' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_difficulty_badge',
			__( 'Difficulty Badge', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_thumbnail' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .difficulty-badge' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_categories',
			__( 'Categories', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course--content__category' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_course_title',
			__( 'Course Title', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course--content__title a' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_author',
			__( 'Author', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course-author' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_author_avatar',
			__( 'Avatar of Author', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_author' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-author img' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_author_name',
			__( 'Name of Author', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_author' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-author .masteriyo-course-author--name' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_rating',
			__( 'Rating', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-rating' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_course_description',
			__( 'Highlights / Description', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course--content__description' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_metadata',
			__( 'Meta Data', 'learning-management-system' ),
			array(
				'description' => __( 'Show/hide the section containing information on number of students, course hours etc.', 'learning-management-system' ),
			),
			array(
				'{{WRAPPER}} .masteriyo-course--content__stats' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_course_duration',
			__( 'Course Duration', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_metadata' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-stats-duration' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_students_count',
			__( 'Students Count', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_metadata' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-stats-students' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_lessons_count',
			__( 'Lessons Count', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_metadata' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-stats-curriculum' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_card_footer',
			__( 'Footer', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course-card-footer' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_price',
			__( 'Price', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_card_footer' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-course-price' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_enroll_button',
			__( 'Enroll Button', 'learning-management-system' ),
			array(
				'condition' => array(
					'show_card_footer' => 'yes',
				),
			),
			array(
				'{{WRAPPER}} .masteriyo-enroll-btn' => 'display: none !important;',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Registers the carousel settings controls section for the Course Carousel widget.
	 *
	 * This method adds various controls for configuring the carousel behavior, such as
	 * enabling/disabling arrows and dots, setting the transition duration, enabling
	 * centered slides, smooth scrolling, reverse direction, autoplay, and more.
	 *
	 * @since 1.13.0
	 *
	 * The controls are added to the 'Course Carousel' section in the Elementor widget
	 * settings panel.
	 */
	protected function register_carousel_settings_controls_section() {
		$this->start_controls_section(
			'course_carousel_section',
			array(
				'label' => __( 'Carousel Settings' ),
				'type'  => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'course_carousel_arrows',
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
			'course_carousel_dots',
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
			'course_carousel_transition',
			array(
				'label'   => __( 'Transition Duration', 'learning-management-system' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => '600',
			)
		);

		$this->add_control(
			'course_carousel_center_slides',
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
			'course_carousel_scroll',
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
			'course_carousel_autoplay',
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
			'course_carousel_autoplay_speed',
			array(
				'label'     => __( 'Auto Play Speed', 'learning-management-system' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2500,
				'condition' => array(
					'course_carousel_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'course_carousel_reverse_direction',
			array(
				'label'        => __( 'Reserve Direction', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'learning-management-system' ),
				'label_off'    => __( 'No', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'course_carousel_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'course_carousel_infinite_loop',
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
			'course_carousel_pause_onhover',
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
			'course_carousel_rewind',
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
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}

		$settings                = $this->get_settings();
		$is_related_course_query = isset( $settings['source'] ) && 'related' === $settings['source'];

		$limit   = max( absint( $settings['limit'] ), 1 );
		$columns = max( absint( $settings['slides_per_view'] ), 1 );

		$tax_query = array(
			'relation' => 'AND',
		);

		if ( ! empty( $settings['include_categories'] ) ) {
			$tax_query[] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $settings['include_categories'],
				'field'    => 'term_id',
				'operator' => 'IN',
			);
		}

		if ( ! empty( $settings['exclude_categories'] ) ) {
			$tax_query[] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $settings['exclude_categories'],
				'field'    => 'term_id',
				'operator' => 'NOT IN',
			);
		}

		if ( $is_related_course_query ) {
			$tax_query[] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $course ? $course->get_category_ids() : array(),
			);
		}

		$args = array(
			'post_type'      => PostType::COURSE,
			'status'         => array( PostStatus::PUBLISH ),
			'posts_per_page' => $limit,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'tax_query'      => $tax_query,
			'post__not_in'   => $is_related_course_query ? array( $course->get_id() ) : array(),
		);

		if ( ! empty( $settings['include_instructors'] ) ) {
			$args['author__in'] = $settings['include_instructors'];
		}

		if ( ! empty( $settings['exclude_instructors'] ) ) {
			$args['author__not_in'] = $settings['exclude_instructors'];
		}

		$order = strtoupper( $settings['sorting_order'] );

		switch ( $settings['order_by'] ) {
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;

			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price';
				$args['order']    = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'rating':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_average_rating';
				$args['order']    = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;

			default:
				$args['orderby'] = 'date';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
		}

		$courses_query = new \WP_Query( $args );
		$courses       = array_filter( array_map( 'masteriyo_get_course', $courses_query->posts ) );

		$show_carousel_arrows = 'yes' === $settings['course_carousel_arrows'] ? true : false;
		$show_carousel_dots   = 'yes' === $settings['course_carousel_dots'] ? true : false;

		$slider_data = array(
			'columns'           => $columns,
			'space_between'     => isset( $settings['space_between'] ) ? absint( $settings['space_between'] ) : 0,
			'reverse_direction' => 'yes' === $settings['course_carousel_reverse_direction'],
			'delay'             => $settings['course_carousel_autoplay_speed'],
			'infinite_loop'     => 'yes' === $settings['course_carousel_infinite_loop'],
			'autoplay'          => 'yes' === $settings['course_carousel_autoplay'],
			'speed'             => $settings['course_carousel_transition'],
			'navigation'        => 'yes' === $settings['course_carousel_arrows'],
			'pagination'        => 'yes' === $settings['course_carousel_dots'],
			'centeredSlides'    => 'yes' === $settings['course_carousel_center_slides'],
			'pauseOnHover'      => 'yes' === $settings['course_carousel_pause_onhover'],
			'scrollbar'         => 'yes' === $settings['course_carousel_scroll'],
			'rewind'            => 'yes' === $settings['course_carousel_rewind'],
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

		add_filter( 'masteriyo_is_course_carousel_enabled', '__return_true' );

		printf( '<div class="masteriyo masteriyo-course-carousel" data-settings="%s">', esc_attr( wp_json_encode( $slider_data ) ) );
		masteriyo_set_loop_prop( 'columns', $columns );

		if ( count( $courses ) > 0 ) {
			$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;
			masteriyo_course_loop_start();

			foreach ( $courses as $course ) {
				$GLOBALS['course'] = $course;
				$card_class        = empty( $settings['card_hover_animation'] ) ? '' : sprintf( 'elementor-animation-%s', $settings['card_hover_animation'] );

				masteriyo_get_template(
					'content-course.php',
					array(
						'card_class' => $card_class,
					)
				);
			}

			$GLOBALS['course'] = $original_course;

			masteriyo_course_loop_end();
			masteriyo_reset_loop();

			if ( $settings['course_carousel_scroll'] ) :
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
		}
		echo '</div>';

		add_filter( 'masteriyo_is_course_carousel_enabled', '__return_false' );

	}
}
