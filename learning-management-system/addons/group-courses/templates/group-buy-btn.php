<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for displaying group buy button.
 *
 * @version 1.9.0
 */

use Masteriyo\Addons\GroupCourses\Models\Setting;

// Get group courses settings
$button_text_template = Setting::get( 'group_buy_button_text' ) ? Setting::get( 'group_buy_button_text' ) : __( 'Buy for Group at {group_price}', 'learning-management-system' );
$helper_text_template = Setting::get( 'group_buy_helper_text' ) ? Setting::get( 'group_buy_helper_text' ) : __( 'Perfect for teams up to {group_size} members', 'learning-management-system' );

// Replace {group_price} placeholder with actual group price
$button_text = str_replace( '{group_price}', $group_price, $button_text_template );

// Replace {group_size} placeholder with actual max group size if helper text is set
$helper_text = '';
if ( ! empty( $helper_text_template ) ) {
	// If group size is 0, show "unlimited" instead
	$group_size_display = ( empty( $max_group_size ) || 0 === $max_group_size ) ? __( 'unlimited', 'learning-management-system' ) : $max_group_size;
	$helper_text        = str_replace( '{group_size}', $group_size_display, $helper_text_template );
}

/**
 * Filter the button text for group buy button.
 *
 * @since 1.9.0
 *
 * @param string $button_text The button text with price.
 */
$button_text = apply_filters( 'masteriyo_group_buy_btn_text', $button_text );
?>
<div class="masteriyo-group-course__group-button" id="masteriyoGroupCoursesEnrollBtn">
	<?php
	/**
	 * Action hook for adding custom description for group course modal.
	 *
	 * @since 1.9.0
	 * @deprecated 1.20.0 Use 'masteriyo_before_group_buy_button' or 'masteriyo_after_group_buy_button' instead.
	 */
	ob_start();
	do_action( 'masteriyo_group_course_modal_description' );
	$hook_content = ob_get_clean();

	if ( ! empty( trim( $hook_content ) ) ) :
		?>
		<p class="masteriyo-group-course__group-desc">
			<?php echo esc_html( $hook_content ); ?>
		</p>
	<?php endif; ?>

	<?php
	/**
	 * Action hook before group buy button.
	 *
	 * @since 1.20.0
	 */
	do_action( 'masteriyo_before_group_buy_button', $course );
	?>

	<?php
	// Create checkout URL with group pricing
	$checkout_url = masteriyo_get_page_permalink( 'checkout' );
	$checkout_url = add_query_arg(
		array(
			'add-to-cart'    => $course->get_id(),
			'group_purchase' => 'yes',
		),
		$checkout_url
	);
	?>
	<span class="masteriyo-group-course__seperator">OR</span>
	<a href="<?php echo esc_url( $checkout_url ); ?>" class="masteriyo-btn masteriyo-btn-secondary masteriyo-group-course__buy-now-button">
		<?php echo wp_kses_post( $button_text ); ?>
	</a>

	<?php
	/**
	 * Action hook after group buy button.
	 *
	 * @since 1.20.0
	 */
	do_action( 'masteriyo_after_group_buy_button', $course );
	?>

	<?php if ( ! empty( $helper_text ) ) : ?>
		<p class="masteriyo-group-course__helper-text">
			<?php echo esc_html( $helper_text ); ?>
		</p>
	<?php endif; ?>
</div>
<?php
