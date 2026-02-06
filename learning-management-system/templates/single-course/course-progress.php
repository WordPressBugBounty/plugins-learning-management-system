<?php
/**
 * The Template for displaying course progress bar in single course page
 *
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.14.0 [free]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering single course progress bar section in single course page.
 *
 * @since 1.14.0 [free]
 */
do_action( 'masteriyo_before_single_course_progress' );

$summary      = isset( $summary ) && is_array( $summary ) ? $summary : array();
$completed    = isset( $summary['total']['completed'] ) ? (int) $summary['total']['completed'] : 0;
$total        = isset( $summary['total']['total'] ) ? (int) $summary['total']['total'] : 0;
$remaining    = max( 0, $total - $completed );
$progress_raw = $total > 0 ? ( $completed / $total ) * 100 : 0;
$progress_pct = max( 0, min( 100, $progress_raw ) );

if ( ! isset( $is_completed ) ) {
	$is_completed = ( $total > 0 ) && ( $completed >= $total );
}

if ( is_singular( 'mto-course' ) ) {
	$show_progress = ! empty( $summary ) && (bool) masteriyo_get_setting( 'single_course.components_visibility.course_progress' );
} else {
	$show_progress = ! empty( $summary ) && (bool) masteriyo_get_setting( 'course_archive.components_visibility.course_progress' );
}

$layout = masteriyo_get_setting( 'single_course.display.template.layout' );
$class  = '';
if ( 'layout1' === $layout && masteriyo_is_single_course_page() ) {
	$class .= 'masteriyo-single-course--card';
}
?>

<?php if ( $show_progress && $progress_pct > 0 ) : ?>
	<div class="masteriyo-single-course-stats masteriyo-course-progress-bar <?php echo esc_attr( $class ); ?>">
		<div class="course-progress-box modern-progress">
			<?php if ( $is_completed ) : ?>
				<div class="course-progress-box course-completed-banner" role="status" aria-live="polite">
					<div class="completed-icon" aria-hidden="true">
						<!-- check-in-circle -->
						<svg xmlns="http://www.w3.org/2000/svg" fill="#26C164" viewBox="0 0 24 24" width="20" height="20" focusable="false" aria-hidden="true">
							<path d="M10.444 2.122a10 10 0 0 1 6.553 1.217.909.909 0 1 1-.908 1.574 8.182 8.182 0 1 0 3.928 5.453.91.91 0 0 1 1.782-.363 9.999 9.999 0 1 1-11.355-7.881Z"/>
							<path d="M20.446 4.087a.909.909 0 1 1 1.286 1.286l-9.091 9.09a.909.909 0 0 1-1.286 0l-2.727-2.726a.909.909 0 1 1 1.286-1.286l2.084 2.085 8.448-8.449Z"/>
						</svg>
					</div>
					<div class="completed-text">
						<strong><?php echo esc_html__( 'Course Completed', 'learning-management-system' ); ?></strong>
					</div>
					<div class="complete-eye-icon">
			<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="_blank" title="<?php echo esc_html__( 'Revisit Course', 'learning-management-system' ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
		<path d="M12 5c2.116 0 4.183.648 5.941 1.861a10.935 10.935 0 0 1 3.776 4.58l.158.375.011.032a1.93 1.93 0 0 1-.011 1.335 10.953 10.953 0 0 1-3.934 4.956A10.453 10.453 0 0 1 12.001 20c-2.116 0-4.184-.648-5.942-1.861a10.952 10.952 0 0 1-3.934-4.955l-.011-.032a1.93 1.93 0 0 1 0-1.304l.011-.032A10.953 10.953 0 0 1 6.06 6.861 10.454 10.454 0 0 1 12 5Zm0 1.875A8.675 8.675 0 0 0 7.07 8.42a9.08 9.08 0 0 0-3.251 4.08 9.086 9.086 0 0 0 3.25 4.08A8.676 8.676 0 0 0 12 18.125a8.674 8.674 0 0 0 4.93-1.545c1.45-1 2.58-2.42 3.25-4.08a9.085 9.085 0 0 0-3.25-4.08A8.674 8.674 0 0 0 12 6.875Z"/>
		<path d="M13.818 12.5c0-1.036-.814-1.875-1.818-1.875s-1.818.84-1.818 1.875c0 1.036.814 1.875 1.818 1.875s1.818-.84 1.818-1.875Zm1.818 0c0 2.071-1.628 3.75-3.636 3.75s-3.636-1.679-3.636-3.75c0-2.071 1.628-3.75 3.636-3.75s3.636 1.679 3.636 3.75Z"/>
	</svg>
</a>

					</div>
				</div>

			<?php else : ?>

				<div class="progress-header">
					<div class="progress-component">
						<h2 class="progress-label">
							<?php echo esc_html__( 'Completed', 'learning-management-system' ); ?>
						</h2>
						<div class="progress-percent">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %f: progress percentage (e.g. 75) */
									_x(
										'%.0f%%',
										'Course progress percentage',
										'learning-management-system'
									),
									$progress_pct
								)
							);
							?>
						</div>
					</div>

					<div class="completed-component">
						<div class="progress-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" fill="#666" viewBox="0 0 24 24" width="20" height="20" focusable="false">
								<path d="M20.182 12a8.182 8.182 0 1 0-16.364 0 8.182 8.182 0 0 0 16.364 0ZM22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10Z"/>
								<path d="M11.09 15.636V12a.91.91 0 1 1 1.82 0v3.636a.91.91 0 0 1-1.82 0Zm.919-8.181a.91.91 0 1 1 0 1.818H12a.91.91 0 1 1 0-1.818h.009Z"/>
							</svg>
							<span class="masteriyo-hidden masteriyo-summary-data" data-summary="<?php echo esc_attr( json_encode( $summary ) ); ?>"></span>
						</div>
						<div class="completed-info">
							<?php
							if ( masteriyo_is_single_course_page() ) {
								/* translators: 1: remaining items, 2: total items */
								echo esc_html( sprintf( __( '%1$d out of %2$d Left', 'learning-management-system' ), $remaining, $total ) );
							}
							?>
						</div>

					</div>
				</div>

				<div class="masteriyo-progress-bar-container modern-style" role="progressbar"
					aria-valuenow="<?php echo esc_attr( round( $progress_pct ) ); ?>"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-label="<?php echo esc_attr__( 'Course progress', 'learning-management-system' ); ?>">
					<div class="masteriyo-progress-bar" style="<?php printf( '--value:%s%%;', esc_attr( $progress_pct ) ); ?>">
						<div class="masteriyo-progress-fill animate"></div>
					</div>
				</div>

			<?php endif; ?>

		</div>
		<?php
		if ( 'layout1' === $layout ) {
			do_action( 'masteriyo_template_course_inside_progress', $course );
			$user_id = get_current_user_id();
			if ( masteriyo_is_user_enrolled_in_course( $course->get_id(), $user_id ) && 'layout1' === $layout && masteriyo_is_single_course_page() ) {
				do_action( 'masteriyo_template_enroll_button', $course );
			}
		}
		?>

	</div>
<?php endif; ?>

<?php
/**
 * Fires after rendering single course progress bar section in single course page.
 *
 * @since 1.14.0 [free]
 */
do_action( 'masteriyo_after_single_course_progress' );
