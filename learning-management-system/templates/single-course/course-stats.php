<?php
/**
 * Template for displaying course stats in single course page.
 *
 * Override by placing in yourtheme/masteriyo/single-course/course-stats.php.
 *
 * @package Masteriyo\Templates
 * @version 1.1.0 (custom override with block-level attribute support)
 */

defined( 'ABSPATH' ) || exit;

do_action( 'masteriyo_before_single_course_stats' );

// For compatibility with global template loading
$attributes = $attributes ?? array();

if ( ! function_exists( 'is_component_visible' ) ) {
	/**
	 * Helper function to check component visibility.
	 *
	 * @param mixed  $block_attr     The block attribute (true/false/null)
	 * @param string $global_key     The global Masteriyo setting key
	 * @param bool   $default        Fallback default
	 *
	 * @return bool
	 */
	function is_component_visible( $block_attr, $global_key, $default = false ) {
		if ( is_bool( $block_attr ) ) {
			return $block_attr;
		}
		return masteriyo_get_setting( "course_archive.components_visibility.$global_key", $default );
	}
}
?>

<div class="masteriyo-single-course-stats">

	<!-- Course Duration -->
	<?php if ( is_component_visible( $attributes['enableCourseDuration'] ?? null, 'course_duration' ) ) : ?>
		<div class="duration">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'time', true ); ?>
				<span>
					<?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Student Count -->
	<?php if ( is_component_visible( $attributes['enableStudentCount'] ?? null, 'students_count' ) ) : ?>
		<div class="student">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'group', true ); ?>
				<span>
					<?php
					printf(
						esc_html( _nx( '%s Student', '%s Students', $enrolled_users_count, 'Enrolled Students Count', 'learning-management-system' ) ),
						esc_html( number_format_i18n( $enrolled_users_count ) )
					);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Available Seats -->
	<?php
	if (
		is_component_visible( $attributes['enableAvailableSeatsCount'] ?? null, 'seats_for_students' )
		&& $course->get_enrollment_limit() > 0
	) :
		?>
		<div class="masteriyo-available-seats-for-students">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'available-seats-for-students', true ); ?>
				<span>
					<?php
					printf(
						esc_html( _nx( 'Available Seat: %s', 'Available Seats: %s', $remaining_available_seats, 'Available Seats Count', 'learning-management-system' ) ),
						esc_html( $remaining_available_seats )
					);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Date Updated -->
	<?php
	if (
		is_component_visible( $attributes['enableDateUpdated'] ?? null, 'date_updated' )
		&& $course->get_date_modified()
	) :
		?>
		<div class="last-updated">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'last-updated', true ); ?>
				<span>
					<?php
					$modified_date = strtotime( $course->get_date_modified() );
					echo esc_html( sprintf( __( 'Last Updated: %s', 'learning-management-system' ), gmdate( 'F j, Y', $modified_date ) ) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Date Started -->
	<?php
	if (
		is_component_visible( $attributes['enableDateStarted'] ?? null, 'date_started' )
		&& $progress
	) :
		?>
		<div class="course-started-at">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'calender', true ); ?>
				<span>
					<?php
					$started_date = strtotime( $progress->get_started_at() );
					echo esc_html( sprintf( __( 'Started At: %s', 'learning-management-system' ), gmdate( 'F j, Y', $started_date ) ) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

</div>

<?php
do_action( 'masteriyo_after_single_course_stats' );
