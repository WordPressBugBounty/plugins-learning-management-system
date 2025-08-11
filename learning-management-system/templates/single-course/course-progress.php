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

?>

<div class="masteriyo-single-course-stats">

<?php if ( ( masteriyo_get_setting( 'course_archive.components_visibility.course_progress' ) && $summary ) || ( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && $summary ) ) : ?>
	<?php
		$completed        = isset( $summary['total']['completed'] ) ? $summary['total']['completed'] : 0;
		$total            = isset( $summary['total']['total'] ) ? $summary['total']['total'] : 0;
		$progress_percent = $total > 0 ? ( $completed / $total ) * 100 : 0;
	?>

	<div class="course-progress-box modern-progress">
		<div class="progress-header">
			<h2 class="progress-label"><?php esc_html_e( 'Your Progress', 'learning-management-system' ); ?></h2>
			<div class="progress-percent"><?php echo esc_html( sprintf( __( '%.0f%%', 'learning-management-system' ), $progress_percent ) ); ?></div>
		</div>

		<div class="masteriyo-progress-bar-container modern-style">
			<div class="masteriyo-progress-bar" style="--value: <?php echo esc_attr( $progress_percent ); ?>%;">
				<div class="masteriyo-progress-fill animate"></div>
			</div>
		</div>

		<div class="completed-info">
			<?php echo esc_html( sprintf( __( '%1$d of %2$d Completed', 'learning-management-system' ), $completed, $total ) ); ?>
		</div>
	</div>
<?php endif; ?>

</div>

<?php
/**
 * Fires after rendering single course progress bar section in single course page.
 *
 * @since 1.14.0 [free]
 */
do_action( 'masteriyo_after_single_course_progress' );
?>
