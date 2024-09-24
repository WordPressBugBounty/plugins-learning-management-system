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
class CoursePriceElement extends \Bricks\Element {

	public $category = 'masteriyo';
	public $name     = 'single_course_price';
	public $icon     = 'fa-solid fa-hand-holding-dollar';

	/**
	* Bricks Single Course Stats Label for the element.
	*
	* @since 1.11.3
	*/
	public function get_label() {
		return esc_html__( 'Course Price', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for Single Course Stats CSS and General controls.
	*
	* @since 1.11.3
	*/
	public function set_control_groups() {
		$this->control_groups['single_course_price'] = array(
			'title' => esc_html__( 'Single Course Price', 'learning-management-system' ),
			'tab'   => 'content',
		);

	}

	public function set_controls() {
		//single_course_price controls
			//typography
			$this->controls['single_course_price_typography'] = array(
				'tab'   => 'content',
				'group' => 'single_course_price',
				'label' => esc_html__( 'Typography', 'learning-management-system' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => '{{WRAPPER}} .masteriyo-course-price .current-amount ',
					),
				),
				// 'exclude' => array(
				// 	'text-align',
				// 	'line-height',
				// 	'text-decoration',
				// 	'color',
				// 	'text-transform',
				// 	'letter-spacing',
				// 	'text-shadow',
				// ),
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
			?>
							<div class="masteriyo-course-price">
								<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?></span>
							</div>
			<?php
							echo '</div>';
		}
	}
}

