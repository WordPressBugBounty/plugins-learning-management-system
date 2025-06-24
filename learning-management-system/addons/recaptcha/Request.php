<?php
/**
 * Recaptcha validation request.
 *
 * @since 1.18.2
 * @package \Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha;

defined( 'ABSPATH' ) || exit;


use WP_Error;

class Request {
	/**
	 * Verification URL.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * Make validation request.
	 *
	 * @since 1.18.2
	 *
	 * @param string $secret_key
	 * @param string $token
	 * @param float $score
	 * @param string $remote_ip
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $secret_key, $token, $remote_ip ) {
		$response = wp_remote_post(
			self::URL,
			array(
				'body' => array(
					'secret'   => $secret_key,
					'response' => $token,
					'remoteip' => $remote_ip,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error(
				'recaptcha_request_failed',
				__( 'Recaptcha invalid response code.', 'learning-management-system' )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'recaptcha_request_failed',
				__( 'Recaptcha invalid response.', 'learning-management-system' )
			);
		}

		return $data;
	}

	/**
	 * Validate the recaptcha response.
	 *
	 * @since 1.18.2
	 *
	 * @param string $secret_key
	 * @param string $token
	 * @param float $score
	 * @param string $remote_ip
	 * @param string $default_message
	 *
	 * @return boolean|WP_Error
	 */
	public function validate( $secret_key, $token, $score = 0.5, $remote_ip = '', $default_message = '' ) {
		$data  = $this->fetch( $secret_key, $token, $remote_ip );
		$score = masteriyo_round( floatval( $score ), 1 ) / 10;

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			$error_code = current( $data['error-codes'] );

			return new WP_Error(
				'recaptcha_request_failed',
				$this->error_code_to_message( $error_code, $default_message )
			);
		}

		if ( isset( $data['score'] ) && $data['score'] < $score ) {
			return new WP_Error(
				'recaptcha_request_failed',
				$default_message
			);
		}

		return true;
	}

	/**
	 * Return error message from error code.
	 *
	 * @since 1.18.2
	 *
	 * @param string $error_code
	 * @param string $default_message
	 *
	 * @return string
	 */
	public function error_code_to_message( $error_code, $default_message = '' ) {
		switch ( $error_code ) {
			case 'missing-input-secret':
				return __( 'The secret parameter is missing.', 'learning-management-system' );
			case 'invalid-input-secret':
				return __( 'The secret parameter is invalid or malformed.', 'learning-management-system' );
			case 'missing-input-response':
				return $default_message;
			case 'invalid-input-response':
				return $default_message;
			case 'bad-request':
				return $default_message;
			default:
				return $default_message;
		}
	}
}
