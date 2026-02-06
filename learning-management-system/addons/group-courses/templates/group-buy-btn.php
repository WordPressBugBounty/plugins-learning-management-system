<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for displaying group buy button.
 *
 * @version 1.9.0
 */

use Masteriyo\Addons\GroupCourses\Models\Setting;

// Determine if we're using new multi-tier pricing or legacy single-tier
$has_pricing_tiers  = ! empty( $pricing_tiers ) && is_array( $pricing_tiers );
$use_legacy_display = ! $has_pricing_tiers && ! empty( $group_price );
$is_coming_soon     = isset( $is_coming_soon ) ? $is_coming_soon : false;

// Calculate available seats (used for both early return check and tier filtering)
$available_seats = 0;
if ( 0 !== $course->get_enrollment_limit() ) {
	$available_seats = $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() );
}

// Check if seats are available.
if ( 0 !== $course->get_enrollment_limit() && 0 >= $available_seats ) {
	return;
}

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

	<span class="masteriyo-group-course__seperator"><?php esc_html_e( 'OR', 'learning-management-system' ); ?></span>

	<?php if ( $has_pricing_tiers ) : ?>
		<!-- Multi-Tier Pricing Display -->
		<div class="masteriyo-group-pricing-tiers">
			<?php
			foreach ( $pricing_tiers as $index => $tier ) :
				$tier_id       = isset( $tier['id'] ) ? $tier['id'] : 'tier_' . ( $index + 1 );
				$seat_model    = isset( $tier['seat_model'] ) ? $tier['seat_model'] : 'fixed';
				$group_name    = isset( $tier['group_name'] ) ? $tier['group_name'] : '';
				$pricing_type  = isset( $tier['pricing_type'] ) ? $tier['pricing_type'] : 'one_time';
				$regular_price = isset( $tier['regular_price'] ) ? floatval( $tier['regular_price'] ) : 0;
				$sale_price    = isset( $tier['sale_price'] ) && ! empty( $tier['sale_price'] ) ? floatval( $tier['sale_price'] ) : 0;
				$display_price = $sale_price > 0 ? $sale_price : $regular_price;

				// Seat information (fixed seats only)
				$group_size = isset( $tier['group_size'] ) ? intval( $tier['group_size'] ) : 0;

				// Skip this tier if it requires more seats than available
				if ( 0 !== $course->get_enrollment_limit() && $group_size > $available_seats ) {
					continue;
				}

				// Build pricing interval text
				$interval_text = '';
				if ( 'recurring' === $pricing_type ) {
					$interval_text = '/month'; // TODO: Support other intervals
				}

				// Build seat info text
				/* translators: %d: Number of seats */
				$seats_info = sprintf( _n( '%d seat included', '%d seats', $group_size, 'learning-management-system' ), $group_size );

				// Build description (fixed seats only)
				if ( 'recurring' === $pricing_type ) {
					$description = __( 'Monthly subscription for teams', 'learning-management-system' );
				} else {
					$description = __( 'One-time payment for teams', 'learning-management-system' );
				}

				// Pre-select first tier only if not coming soon
				$is_first_tier = ( 0 === $index );
				$tier_classes  = 'masteriyo-group-pricing-tier';
				if ( $is_first_tier && ! $is_coming_soon ) {
					$tier_classes .= ' selected';
				}
				if ( $is_coming_soon ) {
					$tier_classes .= ' masteriyo-group-pricing-tier-disabled';
				}
				?>

				<div class="<?php echo esc_attr( $tier_classes ); ?>"
					data-tier-id="<?php echo esc_attr( $tier_id ); ?>"
					data-seat-model="fixed"
					data-pricing-type="<?php echo esc_attr( $pricing_type ); ?>"
					data-regular-price="<?php echo esc_attr( $regular_price ); ?>"
					data-sale-price="<?php echo esc_attr( $sale_price ); ?>"
					data-group-size="<?php echo esc_attr( $group_size ); ?>"
					<?php echo $is_coming_soon ? 'style="pointer-events: none; opacity: 0.7;"' : ''; ?>
				>
					<div class="masteriyo-group-tier-radio"></div>

					<div class="masteriyo-group-tier-header">
						<div class="masteriyo-group-tier-info">
							<?php if ( ! empty( $group_name ) ) : ?>
								<div class="masteriyo-group-tier-name"><?php echo esc_html( $group_name ); ?></div>
							<?php endif; ?>

							<div class="masteriyo-group-tier-seats-info">
								<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
									<path d="M14.727 20.1v-1.8c0-.716-.287-1.403-.799-1.91a2.739 2.739 0 0 0-1.658-.777L12 15.6H6.545c-.723 0-1.416.285-1.928.79a2.686 2.686 0 0 0-.799 1.91v1.8c0 .497-.407.9-.909.9A.905.905 0 0 1 2 20.1v-1.8a4.48 4.48 0 0 1 1.332-3.182A4.568 4.568 0 0 1 6.545 13.8H12c1.206 0 2.361.474 3.214 1.318a4.477 4.477 0 0 1 1.332 3.182v1.8c0 .497-.408.9-.91.9a.905.905 0 0 1-.909-.9Zm2.724-12.6c0-.598-.2-1.18-.57-1.652a2.73 2.73 0 0 0-1.473-.962.899.899 0 0 1-.651-1.097.91.91 0 0 1 1.107-.645c.975.25 1.838.814 2.454 1.602A4.47 4.47 0 0 1 19.27 7.5a4.47 4.47 0 0 1-.95 2.754 4.55 4.55 0 0 1-2.454 1.602.91.91 0 0 1-1.108-.645.899.899 0 0 1 .651-1.097 2.73 2.73 0 0 0 1.473-.962 2.68 2.68 0 0 0 .57-1.652Zm2.731 12.6v-1.8l-.01-.222a2.684 2.684 0 0 0-.562-1.43 2.73 2.73 0 0 0-1.474-.96.899.899 0 0 1-.652-1.096.91.91 0 0 1 1.107-.646 4.55 4.55 0 0 1 2.456 1.6c.617.788.952 1.756.953 2.753V20.1c0 .497-.407.9-.91.9a.905.905 0 0 1-.908-.9ZM12 7.5c0-1.491-1.221-2.7-2.727-2.7-1.506 0-2.728 1.209-2.728 2.7s1.221 2.7 2.728 2.7C10.779 10.2 12 8.991 12 7.5Zm1.818 0c0 2.485-2.035 4.5-4.545 4.5S4.727 9.985 4.727 7.5 6.762 3 9.273 3c2.51 0 4.545 2.015 4.545 4.5Z"/>
								</svg>
								<span><?php echo esc_html( $seats_info ); ?>
							</div>
						</div>
						<div class="masteriyo-group-tier-price">
							<?php if ( $sale_price > 0 ) : ?>
								<span class="masteriyo-group-tier-price-regular"><?php echo masteriyo_price( $regular_price, array( 'currency' => $currency ) ); ?></span> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
							<span class="masteriyo-group-tier-price-current">
								<?php echo masteriyo_price( $display_price, array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php if ( 'recurring' === $pricing_type ) : ?>
									<span class="masteriyo-group-tier-price-interval"><?php echo esc_html( $interval_text ); ?></span>
								<?php endif; ?>
							</span>
						</div>
					</div>
			</div>
		<?php endforeach; ?>

		<?php
		// Get custom button text from settings (with migration for obsolete placeholder)
		$multi_tier_button_text = Setting::get( 'group_buy_button_text' ) ? Setting::get( 'group_buy_button_text' ) : __( 'Buy for Group', 'learning-management-system' );

		// Migration: Remove obsolete {group_price} placeholder if it exists in saved settings
		if ( strpos( $multi_tier_button_text, '{group_price}' ) !== false ) {
			$multi_tier_button_text = __( 'Buy for Group', 'learning-management-system' );
		}
		?>

		<?php
		$multi_tier_btn_classes = 'masteriyo-btn masteriyo-btn-primary';
		if ( $is_coming_soon ) {
			$multi_tier_btn_classes .= ' masteriyo-btn-disabled masteriyo-single-course--course-coming-soon-btn';
			$multi_tier_button_text  = __( 'Coming Soon', 'learning-management-system' );
		} else {
			$multi_tier_btn_classes .= ' masteriyo-group-tier-buy-button';
		}
		?>
		<button type="button" class="<?php echo esc_attr( $multi_tier_btn_classes ); ?>">
			<?php echo esc_html( $multi_tier_button_text ); ?>
		</button>
	</div>

	<?php elseif ( $use_legacy_display ) : ?>
		<!-- Legacy Single-Tier Display (Backward Compatibility) -->
		<?php
		$button_text_template = Setting::get( 'group_buy_button_text' ) ? Setting::get( 'group_buy_button_text' ) : __( 'Buy for Group', 'learning-management-system' );
		$helper_text_template = Setting::get( 'group_buy_helper_text' ) ? Setting::get( 'group_buy_helper_text' ) : __( 'Perfect for teams up to {group_size} members', 'learning-management-system' );

		// Migration: Remove obsolete {group_price} placeholder if it exists in saved settings
		// This handles cases where users have old saved values like "Buy for a Group at {group_price}"
		if ( strpos( $button_text_template, '{group_price}' ) !== false ) {
			// Replace the old template with the new default
			$button_text_template = __( 'Buy for Group', 'learning-management-system' );
		} else {
			// Keep existing custom text if it doesn't use the obsolete placeholder
			$button_text = str_replace( '{group_price}', $group_price, $button_text_template );
		}

		$button_text = $button_text_template;
		$helper_text = '';
		if ( ! empty( $helper_text_template ) ) {
			$group_size_display = ( empty( $max_group_size ) || 0 === $max_group_size ) ? __( 'unlimited', 'learning-management-system' ) : $max_group_size;
			$helper_text        = str_replace( '{group_size}', $group_size_display, $helper_text_template );
		}
		$button_text  = apply_filters( 'masteriyo_group_buy_btn_text', $button_text );
		$checkout_url = masteriyo_get_page_permalink( 'checkout' );
		$checkout_url = add_query_arg(
			array(
				'add-to-cart'    => $course->get_id(),
				'group_purchase' => 'yes',
			),
			$checkout_url
		);
		?>
		<?php
		$legacy_btn_classes = 'masteriyo-btn masteriyo-btn-secondary';
		if ( $is_coming_soon ) {
			$legacy_btn_classes .= ' masteriyo-btn-disabled masteriyo-single-course--course-coming-soon-btn';
			$button_text         = __( 'Coming Soon', 'learning-management-system' );
			$checkout_url        = '#';
		} else {
			$legacy_btn_classes .= ' masteriyo-group-course__buy-now-button';
		}
		?>
		<a href="<?php echo esc_url( $checkout_url ); ?>" class="<?php echo esc_attr( $legacy_btn_classes ); ?>" <?php echo $is_coming_soon ? 'style="pointer-events: none;"' : ''; ?>>
			<?php echo wp_kses_post( $button_text ); ?>
		</a>
		<?php if ( ! empty( $helper_text ) ) : ?>
			<p class="masteriyo-group-course__helper-text">
				<?php echo esc_html( $helper_text ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	/**
	 * Action hook after group buy button.
	 *
	 * @since 1.20.0
	 */
	do_action( 'masteriyo_after_group_buy_button', $course );
	?>
</div>
<?php
