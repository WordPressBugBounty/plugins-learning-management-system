<?php
/**
 * Email template for course enrollment notification to group members.
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
<?php /* translators: %s: Display name */ ?>
	<?php printf( esc_html__( 'Hello %s,', 'learning-management-system' ), esc_html( $student->get_display_name() ) ); ?>
</p>

<p class="email-template--info">
	<?php
		printf(
			/* translators: %1$s: Group title, %2$s: Course title */
			esc_html__( 'Welcome to "%1$s" and congratulations on your enrollment in "%2$s"! We\'re excited to have you embark on this learning journey with us.', 'learning-management-system' ),
			esc_html( $group->get_title() ),
			esc_html( $course->get_title() )
		);
		?>
</p>

<p class="email-template--info">
	<?php echo esc_html__( 'Engage with your course materials, participate actively, and reach out anytime you need help. Together, we\'re going to achieve great things.', 'learning-management-system' ); ?>
</p>

<p class="email-template--info">
	<?php echo esc_html__( 'Let\'s make this journey memorable. Welcome aboard!', 'learning-management-system' ); ?>
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
