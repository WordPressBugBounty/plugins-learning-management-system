<?php
/**
 * New lesson comment reply notification email to admin.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/admin/new-lesson-comment-reply.php.
 *
 * @package Masteriyo\Templates\Emails
 * @version x.x.x
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since x.x.x
 *
 * @param \Masteriyo\Emails\Admin\NewLessonCommentReplyEmailToAdmin $email Email object.
 */
do_action( 'masteriyo_email_header', $email );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

/**
 * Action hook fired in email's footer section.
 *
 * @since x.x.x
 *
 * @param \Masteriyo\Emails\Admin\NewLessonCommentReplyEmailToAdmin $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
