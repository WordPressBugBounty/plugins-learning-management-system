<?php
/**
 * Login Session Management.
 *
 * @since 1.9.3
 */


if ( ! function_exists( 'masteriyo_get_current_device_info' ) ) {
	/**
	 * Gives the user device information.
	 *
	 * @param object $user User Object/WPError.
	 *
	 * @since  1.9.3
	 *
	 * @return object User object or error object.
	 */
	function masteriyo_get_current_device_info() {
		$u_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$device   = 'Desktop'; // Assume desktop as default
		$platform = 'Linux';
		$bname    = 'Unknown';

		if ( preg_match( '/mobile/i', $u_agent ) ) {
			$device = 'Mobile';
		} elseif ( preg_match( '/tablet/i', $u_agent ) ) {
			$device = 'Tablet';
		}

		if ( preg_match( '/ios/i', $u_agent ) ) {
			$platform = 'iOS';
		} elseif ( preg_match( '/android/i', $u_agent ) ) {
			$platform = 'Android';
		} elseif ( preg_match( '/windows/i', $u_agent ) ) {
			$platform = 'Windows';
		} elseif ( preg_match( '/mac/i', $u_agent ) ) {
			$platform = 'Mac';
		}

		return array(
			'device'  => $device,
			'os'      => $platform,
			'browser' => $bname,
		);
	}
}

if ( ! function_exists( 'masteriyo_wp_is_login' ) ) {
	/**
	 * Checks current page is WordPress login page or not.
	 *
	 * @since 1.9.4
	 */
	function masteriyo_wp_is_login() {
		return false !== stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] );
	}
}

if ( ! function_exists( 'masteriyo_check_user_session' ) ) {
	/**
	 * Validate if the maximum active logins limit reached.
	 *
	 * @param object $user User Object/WPError.
	 *
	 * @since  1.9.3
	 *
	 * @return object User object or error object.
	 */
	function masteriyo_check_user_session( $user ) {
		if ( is_wp_error( $user ) ) {
			$error_message = $user->get_error_message();
			return;
		}

		$user_id = $user->ID;

		if ( in_array( 'administrator', $user->roles ) ) {
			return true;
		}

		$masteriyo_max_active_login = masteriyo_get_setting( 'advance.limit_login_session' );
		// Get current user's session.
		$sessions = WP_Session_Tokens::get_instance( $user_id );
		// Get all his active WordPress sessions.
		$all_sessions = $sessions->get_all();

		if ( ! $masteriyo_max_active_login || count( $all_sessions ) < $masteriyo_max_active_login ) {

			$current_user_session_info = array();
			//sorting descending order.
			usort(
				$all_sessions,
				function( $a, $b ) {
					return $b['login'] - $a['login'];
				}
			);

		} else {
			if ( masteriyo_wp_is_login() && ! in_array( 'administrator', $user->roles ) ) {
				$error_message = __( 'User session limit, Please Clear Your Login Session.', 'learning-management-system' );
				return new \WP_Error( 'user_session_limit', $error_message );
			}

			$info_token = wp_rand( 100000000000, 999999999999 );

			update_user_meta( $user_id, 'mas_session_token', $info_token );
			wp_send_json_success(
				array(
					'user_id'           => $user->ID,
					'mas_session_token' => $info_token,
					'_wpnonce'          => wp_create_nonce( 'masteriyo_clear_sessions' ),
				)
			);
		}

	}
}

if ( ! function_exists( 'masteriyo_session_information' ) ) {
	/**
		 * Provides the session information based on current user session token.
		 *
		 * @since  1.9.3
		 */
	function masteriyo_session_information() {
		$token                     = wp_get_session_token();
		$sessions                  = WP_Session_Tokens::get_instance( masteriyo_get_current_user_id() );
		$session_info              = $sessions->get( $token );
		$current_user_session_info = array();
		$date_format               = 'F j,Y H:i a';
		$current_user_session_info['human_readable_date'] = $session_info['login'] ? date_i18n( $date_format, $session_info['login'] ) : null;
		$current_user_session_info['device_info']         = masteriyo_get_current_device_info();
		$current_user_session_info['token']               = $token;

		return $current_user_session_info;
	}
}

if ( ! function_exists( 'masteriyo_get_all_session_data' ) ) {
	/**
	 * Provides all the session data of the current user.
	 * @since  1.9.3
	 */
	function masteriyo_get_all_session_data( $user_id = null ) {

		$results            = masteriyo_get_all_session_info_current_user( $user_id );
		$user_sessions_info = array(); // Array to store converted data

		$current_token = wp_get_session_token();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
					$session_data = unserialize( $result->meta_value );

					// Combine with other meta info
					$session_data['umeta_id'] = $result->umeta_id;
					$session_data['user_id']  = $result->user_id;
					$session_data['meta_key'] = $result->meta_key;
				if ( $current_token === $session_data['token'] ) {
					$session_data['is_current'] = true;
				}
				$user_sessions_info[] = $session_data;
			}
		}

		return $user_sessions_info;
	}
}

if ( ! function_exists( 'masteriyo_delete_session_info' ) ) {
	/**
	 * Deletes the session information data.
	 *
	 * @since  1.9.3
	 */
	function masteriyo_delete_session_info( $session_data ) {
		global $wpdb;
		$umeta_id = sanitize_text_field( $session_data['umeta_id'] );
		$meta_key = sanitize_text_field( $session_data['meta_key'] );

		if ( ! empty( $umeta_id ) ) {
			// Approach 1: Delete by umeta_id (Most efficient)
			$wpdb->delete(
				$wpdb->usermeta,
				array( 'umeta_id' => $umeta_id )
			);

		} else {
			// Approach 2: Delete by meta_key (Use as a fallback if umeta_id not available)
			$wpdb->delete(
				$wpdb->usermeta,
				array( 'meta_key' => $meta_key )
			);
		}
	}
}


if ( ! function_exists( 'masteriyo_delete_session_info_current_user' ) ) {
	/**
	 * Deletes the session information data when user logs out.
	 *
	 * @since  1.9.3
	 */
	function masteriyo_delete_session_info_current_user() {
		$get_all_session_info = masteriyo_get_all_session_info_current_user();

		if ( ! empty( $get_all_session_info ) ) {

			foreach ( $get_all_session_info as $result ) {
				$session_data  = unserialize( $result->meta_value );
				$current_token = wp_get_session_token();

				//get umeta_id and meta_key to use to delete session info.
				$session_data['umeta_id'] = $result->umeta_id;
				$session_data['meta_key'] = $result->meta_key;

				if ( $current_token === $session_data['token'] ) {
					masteriyo_delete_session_info( $session_data );
				}
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_get_all_session_info_current_user' ) ) {
	/**
	 * Gets all the session information for current user.
	 *
	 * @since  1.9.3
	 */
	function masteriyo_get_all_session_info_current_user( $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		$search_pattern = $user_id . '_session_%';

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
				$search_pattern
			)
		);

		return $results;
	}
}
