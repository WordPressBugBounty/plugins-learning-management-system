<?php

/**
 * The Template for displaying course stats in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/course-stats.php.
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
 * Fires before rendering stats section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_stats' );

?>
<div class="masteriyo-single-course-stats">

	<!-- Duration -->
	<div class="duration">
		<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
			<?php masteriyo_get_svg( 'time', true ); ?>
			<span>
				<?php /* translators: %s: Human understanble time string */ ?>
				<?php echo esc_html( sprintf( '%s', masteriyo_minutes_to_time_length_string( $course->get_duration() ) ) ); ?>
			</span>
		</div>
	</div>

	<!-- Student -->
	<div class="student">
		<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
			<?php masteriyo_get_svg( 'group', true ); ?>
			<span>
				<?php
				printf(
					/* translators: %d: Enrolled  students count */
					esc_html( _nx( '%s Student', '%s Students', $enrolled_users_count, 'Enrolled Students Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $enrolled_users_count ) )
				);
				?>
			</span>
		</div>
	</div>

	<!-- Difficulty -->
	<?php if ( $course->get_difficulty() ) : ?>
		<div class="difficulty">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'level', true ); ?>
				<span>
					<?php echo esc_html( $course->get_difficulty()['name'] ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Date Modified -->
	<?php if ( $course->get_date_modified() ) : ?>
		<div class="last-updated">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'last-updated', true ); ?>
				<span>
					<?php
					$modified_date  = strtotime( $course->get_date_modified() );
					$formatted_date = gmdate( 'F j, Y', $modified_date );
					/* translators: %s: Formatted Date */
					echo esc_html( sprintf( __( 'Last Updated: %s', 'learning-management-system' ), $formatted_date ) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Date Started -->
	<?php if ( $progress ) : ?>
		<div class="course-started-at">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'calender', true ); ?>
				<span>
					<?php
					$started_date   = strtotime( $progress->get_started_at() );
					$formatted_date = gmdate( 'F j, Y', $started_date );
					/* translators: %s: Formatted Date */
					echo esc_html( sprintf( __( 'Started At: %s', 'learning-management-system' ), $formatted_date ) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Available seats for students-->
	<?php if ( $course->get_enrollment_limit() > 0 ) : ?>
		<div class="masteriyo-available-seats-for-students">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'available-seats-for-students', true ); ?>
				<span>
					<?php
					printf(
						/* translators: %d: Available Seats Count */
						esc_html( _nx( 'Available Seat: %s', 'Available Seats: %s', $remaining_available_seats, 'Available Seats Count', 'learning-management-system' ) ),
						esc_html( $remaining_available_seats )
					);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

</div>
<?php

/**
 * Fires after rendering stats section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_stats' );
