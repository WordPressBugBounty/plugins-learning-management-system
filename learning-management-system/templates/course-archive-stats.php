<?php

/**
 * The Template for displaying course stats in archive courses page
 *
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.11.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_before_archive_course_stats' );

?>
<div class="masteriyo-course--content__stats">
	<div class="masteriyo-course-stats-duration">
		<?php masteriyo_get_svg( 'time', true ); ?> <span><?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?></span>
	</div>
	<div class="masteriyo-course-stats-students">
		<?php masteriyo_get_svg( 'group', true ); ?> <span><?php echo esc_html( masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count() ); ?></span>
	</div>
	<div class="masteriyo-course-stats-curriculum">
		<?php masteriyo_get_svg( 'book', true ); ?> <span><?php echo esc_html( masteriyo_get_lessons_count( $course ) ); ?></span>
	</div>
	<!-- Available seats for students-->
	<?php if ( $course->get_enrollment_limit() > 0 ) : ?>
		<div class="masteriyo-available-seats-for-students">
			<?php masteriyo_get_svg( 'available-seats-for-students', true ); ?> <span><?php echo esc_html( $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0 ); ?></span>
		</div>
	<?php endif; ?>
</div>

<?php

/**
 * Fires after rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_after_archive_course_stats' );
