<?php
/**
 * Elementor integration class.
 *
 * @package Masteriyo\Addons\WishList
 *
 * @since 2.5.14
 */

namespace Masteriyo\Addons\WishList;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration class.
 *
 * @package Masteriyo\Addons\WishList
 *
 * @since 2.5.14
 */
class ElementorIntegration {
	/**
	 * Initialize the application.
	 *
	 * @since 2.5.14
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.5.14
	 */
	public function init_hooks() {
		add_action( 'elementor/element/masteriyo-course-list/general/before_section_end', array( $this, 'add_wishlist_icon_toggle_in_course_list_widget' ) );
		add_action( 'elementor/element/masteriyo-course-carousel/general/before_section_end', array( $this, 'add_wishlist_icon_toggle_in_course_carousel_widget' ) );
		add_action( 'masteriyo_elementor_integration_widget_after_register_controls', array( $this, 'add_style_controls_in_course_list_widget' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'extend_wishlist_script_enqueue_condition' ), 20 );
	}

	/**
	 * Add wishlist icon toggle to course list elementor widget.
	 *
	 * @since 2.5.14
	 *
	 * @param \Masteriyo\Addons\ElementorIntegration\WidgetBase $course_list
	 */
	public function add_wishlist_icon_toggle_in_course_list_widget( $course_list ) {
		$course_list->add_on_off_switch_control(
			'show_wishlist_icon',
			__( 'Wishlist Icon', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-wishlist' => 'display: none !important;',
			),
			array(
				'position' => array(
					'type' => 'control',
					'at'   => 'after',
					'of'   => 'show_course_title',
				),
			)
		);
	}

	/**
	 * Add wishlist icon toggle to course carousel elementor widget.
	 *
	 * @param \Masteriyo\Addons\ElementorIntegration\WidgetBase $course_carousel
	 */
	public function add_wishlist_icon_toggle_in_course_carousel_widget( $course_carousel ) {
		$course_carousel->add_on_off_switch_control(
			'show_wishlist_icon',
			__( 'Wishlist Icon', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-wishlist' => 'display: none !important;',
			),
			array(
				'position' => array(
					'type' => 'control',
					'at'   => 'after',
					'of'   => 'show_course_title',
				),
			)
		);
	}

