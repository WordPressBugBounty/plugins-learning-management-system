<?php
/**
 * Masteriyo course highlights elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\SingleCourseWidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course highlights elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */
class CourseHighlightsWidget extends SingleCourseWidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-highlights';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Highlights', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.6.12
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-course-highlights-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.6.12
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'description', 'highlights' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.6.12
	 */
	protected function register_content_controls() {}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.6.12
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'highlights_styles_section',
			array(
				'label' => esc_html__( 'Highlights', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'default_styles',
			array(
				'type'      => Controls_Manager::HIDDEN,
				'default'   => 'yes',
				'selectors' => array(
					'{{WRAPPER}} .title' => 'display: none;',
				),
			)
		);

		$this->add_text_region_style_controls(
			'highlights_',
			'.masteriyo-course--content__description',
			array(
				'custom_selectors'   => array(
					'text_color'       => '{{WRAPPER}} *',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-course--content__description:hover *',
					'typography'       => '{{WRAPPER}} *',
					'hover_typography' => '{{WRAPPER}} .masteriyo-course--content__description:hover *',
				),
				'normal_state_start' => function() {
					$this->add_control(
						'spacing',
						array(
							'label'      => __( 'Spacing', 'learning-management-system' ),
							'type'       => Controls_Manager::SLIDER,
							'size_units' => array( 'px' ),
							'range'      => array(
								'px' => array(
									'min' => 0,
									'max' => 300,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .masteriyo-course--content__description li:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
							),
						)
					);
				},
				'hover_state_start'  => function() {
					$this->add_control(
						'hover_spacing',
						array(
							'label'      => __( 'Spacing', 'learning-management-system' ),
							'type'       => Controls_Manager::SLIDER,
							'size_units' => array( 'px' ),
							'range'      => array(
								'px' => array(
									'min' => 0,
									'max' => 300,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .masteriyo-course--content__description:hover li:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
							),
						)
					);
				},
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render heading widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.6.12
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}

		$this->render_highlights( $course );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.6.12
	 */
	protected function render() {
		$course = $this->get_course_to_render();

		if ( $course ) {
			$this->render_highlights( $course );
		} else {
			$this->render_no_course_notice();
		}
	}

	/**
	 * Render highlights, suppressing the social-share output core adds after them.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\Course $course
	 */
	private function render_highlights( $course ) {
		$removed = $this->suppress_hook_callbacks_by_method(
			array(
				'masteriyo_after_single_course_highlights' => array( 'render_social_shares_in_single_course_page' ),
			)
		);

		$this->render_buffered_or_notice(
			function () use ( $course ) {
				masteriyo_single_course_highlights( $course );
			},
			__( 'Course highlights will display here when highlights are added to the course.', 'learning-management-system' )
		);

		$this->restore_hook_callbacks( $removed );
	}
}
