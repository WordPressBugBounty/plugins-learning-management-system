<?php

// As this file autoloads from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Unserialize helpers.
 *
 * @package Masteriyo\Helper
 */

if ( ! function_exists( 'masteriyo_maybe_unserialize' ) ) {
	/**
	 * Unserialize a stored value without instantiating an attacker-chosen class.
	 *
	 * WordPress' maybe_unserialize() calls unserialize() with no class allow-list,
	 * so a crafted object payload in metadata becomes a live instance. A second
	 * pass on the resulting string (the double-unserialize pattern) is the same
	 * sink. This helper:
	 *
	 * - returns non-strings unchanged (so a second pass is a no-op)
	 * - unserializes arrays and scalars
	 * - instantiates only Masteriyo\DateTime — the one class the codebase
	 *   legitimately persists in meta (course cohort dates), a value object
	 *   with no __wakeup/__destruct gadget surface
	 * - leaves every other object payload a string, at any depth: disallowed
	 *   classes come back from unserialize() as __PHP_Incomplete_Class, and a
	 *   payload carrying one anywhere is returned as the original string
	 * - returns a plain (non-serialized) string untouched, whitespace included
	 *
	 * @param mixed $data Value that may be a serialized string.
	 * @return mixed
	 */
	function masteriyo_maybe_unserialize( $data ) {
		if ( ! is_string( $data ) ) {
			return $data;
		}

		// Detect and decode on a trimmed copy, as maybe_unserialize() does, but
		// hand a plain string back exactly as stored.
		$candidate = trim( $data );

		if ( '' === $candidate ) {
			return $data;
		}

		if ( function_exists( 'is_serialized' ) && ! is_serialized( $candidate ) ) {
			return $data;
		}

		if ( ! function_exists( 'is_serialized' ) && ! preg_match( '/^(a|s|i|b|d|O|C):/', $candidate ) && 'N;' !== $candidate ) {
			return $data;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$unserialized = @unserialize( $candidate, array( 'allowed_classes' => array( 'Masteriyo\DateTime' ) ) );

		if ( false === $unserialized && 'b:0;' !== $candidate ) {
			return $data;
		}

		if ( masteriyo_holds_disallowed_object( $unserialized ) ) {
			return $data;
		}

		return $unserialized;
	}
}

if ( ! function_exists( 'masteriyo_holds_disallowed_object' ) ) {
	/**
	 * Whether a decoded value is, or contains at any depth, an object other
	 * than Masteriyo\DateTime (typically a __PHP_Incomplete_Class).
	 *
	 * @param mixed $value Decoded value.
	 * @return bool
	 */
	function masteriyo_holds_disallowed_object( $value ) {
		if ( is_object( $value ) ) {
			return ! $value instanceof \Masteriyo\DateTime;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( masteriyo_holds_disallowed_object( $item ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
