<?php
/**
 * Abstract AI provider.
 *
 * @package Masteriyo\AI
 */

namespace Masteriyo\AI;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Base class for AI text-generation backends.
 *
 * A provider implements one operation — generate text from a prompt, in plain
 * or JSON mode. The base class owns the retry policy and the JSON handling;
 * subclasses talk to their backend and normalize its failures to the
 * masteriyo_ai_* error codes:
 *
 * - masteriyo_ai_quota_exceeded     The backend rate-limited the request.
 * - masteriyo_ai_invalid_credentials The backend rejected the credentials.
 * - masteriyo_ai_request_failed     Transport failure or any other backend error.
 * - masteriyo_ai_empty_response     No usable content after all attempts (base class).
 */
abstract class Provider {

	/**
	 * Whether the provider is ready to serve requests.
	 *
	 * @return bool
	 */
	abstract public function is_configured();

	/**
	 * Run one generation attempt against the backend.
	 *
	 * @param string $prompt       The prompt.
	 * @param bool   $expects_json Whether the caller expects a JSON payload back.
	 * @param array  $options      The caller's options, verbatim. `max_attempts`
	 *                             is consumed by the base class; providers read
	 *                             the keys they understand and ignore the rest.
	 *                             Options must stay provider-neutral — a key one
	 *                             backend requires and another cannot honor
	 *                             breaks substitutability.
	 *
	 * @return string|WP_Error The generated text ('' when the backend answered
	 *                         with no content), or a masteriyo_ai_* WP_Error.
	 */
	abstract protected function do_generate( $prompt, $expects_json, $options );

	/**
	 * Generate plain text.
	 *
	 * @param string $prompt  The prompt.
	 * @param array  $options Optional. `max_attempts` (int, default 3).
	 *
	 * @return string|WP_Error
	 */
	public function generate_text( $prompt, $options = array() ) {
		return $this->generate( $prompt, false, $options );
	}

	/**
	 * Generate a JSON payload and decode it.
	 *
	 * @param string $prompt  The prompt.
	 * @param array  $options Optional. `max_attempts` (int, default 3).
	 *
	 * @return array|WP_Error
	 */
	public function generate_json( $prompt, $options = array() ) {
		return $this->generate( $prompt, true, $options );
	}

	/**
	 * Retry loop: errors return immediately; empty text — and in JSON mode,
	 * text that does not decode — retries until the attempts are spent.
	 *
	 * @param string $prompt       The prompt.
	 * @param bool   $expects_json Whether the caller expects a JSON payload back.
	 * @param array  $options      Options, see generate_text().
	 *
	 * @return string|array|WP_Error Text, the decoded array in JSON mode, or
	 *                               a masteriyo_ai_* WP_Error.
	 */
	private function generate( $prompt, $expects_json, $options ) {
		$max_attempts = isset( $options['max_attempts'] ) ? max( 1, (int) $options['max_attempts'] ) : 3;

		for ( $attempt = 0; $attempt < $max_attempts; $attempt++ ) {
			$text = $this->do_generate( $prompt, $expects_json, $options );

			if ( is_wp_error( $text ) ) {
				return $text;
			}

			if ( $expects_json ) {
				$decoded = masteriyo_ai_decode_json( $text );

				if ( is_array( $decoded ) ) {
					return $decoded;
				}
			} elseif ( '' !== trim( (string) $text ) ) {
				return $text;
			}
		}

		return new WP_Error(
			'masteriyo_ai_empty_response',
			__( 'The AI service returned an empty response. Please try again.', 'learning-management-system' )
		);
	}
}
