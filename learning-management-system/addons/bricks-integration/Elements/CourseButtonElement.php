<?php
/**
 * Masteriyo course button Bricks element class.
 *
 * @since 1.11.3
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;

/**
* Masteriyo course button elements class.
*
* @since 1.11.3
*/
class CourseButtonElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_button';
	public $icon     = 'ti-control-stop';

	/**
	* Bricks course button Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Button', 'learning-management-system' );
	}

	/**
	* Bricks course button set controls groups for course categories CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_button'] = array(
			'title' => esc_html__( 'Course Button', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_button controls
		//visibility
			$this->controls['show_course_button'] = array(
				'tab'       => 'content',
				'group'     => 'course_button',
				'label'     => esc_html__( 'Show Button', 'learning-management-system' ),
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
						'selector' => '.masteriyo-btn',
					),
				),
			);
			//border
			$this->controls['course_button_border'] = array(
				'tab'   => 'content',
				'group' => 'course_button',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-btn',
					),
				),
			);
			//margin
			$this->controls['course_button_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_button',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-btn',
					),
				),
			);

			//padding
			$this->controls['course_button_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_button',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-btn',
					),
				),
			);

			//box shadow
			$this->controls['course_button_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_button',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-btn',
					),
				),
			);

			//typography
			$this->controls['course_button_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_button',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-btn',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'text-decoration',
					'color',
				),
			);

			// .masteriyo-btn.masteriyo-btn-primary
			$this->controls['course_button_background_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Background color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'course_button',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'background-color',
						'selector' => '.masteriyo-btn.masteriyo-btn-primary',
					),
				),
			);

			$this->controls['course_button_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'course_button',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'color',
						'selector' => '.masteriyo-btn.masteriyo-btn-primary',
					),
				),
			);
	}



	/**
	 * Render the element output for the frontend of Single Course Button Element
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
			masteriyo_template_enroll_button( $course );
			echo '</div>';
		}
	}
}
