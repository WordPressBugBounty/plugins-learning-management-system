<?php

/**
 * Course author block class.
 *
 * @since 1.20.0
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\Pro\Addons;
/**
 * Class CourseAuthor
 *
 * Displays the course author information in the single course layout.
 *
 * @since 1.20.0
 */
class CourseComingSoon extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.20.0
	 * @var string
	 */
	protected $block_name = 'course-coming-soon';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.20.0
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {

		if ( ! ( new Addons() )->is_active( MASTERIYO_COURSE_COMING_SOON_SLUG ) ) {
			ob_start();
			?>
		<div style="color:red; padding-left:60px;">
			<?php esc_html_e( 'The ‘Course Coming Soon’ add-on must be enabled to use this block.', 'learning-management-system' ); ?>
		</div>
			<?php
			return ob_get_clean();
		}

		$attr      = $this->attributes;
		$block_css = isset( $attr['blockCSS'] ) ? $attr['blockCSS'] : '';
		$course_id = isset( $attr['courseId'] ) ? (int) $attr['courseId'] : 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $course_id ) {
			ob_start();
			?>
		<div style="color:red; padding-left:60px;">
			<?php esc_html_e( 'Course ID is missing. Please select a course in block settings.', 'learning-management-system' ); ?>
		</div>
			<?php
			return ob_get_clean();
		}

		$course            = $this->get_block_preview_course( $course_id );
		$GLOBALS['course'] = $course;
		ob_start();

		if ( ! empty( $block_css ) ) {
			?>
		<style>
			<?php echo wp_strip_all_tags( $block_css ); ?>
		</style>
			<?php
		}
		$is_block_page = $this->is_block_editor();
		if ( $is_block_page ) {

			?>
			<div style="padding: 20px; background: #f6f8fa; border: 1px dashed #d1d5da; border-radius: 4px; color: #57606a; text-align: center;">
				<strong> <?php esc_html_e( 'Coming Soon Countdown', 'learning-management-system' ); ?></strong><br>
				<small><?php esc_html_e( 'This block will display a countdown timer for course availability on the frontend.', 'learning-management-system' ); ?></small>
			</div>
			<?php
		} else {
			// Render actual frontend output
			do_action( 'masteriyo_course_coming_soon_block', $course );
		}

		return ob_get_clean();
	}
}
