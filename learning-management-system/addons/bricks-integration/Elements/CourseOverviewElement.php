<?php
/**
 * Masteriyo course overview Bricks element class.
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
* Masteriyo Single Course Overview Elements class.
*
* @since 1.11.3
*/
class CourseOverviewElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_overview';
	public $icon     = 'fa-solid fa-file-circle-check';

	/**
	* Bricks Single Course Overview Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Overview', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for course overview CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_overview'] = array(
			'title' => esc_html__( 'Course Overview', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_overview controls
		//visibility
			$this->controls['show_course_overview'] = array(
				'tab'       => 'content',
				'group'     => 'course_overview',
				'label'     => esc_html__( 'Show Overview', 'learning-management-system' ),
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
						'selector' => '.course-overview',
					),
				),
			);
			//border
			$this->controls['course_overview_border'] = array(
				'tab'   => 'content',
				'group' => 'course_overview',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.course-overview',
					),
				),
			);
			//margin
			$this->controls['course_overview_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_overview',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.course-overview',
					),
				),
			);

			//padding
			$this->controls['course_overview_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_overview',
				'label' => esc_html__( 'padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.course-overview',
					),
				),
			);

			//box shadow
			$this->controls['course_overview_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_overview',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.course-overview',
					),
				),
			);

			//typography
			$this->controls['course_overview_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_overview',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .course-overview',
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
	 * Render the element output for the frontend of Single Course Overview Element
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
			masteriyo_single_course_overview( $course );
			echo '</div>';
		}
	}
}
