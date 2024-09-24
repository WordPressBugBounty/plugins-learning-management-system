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
 * @version 1.10.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering price and enroll button section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_single_course_price_and_enroll_button' );

?>
<div class="masteriyo-single-body__aside--price">
	<div class="masteriyo-single-body__aside--price-wrapper">
		<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
			<p class="masteriyo-single-body__aside--price-sale"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price() ) ); ?></p>
		<?php endif; ?>
		<p class="masteriyo-single-body__aside--price-offer">
			<?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?>
		</p>
	</div>

	<?php
	/**
	 * Fires an action hook after rendering the price section in the single course page.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	do_action( 'masteriyo_after_single_course_price', $course )
	?>

	<div class="masteriyo-single-body__aside--enroll">
		<?php
		/**
		 * Action hook for rendering enroll button template.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course Course object.
		 */

		do_action( 'masteriyo_single_course_layout_1_template_enroll_button', $course );
		?>
	</div>
	<?php
	/**
	 * Fires after rendering price and enroll button section in single course page.
	 *
	 * @since 1.10.0
	 */
	do_action( 'masteriyo_after_single_course_enroll_button_wrapper', $course );
	?>
</div>
<?php

/**
 * Fires after rendering price and enroll button section in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_single_course_price_and_enroll_button' );
