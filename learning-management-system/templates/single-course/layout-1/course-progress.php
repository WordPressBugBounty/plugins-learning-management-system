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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering progress bar section in single course page.
 *
 * @since 1.14.0
 */
do_action( 'masteriyo_before_single_course_progress_bar', $course );

?>

<!-- Course Progress -->
<?php if ( ( masteriyo_get_setting( 'course_archive.components_visibility.course_progress' ) && $summary ) || ( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && $summary ) ) : ?>
	<div class="course-progress">
		<div class="course-progress-bar">
			<div class="masteriyo-progress-info">
				<h2 class="progress-text">
					<?php esc_html_e( 'Your Progress', 'learning-management-system' ); ?>
				</h2>
				<div class="progress-percent">
					<?php
					$progress_percent = $summary['total']['completed'] / $summary['total']['total'] * 100;
					/* translators: %s: Progress percentage */
					echo esc_html( sprintf( __( '%.0f%%', 'learning-management-system' ), $progress_percent ) );
					?>
				</div>
			</div>
			<!-- Progress Bar -->
			<div class="masteriyo-progress-bar-container">
				<div class="masteriyo-progress-bar" style="--value: <?php echo esc_attr( $progress_percent ); ?>%;">
					<div class="masteriyo-progress-fill animate"></div>
				</div>
				<div class="completed-text">
					<?php
					/* translators: 1: Number of completed items, 2: Total number of items */
					echo esc_html( sprintf( __( '%1$d of %2$d Completed', 'learning-management-system' ), $summary['total']['completed'], $summary['total']['total'] ) );
					?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php

/**
 * Fires after rendering progress bar section in single course page.
 *
 * @since 1.14.0
 */
do_action( 'masteriyo_after_single_course_progress_bar', $course );
