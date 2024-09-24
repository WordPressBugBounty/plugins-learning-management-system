<?php
/**
 * Masteriyo course google classroom code and button elementor widget class.
 *
 * @package Masteriyo\Addons\GoogleClassroomIntegration
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\GoogleClassroomIntegration;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\WidgetBase;
use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course google classroom code and button elementor widget class.
 *
 * @package Masteriyo\Addons\GoogleClassroomIntegration
 *
 * @since 1.11.0
 */
class CourseGoogleClassroomMetaWidget extends WidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-google-classroom-code-n-completion-button';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Google Classroom Meta', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.11.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'course-google-classroom-meta-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.11.0
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'google classroom', 'completion button', 'classroom code', 'button' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.11.0
	 */
	protected function register_content_controls() {}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.11.0
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'classroom_button_styles_section',
			array(
				'label' => esc_html__( 'Google Classroom Button', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'google_classroom_completion_button_',
			'.masteriyo-course-complete',
			array(
				'custom_selectors' => array(
					'text_color'       => '{{WRAPPER}} *',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-course-complete:hover *',
					'typography'       => '{{WRAPPER}} *',
					'hover_typography' => '{{WRAPPER}} .masteriyo-course-complete:hover *',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'classroom_code_styles_section',
			array(
				'label' => esc_html__( 'Google Classroom Code', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'google_classroom_code_',
			'.masteriyo-copy-this-text',
			array(
				'custom_selectors' => array(
					'text_color'       => '{{WRAPPER}} .masteriyo-copy-this-text',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-copy-this-text:hover *',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render classroom code widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.11.0
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}
		if ( ! ( new Addons() )->is_active( MASTERIYO_GOOGLE_CLASSROOM_INTEGRATION_SLUG ) ) {
			return;
		}
		do_action( 'masteriyo_elementor_classroom_widget', $course );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.11.0
	 */
	protected function render() {
		$course = $this->get_course_to_render();
		if ( ! ( new Addons() )->is_active( MASTERIYO_GOOGLE_CLASSROOM_INTEGRATION_SLUG ) ) {
			return;
		}
		if ( $course ) {
			do_action( 'masteriyo_elementor_classroom_widget', $course );
		}
	}
}
