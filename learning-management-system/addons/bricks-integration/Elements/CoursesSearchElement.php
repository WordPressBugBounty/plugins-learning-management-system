<?php
/**
 * Masteriyo Single Course Search Bricks Element class.
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
* Masteriyo Single Course Search Bricks Element class.
*
* @since 1.11.3
*/
class CoursesSearchElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_search_courses';
	public $icon     = 'ti-search';

	/**
	* Bricks Single Course Search Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Search Courses', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Search CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['search_courses'] = array(
			'title' => esc_html__( 'Search Courses', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//search_courses controls
		//visibility
			$this->controls['show_search_courses'] = array(
				'tab'       => 'content',
				'group'     => 'search_courses',
				'label'     => esc_html__( 'Show Rating', 'learning-management-system' ),
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
						'selector' => '.masteriyo-search',
					),
				),
			);
			//border
			$this->controls['search_courses_border'] = array(
				'tab'   => 'content',
				'group' => 'search_courses',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-search',
					),
				),
			);
			//margin
			$this->controls['search_courses_margin'] = array(
				'tab'   => 'content',
				'group' => 'search_courses',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-search',
					),
				),
			);

			//padding
			$this->controls['search_courses_padding'] = array(
				'tab'   => 'content',
				'group' => 'search_courses',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-search',
					),
				),
			);

			//box shadow
			$this->controls['search_courses_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'search_courses',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-search',
					),
				),
			);

			//typography
			$this->controls['search_courses_typography'] = array(
				'tab'     => 'content',
				'group'   => 'search_courses',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-search',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'text-decoration',
					'color',
					'text-transform',
					'letter-spacing',
					'text-shadow',
				),
			);

			// .masteriyo-btn.masteriyo-btn-primary
			$this->controls['search_courses_background_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Background Color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'search_courses',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'background-color',
						'selector' => '.masteriyo-search',
					),
				),
			);

	}



	/**
	 * Render the element output for the frontend of Single Course Search Element
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

							echo "<div {$this->render_attributes( '_root' )} style='width:100%;'>";
							masteriyo_course_search_form();
							echo '</div>';
		}
	}
}

