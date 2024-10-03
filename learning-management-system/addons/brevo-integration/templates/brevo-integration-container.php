<?php
/**
 * The Template for displaying brevo consent checkbox in sing-up page.
 *
 * @since 1.13.3
 */

if ( is_user_logged_in() ) {
	return;
}

?>
<div class="masteriyo-signup-brevo-consent-checkbox">
	<div style="display:inline-block">
		<input type="checkbox" id="masteriyo_brevo_consent_checkbox" name="masteriyo_brevo_consent_checkbox">
		<label for="masteriyo_brevo_consent_checkbox" class="masteriyo-label"><?php echo esc_html( $consent_message ); ?></label>
	</div>
</div>
<?php
