<?php

/**
 * Course curriculum block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseCurriculum
 *
 * Displays the curriculum of a course in the single course layout.
 *
 * @since 1.18.2
 */
class CourseCurriculum extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-curriculum';

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
		 * Fires before rendering the course curriculum block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_curriculum', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
		if ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) ) {
			$sections = masteriyo_get_course_structure( $course->get_id() );

			printf(
				'<div class="masteriyo-block masteriyo-single-course--main__content masteriyo-course-curriculum-block--%s">',
				esc_attr( $client_id )
			);

			masteriyo_get_template(
				'single-course/curriculum.php',
				array(
					'course'          => $course,
					'sections'        => $sections,
					'is_hidden'       => false,
					'show_curriculum' => true,
				)
			);

			echo '</div>';
		}

		/**
		 * Fires after rendering the course curriculum block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_curriculum', $attr );

		return \ob_get_clean();
	}
}
