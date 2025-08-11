<?php
/**
 * The Template for displaying course overview in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/overview.php.
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
 * Fires before rendering overview section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_overview' );


if ( ! isset( $course ) || ! is_object( $course ) ) {
	return;
}

$query                    = new \Masteriyo\Query\CourseProgressQuery(
	array(
		'course_id' => $course->get_id(),
		'user_id'   => get_current_user_id(),
	)
);
$progress                 = current( $query->get_course_progress() );
$summary                  = $progress ? $progress->get_summary( 'all' ) : '';
$show_overview_active     =
	$show_overview_active = ! masteriyo_is_user_enrolled_in_course( $course->get_id() );

$is_hidden     = ! $show_overview_active;
$course_values = $course->get_custom_fields();
$course_values = is_array( $course_values ) ? $course_values : array();
?>

<div class="tab-content course-overview <?php echo $is_hidden ? 'masteriyo-hidden' : ''; ?>">
	<?php echo do_shortcode( wp_kses_post( $course->get_description() ) ); ?>

	<?php if ( ! empty( $course_values ) ) : ?>
		<div id="masteriyo-custom-fields">
			<script type="application/json" id="masteriyo-course-values">
				<?php echo wp_json_encode( $course_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>
			</script>
			<div class="custom-fields-container"></div>
		</div>
	<?php endif; ?>
</div>

<?php

/**
 * Fires after rendering overview section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_overview' );
