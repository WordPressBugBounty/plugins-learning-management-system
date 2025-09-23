<?php
/**
 * The Template for displaying price and enroll button in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/price-and-enroll-button.php.
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

use Masteriyo\Addons\CourseComingSoon\Helper;

/**
 * Fires before rendering price and enroll button section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_price_and_enroll_button' );

$satisfied = Helper::course_coming_soon_satisfied( $course );
$is_free   = ( method_exists( $course, 'get_price_type' ) && 'free' === $course->get_price_type() ) || ( floatval( $course->get_price() ) == 0 );

$layout = masteriyo_get_setting( 'single_course.display.template.layout' );
$class  = '';
if ( 'layout1' === $layout ) {
	if ( masteriyo_is_single_course_page() ) {
		$class .= 'masteriyo-single-course--card ';
	}
	if ( $progress && method_exists( $progress, 'get_status' ) && $progress->get_status() === 'completed' ) {
		return false;
	}
}
$class = trim( $class );
?>

<?php if ( ( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && masteriyo_get_setting( 'course_archive.components_visibility.card_footer' ) ) || ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) ) : ?>
	<div class="masteriyo-time-btn masteriyo-course-pricing--wrapper <?php echo esc_attr( $class ); ?>">
		<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.price' ) || ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) ) : ?>
			<?php if ( ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) || ! masteriyo_is_course_order( $course->get_id() ) ) : ?>
				<?php if ( ! $satisfied && $is_free ) : ?>
				<?php else : ?>
					<div class="masteriyo-course-price">
						<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
							<del class="old-amount">
								<?php
								echo wp_kses_post(
									masteriyo_price(
										$course->get_regular_price(),
										array(
											'currency' => $course->get_currency(),
										)
									)
								);
								?>
							</del>
						<?php endif; ?>
						<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>

		<?php
		/**
		 * Action hook for rendering retake button template.
		 *
		 * @since 1.8.0
		 *
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';
		if ( 'default' === $layout || 'minimal' === $layout ) {
			do_action( 'masteriyo_template_course_retake_button', $course );
		}
		?>

		<?php
		/**
		 * Action hook for rendering enroll button template.
		 *
		 * @since 1.0.0
		 *
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
		if ( masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ) || ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) ) {
			$user_id = get_current_user_id();
			if ( masteriyo_is_user_enrolled_in_course( $course->get_id(), $user_id ) && 'layout1' === $layout && masteriyo_is_single_course_page() ) {
			} else {
				do_action( 'masteriyo_template_enroll_button', $course );
			}
		}
		?>

			<?php masteriyo_display_all_notices(); ?>
	</div>
<?php endif; ?>

<?php
/**
 * Fires after rendering price and enroll button section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_price_and_enroll_button' );
