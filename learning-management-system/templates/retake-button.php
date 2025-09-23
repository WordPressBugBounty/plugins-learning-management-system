<?php
/**
 * "Retake Course" button.
 *
 * @version 1.8.0
*/

use Masteriyo\Enums\CourseProgressStatus;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! $course->is_purchasable() ) {
	return;
}

/**
 * Fires before rendering retake button.
 *
 * @since 1.8.0
 *
 * @param \Masteriyo\Models\Course $course Course object.
 */
do_action( 'masteriyo_before_retake_button', $course );

?>

<?php if ( masteriyo_can_start_course( $course ) ) : ?>
	<?php if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() && $course->get_enable_course_retake() ) : ?>
		<div class="masteriyo-retake-button-container" id="masteriyoRetakeButton">
			<a href="<?php echo esc_url( $course->get_retake_url() ); ?>" target="_blank" class="<?php echo esc_attr( $class ); ?>">

				<svg xmlns="http://www.w3.org/2000/svg" fill="#4584FF" viewBox="0 0 24 24">
					<path d="M2 12A10 10 0 0 1 12 2h.004l.519.015a10.75 10.75 0 0 1 6.53 2.655l.394.363 2.26 2.26a1 1 0 1 1-1.414 1.414l-2.248-2.248-.31-.286A8.75 8.75 0 0 0 11.996 4 8 8 0 0 0 4 12a1 1 0 1 1-2 0Z"/>
					<path d="M20 3a1 1 0 1 1 2 0v5a1 1 0 0 1-1 1h-5a1 1 0 1 1 0-2h4V3Zm0 9a1 1 0 1 1 2 0 10 10 0 0 1-10 10h-.004a10.75 10.75 0 0 1-7.05-2.67l-.393-.363-2.26-2.26a1 1 0 1 1 1.414-1.414l2.248 2.248.31.286A8.749 8.749 0 0 0 12.003 20 7.999 7.999 0 0 0 20 12Z"/>
					<path d="M2 21v-5a1 1 0 0 1 1-1h5a1 1 0 1 1 0 2H4v4a1 1 0 1 1-2 0Z"/>
				</svg>

				<?php
				$heading = __( 'Retake Course', 'learning-management-system' );

				/**
				 * Filter the display text in certificate share button in single course page.
				 *
				 * @since 2.14.4 [free]
				 *
				 * @param string $heading The default display text.
				 */
				echo esc_html( apply_filters( 'masteriyo_retake_button_text', $heading ) );
				?>
			</a>
		</div>
	<?php endif; ?>
<?php endif; ?>
<?php

/**
 * Fires after rendering retake button.
 *
 * @since 1.8.0
 *
 * @param \Masteriyo\Models\Course $course Course object.
 */
do_action( 'masteriyo_after_retake_button', $course );
