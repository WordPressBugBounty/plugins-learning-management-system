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
class CourseArchiveViewModeElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'course_archive_view_mode';
	public $icon     = 'ti-layout-grid3';

	/**
	* Bricks Single Course Search Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Course Archive View Mode', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Search CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_archive_view_mode'] = array(
			'title' => esc_html__( 'Course Archive View Mode', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_archive_view_mode controls

			//border
			$this->controls['course_archive_view_mode_border'] = array(
				'tab'   => 'content',
				'group' => 'course_archive_view_mode',
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
			$this->controls['course_archive_view_mode_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_archive_view_mode',
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
			$this->controls['course_archive_view_mode_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_archive_view_mode',
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
			$this->controls['course_archive_view_mode_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_archive_view_mode',
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
			$this->controls['course_rating_color'] = array(
				'tab'     => 'content',
				'label'   => esc_html__( 'SVG Color', 'learning-management-system' ),
				'type'    => 'color',
				'group'   => 'course_archive_view_mode',
				'default' => '#e59819',
				'inline'  => true,
				'css'     => array(
					array(
						'property'  => 'fill',
						'important' => true,
						'selector'  => '{{WRAPPER}} .masteriyo-courses-view-mode-item.active .view-mode svg path',
					),
				),
			);

			// masteriyo-rating svg
			$this->controls['course_archive_view_mode_svg_size'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'SVG Size', 'learning-management-system' ),
				'type'   => 'number',
				'min'    => 0,
				'group'  => 'course_archive_view_mode',
				'step'   => '0.5', // Default: 1
				'inline' => true,
				'unit'   => 'px',
				'max'    => 50,
				'css'    => array(
					array(
						'property' => 'width',
						'selector' => '{{WRAPPER}} .masteriyo-courses-view-mode-item.active .view-mode svg',
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-courses-view-mode-item.active .view-mode svg',
						'important' => true,
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-rating',
						'important' => true,
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
							masteriyo_courses_view_mode();
							echo '</div>';
		}
	}
}

