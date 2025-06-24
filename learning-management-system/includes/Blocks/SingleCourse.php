<?php

/**
 * Single course wrapper block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class SingleCourse
 *
 * Handles the rendering of the main wrapper for a single course.
 *
 * @since 1.18.2
 */
class SingleCourse extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'single-course';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content Inner block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr      = $this->attributes;
		$block_css = $attr['blockCSS'] ?? '';
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		\ob_start();

		/**
		 * Fires before rendering the single course block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_single_course', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			.masteriyo-single-course:not(.elementor) {
				display: contents;
			}
			.wp-block-columns .masteriyo-time-btn .wp-block[data-type="masteriyo/course-price"],
			.wp-block-columns .masteriyo-time-btn .wp-block[data-type="masteriyo/course-enroll-button"] {
				margin: 0px 0px;
			}
			.wp-block-column {
				word-break: normal;
				display: block;
			}
			.masteriyo-hidden {
				display: none !important;
			}
			.entry-content > style {
				visibility: hidden;
				position: absolute;
				left: 0px;
			}
		</style>
		<?php
		printf(
			'<div class="masteriyo-single-course masteriyo-block masteriyo-single-course-block--%s">',
			esc_attr( $client_id )
		);

		// Echo the content passed into the block (inner blocks).
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';

		/**
		 * Fires after rendering the single course block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_single_course', $attr );

		return \ob_get_clean();
	}
}
