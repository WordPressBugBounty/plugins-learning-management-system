<?php

/**
 * Course reviews block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseReviews
 *
 * Displays course reviews and replies within a single course block.
 *
 * @since 1.18.2
 */
class CourseReviews extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-reviews';

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
		 * Fires before rendering course reviews in the course-reviews block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_reviews', $attr );
		$is_block_page = $this->is_block_editor();
		?>

		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( ! masteriyo_has_user_already_reviewed_course( $course_id ) && ! $is_block_page ) : ?>
					.masteriyo-submit-container{
						display: block !important;
					}
				<?php endif; ?>

					<?php if ( $is_block_page ) : ?>
					.masteriyo-stab--treviews,.masteriyo-stab--turating,.masteriyo-course-reviews-filters,.masteriyo-course-reviews-list{
						display: none !important;
					}
				<?php endif; ?>
		</style>

		<?php
		if ( $course && $course->is_review_allowed() ) {
			$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course );

				printf(
					'<div class="masteriyo-block masteriyo-course-reviews-block--%s" data-id="%s">',
					esc_attr( $client_id ),
					esc_attr( $course_id )
				);

			masteriyo_get_template(
				'single-course/reviews.php',
				array(
					'course'         => $course,
					'course_reviews' => $reviews_and_replies['reviews'],
					'replies'        => $reviews_and_replies['replies'],
					'is_hidden'      => false,
					'show_review'    => true,
				)
			);

			echo '</div>';
		}

		/**
		 * Fires after rendering course reviews in the course-reviews block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_reviews', $attr );

		return \ob_get_clean();
	}
}
