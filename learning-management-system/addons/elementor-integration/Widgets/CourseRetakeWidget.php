<?php
/**
 * Masteriyo course retake elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\WidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course retake elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.11.0
 */
class CourseRetakeWidget extends WidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-retake';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Retake', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.11.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-course-retake-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.11.0
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'retake', 'course retake' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.11.0
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'general',
			array(
				'label' => __( 'General', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_on_off_switch_control(
			'show_icons',
			__( 'Icons', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn > span' => 'display: none !important;',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.11.0
	 */
	protected function register_style_controls() {
		$this->register_retake_icon_styles_section();
	}

	/**
	 * Register retake icon style controls section.
	 *
	 * @since 1.11.0
	 */
	protected function register_retake_icon_styles_section() {
		$this->start_controls_section(
			'retake_icon_styles',
			array(
				'label' => __( 'Icon', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'default_styles',
			array(
				'type'      => Controls_Manager::HIDDEN,
				'selectors' => array(
					'{{WRAPPER}} span svg' => 'display: inline-flex;',
				),
			)
		);
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon Color', 'learning-management-system' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} span svg' => 'fill: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => __( 'Icon Size', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 300,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn > span svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'spacing',
			array(
				'label'      => __( 'Spacing', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn span svg' => 'margin-left: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render heading widget output in the editor.
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

		?>
		<span class="masteriyo-time-btn masteriyo-retake-btn">
			<?php	masteriyo_template_course_retake_button( $course ); ?>
		</span>
		<?php
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.11.0
	 */
	protected function render() {
		$course = $this->get_course_to_render();

		if ( ! $course || ! $course->get_enable_course_retake() ) {
			return;
		}

		?>
		<span class="masteriyo-time-btn masteriyo-retake-btn">
			<?php	masteriyo_template_course_retake_button( $course ); ?>
		</span>
		<?php
	}
}
