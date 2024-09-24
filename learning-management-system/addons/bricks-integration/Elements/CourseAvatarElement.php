<?php
/**
 * Masteriyo course Author Bricks element class.
 *
 * @since 1.11.3
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;


/**
* Masteriyo Single Course Author Elements class.
*
* @since 1.11.3
*/
class CourseAvatarElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_author';
	public $icon     = 'fa-solid fa-user-tie';

	/**
	* Bricks Single Course Author Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Single Course Author', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for course Author CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['course_author'] = array(
			'title' => esc_html__( 'Course Author', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//course_author controls
		//visibility
			$this->controls['show_course_author_img'] = array(
				'tab'       => 'content',
				'group'     => 'course_author',
				'label'     => esc_html__( 'Show Avatar', 'learning-management-system' ),
				'options'   => array(
					'inline' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course-author img',
					),
				),
			);

			$this->controls['show_course_author_text'] = array(
				'tab'       => 'content',
				'group'     => 'course_author',
				'label'     => esc_html__( 'Show Author Text', 'learning-management-system' ),
				'options'   => array(
					'inline' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course-author .masteriyo-course-author--name',
					),
				),
			);
			//direction
			$this->controls['author_direction'] = array(
				'tab'       => 'content',
				'group'     => 'course_author',
				'label'     => esc_html__( 'Direction', 'learning-management-system' ),
				'options'   => array(
					'row'    => esc_html__( 'Row', 'learning-management-system' ),
					'column' => esc_html__( 'Column', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'flex-direction',
						'selector' => '.masteriyo-course-author a',
					),
				),
			);

			$this->controls['author_vertical_alignment'] = array(
				'tab'       => 'content',
				'group'     => 'course_author',
				'label'     => esc_html__( 'Vertical Alignment', 'learning-management-system' ),
				'options'   => array(
					''              => esc_html__( 'Default', 'learning-management-system' ),
					'flex-start'    => esc_html__( 'Start', 'learning-management-system' ),
					'center'        => esc_html__( 'Center', 'learning-management-system' ),
					'flex-end'      => esc_html__( 'End', 'learning-management-system' ),
					'space-between' => esc_html__( 'Space Between', 'learning-management-system' ),
					'space-around'  => esc_html__( 'Space Around', 'learning-management-system' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'align-items',
						'selector' => '.masteriyo-course-author a',
					),
				),
			);

			$this->controls['author_horizontal_alignment'] = array(
				'tab'       => 'content',
				'group'     => 'course_author',
				'label'     => esc_html__( 'Horizontal Alignment', 'learning-management-system' ),
				'options'   => array(
					''              => esc_html__( 'Default', 'learning-management-system' ),
					'flex-start'    => esc_html__( 'Start', 'learning-management-system' ),
					'center'        => esc_html__( 'Center', 'learning-management-system' ),
					'flex-end'      => esc_html__( 'End', 'learning-management-system' ),
					'space-between' => esc_html__( 'Space Between', 'learning-management-system' ),
					'space-around'  => esc_html__( 'Space Around', 'learning-management-system' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'justify-content',
						'selector' => '.masteriyo-course-author a',
					),
				),
			);
			//border
			$this->controls['course_author_image_border'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Avatar Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course-author img',
					),
				),
			);
			$this->controls['course_author_border']       = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Border', 'learning-management-system' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.{{WRAPPER}} .masteriyo-course-author a img',
					),
				),
			);
			//margin
			$this->controls['course_author_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course-author',
					),
				),
			);

			$this->controls['course_author_text_margin'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Text Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course-author .masteriyo-course-author--name',
					),
				),
			);

			//padding
			$this->controls['course_author_padding'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-course-author',
					),
				),
			);

			//box shadow
			$this->controls['course_author_box_shadow'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Box Shadow', 'learning-management-system' ),
				'type'  => 'box-shadow',
				'css'   => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-course-author',
					),
				),
			);

			//typography
			$this->controls['course_author_typography'] = array(
				'tab'     => 'content',
				'group'   => 'course_author',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-course-author',
					),
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-course-author a',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'Font size',
				),
			);

			//image style
			$this->controls['course_author_hight_width'] = array(
				'tab'   => 'content',
				'group' => 'course_author',
				'label' => esc_html__( 'Image Size', 'learning-management-system' ),
				'type'  => 'number',
				'unit'  => 'px',
				'css'   => array(
					array(
						'property' => 'height',
						'selector' => '{{WRAPPER}} .masteriyo-course-author a img',
					),
				),
			);
	}



	/**
	 * Render the element output for the frontend of Single Course Author Element
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
		$author = masteriyo_get_user( $course->get_author_id() );

		if ( $course ) {
			if ( ! $author ) {
				return;
			}
			echo "<div {$this->render_attributes( '_root' )}>";
			?>
			<div class="masteriyo-course-author" style="display: block !important;">
				<a style="display: inline-flex;" href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
					<img src="<?php echo esc_attr( $author->profile_image_url() ); ?>"
						alt="<?php echo esc_attr( $author->get_display_name() ); ?>"
						title="<?php echo esc_attr( $author->get_display_name() ); ?>"
					>
					<!-- Do not multiline below code, as it will create space around the display name. -->
					<span class="masteriyo-course-author--name"><?php echo esc_html( $author->get_display_name() ); ?></span>
				</a>
			</div>
			<?php
			echo '</div>';
		}
	}
}
