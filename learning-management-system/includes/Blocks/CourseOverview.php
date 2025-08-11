<?php

/**
 * Course overview block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseOverview
 *
 * Displays the course overview section inside the single course container.
 *
 * @since 1.18.2
 */
class CourseOverview extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-overview';

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
		// Build custom inline style if width/height provided.
		$style = '';
		if ( isset( $attr['height'] ) ) {
			$style .= ' height: ' . esc_attr( $attr['height'] ) . ';';
		}
		if ( isset( $attr['width'] ) && ! empty( $attr['width'] ) ) {
			$style .= ' width: ' . esc_attr( $attr['width'] ) . ';';
		} else {
			$style .= ' width: 700px;';
		}

		\ob_start();

		/**
		 * Fires before rendering the course overview.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_overview', $attr );
		?>

		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>

		<?php
		printf(
			'<div class="masteriyo-block masteriyo-course-overview-block--%s" style="%s">',
			esc_attr( $client_id ),
			esc_attr( $style )
		);

		masteriyo_single_course_overview( $course );

		echo '</div>';

		/**
		 * Fires after rendering the course overview.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_overview', $attr );

		return \ob_get_clean();
	}
}
