<?php

/**
 * Course stats block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\Query\CourseProgressQuery;

/**
 * Class CourseStats
 *
 * Handles the rendering of course statistics (comments, enrollment, etc.).
 *
 * @since 1.18.2
 */
class CourseStats extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-stats';

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
			<div style="color: red; padding-left: 60px;">
				Please ensure that only individual course elements are added inside the single course block container.
			</div>
			<?php
			return \ob_get_clean();
		}

		$course            = $this->get_block_preview_course( $course_id );
		$GLOBALS['course'] = $course;
		\ob_start();

		/**
		 * Fires before rendering course stats in the course-stats block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_stats', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
		printf(
			'<div class="masteriyo-single-course masteriyo-block masteriyo-course-stats-block--%s" style="display:block">',
			esc_attr( $client_id )
		);
		$query          = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);
		$progress       = current( $query->get_course_progress() );
		$comments_count = masteriyo_count_course_comments( $course );
		$summary        = $progress ? $progress->get_summary( 'all' ) : '';
		masteriyo_get_template(
			'single-course/course-stats.php',
			array(
				'course'                    => $course,
				'comments_count'            => $comments_count,
				'enrolled_users_count'      => masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count(),
				'remaining_available_seats' => $course->get_enrollment_limit() > 0
					? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() )
					: 0,
				'progress'                  => $progress,
				'summary'                   => $summary,
				'attributes'                => $attr,

			)
		);

		echo '</div>';

		/**
		 * Fires after rendering course stats in the course-stats block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_stats', $attr );

		return \ob_get_clean();
	}
}
