<?php
/**
 * Masteriyo course expiration info elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\SingleCourseWidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo course expiration info elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */
class CourseExpirationInfoWidget extends SingleCourseWidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-course-expiration-info';
	}

	/**
	 * Get widget title.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Course Expiration', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since x.x.x
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-countdown';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'expiration', 'deadline', 'end date', 'course ends', 'cohort' );
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
	protected function register_style_controls() {}

	/**
	 * Render widget output in the editor.
	 *
	 * @since x.x.x
	 */
	protected function content_template() {
		$course = Helper::get_elementor_preview_course();

		if ( ! $course ) {
			$this->render_no_course_notice();
			return;
		}

		$this->render_expiration_info( $course );
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

		$this->render_expiration_info( $course );
	}

	/**
	 * Render the course expiration info, with an editor notice when it has nothing to show.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\Course $course
	 */
	private function render_expiration_info( $course ) {
		$this->render_buffered_or_notice(
			function () use ( $course ) {
				masteriyo_course_expiration_info( $course );
			},
			__( 'Course end date will display here when an end date is set for the course.', 'learning-management-system' )
		);
	}
}
