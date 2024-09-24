<?php
/**
 * Masteriyo Single Course Stats Bricks Element class.
 *
 * @since 1.11.3
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;


/**
* Masteriyo Single Course Stats Bricks Element class.
*
* @since 1.11.3
*/
class CourseStatsElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_stats';
	public $icon     = 'fa-solid fa-chart-line';

	/**
	* Bricks Single Course Stats Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Course Stats', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Stats CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['single_course_stats']            = array(
			'title' => esc_html__( 'Single Course Stats', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['single_course_stats_visibility'] = array(
			'title' => esc_html__( 'General', 'learning-management-system' ),
			'tab'   => 'content',
		);
	}

	public function set_controls() {

		$this->controls['show_single_course_stats_display'] = array(
			'tab'       => 'content',
			'group'     => 'single_course_stats_visibility',
			'label'     => esc_html__( 'Display', 'learning-management-system' ),
			'default'   => 'flex',
			'options'   => array(
				'flex'         => esc_html__( 'Flex', 'learning-management-system' ),
				'block'        => esc_html__( 'Block', 'learning-management-system' ),
				'contents'     => esc_html__( 'Contents', 'learning-management-system' ),
				'inline-block' => esc_html__( 'Inline Block', 'learning-management-system' ),
			),
			'type'      => 'select',
			'clearable' => false,
			'css'       => array(
				array(
					'property' => 'display',
					'selector' => '.masteriyo-single-course-stats',
				),
			),
		);

			$this->controls['show_single_course_stats_duration_visibility'] = array(
				'tab'       => 'content',
				'group'     => 'single_course_stats_visibility',
				'label'     => esc_html__( 'Duration', 'learning-management-system' ),
				'default'   => 'inherit',
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.duration',
					),
				),
			);

			$this->controls['show_single_course_stats_enrolled_count_visibility'] = array(
				'tab'       => 'content',
				'group'     => 'single_course_stats_visibility',
				'label'     => esc_html__( 'Students', 'learning-management-system' ),
				'default'   => 'inherit',
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.student',
					),
				),
			);

			$this->controls['show_single_course_stats_difficulty_level_visibility'] = array(
				'tab'       => 'content',
				'group'     => 'single_course_stats_visibility',
				'label'     => esc_html__( 'Difficulty', 'learning-management-system' ),
				'default'   => 'inherit',
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.difficulty',
					),
				),
			);

			$this->controls['show_single_course_stats_last_updated_visibility'] = array(
				'tab'       => 'content',
				'group'     => 'single_course_stats_visibility',
				'label'     => esc_html__( 'Last Updated', 'learning-management-system' ),
				'default'   => 'inherit',
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.last-updated',
					),
				),
			);

			//icon color
			$this->controls['single_course_stats_icon_color'] = array(
				'tab'   => 'content',
				'group' => 'single_course_stats',
				'label' => esc_html__( 'Icon Color', 'learning-management-system' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property'  => 'fill',
						'important' => true,
						'selector'  => '.masteriyo-single-course-stats .masteriyo-icon-svg svg',
					),
				),
			);

			$this->controls['stats_icon_svg_size'] = array(
				'tab'    => 'content',
				'group'  => 'single_course_stats',
				'label'  => esc_html__( 'Icon Size', 'learning-management-system' ),
				'type'   => 'number',
				'step'   => '0.5', // Default: 1
				'inline' => true,
				'unit'   => 'px',
				'max'    => 50,
				'css'    => array(
					array(
						'property' => 'width',
						'selector' => '{{WRAPPER}} .masteriyo-single-course-stats .masteriyo-icon-svg svg',
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-single-course-stats .masteriyo-icon-svg svg',
						'important' => true,
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-single-course-stats .masteriyo-icon-svg',
						'important' => true,
					),
				),
			);

				//typography
				$this->controls['single_course_stats_typography'] = array(
					'tab'     => 'content',
					'group'   => 'single_course_stats',
					'label'   => esc_html__( 'Typography', 'learning-management-system' ),
					'type'    => 'typography',
					'css'     => array(
						array(
							'property' => 'typography',
							'selector' => '{{WRAPPER}} .masteriyo-single-course .masteriyo-single-course-stats span ',
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

	}



	/**
	 * Render the element output for the frontend of Single Course Stats Element
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
								echo "<div class='masteriyo-single-course'>";
									echo "<div class='masteriyo-single-course-stats'>";
										masteriyo_single_course_stats( $course );
									echo '</div>';
								echo '</div>';
							echo '</div>';
		}
	}
}

