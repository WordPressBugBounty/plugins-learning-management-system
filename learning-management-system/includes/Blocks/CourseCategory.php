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
use Masteriyo\Query\CourseCategoryQuery;

/**
 * Class CourseCategories
 *
 * Displays a list/grid of course categories.
 *
 * @since 1.20.0
 */
class CourseCategory extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.20.0
	 * @var string
	 */
	protected $block_name = 'course-category';

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
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
		/**
		 * Fires before rendering category in the course-category block.
		 *
		 * @since 1.20.0
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_before_single_course_categories', $attr );

		printf(
			'<div class="masteriyo-block masteriyo-course-category-block--%s">',
			esc_attr( $client_id )
		);

		masteriyo_get_template(
			'single-course/categories.php',
			array(
				'course' => $course,
			)
		);

		echo '</div>';

		/**
		 * Fires after rendering course category in the course-category block.
		 *
		 * @since 1.20.0
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_after_single_course_categories', $attr );

		return \ob_get_clean();
	}
}
