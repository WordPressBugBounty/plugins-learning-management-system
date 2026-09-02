<?php
/**
 * "Add to Cart" button.
 *
 * @version 1.0.0
 */

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Notice;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * @var \Masteriyo\Models\Course $course
 * @var string                   $class
 */

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
 * @since 2.13.0
 */
$additional_attributes        = apply_filters( 'masteriyo_add_to_cart_button_attributes', array(), $course );
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
 * @param string                   $target The target attribute value. Default '_blank'.
 * @param \Masteriyo\Models\Course $course Course object.
 */
$button_target = apply_filters( 'masteriyo_course_button_target', '_blank', $course );

if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
	return false;
}

$notice    = '';
$lock_icon = '
	<svg xmlns="http://www.w3.org/2000/svg" fill="#424360" viewBox="0 0 24 24" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;">
		<path d="M19.2 12.91a.905.905 0 0 0-.9-.91H5.7c-.497 0-.9.407-.9.91v6.363c0 .502.403.909.9.909h12.6c.497 0 .9-.407.9-.91V12.91Zm1.8 6.363C21 20.779 19.791 22 18.3 22H5.7C4.209 22 3 20.779 3 19.273v-6.364c0-1.506 1.209-2.727 2.7-2.727h12.6c1.491 0 2.7 1.22 2.7 2.727v6.364Z"></path>
		<path d="M15.6 11.09V7.456c0-.965-.38-1.89-1.055-2.571A3.581 3.581 0 0 0 12 3.818a3.58 3.58 0 0 0-2.545 1.066A3.655 3.655 0 0 0 8.4 7.454v3.637a.905.905 0 0 1-.9.909.905.905 0 0 1-.9-.91V7.456c0-1.447.57-2.834 1.582-3.857A5.372 5.372 0 0 1 12 2c1.432 0 2.805.575 3.818 1.598A5.482 5.482 0 0 1 17.4 7.455v3.636a.905.905 0 0 1-.9.909.905.905 0 0 1-.9-.91Z"></path>
	</svg>
';

$show_lock = false;
if ( post_password_required( get_post( $course->get_id() ) ) ) {
	$show_lock = true;
}

$enrollment_opens_on  = $course->get_enrollment_opens_on();
$enrollment_closes_on = $course->get_enrollment_closes_on();

$open_ts  = $enrollment_opens_on ? $enrollment_opens_on->getTimestamp() : null;
$close_ts = $enrollment_closes_on ? $enrollment_closes_on->getTimestamp() : null;
$now_ts   = current_time( 'timestamp' );

$enrollment_started = $open_ts ? ( $now_ts >= $open_ts ) : true;
$enrollment_closed  = $close_ts ? ( $now_ts > $close_ts ) : false;
$enrollment_open    = $enrollment_started && ! $enrollment_closed;

$cohort_enabled = method_exists( $course, 'get_enable_cohort_mode' ) && $course->get_enable_cohort_mode();

if ( $cohort_enabled && ! $enrollment_started ) {
	$show_lock = true;
}

?>

<?php if ( masteriyo_can_start_course( $course ) ) : ?>
	<?php if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) : ?>
		<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php
			if ( $show_lock ) {
				echo $lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG literal defined in this file.
			}
			?>
			<?php echo wp_kses_post( $course->single_course_completed_text() ); ?>
		</a>
	<?php elseif ( $progress && CourseProgressStatus::PROGRESS === $progress->get_status() ) : ?>
		<?php
		$quiz_attempt = masteriyo_is_course_quiz_started( $course->get_id() );
		$quiz_exists  = $quiz_attempt ? masteriyo_get_quiz( $quiz_attempt->get_quiz_id() ) : false;
		if ( $quiz_exists && $quiz_attempt && $course->get_disable_course_content() ) :
			?>
			<a href="<?php echo esc_url( masteriyo_get_course_item_learn_page_url( $course, $quiz_exists ) ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php
				if ( $show_lock ) {
					echo $lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG literal defined in this file.
				}
				?>
				<?php echo wp_kses_post( $course->single_course_continue_quiz_text() ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $course->continue_course_url( $progress ) ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
				<?php
				if ( $show_lock ) {
					echo $lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG literal defined in this file.
				}
				?>
				<?php echo wp_kses_post( $course->single_course_continue_text() ); ?>
			</a>
		<?php endif; ?>
	<?php else : ?>
		<a href="<?php echo esc_url( $course->start_course_url() ); ?>" target="<?php echo esc_attr( $button_target ); ?>" class="<?php echo esc_attr( $class ); ?>" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>" <?php echo esc_attr( $additional_attributes_string ); ?> >
			<?php
			if ( $show_lock ) {
				echo $lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG literal defined in this file.
			}
			?>
			<?php echo wp_kses_post( $course->single_course_start_text() ); ?>
		</a>
	<?php endif; ?>
<?php elseif ( masteriyo_course_order_awaiting_payment( $course->get_id() ) ) : ?>
	<span class="<?php echo esc_attr( $class ); ?> masteriyo-btn--payment-pending" data-course-id="<?php echo esc_attr( $course->get_id() ); ?>">
		<?php esc_html_e( 'Payment pending', 'learning-management-system' ); ?>
	</span>
	<?php
	masteriyo_display_notice(
		esc_html__( 'You have already ordered this course. It will be available once the payment is confirmed.', 'learning-management-system' ),
		Notice::INFO
	);
	?>
<?php else : ?>
	<?php
	$button_url  = $course->add_to_cart_url();
	$button_text = $course->add_to_cart_text();

	if ( function_exists( 'masteriyo_apply_cohort_button_rules' ) ) {
		masteriyo_apply_cohort_button_rules(
			$course,
			$cohort_enabled,
			$enrollment_open,
			$now_ts,
			$close_ts,
			$show_lock,
			$button_url,
			$button_text,
			$notice
		);
	}
	?>
	<a href="<?php echo esc_url( $button_url ); ?>"
		target="<?php echo esc_attr( $button_target ); ?>"
		class="<?php echo esc_attr( $class ); ?>"
		data-course-id="<?php echo esc_attr( $course->get_id() ); ?>"
		<?php echo $additional_attributes_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each key and value is esc_attr()-ed where the string is built. ?>
	>
		<?php
		if ( $show_lock ) {
			echo $lock_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG literal defined in this file.
		}
		?>
		<?php echo wp_kses_post( $button_text ); ?>
	</a>
<?php endif; ?>


<?php
if ( ! empty( $notice ) ) {
	masteriyo_display_notice(
		esc_html( $notice ),
		Notice::WARNING
	);
}

if ( 0 !== $course->get_enrollment_limit() && 0 >= $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) && empty( $progress ) ) {
	masteriyo_display_notice(
		esc_html__( 'Sorry, students limit reached. Course closed for enrollment.', 'learning-management-system' ),
		Notice::WARNING
	);
}

if ( masteriyo_is_single_course_page() && ! masteriyo_course_has_content( $course ) ) {
	masteriyo_display_notice(
		esc_html__( 'No lessons have been added yet. Check back soon.', 'learning-management-system' ),
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
