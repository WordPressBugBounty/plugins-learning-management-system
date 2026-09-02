<?php
/**
 * Masteriyo checkout payment method card.
 *
 * One card per gateway. The header is the selection control — radio, title and,
 * when the gateway has one, its icon — and stays a single row whatever the icon
 * turns out to be. The label wraps the radio and carries the header's padding,
 * so the whole top surface of the card is the click target rather than the
 * inner row alone; it keeps its `for` as well, which costs nothing and leaves
 * every selector written against the pair working.
 *
 * The body carries everything else the gateway emits and is visible only while
 * the card is selected, which is what the checkout script slides open: it is
 * the `payment-box` the gateway contract names, so `payment_fields()` still
 * renders exactly where every gateway expects.
 *
 * @package Masteriyo\Templates
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$gateway_name = $gateway->get_name();
$gateway_icon = masteriyo_split_payment_gateway_icon( $gateway );

// The body exists when the gateway has something to put in it. `payment_fields()`
// prints the description on its own, so a gateway with only a description still
// gets one — as does a gateway whose icon carried more than images.
$has_payment_body = $gateway->has_fields() || $gateway->get_description() || '' !== $gateway_icon['extra'];
?>

<li class="payment-method payment-method-<?php echo esc_attr( $gateway_name ); ?> masteriyo-payment-method">
	<div class="masteriyo-payment-method__header">
		<label for="payment-method-<?php echo esc_attr( $gateway_name ); ?>" class="masteriyo-payment-method__label">
			<input
				id="payment-method-<?php echo esc_attr( $gateway_name ); ?>"
				type="radio"
				class="input-radio masteriyo-payment-method__radio"
				name="payment_method"
				value="<?php echo esc_attr( $gateway_name ); ?>"
				<?php checked( $gateway->is_chosen(), true ); ?>
				data-order_button_text="<?php echo esc_attr( $gateway->get_order_button_text() ); ?>" />

			<span class="masteriyo-payment-method__title"><?php echo esc_html( $gateway->get_title() ); ?></span>

			<?php if ( '' !== $gateway_icon['marks'] ) : ?>
				<span class="masteriyo-payment-method__icon"><?php echo wp_kses_post( $gateway_icon['marks'] ); ?></span>
			<?php endif; ?>
		</label>
	</div>

	<?php if ( $has_payment_body ) : ?>
		<div class="payment-box payment-method-<?php echo esc_attr( $gateway_name ); ?> masteriyo-payment-method__body">
			<?php
			$gateway->payment_fields();

			echo wp_kses_post( $gateway_icon['extra'] );
			?>
		</div>
	<?php endif; ?>
</li>
<?php
