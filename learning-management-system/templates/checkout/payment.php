<?php
/**
 * Masteriyo checkout form payment.
 *
 * @package Masteriyo\Templates;
 * @since 1.0.0
 * @version 1.5.12
 */

defined( 'ABSPATH' ) || exit;

$available_gateways = isset( $available_gateways ) ? $available_gateways : array();
$order_button_text  = isset( $order_button_text ) ? $order_button_text : '';

// A lone gateway is not a choice, so the list is rendered without selection UI —
// see `.masteriyo-payment-methods--single`, which hides the radio the posted
// contract and every gateway's own script still need.
$is_single_gateway = is_array( $available_gateways ) && 1 === count( $available_gateways );

/**
 * Fires before rendering payment methods in checkout page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_checkout_before_payment_methods' );
?>

<?php if ( masteriyo( 'cart' )->needs_payment() ) : ?>
	<div id="masteriyo-payments" class="masteriyo-checkout-payment">
		<h3 class="masteriyo-checkout-section--title">
			<?php esc_html_e( 'Payment', 'learning-management-system' ); ?>
		</h3>

		<ul class="masteriyo-payment-methods payment-methods methods masteriyo-checkout-payment-method <?php echo $is_single_gateway ? 'masteriyo-payment-methods--single' : ''; ?>">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					masteriyo_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				$message = esc_html__( 'Please enable one or more payment gateways.', 'learning-management-system' );

				if ( ! masteriyo_is_guest_checkout_enabled() && masteriyo_get_current_user()->get_billing_country() ) {
					$message = esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'learning-management-system' );
				}

				echo wp_kses_post(
					sprintf(
						'<li class="masteriyo-notice masteriyo-alert masteriyo-info-msg">%s</li>',
						$message
					)
				);
			}
			?>
		</ul>
	</div>
<?php endif; ?>

<?php
/**
 * Fires after rendering payment methods in checkout page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_checkout_after_payment_methods' );
?>

<?php
/**
 * Fires before rendering submit button in checkout page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_checkout_summary_before_submit' );
?>

<button
	type="submit"
	class="masteriyo-checkout--btn masteriyo-btn masteriyo-btn-primary alt"
	id="masteriyo-place-order"
	name="masteriyo_checkout_place_order"
	data-value="<?php echo esc_attr( $order_button_text ); ?>">
	<span class="masteriyo-place-order-action"><?php echo esc_html( $order_button_text ); ?></span>
	<?php masteriyo_template_checkout_order_total(); ?>
</button>

<?php
wp_nonce_field( 'masteriyo-process_checkout', 'masteriyo-process-checkout-nonce' );

/**
 * Fires after rendering submit button in checkout page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_checkout_summary_after_submit' );
