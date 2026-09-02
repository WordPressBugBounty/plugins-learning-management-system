<?php
/**
 * Course expiry countdown (remaining days shown, with progress bar).
 *
 * Copy to: yourtheme/masteriyo/single-course/course-expiry-countdown.php
 *
 * @package Masteriyo\Templates
 * @version 2.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'masteriyo_before_course_expiry_countdown' );

$remaining = max( 0, absint( $remaining_validity_duration ) );

$layout = masteriyo_get_setting( 'single_course.display.template.layout' );
$class  = '';
if ( 'layout1' === $layout && masteriyo_is_single_course_page() ) {
	$class .= 'masteriyo-single-course--card';
}
?>

<div class="mto-expiry <?php echo esc_attr( $class ); ?>">
	<h3 class="masteriyo-aside-heading"><?php esc_html_e( 'Enrollment Expiration', 'learning-management-system' ); ?></h3>

	<div class="masteriyo-single-course-stats masteriyo-course-progress-bar">


	<div class="course-progress-box modern-progress">
		<div class="progress-header">
			<div class="progress-component">
			<div class="completed-info">
				<?php
				if ( intval( $remaining ) === 1 ) {
					esc_html_e( 'Day Left', 'learning-management-system' );
				} else {
					esc_html_e( 'Day(s) Left', 'learning-management-system' );
				}
				?>
				</div>
			<div class="progress-percent"><?php echo esc_html( $remaining ); ?></div>
			</div>

		</div>
		<div class="masteriyo-progress-bar-container modern-style">
			<div class="masteriyo-progress-bar" style="--value: <?php echo esc_attr( $remaining ); ?>%;">
				<div class="masteriyo-progress-fill animate"></div>
			</div>
		</div>
	</div>

</div>
</div>
<?php
/**
 * Fires after rendering the countdown section in a single course page.
 *
 * @since 2.7.0
 */
do_action( 'masteriyo_after_course_expiry_countdown' );
