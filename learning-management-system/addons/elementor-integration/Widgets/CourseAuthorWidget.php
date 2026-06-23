<?php
/**
 * Masteriyo course author elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\SingleCourseWidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course author elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */
class CourseAuthorWidget extends SingleCourseWidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-author';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Author and Rating', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.6.12
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-course-author-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.6.12
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'author', 'instructor', 'rating' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since 1.6.12
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
			'show_avatar',
			__( 'Avatar', 'learning-management-system' ),
			array(),
			array(
				'{{WRAPPER}} .masteriyo-course-author img' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_name',
			__( 'Name', 'learning-management-system' ),
			array(),
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

		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.6.12
	 */
	protected function register_style_controls() {
		$this->register_author_styles_section();
		$this->register_author_avatar_styles_section();
		$this->register_author_name_styles_section();
	}

	/**
	 * Register author style controls section.
	 *
	 * @since 1.6.12
	 */
	protected function register_author_styles_section() {
		$this->start_controls_section(
			'author_styles',
			array(
				'label' => __( 'Course Author and Rating', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'default_styles',
			array(
				'type'      => Controls_Manager::HIDDEN,
				'default'   => 'yes',
				'selectors' => array(
					// '{{WRAPPER}} .masteriyo-course-author' => 'display: block !important;',
					'{{WRAPPER}} .masteriyo-course-author a' => 'display: inline-flex;',
				),
			)
		);

		$this->add_responsive_control(
			'direction',
			array(
				'label'     => esc_html__( 'Direction', 'learning-management-system' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => array(
					'row'    => esc_html__( 'Row', 'learning-management-system' ),
					'column' => esc_html__( 'Column', 'learning-management-system' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-course-author a' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'vertical_align',
			array(
				'label'     => esc_html__( 'Vertical Alignment', 'learning-management-system' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center',
				'options'   => array(
					''              => esc_html__( 'Default', 'learning-management-system' ),
					'flex-start'    => esc_html__( 'Start', 'learning-management-system' ),
					'center'        => esc_html__( 'Center', 'learning-management-system' ),
					'flex-end'      => esc_html__( 'End', 'learning-management-system' ),
					'space-between' => esc_html__( 'Space Between', 'learning-management-system' ),
					'space-around'  => esc_html__( 'Space Around', 'learning-management-system' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'learning-management-system' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-course-author a' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'horizontal_align',
			array(
				'label'     => esc_html__( 'Horizontal Alignment', 'learning-management-system' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					''              => esc_html__( 'Default', 'learning-management-system' ),
					'flex-start'    => esc_html__( 'Start', 'learning-management-system' ),
					'center'        => esc_html__( 'Center', 'learning-management-system' ),
					'flex-end'      => esc_html__( 'End', 'learning-management-system' ),
					'space-between' => esc_html__( 'Space Between', 'learning-management-system' ),
					'space-around'  => esc_html__( 'Space Around', 'learning-management-system' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'learning-management-system' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-course-author a' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'author_background_color',
			array(
				'label'     => __( 'Background Color', 'learning-management-system' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .masteriyo-course-author' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'author_border_styles_popover_toggle',
			array(
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label'        => esc_html__( 'Border', 'learning-management-system' ),
				'label_off'    => esc_html__( 'Default', 'learning-management-system' ),
				'label_on'     => esc_html__( 'Custom', 'learning-management-system' ),
				'return_value' => 'yes',
			)
		);

		$this->start_popover();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'author_border_styles',
				'label'    => __( 'Border', 'learning-management-system' ),
				'selector' => '{{WRAPPER}} .masteriyo-course-author',
			)
		);

		$this->add_control(
			'author_border_radius',
			array(
				'label'      => __( 'Border Radius', 'learning-management-system' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-author' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_popover();

		$this->add_responsive_control(
			'author_padding',
			array(
				'label'      => __( 'Padding', 'learning-management-system' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-author' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'author_margin',
			array(
				'label'      => __( 'Margin', 'learning-management-system' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-author' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register author avatar style controls section.
	 *
	 * @since 1.6.12
	 */
	protected function register_author_avatar_styles_section() {
		$this->start_controls_section(
			'author_avatar_styles',
			array(
				'label' => __( 'Author Avatar', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'author_avatar_size',
			array(
				'label'      => __( 'Size', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 15,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-author img' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'author_avatar_border_radius',
			array(
				'label'      => __( 'Border Radius', 'learning-management-system' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-author img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register author name style controls section.
	 *
	 * @since 1.6.12
	 */
	protected function register_author_name_styles_section() {
		$this->start_controls_section(
			'author_name_styles_section',
			array(
				'label' => esc_html__( 'Author Name', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_text_region_style_controls(
			'author_name_',
			'.masteriyo-course-author .masteriyo-course-author--name',
			array(
				'disable_align' => true,
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

		$author = masteriyo_get_user( $course->get_author_id() );

		if ( ! $author ) {
			$this->render_feature_disabled_notice( __( 'Author and rating will display here dynamically for each course.', 'learning-management-system' ) );
			return;
		}

		$this->render_author_and_rating( $course, $author );
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

		$author = masteriyo_get_user( $course->get_author_id() );

		if ( ! $author ) {
			$this->render_feature_disabled_notice( __( 'Author and rating will display here dynamically for each course.', 'learning-management-system' ) );
			return;
		}

		$this->render_author_and_rating( $course, $author );
	}

	/**
	 * Render author-and-rating template.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\Course $course
	 * @param \Masteriyo\Models\User   $author
	 */
	private function render_author_and_rating( $course, $author ) {
		ob_start();
		masteriyo_get_template(
			'single-course/author-and-rating.php',
			array(
				'course'     => $course,
				'author'     => $author,
				'attributes' => array(
					'enableRating'        => true,
					'enableAuthorsAvatar' => true,
					'enableAuthorsName'   => true,
				),
			)
		);
		$output = ob_get_clean();

		if ( false === strpos( $output, 'masteriyo-rating' ) && ( Helper::is_elementor_editor() || Helper::is_elementor_preview() ) ) {
			$placeholder = '<span class="masteriyo-rating masteriyo-rating--placeholder" style="opacity:0.4;font-size:13px;display:inline-flex;align-items:center;gap:4px;">'
				. '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"/></svg>'
				. esc_html__( 'Rating will appear here', 'learning-management-system' )
				. '</span>';
			$pos         = strrpos( $output, '</div>' );

			if ( false !== $pos ) {
				$output = substr_replace( $output, $placeholder, $pos, 0 );
			}
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
