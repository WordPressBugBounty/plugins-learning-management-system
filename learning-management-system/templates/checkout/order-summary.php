<?php

/**
 * Order summary.
 *
 * @package Masteriyo\Templates
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="masteriyo-checkout-summary-your-order">
	<h2 class="masteriyo-checkout-summary--title">
		<?php esc_html_e( 'Your Order', 'learning-management-system' ); ?>
	</h2>

	<ul class="masteriyo-checkout-summary-order-details">
		<li class="h-border">
			<strong><?php echo esc_html( apply_filters( 'masteriyo_checkout_summary_your_order_item_title', __( 'Courses', 'learning-management-system' ), $item_id, $items, $cart ) ); ?></strong>
			<strong><?php esc_html_e( 'Subtotal', 'learning-management-system' ); ?></strong>
		</li>

		<?php foreach ( $items as $item ) : ?>
			<?php
			/**
			 * Fires an action to allow developers to add additional order details to the checkout summary.
			 *
			 * @param Masteriyo\Models\Course $course The course object.
			 *
			 * @since 2.11.0
			 */
			do_action( 'masteriyo_checkout_summary_order_details', $item );
			$currency = '';

			if ( is_a( $item, 'Masteriyo\Models\Course' ) ) {
				$currency = $item->get_currency();
			}
			?>
			<li>
				<span>
					<?php
					echo wp_kses(
						$item->get_name(),
						$allowed_html = array(
							'span' => array(
								'class' => array(
									'masteriyo-badge',
								),
								'style' => array(
									'background-color',
								),
							),
						)
					);
					?>
				</span>
				<span><?php echo wp_kses_post( masteriyo_price( $item->get_price(), array( 'currency' => $currency ) ) ); ?></span>
			</li>
		<?php endforeach; ?>

		<?php
		/**
		 * Fires before rendering subtotal row in checkout form.
		 *
		 * @since 2.5.12
		 */
		do_action( 'masteriyo_checkout_summary_your_order_before_subtotal_row' );

		?>

		<li class="masteriyo-subtotal-row">
			<strong><?php esc_html_e( 'Subtotal', 'learning-management-system' ); ?></strong>
			<span><?php echo wp_kses_post( masteriyo_price( $cart->get_subtotal(), array( 'currency' => $currency ) ) ); ?></span>
		</li>

		<?php
		/**
		 * Fires before rendering total row in checkout form.
		 *
		 * @since 2.5.12
		 */
		do_action( 'masteriyo_checkout_summary_your_order_before_total_row' );
		?>

		<?php if ( 'inclusive' !== masteriyo_get_setting( 'payments.taxes.calculation_method' ) ) : ?>
		<li class="masteriyo-order-overview__total total">
				<strong title="<?php esc_attr_e( 'Tax is calculated based on your selected country and state during checkout.', 'learning-management-system' ); ?>">
					<?php esc_html_e( 'Tax', 'learning-management-system' ); ?>
				</strong>
			<span>
				<?php
				$tax_total = $cart->get_tax_total();

				if ( $tax_total > 0 ) {
					echo '+ ';
				}

				echo wp_kses_post(
					masteriyo_price(
						$tax_total,
						array(
							'currency'             => $currency,
							'show_price_free_text' => false,
						)
					)
				);
				?>
			</span>
		</li>
		<?php endif; ?>

		<li class="masteriyo-total-row">
			<strong><?php esc_html_e( 'Total', 'learning-management-system' ); ?></strong>
			<strong>
			<?php
			echo wp_kses_post(
				masteriyo_price(
					$cart->get_total(),
					array(
						'currency'      => $currency,
						'tax_inclusive' => masteriyo_string_to_bool( masteriyo_get_setting( 'payments.taxes.display_inclusive' ) ) || 'inclusive' !== masteriyo_get_setting( 'payments.taxes.calculation_method' ),
					)
				)
			);
			?>
			</strong>
		</li>

		<?php
		/**
		 * Fires after rendering total row in checkout form.
		 *
		 * @since 2.5.12
		 */
		do_action( 'masteriyo_checkout_summary_your_order_after_total_row' );
		?>
	</ul>
</div>
<?php
