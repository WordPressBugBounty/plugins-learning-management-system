<?php
/**
 * Masteriyo single course categories Bricks element class.
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
* Masteriyo single course categories elements class.
*
* @since 1.11.3
*/
class CategoriesOfCourseElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'masteriyo-course--content__category';
	public $icon     = 'ti-layout-menu';

	/**
	* Bricks single course categories Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Categories', 'learning-management-system' );
	}

	/**
	* Bricks single course set controls groups for course categories CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['single_course_categories'] = array(
			'title' => esc_html__( 'Course Categories', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_title controls
		//visibility
			$this->controls['visibility_course_categories'] = array(
				'tab'       => 'content',
				'group'     => 'single_course_categories',
				'label'     => esc_html__( 'View Course Categories', 'learning-management-system' ),
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
						'selector' => '.masteriyo-course--content__category',
					),
				),
			);

			$this->controls['course_categories_background_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Category Background color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'single_course_categories',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'background-color',
						'selector' => '.masteriyo-course--content__category a',
					),
				),
			);
			//border

			//individual categories border
			$this->controls['course_individual_category_border'] = array(
				'tab'   => 'content',
				'group' => 'single_course_categories',
				'label' => esc_html__( 'Individual Categories Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course--content__category a',
					),
				),
			);
			//margin
			$this->controls['course_individual_category_margin'] = array(
				'tab'   => 'content',
				'group' => 'single_course_categories',
				'label' => esc_html__( 'Category Margin Right', 'learning-management-system' ),
				'type'  => 'number',
				'unit'  => 'px',
				'css'   => array(
					array(
						'property' => 'margin-right',
						'selector' => '.masteriyo-course--content__category a',
					),
				),
			);

			//box shadow
			$this->controls['course_individual_title_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'single_course_categories',
				'label' => esc_html__( 'Individual Course Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-course--content__category a',
					),
				),
			);

			//typography
			$this->controls['course_individual_title_typography'] = array(
				'tab'     => 'content',
				'group'   => 'single_course_categories',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course--content__category a',
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
	 * Render the element output for the frontend of Single Course Categories Element
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
				masteriyo_single_course_categories( $course );
			echo '</div>';
		}
	}
}
