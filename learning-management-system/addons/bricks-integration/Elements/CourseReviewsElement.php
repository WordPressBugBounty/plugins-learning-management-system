<?php
/**
 * Masteriyo Single Course Review Bricks Element class.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseQuery;
use Masteriyo\Taxonomy\Taxonomy;


/**
* Masteriyo Single Course Review Bricks Element class.
*
* @since 1.9.0
*/
class CourseReviewsElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_reviews';
	public $icon     = 'ti-comment-alt';

	/**
	* Bricks Single Course Review Bricks Label for the element.
	*
	* @since 1.9.0
	*/
	public function get_label() {
		return esc_html__( 'Single Course Reviews', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Review Bricks Element CSS and General controls.
	*
	* @since 1.9.0
	*/
	public function set_control_groups() {
		$this->control_groups['course_reviews'] = array(
			'title' => esc_html__( 'Course Reviews', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_reviews controls
		//visibility
			$this->controls['show_course_reviews'] = array(
				'tab'       => 'content',
				'group'     => 'course_reviews',
				'label'     => esc_html__( 'Show Reviews', 'learning-management-system' ),
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
						'selector' => '.course-reviews',
					),
				),
			);
			//border
			$this->controls['course_reviews_border'] = array(
				'tab'   => 'content',
				'group' => 'course_reviews',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.course-reviews',
					),
				),
			);
			//margin
			$this->controls['course_reviews_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_reviews',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.course-reviews',
					),
				),
			);

			//padding
			$this->controls['course_reviews_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_reviews',
				'label' => esc_html__( 'padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.course-reviews',
					),
				),
			);

			//box shadow
			$this->controls['course_reviews_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_reviews',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.course-reviews',
					),
				),
			);

			//typography
			$this->controls['course_reviews_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_reviews',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .course-reviews',
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
	 * Render the element output for the frontend of Single Course Review Bricks Element
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
			masteriyo_single_course_reviews( $course );
			echo '</div>';
		}
	}
}
