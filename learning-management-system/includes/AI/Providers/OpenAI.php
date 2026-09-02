<?php
/**
 * OpenAI chat/completions provider.
 *
 * @package Masteriyo\AI
 */

namespace Masteriyo\AI\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\AI\Provider;
use WP_Error;

/**
 * AI provider backed by the OpenAI chat/completions endpoint.
 *
 * Credentials and model selection come from the advance.openai.* settings;
 * the request body is shaped by masteriyo_openai_request_data(), so the
 * model registry (masteriyo_openai_models()) stays the single source of
 * truth for per-model params.
 */
class OpenAI extends Provider {

	/**
	 * Whether an API key is configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== trim( $this->get_api_key() );
	}

	/**
	 * Send one chat/completions request and extract the message content.
	 *
	 * @param string $prompt       The prompt.
	 * @param bool   $expects_json Whether the caller expects a JSON payload back.
	 * @param array  $options      The caller's options; none apply here yet.
	 *
	 * @return string|WP_Error
	 */
	protected function do_generate( $prompt, $expects_json, $options ) {
		$body = masteriyo_openai_request_data( $expects_json );

		$body['messages'] = array(
			array(
				'role'    => 'system',
				'content' => 'You are an expert course content creator.',
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);

		// A null param (from the masteriyo_openai_models filter) means "do not
		// send this key", never a JSON null — same contract as the old builder.
		$body = array_filter(
			$body,
			function ( $value ) {
				return null !== $value;
			}
		);

		$response = wp_remote_request(
			'https://api.openai.com/v1/chat/completions',
			array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $body ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_api_key(),
				),
				'timeout' => 120, // Reasoning models routinely exceed the old 30s limit.
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->transport_error( $response );
		}

		$code          = (int) wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			return $this->http_error( $code, $response_body );
		}

		$decoded = json_decode( $response_body, true );

		if ( is_array( $decoded ) && isset( $decoded['choices'][0]['message']['content'] ) ) {
			return (string) $decoded['choices'][0]['message']['content'];
		}

		return '';
	}

	/**
	 * The configured API key.
	 *
	 * @return string
	 */
	private function get_api_key() {
		$api_key = masteriyo_get_setting( 'advance.openai.api_key' );

		return is_string( $api_key ) ? $api_key : '';
	}

	/**
	 * Normalize a transport-level failure.
	 *
	 * @param WP_Error $error The wp_remote_request() error.
	 *
	 * @return WP_Error
	 */
	private function transport_error( $error ) {
		$message = $error->get_error_message();

		if ( mb_stristr( $message, 'curl error 28' ) ) {
			$message .= '. ' . __( 'You might have tried to generate too much content. Please try again with a maximum limit. If the issue persists, please check your server or service status.', 'learning-management-system' );
		}

		return new WP_Error( 'masteriyo_ai_request_failed', $message );
	}

	/**
	 * Normalize a non-200 response.
	 *
	 * @param int    $code The HTTP status code.
	 * @param string $body The response body with error details in JSON format.
	 *
	 * @return WP_Error
	 */
	private function http_error( $code, $body ) {
		$decoded = json_decode( $body, true );
		$message = 'Unknown error occurred.';

		if ( is_array( $decoded ) && isset( $decoded['error']['message'] ) ) {
			$message = $decoded['error']['message'];
		}

		if ( 429 === $code ) {
			$error_code = 'masteriyo_ai_quota_exceeded';
		} elseif ( 401 === $code || 403 === $code ) {
			$error_code = 'masteriyo_ai_invalid_credentials';
		} else {
			$error_code = 'masteriyo_ai_request_failed';
		}

		return new WP_Error( $error_code, $message, array( 'status' => $code ) );
	}
}
