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

/**
 * Fires before rendering price and enroll button section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_price_and_enroll_button' );

?>
<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && masteriyo_get_setting( 'course_archive.components_visibility.card_footer' ) || ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) ) : ?>
<div class="masteriyo-time-btn">
	<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.price' ) || ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) ) : ?>
	<div class="masteriyo-course-price">
		<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
			<del class="old-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price(), array( 'currency' => $course->get_currency() ) ) ); ?></del>
		<?php endif; ?>
		<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
	</div>
	<?php endif; ?>
	<?php
	/**
	 * Action hook for rendering retake button template.
	 *
	 * @since 1.8.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	do_action( 'masteriyo_template_course_retake_button', $course );

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
		do_action( 'masteriyo_template_enroll_button', $course );
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
