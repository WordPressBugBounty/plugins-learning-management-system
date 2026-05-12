<?php
/**
 * New lesson comment reply notification email to student.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/student/new-lesson-comment-reply.php.
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
 * @param \Masteriyo\Emails\Student\NewLessonCommentReplyEmailToStudent $email Email object.
 */
do_action( 'masteriyo_email_header', $email );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

/**
 * Action hook fired in email's footer section.
 *
 * @since x.x.x
 *
 * @param \Masteriyo\Emails\Student\NewLessonCommentReplyEmailToStudent $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
