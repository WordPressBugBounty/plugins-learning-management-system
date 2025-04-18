<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Email helper functions.
 *
 * @since 1.6.12
 */

use Masteriyo\Enums\UserStatus;

/**
 * Generates a one-time use magic link for passwordless login and returns the link URL.
 *
 * @since 1.6.12
 *
 * @param int|\WP_User|\Masteriyo\Models\User $user User ID, WP_User object, or Masteriyo\Database\Model object.
 * @param string $nonce The nonce for the link.
 *
 * @return string|false The URL for the one-time use magic link, return false if user is not found.
 */
function masteriyo_generate_email_verification_link( $user, $nonce ) {
	$user = masteriyo_get_user( $user );
	$url  = '';

	if ( $user && ! is_wp_error( $user ) ) {
		$token = masteriyo_generate_onetime_token( $user->get_id(), 'masteriyo_email_verification' );

		$url_params = array(
			'uid'   => $user->get_id(),
			'token' => $token,
			'nonce' => $nonce,
		);

		$url = add_query_arg( $url_params, masteriyo_get_page_permalink( 'account' ) );
	}

	/**
	 * Filters email verification link.
	 *
	 * @since 1.6.12
	 *
	 * @param string $url Email verification link.
	 * @param \Masteriyo\Models\User $user User object.
	 * @param string $nonce Nonce.
	 */
	return apply_filters( 'masteriyo_email_verification_link', $url, $user, $nonce );
}

/**
 * Generate a one-time token for the given user ID and action.
 *
 * @since 1.6.12
 *
 * @param int    $user_id The ID of the user for whom to generate the token.
 * @param string $action The action for which to generate the token.
 * @param int    $key_length The length of the random key to be generated. Defaults to 100.
 * @param int    $expiration_time The duration of the token's validity in minutes. Defaults to 24 hours.
 *
 * @return string The generated one-time token.
 */
function masteriyo_generate_onetime_token( $user_id = 0, $action = '', $key_length = 100, $expiration_time = 24 * HOUR_IN_SECONDS ) {
	$time = time();
	$key  = wp_generate_password( $key_length, false );

	// Concatenate the key, action, and current time to form the token string.
	$string = $key . $action . $time;

	// Generate the token hash.
	$token = wp_hash( $string );

	// Set the token expiration time in seconds.
	$expiration = apply_filters( $action . '_onetime_token_expiration', $expiration_time );

	// Set the user meta values for the token and expiration time.
	update_user_meta( $user_id, $action . '_token' . $user_id, $token );
	update_user_meta( $user_id, $action . '_token_expiration' . $user_id, $time + $expiration );

	return $token;
}

/**
 * Generates the resend email verification link URL.
 *
 * @since 1.6.12
 *
 * @param int $user_id The ID of the user for whom to generate the token.
 *
 * @return string The resend verification link.
 */
function masteriyo_generate_resend_verification_link( $user_id ) {
	$url_params = array(
		'uid'                       => $user_id,
		'resend_email_verification' => true,
	);

	$resend_verification_link = add_query_arg( $url_params, masteriyo_get_page_permalink( 'account' ) );

	return $resend_verification_link;
}

if ( ! function_exists( 'masteriyo_is_user_email_verified' ) ) {
	/**
	 * Function to check if a current user's email is verified or not.
	 *
	 * @since 1.6.12
	 *
	 * @return boolean
	 */
	function masteriyo_is_user_email_verified() {

		if ( ! masteriyo_is_email_verification_enabled() || masteriyo_is_current_user_admin() ) {
			return true;
		}

		$user = masteriyo_get_current_user();

		if ( is_null( $user ) || is_wp_error( $user ) ) {
			return false;
		}

		return UserStatus::SPAM !== $user->get_status();
	}
}
if ( ! function_exists( 'masteriyo_get_email_template_header_logo' ) ) {
	/**
	 * Returns the file path to the Masteriyo logo image used in email templates.
	 *
	 * @since 1.15.0
	 *
	 * @return string The file path to the Masteriyo logo image.
	 */
	function masteriyo_get_email_template_header_logo() {
		$logo_id = masteriyo_get_setting( 'emails.general.header_logo.id' );

		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		$src = ( false === $src ) ? masteriyo_get_plugin_url() . '/assets/img/masteriyo-email-template-logo.png' : $src[0];

		/**
		 * Filters the email template header logo image URL.
		 *
		 * @since 1.15.0
		 *
		 * @param string $src The email template header logo image URL.
		 *
		 * @return string The filtered email template header logo image URL.
		 */
		return apply_filters( 'masteriyo_email_template_header_logo', $src );
	}
}

