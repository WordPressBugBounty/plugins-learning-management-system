<?php
/**
 * Email Template for New Member Joining a Group.
 *
 * Provides a warm welcome and essential information for new group members.
 *
 * @since 1.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since 1.9.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_header', $email ); ?>

<p class="email-template--info">
<?php /* translators: %s: username */ ?>
	<?php printf( esc_html__( 'Welcome Aboard, %s!', 'learning-management-system' ), esc_html( $student->get_display_name() ) ); ?>
</p>

<p class="email-template--info">
	<?php
		printf(
		/* translators: %s Group title */
			esc_html__( 'You’ve successfully joined the group "%1$s"! We’re thrilled to have you with us. Your journey towards learning and growth starts here.', 'learning-management-system' ),
			esc_html( $group->get_title() )
		);
		?>
</p>

<p class="email-template--info">
	<?php
		printf(
			/* translators: %s account link */
			esc_html__( 'To get started, you can access your account and discover all the available resources using the following link: %s. Please, set your password the first time you log in.', 'learning-management-system' ),
			wp_kses_post( make_clickable( masteriyo_get_page_permalink( 'account' ) ) )
		);
		?>
</p>

<p class="email-template--info">
	<?php echo esc_html__( 'Dive into the content, participate in discussions, and don’t hesitate to reach out if you need any support. Your learning adventure is just beginning!', 'learning-management-system' ); ?>
</p>

<?php

/**
 * Action hook fired in email's footer section.
 *
 * @since 1.9.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
