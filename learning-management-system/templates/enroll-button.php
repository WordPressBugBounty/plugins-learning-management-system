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

/**
 * Filter the target attribute for course buttons (Start Course, Continue Course, etc.).
 *
 * This filter allows users to control whether course buttons open in a new tab or the same tab.
 * By default, buttons open in a new blank tab (_blank).
 *
 * @since x.x.x [Free]
 *
 * @param string                   $target The target attribute value. Default '_blank'.
 * @param \Masteriyo\Models\Course $course Course object.
 */
$button_target = apply_filters( 'masteriyo_course_button_target', '_blank', $course );

if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
	return false;
}

$svg_lock = '';
if ( post_password_required( get_post( $course->get_id() ) ) ) {
	$svg_lock = '
		<svg xmlns="http://www.w3.org/2000/svg" fill="#424360" viewBox="0 0 24 24" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;">
			<path d="M19.2 12.91a.905.905 0 0 0-.9-.91H5.7c-.497 0-.9.407-.9.91v6.363c0 .502.403.909.9.909h12.6c.497 0 .9-.407.9-.91V12.91Zm1.8 6.363C21 20.779 19.791 22 18.3 22H5.7C4.209 22 3 20.779 3 19.273v-6.364c0-1.506 1.209-2.727 2.7-2.727h12.6c1.491 0 2.7 1.22 2.7 2.727v6.364Z"></path>
			<path d="M15.6 11.09V7.456c0-.965-.38-1.89-1.055-2.571A3.581 3.581 0 0 0 12 3.818a3.58 3.58 0 0 0-2.545 1.066A3.655 3.655 0 0 0 8.4 7.454v3.637a.905.905 0 0 1-.9.909.905.905 0 0 1-.9-.91V7.456c0-1.447.57-2.834 1.582-3.857A5.372 5.372 0 0 1 12 2c1.432 0 2.805.575 3.818 1.598A5.482 5.482 0 0 1 17.4 7.455v3.636a.905.905 0 0 1-.9.909.905.905 0 0 1-.9-.91Z"></path>
		</svg>
	';
}
?>

<?php if ( masteriyo_can_start_course( $course ) ) : ?>
	<?php if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) : ?>
	<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php echo $svg_lock; ?>
			<?php echo wp_kses_post( $course->single_course_completed_text() ); ?>
		</a>
	<?php elseif ( $progress && CourseProgressStatus::PROGRESS === $progress->get_status() ) : ?>
		<?php
		$quiz_attempt = masteriyo_is_course_quiz_started( $course->get_id() );
		$quiz_exists  = $quiz_attempt ? masteriyo_get_quiz( $quiz_attempt->get_quiz_id() ) : false;
		if ( $quiz_exists && $quiz_attempt && $course->get_disable_course_content() ) :
			?>
			<a href="<?php echo esc_url( masteriyo_get_course_item_learn_page_url( $course, masteriyo_get_quiz( $quiz_attempt->get_quiz_id() ) ) ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php echo $svg_lock; ?>
				<?php echo wp_kses_post( $course->single_course_continue_quiz_text() ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $course->continue_course_url( $progress ) ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php echo $svg_lock; ?>
				<?php echo wp_kses_post( $course->single_course_continue_text() ); ?>
			</a>
		<?php endif; ?>
	<?php else : ?>
		<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php echo $svg_lock; ?>
			<?php echo wp_kses_post( $course->single_course_start_text() ); ?>
		</a>
	<?php endif; ?>
<?php else : ?>
	<a href="<?php echo esc_url( $course->add_to_cart_url() ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
		<?php echo $svg_lock; ?>
		<?php echo wp_kses_post( $course->add_to_cart_text() ); ?>
	</a>
<?php endif; ?>
<?php
if ( 0 !== $course->get_enrollment_limit() && 0 >= $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) && empty( $progress ) ) {
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
