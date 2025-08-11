<?php

/**
 * Single course title block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class SingleCourseTitle
 *
 * Handles the rendering of the course title block on a single course page.
 *
 * @since 1.18.2
 */
class SingleCourseTitle extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-title';

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

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
			<div style="color:red;padding-left:60px;">
				Please ensure that only individual course elements are added inside the single course block container.
			</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );
	
		if ( ! $course ) {
			return '';
		}

		$client_id = esc_attr( $attr['clientId'] ?? '' );

		\ob_start();

		/**
		 * Fires before rendering course title in course-title block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_title', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
		printf( '<h1 class="masteriyo-block masteriyo-title-block--%s">', esc_attr( $client_id ) );
		echo esc_html( $course->get_title() );
		echo '</h1>';

		/**
		 * Fires after rendering course title in course-title block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_title', $attr );

		return \ob_get_clean();
	}
}
