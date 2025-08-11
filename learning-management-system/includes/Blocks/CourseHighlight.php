<?php

/**
 * Course highlights block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseHighlight
 *
 * Renders the course highlights section in the single course layout.
 *
 * @since 1.18.2
 */
class CourseHighlight extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-highlight';

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

		$course            = $this->get_block_preview_course( $course_id );
		$GLOBALS['course'] = $course;
		\ob_start();

		/**
		 * Fires before rendering course highlights in the course-highlight block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_highlights', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>

		<?php
		printf(
			'<div class="masteriyo-block masteriyo-course-highlights-block--%s">',
			esc_attr( $client_id )
		);

		masteriyo_single_course_highlights( $course );

		echo '</div>';
		?>

		<?php
		/**
		 * Fires after rendering course highlights in the course-highlight block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_highlights', $attr );

		return \ob_get_clean();
	}
}
