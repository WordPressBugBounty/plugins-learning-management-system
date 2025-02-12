<?php

/**
 * Wire transfer details.
 *
 * @package Masteriyo\Templates
 * @since 1.16.0
 * @version 1.16.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="masteriyo-checkout-wire-transfer">
	<h3><?php echo esc_html( $wire_transfer['title'] ); ?></h3>
	<p><?php echo esc_html( $wire_transfer['description'] ); ?></p>
	<div class="wire-transfer-details">
		<div class="wire-transfer-row">
			<span class="masteriyo-label"><?php esc_html_e( 'Bank Name:', 'learning-management-system' ); ?></span>
			<span class="value"><?php echo esc_html( $wire_transfer['bank_name'] ); ?></span>
		</div>
		<div class="wire-transfer-row">
			<span class="masteriyo-label"><?php esc_html_e( 'Account Number:', 'learning-management-system' ); ?></span>
			<span class="value"><?php echo esc_html( $wire_transfer['account_number'] ); ?></span>
		</div>
		<div class="wire-transfer-row">
			<span class="masteriyo-label"><?php esc_html_e( 'SWIFT Code:', 'learning-management-system' ); ?></span>
			<span class="value"><?php echo esc_html( $wire_transfer['swift_code'] ); ?></span>
		</div>
		<div class="wire-transfer-row">
			<span class="masteriyo-label"><?php esc_html_e( 'Account Holder Name:', 'learning-management-system' ); ?></span>
			<span class="value"><?php echo esc_html( $wire_transfer['account_holder_name'] ); ?></span>
		</div>
	</div>
</div>

<?php
