<?php
/**
 * Masteriyo Single Course Featured Image Bricks element class.
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
* Masteriyo  Single Course Featured Image elements class.
*
* @since 1.11.3
*/
class CourseFeaturedImageElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_featured_image';
	public $icon     = 'fa-regular fa-image';

	/**
	* Bricks  Single Course Featured Image Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Featured Image', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for  Single Course Featured Image CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['container']        = array(
			'title' => esc_html__( 'Featured Image Container', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['difficulty_badge'] = array(
			'title' => esc_html__( 'Featured Image Difficulty Badge', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//container controls
		//visibility
			$this->controls['show_thumbnail'] = array(
				'tab'       => 'content',
				'group'     => 'container',
				'label'     => esc_html__( 'Show Thumbnail', 'learning-management-system' ),
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
						'selector' => '.masteriyo-course--img-wrap',
					),
				),
			);

			//border
			$this->controls['featured_image_border'] = array(
				'tab'   => 'content',
				'group' => 'container',
				'label' => esc_html__( 'Featured Image border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course--img-wrap img',
					),
				),
			);

			//padding
			$this->controls['featured_image_container_margin'] = array(
				'tab'   => 'content',
				'group' => 'container',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-course--img-wrap',
					),
				),
			);

			//margin
			$this->controls['featured_image_container_padding'] = array(
				'tab'   => 'content',
				'group' => 'container',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-course--img-wrap',
					),
				),
			);

			//box shadow
			$this->controls['featured_image_container_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'container',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-course--img-wrap',
					),
				),
			);

			// difficulty badge
			//visibility
			$this->controls['show_difficulty_badge'] = array(
				'tab'       => 'content',
				'group'     => 'difficulty_badge',
				'label'     => esc_html__( 'Show Badge', 'learning-management-system' ),
				'default'   => 'inline',
				'options'   => array(
					'inline' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course--img-wrap .difficulty-badge',
					),
				),
			);
			//typography
			$this->controls['difficulty_badge_typography'] = array(
				'tab'     => 'content',
				'group'   => 'difficulty_badge',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .difficulty-badge .masteriyo-badge',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
				),
			);

			//border
			$this->controls['difficulty_badge_border'] = array(
				'tab'   => 'content',
				'group' => 'difficulty_badge',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '{{WRAPPER}} .difficulty-badge .masteriyo-badge',
					),
				),
			);

			//border radius
			$this->controls['difficulty_badge_border_radius'] = array(
				'tab'   => 'content',
				'group' => 'difficulty_badge',
				'label' => esc_html__( 'Border Radius', 'learning-management-system' ),
				'type'  => 'border',
				'unit'  => 'px',
				'css'   => array(
					array(
						'property' => 'border-radius',
						'selector' => '{{WRAPPER}} .difficulty-badge .masteriyo-badge',
					),
				),
			);
			// top
			$this->controls['difficulty_badge_vertical_position'] = array(
				'tab'       => 'content',
				'group'     => 'difficulty_badge',
				'label'     => esc_html__( 'Vertical Badge Position', 'learning-management-system' ),
				'default'   => '24px',
				'small'     => true,
				'type'      => 'number',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'top',
						'selector'  => '{{WRAPPER}} .masteriyo-course--img-wrap .difficulty-badge',
						'important' => true,
					),
				),
				'required'  => array( 'show_thumbnail', '!=', 'none' ),
			);
			//left
			$this->controls['difficulty_badge_horizontal_position'] = array(
				'tab'       => 'content',
				'group'     => 'difficulty_badge',
				'label'     => esc_html__( 'Horizontal Badge Position', 'learning-management-system' ),
				'default'   => '24px',
				'small'     => true,
				'type'      => 'number',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'left',
						'selector'  => '{{WRAPPER}} .masteriyo-course--img-wrap .difficulty-badge',
						'important' => true,
					),
				),
				'required'  => array( 'show_thumbnail', '!=', 'none' ),
			);
	}



	/**
	 * Render the element output for the frontend of  Single Course Featured Image Element
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
				'single-course/featured-image.php',
				array(
					'course'     => $course,
					'difficulty' => $course->get_difficulty(),
				)
			);
			echo '</div>';
		}
	}
}
