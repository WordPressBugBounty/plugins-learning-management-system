<?php
/**
 * The Template for displaying course highlights in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/highlights.php.
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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering highlights section in single course page.
 *
 * @since 1.10.0
 *
 * @param \Masteriyo\Course\Course $course The course object. @since 1.12.0
 */
do_action( 'masteriyo_before_single_course_highlights', $course );
?>

<?php if ( ( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && masteriyo_get_setting( 'course_archive.components_visibility.course_description' ) && ! empty( $course->get_highlights() ) && ! empty( wp_strip_all_tags( $course->get_highlights(), true ) ) ) || ( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && ! empty( $course->get_highlights() ) && ! empty( wp_strip_all_tags( $course->get_highlights(), true ) ) ) ) : ?>
	<div class="masteriyo-single-body__aside--course-includes">
		<h3 class="masteriyo-single-body__aside--heading title"><?php esc_html_e( 'Course Includes', 'learning-management-system' ); ?></h3>
		<?php
		/**
		 * Filters course highlights to before rendering.
		 *
		 * @since 1.10.0
		 *
		 * @param string $highlights The course highlights.
		 */
		echo wp_kses_post( apply_filters( 'masteriyo_single_course_highlights_content', masteriyo_format_course_highlights( $course->get_highlights() ) ) );
		?>
	</div>
<?php endif; ?>

<?php

/**
 * Fires after rendering highlights section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_after_single_course_highlights', $course );
