<?php
/**
 * Masteriyo Single Course Rating Bricks element class.
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
* Masteriyo Single Course Rating Elements Class.
*
* @since 1.11.3
*/
class CourseRatingElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_ratings';
	public $icon     = 'fa-solid fa-star-half-stroke ';

	/**
	* Bricks single course rating label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Rating', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for single course rating CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_rating'] = array(
			'title' => esc_html__( 'Course Rating', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_rating controls
		//visibility
			$this->controls['show_course_rating']       = array(
				'tab'       => 'content',
				'group'     => 'course_rating',
				'label'     => esc_html__( 'Show Rating', 'learning-management-system' ),
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.{{WRAPPER}} .masteriyo-rating svg',
					),
				),
			);
			$this->controls['show_course_rating_count'] = array(
				'tab'       => 'content',
				'group'     => 'course_rating',
				'label'     => esc_html__( 'Show Rating Count', 'learning-management-system' ),
				'options'   => array(
					'inherit' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'    => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-rating .text',
					),
				),
			);
			//border
			$this->controls['course_rating_border'] = array(
				'tab'   => 'content',
				'group' => 'course_rating',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-rating',
					),
				),
			);

			//box shadow
			$this->controls['course_rating_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_rating',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-rating',
					),
				),
			);

			//typography
			$this->controls['course_rating_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_rating',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-rating',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'text-decoration',
					'text-transform',
					'color',
				),
			);

			// .masteriyo-btn.masteriyo-btn-primary
			$this->controls['course_rating_background_color'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Background Color', 'learning-management-system' ),
				'type'   => 'color',
				'group'  => 'course_rating',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'background-color',
						'selector' => '{{WRAPPER}} .masteriyo-rating',
					),
				),
			);

			$this->controls['course_rating_color'] = array(
				'tab'     => 'content',
				'label'   => esc_html__( 'Rating Color', 'learning-management-system' ),
				'type'    => 'color',
				'group'   => 'course_rating',
				'default' => '#e59819',
				'inline'  => true,
				'css'     => array(
					array(
						'property' => 'fill',
						'selector' => '{{WRAPPER}} .masteriyo-rating svg',
					),
				),
			);

			// masteriyo-rating svg
			$this->controls['course_rating_svg_size'] = array(
				'tab'    => 'content',
				'label'  => esc_html__( 'Rating Size', 'learning-management-system' ),
				'type'   => 'number',
				'min'    => 0,
				'group'  => 'course_rating',
				'step'   => '0.5', // Default: 1
				'inline' => true,
				'unit'   => 'px',
				'max'    => 50,
				'css'    => array(
					array(
						'property' => 'width',
						'selector' => '{{WRAPPER}} .masteriyo-rating svg',
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-rating svg',
						'important' => true,
					),
					array(
						'property'  => 'height',
						'selector'  => '{{WRAPPER}} .masteriyo-rating',
						'important' => true,
					),

				),
			);

			$this->controls['course_rating_text'] = array(
				'tab'   => 'content',
				'label' => esc_html__( 'Rating Text', 'learning-management-system' ),
				'type'  => 'dimensions',
				'group' => 'course_rating',
				'unit'  => 'px',
				'css'   => array(
					array(
						'property'  => 'padding',
						'selector'  => '{{WRAPPER}} .masteriyo-rating .text',
						'important' => true,
					),
				),
			);

			$this->controls['course_rating_margin'] = array(
				'tab'   => 'content',
				'label' => esc_html__( 'Rating Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'group' => 'course_rating',
				'unit'  => 'px',
				'css'   => array(
					array(
						'property'  => 'margin',
						'selector'  => '{{WRAPPER}} .masteriyo-rating',
						'important' => true,
					),
				),
			);

			$this->controls['display_course_rating'] = array(
				'tab'       => 'content',
				'group'     => 'course_rating',
				'label'     => esc_html__( 'Display Course Rating', 'learning-management-system' ),
				'options'   => array(
					'flex'         => esc_html__( 'Flex', 'learning-management-system' ),
					'inline-block' => esc_html__( 'Inline Block', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.{{WRAPPER}} .masteriyo-rating',
						'important' => true,
					),
				),
			);

			$this->controls['display_course_rating_justify_content'] = array(
				'tab'       => 'content',
				'group'     => 'course_rating',
				'label'     => esc_html__( 'Justify Content Rating', 'learning-management-system' ),
				'options'   => array(
					'normal' => esc_html__( 'Normal', 'learning-management-system' ),
					'center' => esc_html__( 'Center', 'learning-management-system' ),
					'end'    => esc_html__( 'End', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'align-items',
						'selector'  => '.{{WRAPPER}} .masteriyo-rating',
						'important' => true,
					),
				),
			);
	}



	/**
	 * Render the element output for the frontend of Single Course Rating Elements Element
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

			if ( $course->is_review_allowed() ) :

				echo "<div {$this->render_attributes( '_root' )}>";
				?>
					<span class="masteriyo-icon-svg masteriyo-rating">
									<?php masteriyo_format_rating( $course->get_average_rating(), true ); ?> <span class="text"><?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?> (<?php echo esc_html( $course->get_review_count() ); ?>)</span>
					</span>
				<?php
				echo '</div>';

			endif;
		}
	}
}

