<?php
/**
 * Instructor registration page content for logged-in visitors.
 *
 * @version 2.3.2 [Free]
 *
 * @var string $state One of 'admin', 'instructor', 'applied', 'can_apply', 'closed'.
 * @var string $message Status message for the current user.
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="masteriyo-signup masteriyo-instructor-registration-status">
	<div class="masteriyo-signup--wrapper masteriyo-form-container">
		<h3 class="masteriyo-signup--title"><?php esc_html_e( 'Become an instructor', 'learning-management-system' ); ?></h3>

		<?php masteriyo_display_all_notices(); ?>

		<p class="masteriyo-instructor-registration-status--message"><?php echo esc_html( $message ); ?></p>

		<?php if ( 'can_apply' === $state ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'masteriyo-apply-for-instructor' ); ?>
				<button type="submit" name="masteriyo-apply-for-instructor" value="yes" class="masteriyo-btn masteriyo-btn-primary">
					<?php esc_html_e( 'Apply for Instructor', 'learning-management-system' ); ?>
				</button>
			</form>
		<?php elseif ( 'instructor' === $state ) : ?>
			<a class="masteriyo-btn masteriyo-btn-primary" href="<?php echo esc_url( masteriyo_get_page_permalink( 'account' ) ); ?>">
				<?php esc_html_e( 'Go to Account', 'learning-management-system' ); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
