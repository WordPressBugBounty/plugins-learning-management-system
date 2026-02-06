<?php
/**
 * The Template for displaying course cohort info in single course page.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/cohort.php.
 *
 * @package Masteriyo\Templates
 * @version 2.1.0
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

if ( ! empty( $course->get_enable_end_date() ) ) {

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
}
