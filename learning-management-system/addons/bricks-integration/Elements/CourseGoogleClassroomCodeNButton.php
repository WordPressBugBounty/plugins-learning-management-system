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
use Masteriyo\Pro\Addons;
use Masteriyo\Query\CourseQuery;
use Masteriyo\Taxonomy\Taxonomy;


/**
* Masteriyo Single Course Google Classroom Code Elements Class.
*
* @since 1.11.3
*/
class CourseGoogleClassroomCodeNButton extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_google_classroom_code';
	public $icon     = 'ion-logo-googleplus';

	/**
	* Bricks single course retake label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Google Classroom Code', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for single course retake CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_google_classroom_code'] = array(
			'title' => esc_html__( 'Course Google Classroom Code', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {

		$this->controls['classroom_button_color'] = array(
			'tab'     => 'content',
			'label'   => esc_html__( 'Button Color', 'learning-management-system' ),
			'type'    => 'color',
			'group'   => 'course_google_classroom_code',
			'default' => 'inherit',
			'inline'  => true,
			'css'     => array(
				array(
					'property' => 'color',
					'selector' => '{{WRAPPER}} .masteriyo-course-complete',
				),
			),
		);

		// masteriyo-rating svg
		$this->controls['classroom_button_background'] = array(
			'tab'    => 'content',
			'label'  => esc_html__( 'Button Background', 'learning-management-system' ),
			'type'   => 'number',
			'min'    => 0,
			'group'  => 'course_google_classroom_code',
			'step'   => '0.5', // Default: 1
			'inline' => true,
			'unit'   => 'px',
			'max'    => 50,
			'css'    => array(
				array(
					'property' => 'background-color',
					'selector' => '{{WRAPPER}} .masteriyo-course-complete',
				),
			),
		);

	}



	/**
	 * Render the element output for the frontend of Single Course Google Classroom Code Elements Element
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
		if ( ! ( new Addons() )->is_active( MASTERIYO_GOOGLE_CLASSROOM_INTEGRATION_SLUG ) ) {
			return;
		}
		if ( $course ) {
			do_action( 'masteriyo_bricks_classroom_element', $course );
		}
	}
}

