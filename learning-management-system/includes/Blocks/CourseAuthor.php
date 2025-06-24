<?php

/**
 * Course author block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseAuthor
 *
 * Displays the course author information in the single course layout.
 *
 * @since 1.18.2
 */
class CourseAuthor extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-author';

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

		$course              = $this->get_block_preview_course( $course_id );
		$hide_authors_name   = 'yes' === ( $attr['hideAuthorsName'] ?? 'no' ) ? 'none' : 'inline-block';
		$hide_authors_avatar = 'yes' === ( $attr['hideAuthorsAvatar'] ?? 'no' ) ? 'none' : 'inline-block';

		\ob_start();

		/**
		 * Fires before rendering course author in the course-author block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_author', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			.masteriyo-single-course .masteriyo-course-author img {
				display: <?php echo esc_attr( $hide_authors_avatar ); ?>;
			}
			.masteriyo-single-course .masteriyo-course-author .masteriyo-course-author--name {
				display: <?php echo esc_attr( $hide_authors_name ); ?>;
			}
			.masteriyo-single-course .masteriyo-course-author a {
				text-decoration: none;
			}
		</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-title-block--%s">',
			esc_attr( $client_id )
		);

		masteriyo_get_template(
			'single-course/author-and-rating.php',
			array(
				'course' => $course,
				'author' => masteriyo_get_user( $course->get_author_id() ),
			)
		);

		echo '</div>';

		/**
		 * Fires after rendering course author in the course-author block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_author', $attr );

		return \ob_get_clean();
	}
}
