<?php
/**
 * Masteriyo course enroll button elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\SingleCourseWidgetBase;
use Masteriyo\Query\CourseProgressQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course enroll button elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */
class CourseEnrollButtonWidget extends SingleCourseWidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-enroll-button';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Price and Enroll Button', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.6.12
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-course-enroll-button-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.6.12
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'buy', 'enroll', 'start', 'continue', 'course', 'button' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.6.12
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'enroll_button_content_section',
			array(
				'label' => esc_html__( 'Price and Enroll Button', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'                => esc_html__( 'Show Price', 'learning-management-system' ),
				'type'                 => Controls_Manager::SWITCHER,
				'label_on'             => esc_html__( 'Yes', 'learning-management-system' ),
				'label_off'            => esc_html__( 'No', 'learning-management-system' ),
				'return_value'         => 'yes',
				'default'              => 'yes',
				'selectors'            => array(
					'{{WRAPPER}} .masteriyo-course-price' => '{{VALUE}}',
				),
				'selectors_dictionary' => array(
					''    => 'display: none !important;',
					'yes' => '',
				),
			)
		);

		$this->add_control(
			'show_enroll_button',
			array(
				'label'                => esc_html__( 'Show Enroll Button', 'learning-management-system' ),
				'type'                 => Controls_Manager::SWITCHER,
				'label_on'             => esc_html__( 'Yes', 'learning-management-system' ),
				'label_off'            => esc_html__( 'No', 'learning-management-system' ),
				'return_value'         => 'yes',
				'default'              => 'yes',
				'selectors'            => array(
					'{{WRAPPER}} .masteriyo-single-course--btn, {{WRAPPER}} .masteriyo-btn' => '{{VALUE}}',
				),
				'selectors_dictionary' => array(
					''    => 'display: none !important;',
					'yes' => '',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.6.12
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'enroll_button_styles_section',
			array(
				'label' => esc_html__( 'Price and Enroll Button', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_text_region_style_controls(
			'enroll_button_',
			'.masteriyo-single-course--btn',
			array(
				'custom_selectors' => array(
					'text_align'       => '{{WRAPPER}}',
					'hover_text_align' => '{{WRAPPER}}::hover',
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
	 * @since 1.6.12
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );
		$summary  = $progress ? $progress->get_summary( 'all' ) : '';
		$removed  = $this->suppress_hook_callbacks_by_method( $this->get_group_buy_hook_map() );

		// Suppress retake hook — the retake widget renders it as a separate Elementor widget.
		remove_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_layout_1_single_course_retake_button', 15 );

		masteriyo_get_template(
			'single-course/layout-1/price-and-enroll-button.php',
			array(
				'course'   => $course,
				'progress' => $progress,
				'summary'  => $summary,
			)
		);

		add_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_layout_1_single_course_retake_button', 15 );
		$this->restore_hook_callbacks( $removed );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.6.12
	 */
	protected function render() {
		$course = $this->get_course_to_render();

		if ( ! $course ) {
			$this->render_no_course_notice();
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );
		$summary  = $progress ? $progress->get_summary( 'all' ) : '';
		$removed  = $this->suppress_hook_callbacks_by_method( $this->get_group_buy_hook_map() );

		// Suppress retake hook — the retake widget renders it as a separate Elementor widget.
		remove_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_layout_1_single_course_retake_button', 15 );

		masteriyo_get_template(
			'single-course/layout-1/price-and-enroll-button.php',
			array(
				'course'   => $course,
				'progress' => $progress,
				'summary'  => $summary,
			)
		);

		add_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_layout_1_single_course_retake_button', 15 );
		$this->restore_hook_callbacks( $removed );
	}

	/**
	 * Hooks => group-buy callbacks to suppress, so the button isn't duplicated here.
	 *
	 * @since x.x.x
	 *
	 * @return array<string,string[]>
	 */
	private function get_group_buy_hook_map() {
		return array(
			'masteriyo_template_enroll_button' => array( 'masteriyo_template_group_buy_button' ),
			'masteriyo_after_single_course_enroll_button_wrapper' => array( 'masteriyo_template_group_buy_button_for_new_layout' ),
		);
	}
}
