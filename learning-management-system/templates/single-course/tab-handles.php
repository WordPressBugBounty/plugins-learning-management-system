<?php
/**
 * The Template for displaying tab handles in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/tab-handles.php.
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

use Masteriyo\Query\CourseProgressQuery;


$query    = new CourseProgressQuery(
	array(
		'course_id' => $course->get_id(),
		'user_id'   => get_current_user_id(),
	)
);
$progress = current( $query->get_course_progress() );
$summary  = $progress ? $progress->get_summary( 'all' ) : '';


$show_overview_active = false;

if (
	( isset( $summary['total']['completed'] ) && $summary['total']['completed'] === 0 && $summary['total']['total'] > 0 ) ||
	! is_user_logged_in() ||
	empty( $progress )
) {
	$show_overview_active = true;
}
?>

<div class="tab-menu masteriyo-stab">

	<?php if ( $show_overview_active ) : ?>
		<div class="masteriyo-tab active-tab" onClick="masteriyo_select_single_course_page_tab(event, '.tab-content.course-overview');">
			<?php echo esc_html__( 'Overview', 'learning-management-system' ); ?>
		</div>
	<?php endif; ?>

	<?php
	if ( $show_curriculum ) :
		if ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) ) :
			$curriculum_class = $show_overview_active ? 'masteriyo-tab' : 'masteriyo-tab active-tab';
			?>
			<div class="<?php echo esc_attr( $curriculum_class ); ?>" onClick="masteriyo_select_single_course_page_tab(event, '.tab-content.course-curriculum');">
				<?php echo esc_html__( 'Curriculum', 'learning-management-system' ); ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( ! $show_overview_active ) : ?>
		<div class="masteriyo-tab" onClick="masteriyo_select_single_course_page_tab(event, '.tab-content.course-overview');">
			<?php echo esc_html__( 'Overview', 'learning-management-system' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.enable_review' ) ) && $course->is_review_allowed() ) : ?>
		<div class="masteriyo-tab" onClick="masteriyo_select_single_course_page_tab(event, '.tab-content.course-reviews');">
			<?php echo esc_html__( 'Reviews', 'learning-management-system' ); ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Hooks for single course page tabs
	 *
	 * @since 1.5.7
	 */
	do_action( 'masteriyo_single_course_main_content_tab', $course );
	?>
</div>
