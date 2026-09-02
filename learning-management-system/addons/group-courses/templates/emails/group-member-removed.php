<?php
/**
 * Email Template for a Member Removed from a Group.
 *
 * Tells the member they were removed and that their course access changed.
 */

defined( 'ABSPATH' ) || exit;

/** @var \Masteriyo\Addons\GroupCourses\Emails\GroupMemberRemovedEmailToMember $email */
/** @var string $content */

/**
 * Fires before rendering email header.
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_header', $email ); ?>

<?php echo wp_kses_post( wpautop( wptexturize( $content ) ) ); ?>

<?php

/**
 * Action hook fired in email's footer section.
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
