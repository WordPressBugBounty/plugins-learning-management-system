<?php

/**
 * The Template for displaying group buy button.
 *
 * @version 1.9.0
 */

/**
 * Filter the title text for group buy button.
 *
 * @since 1.9.0
 *
 * @param string $title Default title text.
 */
$group_title = apply_filters( 'masteriyo_group_buy_btn_title', __( 'For Business', 'learning-management-system' ) );

$button_text = __( 'Buy for Group at ', 'learning-management-system' );
$group_price = $group_price;

/**
 * Filter the price text in group buy button.
 *
 * @since 1.9.0
 *
 * @param string $price The group price text.
 */
$button_text .= apply_filters( 'masteriyo_group_buy_btn_price_text', $group_price );
?>
<div class="masteriyo-group-course__group-button" id="masteriyoGroupCoursesEnrollBtn">
	<h3 class="masteriyo-group-course__group-title">
		<?php echo esc_html( $group_title ); ?>
	</h3>

	<p class="masteriyo-group-course__group-desc">
		<?php
		/**
		 * Action hook for adding custom description for group course modal.
		 *
		 * @since 1.9.0
		 */
		do_action( 'masteriyo_group_course_modal_description' );
		?>
	</p>

	<a href="javascript:;" class="masteriyo-group-course__buy-now-button">
		<?php
		echo wp_kses_post( $button_text );
		?>
	</a>
</div>
<?php
