<?php
/**
 * Masteriyo form checkout.
 *
 * @package Masteriyo\Templates
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering checkout form.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_checkout_form' );

if ( ! is_user_logged_in() && ! masteriyo_is_guest_checkout_enabled() ) {
	$message = sprintf(
		/**
		 * Filters message to show for requiring user to login for using checkout page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message The message.
		 */
		apply_filters(
			'masteriyo_checkout_must_be_logged_in_message',
			// translators: %s: account page link
			__( 'You must be %1$slogged in%2$s to checkout.', 'learning-management-system' )
		),
		'<a class="masteriyo-checkout-login-link" href="' . esc_url( masteriyo_get_account_url() ) . '">',
		'</a>'
	);

	echo wp_kses(
		$message,
		array(
			'a' => array(
				'href'  => array(),
				'title' => array(),
				'class' => 'masteriyo-checkout-login-link',
			),
		)
	);

	return;
}

?>
<div class="masteriyo-checkout masteriyo-checkout-container" id="masteriyo-checkout">
	<form
		name="checkout"
		method="post"
		class="masteriyo-checkout masteriyo-checkout--form"
		action="<?php echo esc_url( masteriyo_get_checkout_url() ); ?>"
		enctype="multipart/form-data"
	>
		<div class="masteriyo-checkout-wrapper">
			<div class="masteriyo-checkout-main">
				<?php
				/**
				 * Fires inside form element of checkout form.
				 *
				 * @since 1.0.0
				 */
				do_action( 'masteriyo_checkout_form' );

				/**
				 * Action hook for rendering the payment area at the end of the checkout's main column.
				 *
				 * The payment methods, the offline payment instructions and the submit button render
				 * here — below the billing fields, where the buyer finishes the form — rather than in
				 * the summary column. `masteriyo_checkout_summary` still fires for everything that
				 * belongs beside the order total.
				 */
				do_action( 'masteriyo_checkout_payment_section' );
				?>
			</div>

			<div class="masteriyo-checkout-summary">
				<?php
				/**
				 * The disclosure bar, and the panel it discloses.
				 *
				 * Both are the shell's, not a hook's: the panel is a wrapper, and a wrapper
				 * opened by one callback and closed by another is exactly the imbalance this
				 * template was rebuilt to remove. Keeping them here also keeps them outside
				 * `.masteriyo-checkout-summary-your-order` — the node the checkout fragments
				 * replace wholesale on a tax recalculation, a coupon change or a currency
				 * switch — so a refresh can neither close an open drawer nor leave a second
				 * bar behind it.
				 */
				masteriyo_template_checkout_summary_toggle();
				?>

				<div class="masteriyo-checkout-summary-panel" id="masteriyo-checkout-summary-panel">
					<?php
					/**
					 * Action hook for rendering checkout summary in checkout form.
					 *
					 * @since 1.0.0
					 */
					do_action( 'masteriyo_checkout_summary' );
					?>
				</div>
			</div>
		</div>
	</form>
</div>

<?php
/**
 * Fires after rendering checkout form.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_checkout_form' );
