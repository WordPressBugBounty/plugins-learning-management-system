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

<?php echo wp_kses_post( wpautop( wptexturize( $content ) ) ); ?>

<?php

/**
 * Action hook fired in email's footer section.
 *
 * @since 1.9.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
