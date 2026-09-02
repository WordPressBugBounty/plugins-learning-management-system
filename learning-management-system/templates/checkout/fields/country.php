<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for displaying checkout form field.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/checkout/fields/email.php.
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

use Masteriyo\Notice;

$field = isset( $field ) ? $field : array();
?>

<div class="masteriyo-checkout----country">
	<label for="billing-country" class="masteriyo-label">
		<?php esc_html_e( 'Country', 'learning-management-system' ); ?>
		<?php if ( ! empty( $field['required'] ) ) : ?>
			<span>*</span>
		<?php endif; ?>
	</label>

	<select id="billing-country" class="masteriyo-input" name="billing_country" <?php masteriyo_checkout_field_required_attributes( $field ); ?>>
		<option value="">
			<?php esc_html_e( 'Select Country', 'learning-management-system' ); ?>
		</option>
	</select>

	<?php if ( masteriyo_notice_exists( 'billing_country', Notice::ERROR ) ) : ?>
		<div class="masteriyo-error masteriyo-danger-msg">
			<?php echo wp_kses_post( masteriyo_notice_by_id( 'billing_country', Notice::ERROR ) ); ?>
		</div>
	<?php endif; ?>
</div>
<?php
