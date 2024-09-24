<?php
/**
 * "Add to Cart" button.
 *
 * @version 1.0.0
 */

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Notice;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! $course->is_purchasable() ) {
	return;
}

/**
 * Fires before rendering enroll/add-to-cart button.
 *
 * @since 1.0.0
 * @since 1.5.12 Added $course parameter.
 *
 * @param \Masteriyo\Models\Course $course Course object.
 */
do_action( 'masteriyo_before_add_to_cart_button', $course );

/**
 * Filter the additional attributes for the enroll button.
 *
 * @since 1.12.0
 */
$additional_attributes = apply_filters( 'masteriyo_add_to_cart_button_attributes', array(), $course );

$additional_attributes_string = '';
foreach ( $additional_attributes as $key => $value ) {
	$additional_attributes_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
}

?>

<?php if ( masteriyo_can_start_course( $course ) ) : ?>
	<?php if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) : ?>
		<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="_blank" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php echo wp_kses_post( $course->single_course_completed_text() ); ?>
		</a>
	<?php elseif ( $progress && CourseProgressStatus::PROGRESS === $progress->get_status() ) : ?>
		<?php
		$quiz_attempt = masteriyo_is_course_quiz_started( $course->get_id() );
		if ( $quiz_attempt && masteriyo_get_quiz( $quiz_attempt->get_quiz_id() ) && $course->get_disable_course_content() ) :
			?>
			<a href="<?php echo esc_url( masteriyo_get_course_item_learn_page_url( $course, masteriyo_get_quiz( $quiz_attempt->get_quiz_id() ) ) ); ?>" target="_blank" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php echo wp_kses_post( $course->single_course_continue_quiz_text() ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $course->continue_course_url( $progress ) ); ?>" target="_blank" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php echo wp_kses_post( $course->single_course_continue_text() ); ?>
			</a>
		<?php endif; ?>
	<?php else : ?>
		<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="_blank" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php echo wp_kses_post( $course->single_course_start_text() ); ?>
		</a>
	<?php endif; ?>
<?php else : ?>
	<a href="<?php echo esc_url( $course->add_to_cart_url() ); ?>" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
		<?php echo wp_kses_post( $course->add_to_cart_text() ); ?>
	</a>
<?php endif; ?>
<?php

if ( 0 !== $course->get_enrollment_limit() && 0 === $course->get_available_seats() && ! masteriyo_can_start_course( $course ) ) {
	masteriyo_display_notice(
		esc_html__( 'Sorry, students limit reached. Course closed for enrollment.', 'learning-management-system' ),
		Notice::WARNING
	);
}

/**
 * Fires after rendering enroll/add-to-cart button.
 *
 * @since 1.0.0
 * @since 1.5.12 Added $course parameter.
 *
 * @param \Masteriyo\Models\Course $course Course object.
 */
do_action( 'masteriyo_after_add_to_cart_button', $course );
?>