if ( ! function_exists( 'masteriyo_get_email_template_header_background_img' ) ) {
	/**
	 * Returns the file path to the Masteriyo background image used in email templates.
	 *
	 * @since 2.13.0
	 *
	 * @return string The file path to the Masteriyo background image.
	 */
	function masteriyo_get_email_template_header_background_img() {
		$img_id = masteriyo_get_setting( 'emails.general.header_bg_img.id' );

		$src = wp_get_attachment_image_src( $img_id, 'full' );
		$src = ( false === $src ) ? masteriyo_get_plugin_url() . '/assets/img/email-template-header-bg-img.png' : $src[0];

		/**
		 * Filters the email template header background image URL.
		 *
		 * @since 2.13.0
		 *
		 * @param string $src The email template header background image URL.
		 *
		 * @return string The filtered email template header background image URL.
		 */
		return apply_filters( 'masteriyo_email_template_header_bg_img', $src );
	}
}


/**
 * Returns the footer text to be used in email templates.
 *
 * @since 1.15.0
 *
 * @return string The email template footer text.
 */
if ( ! function_exists( 'masteriyo_get_email_footer_text' ) ) {
	function masteriyo_get_email_footer_text() {
		$footer_text = masteriyo_get_setting( 'emails.general.footer_text' );
		$footer_text = ! empty( $footer_text ) ? $footer_text : 'Thanks. <br /> {site_title} Team';

		/**
		 * Returns the filtered email template footer text.
		 *
		 * @since 1.15.0
		 *
		 * @return string The email template footer text.
		 */
		return apply_filters( 'masteriyo_get_email_footer_text', $footer_text );
	}
}
if ( ! function_exists( 'masteriyo_get_default_email_contents' ) ) {
	/**
	 * Returns the default email contents used.
	 *
	 * The returned array is structured with the following keys:
	 * - `admin`: Contains email content for admin-related emails.
	 * - `instructor`: Contains email content for instructor-related emails.
	 * - `student`: Contains email content for student-related emails.
	 * - `everyone`: Contains email content for everyone-related emails.
	 *
	 * Each of these top-level keys contains an array of email types, with the email
	 * type as the key and an array with a `content` key containing the default email
	 * content.
	 *
	 * @since 1.15.0
	 *
	 * @return array The default email contents used in Masteriyo.
	 */
	function masteriyo_get_default_email_contents() {
		$data = array(
			'admin'      => array(
				'new_order'                 => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'You made a sale!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><h4>Congrats! You made a sale!</h4>{new_order_celebration_image}<p><span class="email-text--bold">Order #</span>: {order_id}<br /><span class="email-text--bold">Date</span>: {order_date}<br /><span class="email-text--bold">Name</span>: {billing_name}<br /><span class="email-text--bold">Email</span>: {billing_email}</p><p class="email-template--info"><span class="email-text--bold">Course</span>: {course_name}</p>{order_table}',
				),
				'instructor_apply'          => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has applied for instructor status!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A student has submitted a request to become an instructor. Please find the applicant’s details below:</p><p><span class="email-text--bold">Date: </span>{student_registered_date}<br /><span class="email-text--bold">Name: </span>{student_name}<br /><span class="email-text--bold">Email: </span>{student_email}<p><p class="email-template--info">Kindly review the application and take appropriate action.</p> {review_application_link}',
				),
				'new_withdraw_request'      => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'New withdraw request!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">{withdrawer_first_name} has requested to withdraw funds in the amount of {withdraw_amount}</p>',
				),
				'instructor_registration'   => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A user has applied for instructor status!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A user has submitted a request to become an instructor. Please find the applicant’s details below:</p><p><span class="email-text--bold">Date: </span>{instructor_registered_date}<br /><span class="email-text--bold">Name: </span>{instructor_name}<br /><span class="email-text--bold">Email: </span>{instructor_email}<p class="email-template--info">Kindly review the application and take appropriate action.</p> {review_application_link}',
				),
				'student_registration'      => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'New Student Registration!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p>{student_display_name} has just registered as student.</p>',
				),
				'course_start'              => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has started a course!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A student has just started a course. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}</p>',
				),
				'course_completion'         => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has completed a course!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A student has just completed a course. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}</p>',
				),
				'new_quiz_attempt'          => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has made a quiz attempt!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A student has just made a quiz attempt. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}  <br /> <span class="email-text--bold">Quiz</span>: {quiz_name}</p><p class"email--template--info">If necessary, please review the quiz attempt.</p>{quiz_attempt_review_link}',
				),
				'new_assignment_submission' => array(
					'enable'           => false,
					'recipients'       => array(),
					'subject'          => 'A student has made an assignment submission!',
					'from_address'     => get_bloginfo( 'admin_email' ),
					'from_name'        => get_bloginfo( 'name' ),
					'reply_to_address' => get_bloginfo( 'admin_email' ),
					'reply_to_name'    => get_bloginfo( 'name' ),
					'to_address'       => '{admin_email}',
					'content'          => '<p class="email-template--info">Hi {site_title},</p><p class="email-template--info">A student has just made an assignment submission. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}  <br /> <span class="email-text--bold">Assignment</span>: {assignment_name}</p><p class"email--template--info">If necessary, please review the assignment submission.</p>{assignment_submission_review_link}',
				),
			),
			'instructor' => array(
				'instructor_registration'   => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'Welcome to {site_title}!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p><p>Thank you for registering at <span class="email-text--bold">{site_title}</span>.  We are thrilled to have you onboard!.</p><p class="email-template--info">To get started, log in to your account by clicking the button below:</p> {account_login_link} ',
				),
				'instructor_apply_approved' => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'Congrats! You are now a instructor!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p>{instructor_approval_celebration_image}<p>Congratulations! We\'re excited to let you know that your application for instructor status has been approved.</p><p class="email-template--info">You can now start building your own courses. To get started, log in to your account by clicking the button below:</p> {account_login_link} ',
				),
				'withdraw_request_pending'  => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'Withdraw request pending!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {withdrawer_first_name},</p><p class="email-template--info">Your withdraw request of {withdraw_amount} is pending approval</p>',
				),
				'withdraw_request_approved' => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'Withdraw request approved!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {withdrawer_first_name},</p><p class="email-template--info">Your withdraw request of {withdraw_amount} has been approved.</p>',
				),
				'withdraw_request_rejected' => array(
					'enable'     => true,
					'recipients' => array(),
					'subject'    => 'Withdraw request rejected!',
					'content'    => '<p class="email-template--info">Hi {withdrawer_first_name},</p><p class="email-template--info">Your withdraw request of {withdraw_amount} has been rejected.</p>',
				),
				'course_start'              => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has started a course!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p><p class="email-template--info">A student has just started a course. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}</p>',
				),
				'course_completion'         => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has completed a course!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p><p class="email-template--info">A student has just completed a course. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}</p>',
				),
				'new_quiz_attempt'          => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'A student has made a quiz attempt!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p><p class="email-template--info">A student has just made a quiz attempt. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}  <br /> <span class="email-text--bold">Quiz</span>: {quiz_name}</p><p class"email--template--info">If necessary, please review the quiz attempt.</p>{quiz_attempt_review_link}',
				),
				'new_assignment_submission' => array(
					'enable'           => false,
					'recipients'       => array(),
					'subject'          => 'A student has made an assignment submission!',
					'from_address'     => get_bloginfo( 'admin_email' ),
					'from_name'        => get_bloginfo( 'name' ),
					'reply_to_address' => get_bloginfo( 'admin_email' ),
					'reply_to_name'    => get_bloginfo( 'name' ),
					'to_address'       => '{instructor_email}',
					'content'          => '<p class="email-template--info">Hi {instructor_first_name},</p><p class="email-template--info">A student has just made an assignment submission. Here are the details:</p><p><span class="email-text--bold">Name</span>: {student_name} <br /> <span class="email-text--bold">Course</span>: {course_name}  <br /> <span class="email-text--bold">Assignment</span>: {assignment_name}</p><p class"email--template--info">If necessary, please review the assignment submission.</p>{assignment_submission_review_link}',
				),
			),
			'student'    => array(
				'student_registration'       => array(
					'enable'           => true,
					'subject'          => 'Welcome to {site_title}!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {student_first_name},</p><p>Thank you for registering at <span class="email-text--bold">{site_title}</span>.  We are thrilled to have you onboard!.</p><p class="email-template--info">To get started, log in to your account by clicking the button below:</p> {account_login_link} ',
				),
				'automatic_registration'     => array(
					'enable'           => true,
					'subject'          => 'Welcome to {site_title}!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {student_first_name},</p><p class="email-template--info">Thank you for registering at <span class="email-text--bold">{site_title}</span>. We are thrilled to have you onboard!</p><p class="email-template--info">Below are your login details to get started:</p><ul><li><span class="email-text--bold">Username: </span>{student_username}</li><li><span class="email-text--bold">Password: </span>{generated_password}</li></ul><p class="email-template--info">To enhance your security, we recommend changing your password. You can do so easily by clicking the link below:</p> {password_reset_link}<p class="email-template--info">If you have any questions or need assistance, feel free to reach out. We\'re here to help!</p>',
				),
				'instructor_apply_rejected'  => array(
					'enable'           => true,
					'subject'          => 'Update Regarding Your Application for Instructor Status',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {student_display_name},</p><p>We regret to inform you that your application for instructor status has been rejected.</p>',
				),
				'completed_order'            => array(
					'enable'           => true,
					'subject'          => 'Thanks for your purchase!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {billing_first_name},</p><h4>Thanks for your purchase!.</h4><p><span class="email-text--bold">Order #</span>: {order_id}<br /><span class="email-text--bold">Status</span>: Completed <br /><span class="email-text--bold">Date</span>: {order_date}</p><p><span class="email-text--bold">Course</span>: {course_name}</p>{order_table}<p class="email-template--info">If necessary, log in to your account by clicking the button below:</p>{account_login_link}',
				),
				'onhold_order'               => array(
					'enable'           => true,
					'subject'          => 'Your order in on hold!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {billing_first_name},</p><h4>Your order is on hold.</h4><p><span class="email-text--bold">Order #</span>: {order_id}<br /><span class="email-text--bold">Status</span>: On Hold <br /><span class="email-text--bold">Date</span>: {order_date}</p><p><span class="email-text--bold">Course</span>: {course_name}</p>{order_table}<p class="email-template--info">If necessary, log in to your account by clicking the button below:</p>{account_login_link}',
				),
				'cancelled_order'            => array(
					'enable'           => true,
					'recipients'       => array(),
					'subject'          => 'Your order has been cancelled!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {billing_first_name},</p><h4>Your order has been cancelled.</h4><p><span class="email-text--bold">Order #</span>: {order_id}<br /><span class="email-text--bold">Status</span>: Cancelled <br /><span class="email-text--bold">Date</span>: {order_date}</p><p><span class="email-text--bold">Course</span>: {course_name}</p>{order_table}<p class="email-template--info">If necessary, log in to your account by clicking the button below:</p>{account_login_link}',
				),
				'course_completion_reminder' => array(
					'enable'           => false,
					'recipients'       => array(),
					'subject'          => 'Reminder to complete your course!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{student_email}',
					'content'          => '<p class="email-template--info">Hi {student_first_name},</p><p>Hope you are doing well. <br />This is a friendly reminder to complete your course, <a href="{course_url}">{course_name}</a>.</p><p class="email-template--info">If necessary, log in to your account by clicking the button below:</p> {account_login_link} <p class="email-template--info">We look forward to seeing your progress. If you need any further assistance, please don’t hesitate to reach out.</p>',
				),
				'course_completion'          => array(
					'enable'       => true,
					'recipients'   => array(),
					'to_address'   => '{student_email}',
					'from_address' => '',
					'from_name'    => '',
					'subject'      => 'Congrats! You have completed a course!',
					'content'      => '<p class="email-template--info">Hi {student_first_name},</p><p>Congratulations on completing the <span class="email-text--bold">{course_name}</span> course! That’s an amazing achievement, and we’re excited to see your progress.</p>{course_completion_celebration_image}<p class="email-template--info">If necessary, log in to your account by clicking the button below:</p> {account_login_link}  ',
				),
				'group_course_enroll'        => array(
					'enable'       => true,
					'recipients'   => array(),
					'to_address'   => '{student_email}',
					'from_address' => '',
					'from_name'    => '',
					'subject'      => 'Welcome to {group_name}! Your Journey in "{course_name}" Begins',
					'content'      => '<p class="email-template--info">Hi {student_first_name},</p><p>Welcome to "{group_name}" and congratulations on your enrollment in "{course_name}"! We\'re excited to have you embark on this learning journey with us.</p><p class="email-template--info">Engage with your course materials, participate actively, and reach out anytime you need help. Together, we\'re going to achieve great things.</p><p class="email-template--info">Let\'s make this journey memorable. Welcome aboard!</p>',
				),
				'group_joining'              => array(
					'enable'       => true,
					'recipients'   => array(),
					'to_address'   => '{student_email}',
					'from_address' => '',
					'from_name'    => '',
					'subject'      => 'Congratulations! You\'re Now Part of the "{group_name}"!',
					'content'      => '<p class="email-template--info">Hi {student_first_name},</p><p>You’ve successfully joined the group "{group_name}"! We’re thrilled to have you with us. Your journey towards learning and growth starts here.</p><p class="email-template--info">To get started, you can access your account and discover all the available resources using the following link: {account_login_link}. Please, set your password the first time you log in.</p><p class="email-template--info">Dive into the content, participate in discussions, and don’t hesitate to reach out if you need any support. Your learning adventure is just beginning!</p>',
				),
			),
			'everyone'   => array(
				'password_reset'     => array(
					'enable'           => true,
					'subject'          => 'Password Reset Request!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{user_email}',
					'content'          => '<p class="email-template--info">Hi {username},</p><p class="email-template--info">A password reset has been requested for your account on <span class="email-text--bold">{site_title}</span>.</p><p class="email-template--info"><span class="email-text--bold">Username:</span> {username}</p><p class="email-template--info">If you didn\'t request this, you can safely ignore this email. If you\'d like to proceed, please click the link below to reset your password:</p>{password_reset_link}<p class="email-template--info">If you need any further assistance, feel free to reach out.</p>',
				),
				'email_verification' => array(
					'enable'           => true,
					'subject'          => 'Please verify your email address!',
					'from_address'     => '',
					'from_name'        => '',
					'reply_to_address' => '',
					'reply_to_name'    => '',
					'to_address'       => '{user_email}',
					'content'          => '<p class="email-template--info">Hi {first_name},</p><p class="email-template--info">Thank you for registering with <span class="email-text--bold">{site_title}</span>.</p><p class="email-template--info"><span class="email-text--bold">Username:</span> {username}</p><p class="email-template--info">To verify your account and finalize your registration, please click the link below:</p>{email_verification_link}<p class="email-template--info">This verification link is valid for 24 hours. If it expires, you can request a new one to complete the process.</p><p class="email-template--info">If you need any assistance, feel free to contact us.</p>',
				),
			),
		);
		return $data;
	}
}
