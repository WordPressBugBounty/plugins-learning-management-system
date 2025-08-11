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
use Masteriyo\Query\CourseProgressQuery;

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
		$query  = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );
		$summary  = $progress ? $progress->get_summary( 'all' ) : '';
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

			.masteriyo-time-btn .masteriyo-course-price{
				display: none;
			}
		</style>
		<?php

		printf(
			'<div class="masteriyo-block  masteriyo-course--content  masteriyo-enroll-button-block--%s">',
			esc_attr( $client_id )
		);

			masteriyo_get_template(
				'single-course/price-and-enroll-button.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);

		echo '</div>';

		?>
			<style>
	.masteriyo-enroll-button-block--<?php echo esc_attr( $client_id ); ?> .masteriyo-group-course__group-button {
		display: none;
	}
</style>

		<?php

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
