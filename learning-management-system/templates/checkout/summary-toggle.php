<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for displaying the checkout's order-summary disclosure bar.
 *
 * A narrow-container affordance: below the two-column threshold the summary leads
 * the page collapsed to this one row, and opens the full breakdown in place. The
 * bar and its state live outside `.masteriyo-checkout-summary-your-order`, the
 * node the checkout fragments replace whenever the total changes, so a tax
 * recalculation or a coupon cannot shut a drawer the buyer opened.
 *
 * `aria-controls` names the panel `checkout/form-checkout.php` opens around the
 * summary column's content; the checkout script reads the id from here rather
 * than knowing one of its own, so overriding both templates together keeps
 * working.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/checkout/summary-toggle.php.
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

?>

<button
	type="button"
	id="masteriyo-checkout-summary-toggle"
	class="masteriyo-checkout-summary-toggle"
	aria-expanded="false"
	aria-controls="masteriyo-checkout-summary-panel">
	<span class="masteriyo-checkout-summary-toggle__label">
		<?php esc_html_e( 'Order summary', 'learning-management-system' ); ?>
	</span>
	<span class="masteriyo-checkout-summary-toggle__chevron" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" focusable="false">
			<path d="M19.561 7.403a1.468 1.468 0 0 1 2.02 0 1.339 1.339 0 0 1 0 1.944l-8.57 8.25a1.468 1.468 0 0 1-2.021 0l-8.572-8.25a1.339 1.339 0 0 1 0-1.944 1.468 1.468 0 0 1 2.02 0L12 14.68l7.561-7.278Z" />
		</svg>
	</span>
	<?php masteriyo_template_checkout_summary_total(); ?>
</button>
<?php
