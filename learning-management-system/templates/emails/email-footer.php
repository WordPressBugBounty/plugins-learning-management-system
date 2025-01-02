<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/email-footer.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates\Emails
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$footer_text = masteriyo_get_email_footer_text();
$footer_text = str_replace( '{site_title}', get_bloginfo( 'name' ), $footer_text );

?>
		</div>
		<?php if ( ! empty( $footer_text ) ) : ?>
		<div class="email-footer">
			<p><?php echo wp_kses_post( $footer_text ); ?></p>
		</div>
		<?php endif; ?>
	</div>
</body>
</html>
