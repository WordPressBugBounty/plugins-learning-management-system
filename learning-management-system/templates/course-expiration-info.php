<?php
/**
 * The Template for displaying course cohort info in single course page.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-expiration-info.php.
 *
 * @package Masteriyo\Templates
 * @version 3.1.0
 */

defined( 'ABSPATH' ) || exit;

$date_format     = 'M j, Y';
$time_format     = 'g:i A';
$datetime_format = $date_format . ' ' . $time_format;

$layout = masteriyo_get_setting( 'single_course.display.template.layout' );
$class  = '';

if ( 'layout1' === $layout && masteriyo_is_single_course_page() ) {
	$class .= 'masteriyo-single-course--card';
}

// If end date is enabled, show only course end date card (with time) and return.
if ( ! empty( $course->get_enable_end_date() ) && ! $course->get_enable_cohort_mode() ) {

	if ( ! $course ) {
		return false;
	}

	$course_end_date = $course->get_end_date();

	if ( $course_end_date ) : ?>
		<div class="cohort-cards-wrapper masteriyo-single-course--cohort <?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>">
			<div class="cohort-section">
				<div class="cohort-card">
					<div class="cohort-card-text">
						<span class="cohort-label">
							<strong><?php esc_html_e( 'Course Ends:', 'learning-management-system' ); ?></strong>
						</span>
						<span class="cohort-date-time">
							<?php echo esc_html( masteriyo_format_datetime( $course_end_date, $datetime_format ) ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>
		<?php
	endif;

	return;
}

if ( ! $course || ! $course->get_enable_cohort_mode() ) {
	return false;
}

$enrollment_opens_on  = $course->get_enrollment_opens_on();
$enrollment_closes_on = $course->get_enrollment_closes_on();
$course_start_date    = $course->get_course_start_date();
$course_end_date      = $course->get_end_date();

$now_ts   = current_time( 'timestamp' );
$start_ts = $course_start_date ? $course_start_date->getTimestamp() : null;
$end_ts   = $course_end_date ? $course_end_date->getTimestamp() : null;
$open_ts  = $enrollment_opens_on ? $enrollment_opens_on->getTimestamp() : null;
$close_ts = $enrollment_closes_on ? $enrollment_closes_on->getTimestamp() : null;

$is_enrollment_open = function_exists( 'is_enrollment_open_now' ) ? is_enrollment_open_now( $course ) : false;
$is_cohort_active   = function_exists( 'is_cohort_active_now' ) ? is_cohort_active_now( $course ) : false;

$is_course_future  = $start_ts && ( $now_ts < $start_ts );
$is_course_started = $start_ts && ( $now_ts >= $start_ts );
$is_course_ended   = $end_ts && ( $now_ts > $end_ts );

$current_user_id = get_current_user_id();
$is_enrolled     = $current_user_id ? masteriyo_is_user_enrolled_in_course( $course->get_id(), $current_user_id ) : false;

$has_enrollment_dates = ( $enrollment_opens_on || $enrollment_closes_on );

$is_logged_in = is_user_logged_in();

$show_all_info = ( ! $is_logged_in ) || ( ! $is_enrolled );

$enrollment_not_started = ( $open_ts && ( $now_ts < $open_ts ) );
$enrollment_closed      = ( $close_ts && ( $now_ts > $close_ts ) );

$course_not_started = ( $start_ts && ( $now_ts < $start_ts ) );
$course_finished    = ( $end_ts && ( $now_ts > $end_ts ) );

if ( ! $show_all_info ) {
	$show_enrollment_block      = $has_enrollment_dates;
	$show_enrollment_open_card  = ( $enrollment_opens_on && $enrollment_not_started );
	$show_enrollment_close_card = ( $enrollment_closes_on && ! $enrollment_closed );

	$show_course_start_card = ( $course_start_date && $course_not_started );
	$show_course_end_card   = ( $course_end_date && ! $course_finished );
} else {
	$show_enrollment_block      = $has_enrollment_dates;
	$show_enrollment_open_card  = (bool) $enrollment_opens_on;
	$show_enrollment_close_card = (bool) $enrollment_closes_on;

	$show_course_start_card = (bool) $course_start_date;
	$show_course_end_card   = (bool) $course_end_date;
}

if ( $has_enrollment_dates || $course_start_date || $course_end_date ) :
	?>
	<div class="cohort-cards-wrapper masteriyo-single-course--cohort <?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>">

		<?php if ( $show_enrollment_block ) : ?>
			<div class="cohort-section">
				<?php if ( $show_enrollment_open_card ) : ?>
					<div class="cohort-card">
						<div class="cohort-card-text">
							<span class="cohort-label">
								<strong><?php esc_html_e( 'Enrollment Opens:', 'learning-management-system' ); ?></strong>
							</span>
							<span class="cohort-date-time">
								<?php echo esc_html( masteriyo_format_datetime( $enrollment_opens_on, $datetime_format ) ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $show_enrollment_close_card ) : ?>
					<div class="cohort-card">
						<div class="cohort-card-text">
							<span class="cohort-label">
								<strong><?php esc_html_e( 'Enrollment Closes:', 'learning-management-system' ); ?></strong>
							</span>
							<span class="cohort-date-time">
								<?php echo esc_html( masteriyo_format_datetime( $enrollment_closes_on, $datetime_format ) ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $course_start_date || $course_end_date ) : ?>
			<div class="cohort-section">

				<?php if ( $show_course_start_card ) : ?>
					<div class="cohort-card">
						<div class="cohort-card-text">
							<span class="cohort-label">
								<strong><?php esc_html_e( 'Course Starts:', 'learning-management-system' ); ?></strong>
							</span>
							<span class="cohort-date-time">
								<?php echo esc_html( masteriyo_format_datetime( $course_start_date, $datetime_format ) ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $show_course_end_card ) : ?>
					<div class="cohort-card">
						<div class="cohort-card-text">
							<span class="cohort-label">
								<strong><?php esc_html_e( 'Course Ends:', 'learning-management-system' ); ?></strong>
							</span>
							<span class="cohort-date-time">
								<?php echo esc_html( masteriyo_format_datetime( $course_end_date, $datetime_format ) ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>

			</div>
		<?php endif; ?>

	</div>
<?php endif; ?>
