<?php
/**
 * Course completion email to student.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/student/course-completion.php.
 *
 * HOWEVER, on occasion masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @since 1.15.0
 *
 * @package masteriyo\Templates\Emails\HTML
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since 1.15.0
 *
 * @param \Masteriyo\Emails\Student\CourseCompletionEmailToStudent $email Email object.
 */
do_action( 'masteriyo_email_header', $email );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

/**
 * Action hook fired in email's footer section.
 *
 * @since 1.15.0
 *
 * @param \Masteriyo\Emails\Student\CourseCompletionEmailToStudent $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
