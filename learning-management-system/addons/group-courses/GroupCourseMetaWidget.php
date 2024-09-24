<?php
/**
 * Masteriyo group course elementor widget class.
 *
 * @package Masteriyo\Addons\GroupCourses
 *
 * @since 1.12.2
 */

namespace Masteriyo\Addons\GroupCourses;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\WidgetBase;
use Masteriyo\Addons\GroupCourses\Models\Group;
use Masteriyo\Pro\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo GroupCourses elementor widget class.
 *
 * @package Masteriyo\Addons\GroupCourses
 *
 * @since 1.12.2
 */
class GroupCourseMetaWidget extends WidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.12.2
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-group-course-code';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.12.2
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Group Course', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.12.2
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'group-course-meta-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.12.2
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'group', 'group course', 'course' );
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

		$this->register_group_course_styles_section();

	}

	/**
	 * Register group course style controls section.
	 *
	 * @since 1.12.2
	 */
	protected function register_group_course_styles_section() {

		$this->start_controls_section(
			'group_courses_text_styles_section',
			array(
				'label' => esc_html__( 'Group Courses Text', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'group_courses_text_',
			'.masteriyo-group-course__group-title',
			array(
				'disable_align'         => true,
				'disable_border'        => true,
				'disable_border_radius' => true,
				'custom_selectors'      => array(
					'text_color'       => '{{WRAPPER}} .masteriyo-group-course__group-title',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-group-course__group-title:hover',
					'typography'       => '{{WRAPPER}} .masteriyo-group-course__group-title',
					'hover_typography' => '{{WRAPPER}} .masteriyo-group-course__group-title:hover',
				),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'group_courses_btn_styles_section',
			array(
				'label' => esc_html__( 'Group Course Button', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'group_courses_btn_',
			'.masteriyo-group-course__buy-now-button',
			array(
				'custom_selectors' => array(
					'text_color'       => '{{WRAPPER}} .masteriyo-group-course__buy-now-button',
					'hover_text_color' => '{{WRAPPER}} .masteriyo-group-course__buy-now-button:hover',
					'typography'       => '{{WRAPPER}} .masteriyo-group-course__buy-now-button',
					'hover_typography' => '{{WRAPPER}} .masteriyo-group-course__buy-now-button:hover',
				),
			)
		);

		$this->end_controls_section();

	}

	/**
	 * Render group course widget output in the editor.
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
		if ( ! ( new Addons() )->is_active( MASTERIYO_GROUP_COURSES_ADDON_SLUG ) ) {
			return;
		}

		$now = new GroupCoursesAddon();
		$now->masteriyo_template_group_buy_button( $course );

	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.12.2
	 */
	protected function render() {
		$course = $this->get_course_to_render();
		if ( ! ( new Addons() )->is_active( MASTERIYO_GROUP_COURSES_ADDON_SLUG ) ) {
			return;
		}
		if ( $course ) {
			$now = new GroupCoursesAddon();
			$now->masteriyo_template_group_buy_button( $course );
		}
	}
}
