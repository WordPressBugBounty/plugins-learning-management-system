<?php
/**
 * Masteriyo Single Course Highlights Bricks element class.
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
* Masteriyo Single Course Highlights elements class.
*
* @since 1.11.3
*/
class CourseHighlightsElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_highlights';
	public $icon     = 'fa-solid fa-list-check';

	/**
	* Bricks Single Course Highlights Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Highlights', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Highlights CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_highlight'] = array(
			'title' => esc_html__( 'Course Highlights', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_highlight controls
		//visibility
			$this->controls['show_course_highlight'] = array(
				'tab'       => 'content',
				'group'     => 'course_highlight',
				'label'     => esc_html__( 'Show Highlights', 'learning-management-system' ),
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
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);
			//border
			$this->controls['course_highlight_border'] = array(
				'tab'   => 'content',
				'group' => 'course_highlight',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);
			//margin
			$this->controls['course_highlight_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_highlight',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);

			//padding
			$this->controls['course_highlight_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_highlight',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);

			//box shadow
			$this->controls['course_highlight_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_highlight',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);

			//typography
			$this->controls['course_highlight_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_highlight',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-course--content__description',
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
			$this->controls['course_highlight_background_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Background color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'course_highlight',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'background-color',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);

			$this->controls['course_highlight_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'course_highlight',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'color',
						'selector' => '.masteriyo-course--content__description',
					),
				),
			);
	}



	/**
	 * Render the element output for the frontend of Single Course Highlights Element
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
			echo wp_kses_post( apply_filters( 'masteriyo_single_course_highlights_content', masteriyo_format_course_highlights( $course->get_highlights() ) ) );
			echo '</div>';
		}
	}
}
