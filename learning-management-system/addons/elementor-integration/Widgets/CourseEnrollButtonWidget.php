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
use Masteriyo\Addons\ElementorIntegration\WidgetBase;
use Masteriyo\Query\CourseProgressQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course enroll button elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since 1.6.12
 */
class CourseEnrollButtonWidget extends WidgetBase {

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
		return __( 'Course Enroll Button', 'learning-management-system' );
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
	protected function register_content_controls() {}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since 1.6.12
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'enroll_button_styles_section',
			array(
				'label' => esc_html__( 'Enroll Button', 'learning-management-system' ),
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

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );
		$summary  = $progress ? $progress->get_summary( 'all' ) : '';
		?>
		<style>

			.masteriyo-time-btn .masteriyo-course-price{
				display: none;
			}

			.masteriyo-course--content{
				padding: 0;
			}
		</style>
		<?php
		if ( $course ) {
			?>
			<div class="masteriyo-course--content">
			<?php
			masteriyo_get_template(
				'single-course/price-and-enroll-button.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);
			?>
			</div>
			<?php
		}
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 1.6.12
	 */
	protected function render() {
		$course = $this->get_course_to_render();
		$query  = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );
		$summary  = $progress ? $progress->get_summary( 'all' ) : '';
		?>
		<style>

			.masteriyo-time-btn .masteriyo-course-price{
				display: none;
			}

			.masteriyo-course--content{
				padding: 0;
			}
		</style>
		<?php
		if ( $course ) {
			?>
			<div class="masteriyo-course--content">
			<?php
			masteriyo_get_template(
				'single-course/price-and-enroll-button.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);
			?>
			</div>
			<?php
		}
	}
}
