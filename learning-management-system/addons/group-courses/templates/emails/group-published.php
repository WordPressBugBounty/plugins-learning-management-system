<?php
/**
 * Email Template for Group Published Notification.
 *
 * Sent to group authors when their group is published/activated.
 *
 * @since 1.20.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since 1.20.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_header', $email ); ?>

<?php echo wp_kses_post( wpautop( wptexturize( $content ) ) ); ?>

<?php

/**
 * Action hook fired in email's footer section.
 *
 * @since 1.20.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
