<?php

/**
 * Course feature image block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseFeatureImage
 *
 * Displays the featured image of a course.
 *
 * @since 1.18.2
 */
class CourseFeatureImage extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-feature-image';

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

		$height = isset( $attr['height_n_width']['height'] ) ? esc_attr( $attr['height_n_width']['height'] ) : '';
		$width  = isset( $attr['height_n_width']['width'] ) ? esc_attr( $attr['height_n_width']['width'] ) : '';

		\ob_start();

		/**
		 * Fires before rendering the course feature image in the course-feature-image block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_feature_image', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			.masteriyo-feature-img {
				<?php
				if ( $height ) :
					?>
					height: <?php echo $height; ?>; <?php endif; ?>
				<?php
				if ( $width ) :
					?>
					width: <?php echo $width; ?>; <?php endif; ?>
			}
		</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-course-featured-image--%s">',
			esc_attr( $client_id )
		);

		masteriyo_get_template(
			'single-course/featured-image.php',
			array(
				'course'     => $course,
				'difficulty' => $course->get_difficulty(),
			)
		);

		echo '</div>';

		/**
		 * Fires after rendering the course feature image in the course-feature-image block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_feature_image', $attr );

		return \ob_get_clean();
	}
}
