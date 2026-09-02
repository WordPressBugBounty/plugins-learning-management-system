<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Depreciated functions.
 *
 * @since 1.5.12
 */

if ( ! function_exists( 'masteriyo_openai_retry' ) ) {
	/**
	 * Retry a callable returning a raw OpenAI chat/completions response and
	 * extract the message content.
	 *
	 * Kept for third-party callers; core generation goes through
	 * masteriyo_ai() and the retry loop on \Masteriyo\AI\Provider.
	 *
	 * @since 1.6.15
	 * @deprecated Use masteriyo_ai()->generate_text() instead.
	 *
	 * @param callable $func The function to execute and retry.
	 * @param array $args The arguments for the function.
	 * @param int $max_attempts Maximum number of attempts before giving up.
	 *
	 * @return mixed
	 */
	function masteriyo_openai_retry( $func, $args = array(), $max_attempts = 3 ) {
		masteriyo_deprecated_function( 'masteriyo_openai_retry', 'x.x.x', 'masteriyo_ai()->generate_text()' );

		$attempts      = 0;
		$response_text = '';

		do {
			$response = call_user_func_array( $func, $args );

			if ( is_null( $response ) || is_wp_error( $response ) ) {
				return $response;
			}

			$response_data = json_decode( $response, true );

			if ( is_array( $response_data ) && isset( $response_data['choices'][0]['message']['content'] ) ) {
				$response_text = $response_data['choices'][0]['message']['content'];
			}

			++$attempts;

		} while ( empty( $response_text ) && $attempts < $max_attempts );

		return $response_text;
	}
}

if ( ! function_exists( 'masteriyo_get_course_access_modes' ) ) {
	/**
	 * Get masteriyo access modes.
	 *
	 * @since 1.0.0
	 * @deprecated 1.5.12
	 * @return string
	 */
	function masteriyo_get_course_access_modes() {
		masteriyo_deprecated_function( 'masteriyo_get_course_access_modes', '1.5.12', 'CourseAccessMode:all()' );

		/**
		 * Filters course access modes.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $access_modes Course access modes.
		 */
		return apply_filters(
			'masteriyo_course_access_modes',
			array(
				'open',
				'need_registration',
				'one_time',
				'recurring',
				'close',
			)
		);
	}
}
