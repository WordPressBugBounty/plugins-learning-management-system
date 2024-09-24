<?php

/**
 * The Template for displaying all single course detail
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/content-single-course-1.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0
 */

defined( 'ABSPATH' ) || exit;

global $course;

// Ensure visibility.
if ( empty( $course ) || ! $course->is_visible() ) {
	return;
}

/**
 * Fires before rendering single course page content.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_content' );

?>
<div id="course-<?php the_ID(); ?>" class="masteriyo-single">
		<?php
			/**
			 * Action hook for rendering single course page content.
			 *
			 * @since 1.10.0
			 */
			do_action( 'masteriyo_layout_1_single_course_content', $course );
		?>
</div>
<?php
/**
 * Fires after rendering single course page content.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_content' );
