<?php

/**
 * Course categories block class.
 *
 * @since 1.20.0
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\Query\CourseProgressQuery;
/**
 * Class CourseUserProgress
 *
 * Displays a list/grid of course user progress.
 *
 * @since 1.20.0
 */
class CourseUserProgress extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.20.0
	 * @var string
	 */
	protected $block_name = 'course-user-progress';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.20.0
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr      = $this->attributes;
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		\ob_start();

		if ( ! $course_id ) {
			?>
		<div style="color:red;padding-left:60px;">
			<?php
			printf(
				esc_html__( 'No course found for the course user progress block: %s', 'learning-management-system' ),
				esc_html( $this->block_name )
			);
			?>
		</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );

		if ( ! masteriyo_is_standard_course_type( $course ) ) {
			?>
		<div style="color:red;padding-left:60px;">
			<?php esc_html_e( 'Invalid course type.', 'learning-management-system' ); ?>
		</div>
			<?php
			return \ob_get_clean();
		}

		$GLOBALS['course'] = $course;

		if ( ! empty( $block_css ) ) {
			echo '<style>';
			echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</style>';
		}

		printf(
			'<div class="masteriyo-block masteriyo-user-course-progress-block--%s">',
			$client_id
		);

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress      = current( $query->get_course_progress() );
		$is_block_page = $this->is_block_editor();
		if ( empty( $progress ) && $is_block_page ) {
			?>
		<div style="color:red;padding-left:60px;">
			<?php
			printf(
				esc_html__( 'No progress found for the course: %s', 'learning-management-system' ),
				esc_html( $course->get_title() )
			);
			?>
		</div>
		</div>
			<?php
			return \ob_get_clean();
		}

		if ( empty( $progress ) ) {
				return \ob_get_clean();
		}

		$summary = $progress->get_summary( 'all' );

		if ( ! empty( $summary ) && isset( $summary['total']['total'] ) && $summary['total']['total'] > 0 ) {
			masteriyo_get_template(
				'single-course/course-progress.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);
		}

		echo '</div>';

		return \ob_get_clean();
	}
}
