<?php
/**
 * Masteriyo course progress elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\SingleCourseWidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course progress elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */
class CourseProgressWidget extends SingleCourseWidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-progress';
	}

	/**
	 * Get widget title.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Progress', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since x.x.x
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-course-progress-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'progress', 'completion', 'bar', 'percent', 'enrolled' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since x.x.x
	 */
	protected function register_content_controls() {}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since x.x.x
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'progress_styles_section',
			array(
				'label' => esc_html__( 'Progress Bar', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'progress_bar_color',
			array(
				'label'     => __( 'Bar Fill Color', 'learning-management-system' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-progress-fill' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'progress_bar_bg_color',
			array(
				'label'     => __( 'Bar Background Color', 'learning-management-system' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-progress-bar' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'progress_bar_height',
			array(
				'label'      => __( 'Bar Height', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 2,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-progress-bar' => 'height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_text_region_style_controls(
			'progress_title_',
			'.course-progress .progress-text',
			array(
				'disable_align' => true,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output in the editor.
	 *
	 * @since x.x.x
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}

		$this->render_feature_disabled_notice( __( 'Course progress bar will display here for enrolled users.', 'learning-management-system' ) );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since x.x.x
	 */
	protected function render() {
		$course = $this->get_course_to_render();

		if ( ! $course ) {
			$this->render_no_course_notice();
			return;
		}

		$this->render_buffered_or_notice(
			function() use ( $course ) {
				// Suppress retake hooks — Elementor places the retake widget separately.
				remove_action( 'masteriyo_template_course_inside_progress', 'masteriyo_single_course_retake_button_modal' );
				remove_action( 'masteriyo_template_course_inside_progress', 'masteriyo_template_course_retake_button', 15 );

				masteriyo_single_course_progress_bar( $course );

				add_action( 'masteriyo_template_course_inside_progress', 'masteriyo_single_course_retake_button_modal' );
				add_action( 'masteriyo_template_course_inside_progress', 'masteriyo_template_course_retake_button', 15 );
			},
			__( 'Course progress bar will display here for enrolled users.', 'learning-management-system' )
		);
	}
}
