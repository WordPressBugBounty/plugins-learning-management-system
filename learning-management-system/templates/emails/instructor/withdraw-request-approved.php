<?php
/**
 * Withdraw request approved email to instructor.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/instructor/withdraw-request-approved.php.
 *
 * HOWEVER, on occasion masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package masteriyo\Templates\Emails\HTML
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires before rendering email header.
 *
 * @since 1.6.14
 *
 * @param \Masteriyo\Emails\Instructor\WithdrawRequestApprovedEmailToInstructor $email Email object.
 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
 */
do_action( 'masteriyo_email_header', $email, $withdraw );

echo wp_kses_post( wpautop( wptexturize( $content ) ) );

/**
 * Action hook for rendering withdraw details in new withdraw email.
 *
 * @since 1.6.14
 *
 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
 * @param \Masteriyo\Emails\Instructor\WithdrawRequestApprovedEmailToInstructor $email Email object.
 */
do_action( 'masteriyo_email_withdraw_details', $withdraw, $email );

/**
 * Action hook fired in email's footer section.
 *
 * @since 1.6.14
 *
 * @param \Masteriyo\Emails\Instructor\WithdrawRequestApprovedEmailToInstructor $email Email object.
 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
 */
do_action( 'masteriyo_email_footer', $email, $withdraw );