	/**
	 * Add style controls for wishlist icon in course list elementor widget.
	 *
	 * @since 2.5.14
	 *
	 * @param \Masteriyo\Addons\ElementorIntegration\WidgetBase $widget
	 */
	public function add_style_controls_in_course_list_widget( $widget ) {
		if ( ! in_array( $widget->get_name(), array( 'masteriyo-course-list', 'masteriyo-course-carousel' ), true ) ) {
			return;
		}

		$widget->start_controls_section(
			'wishlist_icon_styles',
			array(
				'label' => __( 'Wishlist Icon', 'learning-management-system' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$widget->add_control(
			'wishlist_icon_size',
			array(
				'label'      => __( 'Icon Size', 'learning-management-system' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_padding',
			array(
				'label'      => __( 'Padding', 'learning-management-system' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_margin',
			array(
				'label'      => __( 'Margin', 'learning-management-system' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_styles_tabs_divider',
			array(
				'type' => \Elementor\Controls_Manager::DIVIDER,
			)
		);

		$widget->start_controls_tabs( 'wishlist_icon_states' );

		$widget->start_controls_tab(
			'wishlist_icon_normal_state_style_tab',
			array(
				'label' => __( 'Normal', 'learning-management-system' ),
			)
		);

		$widget->add_control(
			'wishlist_icon_color',
			array(
				'label'     => __( 'Color', 'learning-management-system' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle svg' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .masteriyo-wishlist-toggle.active svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_background_color',
			array(
				'label'     => __( 'Background Color', 'learning-management-system' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle' => 'background-color: {{VALUE}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_border_styles_popover',
			array(
				'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'label'        => esc_html__( 'Border', 'learning-management-system' ),
				'label_off'    => esc_html__( 'Default', 'learning-management-system' ),
				'label_on'     => esc_html__( 'Custom', 'learning-management-system' ),
				'return_value' => 'yes',
			)
		);

		$widget->start_popover();

		$widget->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'wishlist_icon_border_styles',
				'label'    => __( 'Border', 'learning-management-system' ),
				'selector' => '{{WRAPPER}} .masteriyo-wishlist-toggle',
			)
		);

		$widget->add_control(
			'wishlist_icon_border_radius',
			array(
				'label'      => __( 'Border Radius', 'learning-management-system' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$widget->end_popover();

		$widget->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'wishlist_icon_box_shadow',
				'label'    => __( 'Box Shadow', 'learning-management-system' ),
				'selector' => '{{WRAPPER}} .masteriyo-wishlist-toggle',
			)
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			'wishlist_icon_hover_state_style_tab',
			array(
				'label' => __( 'Hover', 'learning-management-system' ),
			)
		);

		$widget->add_control(
			'wishlist_icon_hover_color',
			array(
				'label'     => __( 'Color', 'learning-management-system' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle:hover svg' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .masteriyo-wishlist-toggle.active:hover svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_hover_background_color',
			array(
				'label'     => __( 'Background Color', 'learning-management-system' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$widget->add_control(
			'wishlist_icon_hover_border_styles_popover',
			array(
				'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'label'        => esc_html__( 'Border', 'learning-management-system' ),
				'label_off'    => esc_html__( 'Default', 'learning-management-system' ),
				'label_on'     => esc_html__( 'Custom', 'learning-management-system' ),
				'return_value' => 'yes',
			)
		);

		$widget->start_popover();

		$widget->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'wishlist_icon_hover_border_styles',
				'label'    => __( 'Border', 'learning-management-system' ),
				'selector' => '{{WRAPPER}} .masteriyo-wishlist-toggle:hover',
			)
		);

		$widget->add_control(
			'wishlist_icon_hover_border_radius',
			array(
				'label'      => __( 'Border Radius', 'learning-management-system' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-wishlist-toggle:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$widget->end_popover();

		$widget->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'wishlist_icon_hover_box_shadow',
				'label'    => __( 'Box Shadow', 'learning-management-system' ),
				'selector' => '{{WRAPPER}} .masteriyo-wishlist-toggle:hover',
			)
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->end_controls_section();
	}

	/**
	 * Extend wishlist script enqueue condition to include Elementor pages with the carousel widget.
	 *
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function extend_wishlist_script_enqueue_condition( $scripts ) {
		if ( ! isset( $scripts['wishlist'] ) ) {
			return $scripts;
		}

		$original_callback               = $scripts['wishlist']['callback'];
		$scripts['wishlist']['callback'] = function() use ( $original_callback ) {
			if ( is_callable( $original_callback ) && call_user_func( $original_callback ) ) {
				return true;
			}

			global $post;

			if ( $post && class_exists( '\Elementor\Plugin' ) ) {
				$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
				$elementor_arr  = is_array( $elementor_data ) ? $elementor_data : json_decode( (string) $elementor_data, true );
				if ( is_array( $elementor_arr ) && self::elementor_elements_contain_widget( $elementor_arr, 'masteriyo-course-carousel' ) ) {
					return true;
				}
			}

			return false;
		};

		return $scripts;
	}

	/**
	 * Recursively check whether any element in Elementor data uses the given widget type.
	 *
	 * @param array  $elements    Decoded Elementor elements array.
	 * @param string $widget_type Widget type slug to search for.
	 * @return bool
	 */
	private static function elementor_elements_contain_widget( array $elements, string $widget_type ): bool {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && $widget_type === $element['widgetType'] ) {
				return true;
			}
			if ( ! empty( $element['elements'] ) && self::elementor_elements_contain_widget( $element['elements'], $widget_type ) ) {
				return true;
			}
		}
		return false;
	}
}
