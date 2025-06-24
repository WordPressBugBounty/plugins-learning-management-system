<?php

/**
 * Course enroll button block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseEnrollButton
 *
 * Displays the enroll button for a course inside the single course layout.
 *
 * @since 1.18.2
 */
class CourseEnrollButton extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-enroll-button';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr      = $this->attributes;
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $course_id ) {
			\ob_start();
			?>
			<div style="color:red;padding-left:60px;">
				Please ensure that only individual course elements are added inside the single course block container.
			</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );

		\ob_start();

		/**
		 * Fires before rendering the course enroll button.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_enroll_button', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			/* .masteriyo-single-course--btn {
				text-wrap: nowrap;
			} */
		</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-enroll-button-block--%s">',
			esc_attr( $client_id )
		);

		do_action( 'masteriyo_template_enroll_button', $course );

		echo '</div>';

		/**
		 * Fires after rendering the course enroll button.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_enroll_button', $attr );

		return \ob_get_clean();
	}
}
