<?php

/**
 * Course price block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CoursePrice
 *
 * Handles the display of course pricing (regular and sale).
 *
 * @since 1.18.2
 */
class CoursePrice extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-price';

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
		if ( ! $course ) {
			return '';
		}

		$client_id = esc_attr( $attr['clientId'] ?? '' );

		\ob_start();

		/**
		 * Fires before rendering course price in the course-price block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_price', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			.amount {
				text-wrap: nowrap;
			}
		</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-price-block--%s">',
			esc_attr( $client_id )
		);
		?>
		<div class="masteriyo-course-price">
			<?php if ( ! empty( $course->get_sale_price() ) ) : ?>
				<del class="old-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price() ) ); ?></del>
			<?php endif; ?>
			<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?></span>
		</div>
		<?php
		echo '</div>';

		/**
		 * Fires after rendering course price in the course-price block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_price', $attr );

		return \ob_get_clean();
	}
}
