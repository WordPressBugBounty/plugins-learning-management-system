<?php
/**
 * New question notification email to instructor.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/instructor/new-question.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates\Emails
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since 2.0.0
 *
 * @param \Masteriyo\Emails\Instructor\NewQuestionEmailToInstructor $email Email object.
 */
do_action( 'masteriyo_email_header', $email );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

/**
 * Action hook fired in email's footer section.
 *
 * @since 2.0.0
 *
 * @param \Masteriyo\Emails\Instructor\NewQuestionEmailToInstructor $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
