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
	 * - instantiates only Masteriyo\DateTime (course cohort dates) and stdClass
	 *   (matching answers in quiz attempts); neither has a __wakeup/__destruct
	 *   gadget surface
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
		$unserialized = @unserialize( $candidate, array( 'allowed_classes' => array( 'Masteriyo\DateTime', 'stdClass' ) ) );

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
	 * than Masteriyo\DateTime or stdClass (typically a __PHP_Incomplete_Class).
	 * A stdClass and a Masteriyo\DateTime are looked inside, like an array.
	 *
	 * A serialized value can refer to itself (`r:` and `R:` back-references), so
	 * the walk goes at most 512 levels deep and into at most 100,000 arrays and
	 * objects. A value that needs more counts as disallowed. Scalars do not count,
	 * so a long list of them always decodes.
	 *
	 * @param mixed    $value  Decoded value.
	 * @param int|null $budget Arrays and objects left to visit. Callers leave it out.
	 * @param int      $depth  Current depth. Callers leave it out.
	 * @return bool
	 */
	function masteriyo_holds_disallowed_object( $value, &$budget = null, $depth = 0 ) {
		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			return false;
		}

		if ( null === $budget ) {
			$budget = 100000;
		}

		if ( --$budget < 0 || $depth > 512 ) {
			return true;
		}

		if ( is_object( $value ) && ! $value instanceof \Masteriyo\DateTime && 'stdClass' !== get_class( $value ) ) {
			return true;
		}

		// The array cast also lists protected properties, such as Masteriyo\DateTime::$utc_offset, which foreach skips.
		foreach ( (array) $value as $item ) {
			if ( masteriyo_holds_disallowed_object( $item, $budget, $depth + 1 ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_unserialize_import_value' ) ) {
	/**
	 * Decode a meta value read from an import file.
	 *
	 * Exports carry meta as stored in the database, so a value can be a
	 * serialized string. It is decoded with masteriyo_maybe_unserialize(), which
	 * never instantiates a class outside its allow-list. Some meta is stored
	 * serialized twice (the MasterStudy migration's `_download_materials`), so a
	 * decoded serialized string is kept as stored. A value that holds a
	 * disallowed object, directly or inside such a string, or that cannot be
	 * decoded, is refused: saving it would hand it to any later
	 * maybe_unserialize() of that meta.
	 *
	 * @param mixed $value Meta value from the import file.
	 * @return mixed|null The decoded value, or null when it must not be saved.
	 */
	function masteriyo_unserialize_import_value( $value ) {
		$value = masteriyo_maybe_unserialize( $value );

		// masteriyo_maybe_unserialize() returns a string it refuses unchanged. One more
		// level covers readers that decode stored meta a second time.
		if ( is_string( $value ) && is_serialized( $value ) && masteriyo_maybe_unserialize( $value ) === $value ) {
			return null;
		}

		return $value;
	}
}
