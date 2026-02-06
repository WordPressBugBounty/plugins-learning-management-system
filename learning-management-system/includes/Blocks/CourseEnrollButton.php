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
		</style>
		<?php

		$class_name = $attr['className'] ?? '';

		printf(
			'<div class="masteriyo-block  masteriyo-enroll-button-block--%s %s">',
			esc_attr( $client_id ),
			esc_attr( $class_name )
		);

		/**
		 * Action hook for rendering retake button template.
		 *
		 * @since 1.8.0
		 *
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';
		if ( 'default' === $layout || 'minimal' === $layout ) {
			do_action( 'masteriyo_template_course_retake_button', $course );
		}
		?>

		<?php
		/**
		 * Action hook for rendering enroll button template.
		 *
		 * @since 1.0.0
		 *
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
			$user_id = get_current_user_id();
		if ( masteriyo_is_user_enrolled_in_course( $course->get_id(), $user_id ) && 'layout1' === $layout && masteriyo_is_single_course_page() && $progress_pct > 0 ) {
		} else {
			do_action( 'masteriyo_template_enroll_button', $course );
		}

		?>

		<?php masteriyo_display_all_notices(); ?>

		<?php echo '</div>'; ?>

	 
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
