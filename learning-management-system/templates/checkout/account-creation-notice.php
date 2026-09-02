<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for telling a buyer without an account that one comes with the purchase.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/checkout/account-creation-notice.php.
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

<div class="masteriyo-checkout---account-notice-wrapper">
	<p class="masteriyo-checkout----account-notice">
		<?php esc_html_e( 'An account will be created for you so you can access your course — login details will be sent to your email.', 'learning-management-system' ); ?>
	</p>
</div>
<?php
