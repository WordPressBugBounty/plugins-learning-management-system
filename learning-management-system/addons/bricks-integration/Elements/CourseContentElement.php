<?php
/**
 * Masteriyo single course content bricks element class.
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
* Masteriyo single course content elements class.
*
* @since 1.11.3
*/
class CourseContentElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_contents';
	public $icon     = 'fa-regular fa-newspaper';

	/**
	* Bricks single course content label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Content', 'learning-management-system' );
	}

	/**
	* Bricks controls groups for single course content CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_contents'] = array(
			'title' => esc_html__( 'Course Contents', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//visibility
			$this->controls['show_course_contents'] = array(
				'tab'       => 'content',
				'group'     => 'course_contents',
				'label'     => esc_html__( 'Show Title', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-single-course--main__content',
					),
				),
			);
			//border
			$this->controls['course_contents_border'] = array(
				'tab'   => 'content',
				'group' => 'course_contents',
				'label' => esc_html__( 'Course Title Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-single-course--main__content',
					),
				),
			);
			//margin
			$this->controls['course_contents_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_contents',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-single-course--main__content',
					),
				),
			);

			//padding
			$this->controls['course_contents_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_contents',
				'label' => esc_html__( 'padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-single-course--main__content',
					),
				),
			);

			//box shadow
			$this->controls['course_contents_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_contents',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-single-course--main__content',
					),
				),
			);

			//typography
			$this->controls['course_contents_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_contents',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-single-course--main__content',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'Font size',
					'color',
				),
			);
	}



	/**
	 * Render the element output for the frontend of Single Course Content Element
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
			echo "<div {$this->render_attributes( '_root' )} style='width:100%;' >";
			masteriyo_template_single_course_main_content( $course );
			echo '</div>';
		}
	}
}
