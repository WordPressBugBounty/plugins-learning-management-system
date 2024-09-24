<?php
/**
 * Masteriyo Single Course Rating Bricks element class.
 *
 * @since 1.11.3
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseQuery;
use Masteriyo\Taxonomy\Taxonomy;


/**
* Masteriyo Single Course Retake Elements Class.
*
* @since 1.11.3
*/
class CourseRetakeElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_retake';
	public $icon     = 'fa-solid fa-rotate';

	/**
	* Bricks single course retake label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Retake', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for single course retake CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_retake'] = array(
			'title' => esc_html__( 'Course Retake', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {

		$this->controls['course_retake_color'] = array(
			'tab'    => 'content',
			'label'  => esc_html__( 'Retake Color', 'learning-management-system' ),
			'type'   => 'color',
			'group'  => 'course_retake',
			'inline' => true,
			'css'    => array(
				array(
					'property' => 'fill',
					'selector' => '{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn > span svg',
				),
			),
		);

		// masteriyo-rating svg
		$this->controls['course_retake_svg_size'] = array(
			'tab'    => 'content',
			'label'  => esc_html__( 'Retake Size', 'learning-management-system' ),
			'type'   => 'number',
			'min'    => 0,
			'group'  => 'course_retake',
			'step'   => '0.5', // Default: 1
			'inline' => true,
			'unit'   => 'px',
			'max'    => 50,
			'css'    => array(
				array(
					'property' => 'width',
					'selector' => '{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn > span svg',
				),
				array(
					'property'  => 'height',
					'selector'  => '{{WRAPPER}} .masteriyo-time-btn .masteriyo-retake-btn > span svg',
					'important' => true,
				),
				array(
					'property'  => 'height',
					'selector'  => '{{WRAPPER}} .masteriyo-time-btn masteriyo-retake-btn',
					'important' => true,
				),

			),
		);

		//margin
		$this->controls['course_rating_margin'] = array(
			'tab'   => 'content',
			'group' => 'course_retake',
			'label' => esc_html__( 'Margin', 'learning-management-system' ),
			'type'  => 'dimensions',
			'css'   => array(
				array(
					'property' => 'margin',
					'selector' => '{{WRAPPER}} .masteriyo-time-btn masteriyo-retake-btn',
				),
			),
		);

	}



	/**
	 * Render the element output for the frontend of Single Course Retake Elements Element
	 *
	 * Includes border, color, and background color etc. options for the
	 * element reflected based on components controls.
	 *
	 * @since 1.11.3
	 */
	public function render() {
		// Get the current page URL.
		$course = Helper::get_bricks_preview_course();
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 

		if ( $course ) {

			if ( $course->get_enable_course_retake() ) {
				?>
				<span class="masteriyo-time-btn masteriyo-retake-btn">
					<?php	masteriyo_template_course_retake_button( $course ); ?>
				</span>
				<?php
			}
		}
	}
}

