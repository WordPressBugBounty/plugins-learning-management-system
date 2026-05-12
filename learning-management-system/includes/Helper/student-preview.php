<?php
/**
 * Student preview helper functions.
 *
 * @since x.x.x
 * @package Masteriyo\Helper
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the raw pipe-delimited payload string for token signing.
 *
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
 * @param string $token     base64-encoded signed token.
 * @param int    $course_id Expected course ID (0 = global preview).
 * @return bool
 */
function masteriyo_validate_student_preview_token( string $token, int $course_id ): bool {
	$decoded = base64_decode( str_pad( $token, strlen( $token ) + ( 4 - strlen( $token ) % 4 ) % 4, "=" ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
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
	if ( (int) $user_id !== get_current_user_id() ) {
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
 * @since x.x.x
 * @return bool
 */
function masteriyo_is_student_preview_mode(): bool {
	return masteriyo_validate_preview_originator_cookie() !== null;
}

/**
 * Persist preview state as a signed browser cookie.
 *
 * @since x.x.x
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
 *
 * @since x.x.x
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
 * @since x.x.x
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
		$data                                          = "{$course_id}|{$user_id}|{$expiry}";
		$target_user_id                                = 0;
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
 * @since x.x.x
 * @return int -1 on failure, 0 for global preview, positive course ID for course-specific.
 */
function masteriyo_validate_student_preview_cookie(): int {
	return masteriyo_validate_student_preview_cookie_for_user( get_current_user_id() );
}

/**
 * Get or create the single site-wide demo student account.
 *
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 *
 * @since x.x.x
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
 *
 * @since x.x.x
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
 * Validate the URL token on the first page load, set the preview cookie,
 * swap the auth session to the demo student, and redirect to the clean URL.
 *
 * Also handles the exit flow: restores the original admin session.
 *
 * Priority 5 on 'init' — runs before handle_learn_page().
 *
 * @since x.x.x
 */
function masteriyo_handle_student_preview_token(): void {

	// -------------------------------------------------------------------------
	// EXIT: clear preview cookies and restore original admin session.
	// No is_user_logged_in() check needed here — we validate via the signed
	// originator cookie, not the current WP auth state.
	// -------------------------------------------------------------------------
	if ( isset( $_GET['mto-exit-student-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$originator = masteriyo_validate_preview_originator_cookie();

		if ( ! $originator ) {
			// Missing or tampered originator: clear any stale preview cookies and redirect safely.
			masteriyo_clear_student_preview_cookie();
			masteriyo_clear_preview_originator_cookie();
			masteriyo_clear_preview_return_to_cookie();
			wp_safe_redirect( is_user_logged_in() ? admin_url() : home_url( '/' ) );
			exit;
		}

		$admin_id      = $originator['admin_id'];
		$session_token = $originator['session_token'];

		// Verify the admin's original session is still active in the DB.
		// If the admin logged out elsewhere or the session expired, we do not
		// resurrect it — fail closed and land on the login screen.
		$sessions     = \WP_Session_Tokens::get_instance( $admin_id );
		$session_live = $sessions->verify( $session_token );

		// Capture return URL before clearing cookies.
		$return_url = '';
		if ( ! empty( $_COOKIE['mto_preview_return_to'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$decoded_url = urldecode( wp_unslash( $_COOKIE['mto_preview_return_to'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// wp_validate_redirect preserves URL fragments and constrains to same-origin.
			$return_url = wp_validate_redirect( $decoded_url, '' );
		}

		masteriyo_clear_student_preview_cookie();
		masteriyo_clear_preview_originator_cookie();
		masteriyo_clear_preview_return_to_cookie();

		// Drop the demo student's auth cookies.
		wp_clear_auth_cookie();

		if ( $session_live ) {
			// Mint fresh auth cookies for the original admin (original session stays
			// valid for other devices; this creates an additional session).
			wp_set_auth_cookie( $admin_id, false, is_ssl() );
			wp_safe_redirect( $return_url ?: admin_url() );
		} else {
			// Admin session was destroyed — redirect to login, pre-filling the return URL.
			wp_safe_redirect( wp_login_url( $return_url ?: admin_url() ) );
		}
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
		$tok_target_user_id                                      = 0;
		$sign_data                                               = "{$tok_course_id}|{$tok_user_id}|{$tok_expiry}";
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
	$session_token = wp_get_session_token();
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
 * @since x.x.x
 * @return bool
 */
function masteriyo_current_user_can_student_preview(): bool {
	return masteriyo_is_current_user_admin() || masteriyo_is_current_user_instructor();
}

/**
 * Exclude demo/preview students from enrolled-user counts so they do not
 * consume course seats or inflate student analytics.
 *
 * @since x.x.x
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$demo_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items
				 WHERE user_id IN ({$placeholders_users})
				   AND item_id IN ({$placeholders_courses})
				   AND ( status = 'active' OR status = 'enrolled' )",
				...$args
			)
		);

		return max( 0, $count - $demo_count );
	},
	10,
	2
);

/**
 * Prevent the demo student from receiving password-reset emails.
 * The account is only accessible via the signed preview-token flow.
 *
 * @since x.x.x
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
 *
 * @since x.x.x
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
