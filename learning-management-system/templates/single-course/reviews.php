<?php
/**
 * The Template for displaying course reviews in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/reviews.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering reviews list section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_reviews' );

$is_hidden = isset( $is_hidden ) ? $is_hidden : true;
if ( isset( $show_review ) && $show_review ) {
	$is_hidden = false;
}
?>
<div class="tab-content course-reviews <?php echo $is_hidden ? 'masteriyo-hidden' : ''; ?>">
		<!-- Review form -->
				<?php
				$course_id = $course->get_id();

				$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course_id, 1 );
				/**
				 * Fires to render review content on single course page layout 1.
				 *
				 * @since 1.10.0 [Free]
				 *
				 * @param \Masteriyo\Models\Course $course The course object.
				 */
				do_action( 'masteriyo_course_reviews_content', $course, $reviews_and_replies );
				?>
</div>
<?php

/**
 * Fires after rendering reviews list section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_reviews' );
