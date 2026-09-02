<?php
/**
 * Student preview helper functions.
 *
 * @package Masteriyo\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Build the raw pipe-delimited payload string for token signing.
 *
 * @param int $course_id
 * @param int $user_id
 * @param int $target_user_id  0 = demo student (omitted from payload).
 * @param int $expiry          Unix timestamp.
 * @return string
 */
function masteriyo_build_preview_token_data( int $course_id, int $user_id, int $target_user_id, int $expiry ): string {
	return $target_user_id > 0
		? "{$course_id}|{$user_id}|{$target_user_id}|{$expiry}"
		: "{$course_id}|{$user_id}|{$expiry}";
}

/**
 * Generate a student preview token for a course.
 *
 * When $target_user_id > 0 the preview will impersonate that specific user
 * instead of the auto-created demo student for the admin.
 *
 * course_id = 0 means global preview (not tied to a specific course).
 *
 * @param int $course_id      0 for global preview, positive for course-specific.
 * @param int $user_id        The admin/instructor who is launching the preview.
 * @param int $target_user_id Optional. User to impersonate. 0 = demo student.
 * @return string base64-encoded signed token
 */
function masteriyo_generate_student_preview_token( int $course_id, int $user_id, int $target_user_id = 0 ): string {
	$expiry = time() + 4 * HOUR_IN_SECONDS;
	$data   = masteriyo_build_preview_token_data( $course_id, $user_id, $target_user_id, $expiry );

	$signature = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	return rtrim( base64_encode( $data . '|' . $signature ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Validate a student preview token for the current user and course.
 *
 * @param string $token     base64-encoded signed token.
 * @param int    $course_id Expected course ID (0 = global preview).
 * @return bool
 */
function masteriyo_validate_student_preview_token( string $token, int $course_id ): bool {
	$decoded = base64_decode( str_pad( $token, strlen( $token ) + ( 4 - strlen( $token ) % 4 ) % 4, '=' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( false === $decoded ) {
		return false;
	}
	$parts = explode( '|', $decoded );
	$count = count( $parts );

	$target_user_id = null;

	if ( 4 === $count ) {
		[ $token_course_id, $user_id, $expiry, $signature ] = $parts;
		$data = "{$token_course_id}|{$user_id}|{$expiry}";
	} elseif ( 5 === $count ) {
		[ $token_course_id, $user_id, $target_user_id, $expiry, $signature ] = $parts;
		$data = "{$token_course_id}|{$user_id}|{$target_user_id}|{$expiry}";
	} else {
		return false;
	}

	if ( (int) $token_course_id !== $course_id ) {
		return false;
	}
	if ( get_current_user_id() !== (int) $user_id ) {
		return false;
	}
	if ( time() > (int) $expiry ) {
		return false;
	}
	$expected = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $signature ) ) {
		return false;
	}

	// Verify target is a demo student only after signature passes.
	if ( null !== $target_user_id && ! get_user_meta( (int) $target_user_id, '_masteriyo_is_demo_student', true ) ) {
		return false;
	}

	return true;
}

/**
 * Check if the current page load is a validated student preview session.
 *
 * @return bool
 */
function masteriyo_is_student_preview_mode(): bool {
	return masteriyo_validate_preview_originator_cookie() !== null;
}

/**
 * Persist preview state as a signed browser cookie.
 *
 * @param int $course_id      0 for global preview, positive for course-specific.
 * @param int $user_id
 * @param int $target_user_id Optional. 0 = use demo student.
 */
function masteriyo_set_student_preview_cookie( int $course_id, int $user_id, int $target_user_id = 0 ): void {
	$expiry = time() + 4 * HOUR_IN_SECONDS;
	$data   = masteriyo_build_preview_token_data( $course_id, $user_id, $target_user_id, $expiry );

	$signature = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	$value     = base64_encode( $data . '|' . $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

	setcookie(
		'mto_student_preview',
		$value,
		array(
			'expires'  => $expiry,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE['mto_student_preview'] = $value;
}

/**
 * Clear the student preview cookie on exit.
 */
function masteriyo_clear_student_preview_cookie(): void {
	$expired = array(
		'expires'  => time() - 3600,
		'path'     => COOKIEPATH,
		'domain'   => COOKIE_DOMAIN,
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);

	setcookie( 'mto_student_preview', '', $expired );
	unset( $_COOKIE['mto_student_preview'] );
}

/**
 * Validate the student preview cookie against an explicit user ID.
 * Safe to call before wp_set_current_user() runs.
 *
 * Returns -1 on any validation failure.
 * Returns 0 for a valid global preview (not course-specific).
 * Returns a positive course ID for a course-specific preview.
 *
 * @param int $admin_id The authenticated admin/instructor user ID to validate against.
 * @return int -1 on failure, 0 for global preview, positive course ID for course-specific.
 */
function masteriyo_validate_student_preview_cookie_for_user( int $admin_id ): int {
	if ( empty( $_COOKIE['mto_student_preview'] ) ) {
		return -1;
	}
	$decoded = base64_decode( $_COOKIE['mto_student_preview'], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( ! $decoded ) {
		return -1;
	}
	$parts = explode( '|', $decoded );
	$count = count( $parts );

	if ( 4 === $count ) {
		[ $course_id, $user_id, $expiry, $signature ] = $parts;
		$data           = "{$course_id}|{$user_id}|{$expiry}";
		$target_user_id = 0;
	} elseif ( 5 === $count ) {
		[ $course_id, $user_id, $target_user_id, $expiry, $signature ] = $parts;
		$data = "{$course_id}|{$user_id}|{$target_user_id}|{$expiry}";
	} else {
		return -1;
	}

	if ( (int) $user_id !== $admin_id ) {
		return -1;
	}
	if ( time() > (int) $expiry ) {
		return -1;
	}
	$expected = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $signature ) ) {
		return -1;
	}

	return (int) $course_id;
}

/**
 * Validate the student preview cookie and return the course ID it covers.
 * Uses get_current_user_id() — only call this after WordPress has set the current user.
 *
 * @return int -1 on failure, 0 for global preview, positive course ID for course-specific.
 */
function masteriyo_validate_student_preview_cookie(): int {
	return masteriyo_validate_student_preview_cookie_for_user( get_current_user_id() );
}

/**
 * Get or create the single site-wide demo student account.
 *
 * @return int Demo student user ID, or 0 on failure.
 */
function masteriyo_get_or_create_preview_student(): int {
	$username = 'masteriyo_demo_student';
	$user     = get_user_by( 'login', $username );

	if ( $user ) {
		return (int) $user->ID;
	}

	$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
	$user_id   = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'user_email'   => 'demo-student@' . $site_host,
			'display_name' => __( 'System account', 'learning-management-system' ),
			'first_name'   => __( 'Demo', 'learning-management-system' ),
			'last_name'    => __( 'Student', 'learning-management-system' ),
		)
	);

	if ( is_wp_error( $user_id ) ) {
		// Two concurrent first-time previews can both pass the get_user_by check above
		// and then race to wp_insert_user. The loser gets existing_user_login — fetch
		// the winner's record rather than failing the whole preview.
		if ( 'existing_user_login' === $user_id->get_error_code() ) {
			$user = get_user_by( 'login', $username );
			return $user ? (int) $user->ID : 0;
		}
		return 0;
	}

	$user = new \WP_User( $user_id );
	$user->set_role( 'masteriyo_student' );
	update_user_meta( $user_id, '_masteriyo_is_demo_student', 1 );
	update_user_meta( $user_id, '_masteriyo_auto_created', 1 );

	return $user_id;
}

/**
 * Return the email address of the user currently being previewed.
 * Must be called after determine_current_user has set the globals.
 *
 * @return string
 */
function masteriyo_get_preview_as_email(): string {
	return isset( $GLOBALS['masteriyo_preview_as_email'] )
		? (string) $GLOBALS['masteriyo_preview_as_email']
		: '';
}

/**
 * Return the email address of the site-wide demo student.
 *
 * @return string
 */
function masteriyo_get_demo_student_email(): string {
	$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
	return 'demo-student@' . $site_host;
}

/**
 * Get the frontend landing URL for a preview session.
 *
 * Prefers the Masteriyo account page, then My Courses, then site home.
 *
 * @return string
 */
function masteriyo_get_preview_landing_url(): string {
	if ( function_exists( 'masteriyo_get_page_permalink' ) ) {
		$account_url = masteriyo_get_page_permalink( 'account' );
		if ( $account_url ) {
			return $account_url;
		}
		$courses_url = masteriyo_get_page_permalink( 'mycourselist' );
		if ( $courses_url ) {
			return $courses_url;
		}
	}
	return home_url( '/' );
}

/**
 * Generate a preview magic link for an arbitrary registered user identified by email.
 *
 * @param int    $course_id
 * @param int    $admin_id
 * @param string $email     The registered user's email to preview as.
 * @return array{preview_url:string,preview_email:string}|WP_Error
 */
function masteriyo_generate_preview_link_for_email( int $course_id, int $admin_id, string $email ) {
	$target_user = get_user_by( 'email', sanitize_email( $email ) );

	if ( ! $target_user ) {
		return new WP_Error(
			'masteriyo_preview_user_not_found',
			__( 'No registered user found with that email address.', 'learning-management-system' ),
			array( 'status' => 404 )
		);
	}

	if ( ! get_user_meta( (int) $target_user->ID, '_masteriyo_is_demo_student', true ) ) {
		return new WP_Error(
			'masteriyo_preview_not_demo_student',
			__( 'Preview is only available for the demo student account.', 'learning-management-system' ),
			array( 'status' => 403 )
		);
	}

	$course = masteriyo_get_course( $course_id );
	if ( ! $course ) {
		return new WP_Error(
			'masteriyo_preview_course_not_found',
			__( 'Course not found.', 'learning-management-system' ),
			array( 'status' => 404 )
		);
	}

	$token       = masteriyo_generate_student_preview_token( $course_id, $admin_id, (int) $target_user->ID );
	$preview_url = add_query_arg( 'mto-student-preview', $token, $course->get_permalink() );

	return array(
		'preview_url'   => $preview_url,
		'preview_email' => $target_user->user_email,
	);
}

/**
 * Set the originator cookie to allow restoring the admin session after preview.
 *
 * Stores a signed {admin_id}|{session_token}|{expiry} payload. The session_token
 * is the admin's raw WP session token (wp_get_session_token()), used on exit to
 * verify the admin session is still live before issuing new auth cookies.
 *
 * Cookie is httponly to prevent JS theft — this is effectively a
 * "switch-back-to-admin" capability token.
 *
 * @param int    $admin_id      The admin/instructor launching the preview.
 * @param string $session_token Raw WP session token from wp_get_session_token().
 */
function masteriyo_set_preview_originator_cookie( int $admin_id, string $session_token ): void {
	$expiry    = time() + 4 * HOUR_IN_SECONDS;
	$data      = "{$admin_id}|{$session_token}|{$expiry}";
	$signature = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	$value     = base64_encode( $data . '|' . $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

	setcookie(
		'mto_preview_originator',
		$value,
		array(
			'expires'  => $expiry,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE['mto_preview_originator'] = $value;
}

/**
 * Validate the originator cookie and return the stored payload.
 *
 * Returns null on any failure (missing, tampered, expired).
 * Returns an array with 'admin_id' (int) and 'session_token' (string) on success.
 *
 * @return array{admin_id:int,session_token:string}|null
 */
function masteriyo_validate_preview_originator_cookie(): ?array {
	if ( empty( $_COOKIE['mto_preview_originator'] ) ) {
		return null;
	}
	$decoded = base64_decode( $_COOKIE['mto_preview_originator'], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( ! $decoded ) {
		return null;
	}

	// Format: admin_id|session_token|expiry|signature
	// Session tokens are alphanumeric (wp_generate_password(43, false, false)), safe to split by |.
	$parts = explode( '|', $decoded );
	if ( count( $parts ) !== 4 ) {
		return null;
	}

	[ $admin_id, $session_token, $expiry, $signature ] = $parts;

	$data     = "{$admin_id}|{$session_token}|{$expiry}";
	$expected = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $signature ) ) {
		return null;
	}
	if ( time() > (int) $expiry ) {
		return null;
	}

	return array(
		'admin_id'      => (int) $admin_id,
		'session_token' => $session_token,
	);
}

/**
 * Clear the originator cookie.
 */
function masteriyo_clear_preview_originator_cookie(): void {
	setcookie(
		'mto_preview_originator',
		'',
		array(
			'expires'  => time() - 3600,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	unset( $_COOKIE['mto_preview_originator'] );
}

/**
 * Clear the JS-set return-to cookie.
 *
 * The cookie is set by JS with Path=/ so we clear with Path=/ as well.
 */
function masteriyo_clear_preview_return_to_cookie(): void {
	setcookie(
		'mto_preview_return_to',
		'',
		array(
			'expires'  => time() - 3600,
			'path'     => '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);
	unset( $_COOKIE['mto_preview_return_to'] );
}

/**
 * Whether this request is authenticated as the reserved demo student.
 *
 * The identity, not a cookie: the preview cookies last 4 hours while the demo
 * student's auth cookie lasts 2 days, and direct login to the account is blocked.
 *
 * @return bool
 */
function masteriyo_is_current_user_demo_student(): bool {
	$user_id = get_current_user_id();

	return $user_id > 0 && (bool) get_user_meta( $user_id, '_masteriyo_is_demo_student', true );
}

/**
 * End the preview: clear its cookies and restore the original admin session.
 *
 * Validates via the signed originator cookie, not the current WP auth state, so
 * it is safe to call whoever the request is currently authenticated as.
 *
 * @param string $return_url Where to land afterwards; the dashboard by default.
 * @return string The URL to redirect to.
 */
function masteriyo_end_student_preview( string $return_url = '' ): string {
	$originator = masteriyo_validate_preview_originator_cookie();
	$previewing = $originator || masteriyo_is_current_user_demo_student();

	masteriyo_clear_student_preview_cookie();
	masteriyo_clear_preview_originator_cookie();
	masteriyo_clear_preview_return_to_cookie();

	// Nobody is previewing, so there is no session to end. The exit URL carries no
	// nonce and answers on any request, so clearing auth here would sign out any
	// administrator who followed a stale or hostile link.
	if ( ! $previewing ) {
		if ( $return_url ) {
			return $return_url;
		}

		return is_user_logged_in() ? admin_url() : home_url( '/' );
	}

	// The demo student's session is never the one to keep. Drop it before either
	// branch below decides where to land, or a failed restore leaves the browser
	// signed in as the demo student and the wp-admin guard traps it again.
	wp_clear_auth_cookie();

	// Nobody to restore, or the admin's own session is gone: fail closed to login.
	// We do not resurrect a session the admin ended elsewhere.
	if ( ! $originator || ! \WP_Session_Tokens::get_instance( $originator['admin_id'] )->verify( $originator['session_token'] ) ) {
		return wp_login_url( $return_url ? $return_url : admin_url() );
	}

	// Mint fresh auth cookies for the original admin (the original session stays
	// valid for other devices; this creates an additional session).
	wp_set_auth_cookie( $originator['admin_id'], false, is_ssl() );

	return $return_url ? $return_url : admin_url();
}

/**
 * Read the frontend banner's return-to cookie, constrained to this origin.
 *
 * @return string Empty when absent or off-origin.
 */
function masteriyo_get_preview_return_to(): string {
	if ( empty( $_COOKIE['mto_preview_return_to'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return '';
	}

	// wp_validate_redirect preserves URL fragments and constrains to same-origin.
	return (string) wp_validate_redirect( urldecode( wp_unslash( $_COOKIE['mto_preview_return_to'] ) ), '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Validate the URL token on the first page load, set the preview cookie,
 * swap the auth session to the demo student, and redirect to the clean URL.
 *
 * Also handles both exit flows: the banner's Switch to Admin button, and a
 * preview session navigating to wp-admin.
 *
 * Priority -1 on 'init' — Masteriyo's own student guard runs at priority 0 and
 * bounces any student request for wp-admin to the home page, which would
 * swallow the wp-admin exit below.
 */
function masteriyo_handle_student_preview_token(): void {

	if ( isset( $_GET['mto-exit-student-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( masteriyo_end_student_preview( masteriyo_get_preview_return_to() ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// EXIT: a preview session asked for wp-admin. The frontend banner is the
	// only switch-back control and wp_footer never runs there, so end the
	// preview here — before any capability check — and continue to the screen
	// that was asked for.
	// -------------------------------------------------------------------------
	// Keyed on the demo-student identity alone, not on a preview cookie: those last
	// 4 hours while the auth cookie lasts 2 days, and in between there is no pill
	// and no recovery — the trap again.
	if ( is_admin() && ! wp_doing_ajax() && masteriyo_is_current_user_demo_student() ) {
		wp_safe_redirect( masteriyo_end_student_preview( add_query_arg( 'mto-preview-ended', '1' ) ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// ENTRY: validate URL token and swap auth to the demo student.
	// -------------------------------------------------------------------------
	if ( ! is_user_logged_in() ) {
		return;
	}

	$token = isset( $_GET['mto-student-preview'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? wp_unslash( $_GET['mto-student-preview'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		: '';

	if ( ! $token ) {
		return;
	}

	if ( headers_sent() ) {
		masteriyo_get_logger()->error(
			'Student preview token received but HTTP headers already sent; cannot swap auth cookies.',
			array( 'source' => 'student-preview' )
		);
		return;
	}

	$decoded = base64_decode( str_pad( $token, strlen( $token ) + ( 4 - strlen( $token ) % 4 ) % 4, '=' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( ! $decoded ) {
		return;
	}
	$parts = explode( '|', $decoded );
	$count = count( $parts );

	if ( 4 === $count ) {
		[ $tok_course_id, $tok_user_id, $tok_expiry, $tok_sig ] = $parts;
		$tok_target_user_id                                     = 0;
		$sign_data = "{$tok_course_id}|{$tok_user_id}|{$tok_expiry}";
	} elseif ( 5 === $count ) {
		[ $tok_course_id, $tok_user_id, $tok_target_user_id, $tok_expiry, $tok_sig ] = $parts;
		$sign_data = "{$tok_course_id}|{$tok_user_id}|{$tok_target_user_id}|{$tok_expiry}";
	} else {
		return;
	}

	if ( ! is_numeric( $tok_course_id ) || (int) $tok_course_id < 0 ) {
		return;
	}
	$course_id = (int) $tok_course_id;

	$admin_id = get_current_user_id();
	if ( (int) $tok_user_id !== $admin_id ) {
		return;
	}
	if ( time() > (int) $tok_expiry ) {
		return;
	}
	$expected_sig = hash_hmac( 'sha256', $sign_data, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected_sig, $tok_sig ) ) {
		return;
	}

	// Resolve the preview user (specific target or auto-created demo student).
	$tok_target_user_id = (int) $tok_target_user_id;
	if ( $tok_target_user_id > 0 ) {
		if ( ! get_user_meta( $tok_target_user_id, '_masteriyo_is_demo_student', true ) ) {
			return;
		}
		$preview_user_id = $tok_target_user_id;
	} else {
		$preview_user_id = masteriyo_get_or_create_preview_student();
		if ( ! $preview_user_id ) {
			return;
		}
	}

	// Capture originator info before clearing admin auth.
	// wp_get_session_token() reads from the logged_in cookie; it returns '' when
	// the request uses non-cookie auth (application passwords, REST Basic Auth).
	// An empty token would produce a broken originator cookie that always fails
	// verification on exit, locking the admin out. Abort early instead.
	$session_token = wp_get_session_token();
	if ( empty( $session_token ) ) {
		masteriyo_get_logger()->warning(
			'Student preview aborted: wp_get_session_token() returned empty — session-based auth required.',
			array( 'source' => 'student-preview' )
		);
		return;
	}
	masteriyo_set_preview_originator_cookie( $admin_id, $session_token );

	// Swap to demo student: clear admin auth, issue demo student auth cookies.
	wp_clear_auth_cookie();
	wp_set_auth_cookie( $preview_user_id, false, is_ssl() );

	// Keep the preview-scope cookie for course-specific gating and legacy callers.
	masteriyo_set_student_preview_cookie( $course_id, $admin_id, $tok_target_user_id );

	wp_safe_redirect( remove_query_arg( 'mto-student-preview' ) );
	exit;
}

/**
 * Whether the current user may launch or generate a student preview.
 *
 * @return bool
 */
function masteriyo_current_user_can_student_preview(): bool {
	return masteriyo_is_current_user_admin() || masteriyo_is_current_user_instructor();
}

/**
 * During a validated student preview session, let the impersonated demo
 * student view the covered course even when it is unpublished.
 *
 * WP core lets a singular query pass its status check when the query names
 * the post status explicitly — the same mechanism the private-course filter
 * in FrontendQuery uses. No capability is granted anywhere, so the session
 * changes nothing outside this one main query: not edit checks, not
 * purchasability, not catalog visibility.
 *
 * The scope is strict. The query must be the frontend main query for the
 * exact course the preview cookie covers. The current user must be a demo
 * student account.
 *
 * @param \WP_Query $query The WordPress query object.
 */
function masteriyo_student_preview_show_covered_course( $query ): void {
	if ( is_admin() || ! $query->is_main_query() || empty( $_COOKIE['mto_preview_originator'] ) ) {
		return;
	}

	$post_type = isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : '';
	if ( 'mto-course' !== $post_type ) {
		return;
	}

	if ( ! get_user_meta( get_current_user_id(), '_masteriyo_is_demo_student', true ) ) {
		return;
	}

	$originator = masteriyo_validate_preview_originator_cookie();
	if ( ! $originator ) {
		return;
	}

	// Match the exit flow: a revoked originator session — logout elsewhere,
	// password change — invalidates the preview now, not at cookie expiry.
	if ( ! \WP_Session_Tokens::get_instance( $originator['admin_id'] )->verify( $originator['session_token'] ) ) {
		return;
	}

	$covered_course_id = masteriyo_validate_student_preview_cookie_for_user( $originator['admin_id'] );
	if ( $covered_course_id <= 0 ) {
		return;
	}

	$covered = get_post( $covered_course_id );
	if ( ! $covered || 'mto-course' !== $covered->post_type ) {
		return;
	}

	// The cookie stays valid for four hours, but the originator's rights can
	// change inside that window — the owner can unpublish, a role can go away.
	// Re-check at request time: the session only shows what its originator
	// can still edit.
	if ( ! user_can( $originator['admin_id'], 'edit_post', $covered_course_id ) ) {
		return;
	}
	$status = $covered->post_status;
	if ( in_array( $status, array( \Masteriyo\Enums\PostStatus::PUBLISH, \Masteriyo\Enums\PostStatus::TRASH ), true ) ) {
		return;
	}

	// Drafts and pending courses resolve by ID; private and scheduled courses
	// resolve by their pretty permalink. Slug matching is safe only for the
	// statuses wp_unique_post_slug() uniquifies — a shared draft/pending slug
	// could resolve the forced-status query to a different course.
	$slug_safe    = in_array(
		$status,
		array( \Masteriyo\Enums\PostStatus::PVT, \Masteriyo\Enums\PostStatus::FUTURE ),
		true
	);
	$queried_id   = isset( $query->query_vars['p'] ) ? (int) $query->query_vars['p'] : 0;
	$queried_name = isset( $query->query_vars['name'] ) ? (string) $query->query_vars['name'] : '';
	$is_covered   = $queried_id === $covered_course_id
		|| ( $slug_safe && '' !== $queried_name && $queried_name === $covered->post_name );
	if ( ! $is_covered ) {
		return;
	}

	$query->set( 'post_status', array( $status ) );
}
add_action( 'pre_get_posts', 'masteriyo_student_preview_show_covered_course' );

/**
 * Force wp-auth-check:false in every Heartbeat response while a student preview
 * session is active. WordPress core's wp_auth_check() is hooked to heartbeat_send
 * at priority 10 and unconditionally sets wp-auth-check to is_user_logged_in().
 * We override it at priority 11 on the same hook so our value wins.
 *
 * Must use heartbeat_send (not heartbeat_received): heartbeat_send always fires,
 * whereas heartbeat_received only fires when the client sends non-empty $data.
 *
 * The client JS (wp-auth-check.js) listens to heartbeat-tick and calls show()
 * when it sees wp-auth-check:false, which opens the "session expired" overlay.
 */
add_filter(
	'heartbeat_send',
	static function ( array $response, $_screen_id ): array {
		if ( masteriyo_is_student_preview_mode() ) {
			$response['wp-auth-check'] = false;
		}
		return $response;
	},
	11,
	2
);

/**
 * Confirm the preview ended, and that other tabs went with it — auth cookies are
 * shared across the browser, so ending it here ends it everywhere.
 *
 * On `masteriyo_admin_notices`, not `admin_notices`: Masteriyo pages clear every
 * `admin_notices` callback (Masteriyo::display_masteriyo_notices_only) and fire
 * this action instead, and it is forwarded on every other admin screen.
 */
add_action(
	'masteriyo_admin_notices',
	static function (): void {
		if ( ! isset( $_GET['mto-preview-ended'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			esc_html__( 'Student Preview ended because you returned to wp-admin. Any other tab still showing the preview is signed out of it too.', 'learning-management-system' )
		);
	}
);

/**
 * Admin pages: clear any stale localStorage preview signal on load, then attach
 * a cross-tab listener. When the frontend preview page writes a new timestamp to
 * localStorage, the 'storage' event fires here immediately (sub-millisecond) and
 * we directly trigger the 'heartbeat-tick' jQuery event that wp-auth-check.js
 * listens to, which shows the "Your session has expired" overlay instantly.
 *
 * removeItem() runs on every admin page load so the key is always absent — this
 * ensures the next setItem() from the frontend is always a value change, which
 * is required for the browser to actually fire the storage event.
 */
add_action(
	'admin_footer',
	static function (): void {
		?>
		<script>
		( function () {
			try { localStorage.removeItem( 'mto_preview_active' ); } catch ( e ) {}
			window.addEventListener( 'storage', function ( e ) {
				if ( e.key === 'mto_preview_active' && e.newValue ) {
					// Directly fire the heartbeat-tick event that wp-auth-check.js listens for.
					// This avoids the Heartbeat AJAX round-trip and works even when the tab
					// is in the background (where wp.heartbeat may be suspended).
					if ( typeof jQuery !== 'undefined' ) {
						jQuery( document ).trigger( 'heartbeat-tick', [ { 'wp-auth-check': false } ] );
					}
				}
			} );
		}() );
		</script>
		<?php
	}
);

/**
 * Frontend preview page: write a timestamp to localStorage so any open admin
 * tab detects the switch instantly via the 'storage' event (fires cross-tab in
 * the same browser with no polling needed).
 *
 * Date.now() guarantees the value always changes, which is required for the
 * browser to fire the storage event — a repeated identical value is a no-op.
 *
 * Priority 20 runs after the preview banner at default priority 10.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( masteriyo_is_student_preview_mode() ) {
			?>
			<script>try { localStorage.setItem( 'mto_preview_active', String( Date.now() ) ); } catch ( e ) {}</script>
			<?php
		}
	},
	20
);

/**
 * Replace the generic "Your session has expired" message in the interim-login
 * overlay with a context-aware message when a student preview session is active.
 *
 * The message comes from wp-login.php as a 'message'-severity WP_Error entry
 * and is assembled into HTML before login_messages fires (line ~300 of wp-login.php).
 * wp-login.php loads all plugins via wp-load.php, so this filter is available.
 */
add_filter(
	'login_messages',
	static function ( string $messages ): string {
		if ( isset( $_GET['interim-login'] ) && masteriyo_is_student_preview_mode() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p>' . esc_html__( 'You are currently previewing the site as a student. Please log back in as admin to return to the dashboard.', 'learning-management-system' ) . '</p>';
		}
		return $messages;
	}
);

/**
 * Exclude demo/preview students from enrolled-user counts so they do not
 * consume course seats or inflate student analytics.
 */
add_filter(
	'masteriyo_count_enrolled_users',
	static function ( int $count, $course ): int {
		global $wpdb;

		$demo_user_ids = get_users(
			array(
				'meta_key'   => '_masteriyo_is_demo_student', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			)
		);

		if ( empty( $demo_user_ids ) ) {
			return $count;
		}

		$course_ids = is_array( $course )
			? array_values( array_filter( array_map( 'absint', $course ) ) )
			: array( absint( $course ) );

		if ( empty( $course_ids ) ) {
			return $count;
		}

		$placeholders_users   = implode( ',', array_fill( 0, count( $demo_user_ids ), '%d' ) );
		$placeholders_courses = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
		$args                 = array_merge(
			array_map( 'intval', $demo_user_ids ),
			$course_ids
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$demo_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items
				 WHERE user_id IN ({$placeholders_users})
				   AND item_id IN ({$placeholders_courses})
				   AND ( status = 'active' OR status = 'enrolled' )",
				...$args
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return max( 0, $count - $demo_count );
	},
	10,
	2
);

/**
 * Prevent the demo student from receiving password-reset emails.
 * The account is only accessible via the signed preview-token flow.
 */
add_filter(
	'allow_password_reset',
	static function ( $allow, int $user_id ) {
		if ( get_user_meta( $user_id, '_masteriyo_is_demo_student', true ) ) {
			return false;
		}
		return $allow;
	},
	10,
	2
);

/**
 * Block direct username/password logins for demo student accounts.
 *
 * Runs at priority 30 (after WP's own credential check at priority 20).
 * If authentication succeeded for a demo student, reject it.
 */
add_filter(
	'authenticate',
	static function ( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_a( $user, 'WP_User' ) ) {
			return $user;
		}
		if ( get_user_meta( (int) $user->ID, '_masteriyo_is_demo_student', true ) ) {
			return new \WP_Error(
				'masteriyo_auto_created_login',
				__( 'This account cannot be accessed directly.', 'learning-management-system' )
			);
		}
		return $user;
	},
	30,
	3
);
