<?php
/**
 * Template for displaying course stats in single course page.
 *
 * Override by placing in yourtheme/masteriyo/single-course/course-stats.php.
 *
 * @package Masteriyo\Templates
 * @version 2.3.2 [free] (custom override with block-level attribute support)
 */

defined( 'ABSPATH' ) || exit;
use Masteriyo\CoreFeatures\CourseComingSoon\Helper;
use Masteriyo\Enums\CourseProgressStatus;

do_action( 'masteriyo_before_single_course_stats' );

// For compatibility with global template loading
$attributes = $attributes ?? array();

// Callers pass $course in, but a theme override may not. There are no stats
// without one, so leave rather than fatal on the first dereference.
$course = isset( $course ) ? masteriyo_get_course( $course ) : null;

if ( ! $course ) {
	return;
}

$is_enrolled = function_exists( 'masteriyo_is_user_enrolled_in_course' )
	? masteriyo_is_user_enrolled_in_course( $course->get_id() )
	: false;
$satisfied   = Helper::course_coming_soon_satisfied( $course );

if ( ! $satisfied ) {
	return false;
}

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
		return masteriyo_get_setting( "single_course.components_visibility.$global_key", $default );
	}
}
?>

<div class="masteriyo-single-course-stats masteriyo-course-statistics">
<?php if ( $is_enrolled ) : ?>

	<!-- Date Updated -->
	<?php
	if (
		is_component_visible( $attributes['enableDateUpdated'] ?? null, 'date_updated' )
		&& $course->get_date_modified()
	) :
		?>
		<div class="masteriyo-stats last-updated">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'last-updated', true ); ?>
				<span>
					<?php
					$modified_date = strtotime( $course->get_date_modified() );
					printf(
						/* translators: %s: last updated date */
						esc_html__( 'Last Updated: %s', 'learning-management-system' ),
						esc_html( gmdate( 'F j, Y', $modified_date ) )
					);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Date Started -->
	<?php
	if (
		is_component_visible( $attributes['enableDateStarted'] ?? null, 'date_started' )
		&& ! empty( $progress ) && $progress->get_started_at()
	) :
		?>
		<div class="masteriyo-stats course-started-at">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'start-calendar', true ); ?>
				<span>
					<?php
					$started_date = strtotime( $progress->get_started_at() );
						printf(
							/* translators: %s: course start date */
							esc_html__( 'Started At: %s', 'learning-management-system' ),
							esc_html( gmdate( 'F j, Y', $started_date ) )
						);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Way out of a finished course -->
	<?php
	if ( ! empty( $progress ) && CourseProgressStatus::COMPLETED === $progress->get_status() ) :
		$learner_home = masteriyo_get_learner_home( $course );
		?>
		<a href="<?php echo esc_url( $learner_home['url'] ); ?>" class="masteriyo-course-statistics__home">
			<svg class="masteriyo-course-statistics__home-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
				<path d="M20 11H7.83l4.88-4.88a1 1 0 1 0-1.42-1.41l-6.58 6.58a1 1 0 0 0 0 1.42l6.58 6.58a1 1 0 0 0 1.42-1.41L7.83 13H20a1 1 0 0 0 0-2Z"/>
			</svg>
			<?php echo esc_html( $learner_home['label'] ); ?>
		</a>
	<?php endif; ?>

<?php else : ?>

	<!-- Course Duration -->
	<?php if ( is_component_visible( $attributes['enableCourseDuration'] ?? null, 'course_duration' ) && $course->get_duration() > 0 ) : ?>
		<div class="masteriyo-stats duration">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'time', true ); ?>
				<span>
					<?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Student Count -->
	<?php if ( is_component_visible( $attributes['enableStudentCount'] ?? null, 'students_count' ) && $enrolled_users_count > 0 ) : ?>
		<div class="masteriyo-stats student">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'group', true ); ?>
				<span>
					<?php
					printf(
						esc_html(
							/* translators: %s: number of enrolled students */
							_nx(
								'%s Student',
								'%s Students',
								$enrolled_users_count,
								'Enrolled Students Count',
								'learning-management-system'
							)
						),
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
		<div class="masteriyo-stats masteriyo-available-seats-for-students">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'available-seats-for-students', true ); ?>
				<span>
					<?php
					printf(
						esc_html(
							/* translators: %s: number of available seats */
							_nx(
								'Available Seat: %s',
								'Available Seats: %s',
								$remaining_available_seats,
								'Available Seats Count',
								'learning-management-system'
							)
						),
						esc_html( number_format_i18n( $remaining_available_seats ) )
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
		<div class="masteriyo-stats last-updated">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'last-updated', true ); ?>
				<span>
					<?php
					$modified_date = strtotime( $course->get_date_modified() );
					/* translators: %s: course last updated date */
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
		&& ! empty( $progress ) && $progress->get_started_at()
	) :
		?>
		<div class="masteriyo-stats course-started-at">
			<div class="masteriyo-single-course--mdetail masteriyo-icon-svg">
				<?php masteriyo_get_svg( 'start-calendar', true ); ?>
				<span>
					<?php
					$started_date = strtotime( $progress->get_started_at() );
					/* translators: %s: course start date */
					echo esc_html( sprintf( __( 'Started At: %s', 'learning-management-system' ), gmdate( 'F j, Y', $started_date ) ) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>

<?php endif; ?>
</div>

<?php
do_action( 'masteriyo_after_single_course_stats' );
