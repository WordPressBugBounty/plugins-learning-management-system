<?php
/**
 * Masteriyo course coming soon timer elementor widget class.
 *
 * @package Masteriyo\Addons\CourseComingSoon
 *
 * @since 1.12.2
 */

namespace Masteriyo\Addons\CourseComingSoon;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\WidgetBase;
use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course coming soon timer elementor widget class.
 *
 * @package Masteriyo\Addons\CourseComingSoon
 *
 * @since 1.12.2
 */
class CourseComingSoonMetaWidget extends WidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.12.2
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-coming-soon-code';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.12.2
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Coming Soon', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.12.2
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'course-coming-soon-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.12.2
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'coming soon', 'coming soon code', 'course coming soon' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.12.2
	 */
	protected function register_content_controls() {}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.12.2
	 */
	protected function register_style_controls() {

		$this->register_course_coming_soon_styles_section();

	}

	/**
	 * Register course coming soon style controls section.
	 *
	 * @since 1.12.2
	 */
	protected function register_course_coming_soon_styles_section() {

		$this->start_controls_section(
			'course_coming_soon_timer_styles_section',
			array(
				'label' => esc_html__( 'Course Coming Soon Timer', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'course_coming_soon_timer_',
			'.masteriyo-single-course--course-coming-soon-timer',
			array(
				'disable_align'         => true,
				'disable_border'        => true,
				'disable_border_radius' => true,
				'custom_selectors'      => array(
					'text_color'       => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-timer *',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-timer:hover *',
					'typography'       => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-timer *',
					'hover_typography' => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-timer:hover *',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'course_coming_soon_text_styles_section',
			array(
				'label' => esc_html__( 'Course Coming Soon Text', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'course_coming_soon_text_',
			'.masteriyo-single-course--course-coming-soon-text',
			array(
				'disable_border'        => true,
				'disable_border_radius' => true,
				'custom_selectors'      => array(
					'text_color'       => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-text *',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-text:hover *',
					'typography'       => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-text *',
					'hover_typography' => '{{WRAPPER}} .masteriyo-single-course--course-coming-soon-text:hover *',
				),
			)
		);
		$this->end_controls_section();

	}

	/**
	 * Render course coming soon code widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.12.2
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}
		if ( ! ( new Addons() )->is_active( MASTERIYO_COURSE_COMING_SOON_SLUG ) ) {
			return;
		}

		do_action( 'masteriyo_elementor_course_coming_soon_widget', $course );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.12.2
	 */
	protected function render() {
		$course = $this->get_course_to_render();
		if ( ! ( new Addons() )->is_active( MASTERIYO_COURSE_COMING_SOON_SLUG ) ) {
			return;
		}
		if ( $course ) {
			do_action( 'masteriyo_elementor_course_coming_soon_widget', $course );
		}
	}
}
