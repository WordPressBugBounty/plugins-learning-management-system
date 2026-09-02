<?php
/**
 * Google Meet session reminder email template for students.
 *
 * @package Masteriyo\Emails\Templates\Student
 *
 * @since 2.30.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: masteriyo_email_header.
 *
 * @since 2.30.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_header', $email );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

// Show additional content if set.
if ( ! empty( $additional_content ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * Hook: masteriyo_email_footer.
 *
 * @since 2.30.0
 *
 * @param \Masteriyo\Emails\Email $email Email object.
 */
do_action( 'masteriyo_email_footer', $email );
