<?php
/**
 * Masteriyo Course Title Element Bricks element class.
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
* Masteriyo Course Title Element class.
*
* @since 1.11.3
*/
class CourseTitleElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_title';
	public $icon     = 'ti-text';

	/**
	* Bricks Course Title Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Title', 'learning-management-system' );
	}

	/**
	* Bricks courses set controls groups for Course Title CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_title'] = array(
			'title' => esc_html__( 'Course Title', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_title controls
		//visibility
			$this->controls['show_course_title'] = array(
				'tab'       => 'content',
				'group'     => 'course_title',
				'label'     => esc_html__( 'Show Title', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-single-course--title',
					),
				),
			);
			//border
			$this->controls['course_title_border'] = array(
				'tab'   => 'content',
				'group' => 'course_title',
				'label' => esc_html__( 'Course Title Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-single-course--title',
					),
				),
			);
			//margin
			$this->controls['course_title_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_title',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-single-course--title',
					),
				),
			);

			//padding
			$this->controls['course_title_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_title',
				'label' => esc_html__( 'padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-single-course--title',
					),
				),
			);

			//box shadow
			$this->controls['course_title_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_title',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-single-course--title',
					),
				),
			);

			//typography
			$this->controls['course_title_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_title',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-single-course--title',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'Font size',
				),
			);
	}



	/**
	 * Render the element output for the frontend of Course Title Element
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
			echo "<div {$this->render_attributes( '_root' )}>";
			masteriyo_get_template(
				'single-course/title.php',
				array(
					'course' => $course,
				)
			);
			echo '</div>';
		}
	}
}
