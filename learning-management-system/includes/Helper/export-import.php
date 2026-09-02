<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Export/Import helper functions.
 *
 * @since 1.6.13
 * @package Masteriyo\Helper
 */


if ( ! function_exists( 'masteriyo_csv_formula_triggers' ) ) {
	/**
	 * Characters that make a spreadsheet read a cell as a formula.
	 *
	 * @return string[]
	 */
	function masteriyo_csv_formula_triggers() {
		return array( '=', '+', '-', '@', "\t", "\r" );
	}
}

if ( ! function_exists( 'masteriyo_escape_csv_formula' ) ) {
	/**
	 * Neutralizes a spreadsheet formula in a CSV cell.
	 *
	 * @param string $value The value to escape.
	 * @return string
	 */
	function masteriyo_escape_csv_formula( $value ) {
		$value = (string) $value;

		if ( '' === $value || ! in_array( $value[0], masteriyo_csv_formula_triggers(), true ) ) {
			return $value;
		}

		return "'" . $value;
	}
}

if ( ! function_exists( 'masteriyo_unescape_csv_formula' ) ) {
	/**
	 * Removes the guard masteriyo_escape_csv_formula() writes, so an export can be imported again.
	 *
	 * A leading quote is kept unless a formula trigger follows it, which is the only
	 * sequence the exporter produces.
	 *
	 * @param string $value The value to unescape.
	 * @return string
	 */
	function masteriyo_unescape_csv_formula( $value ) {
		$value = (string) $value;

		if ( 2 > strlen( $value ) || "'" !== $value[0] ) {
			return $value;
		}

		return in_array( $value[1], masteriyo_csv_formula_triggers(), true ) ? substr( $value, 1 ) : $value;
	}
}

if ( ! function_exists( 'masteriyo_get_filesystem_and_folder' ) ) {
	/**
	 * A helper method to reduce code repetition.
	 * Gets the filesystem and the export folder.
	 *
	 * @return array The filesystem and the export folder.
	 */
	function masteriyo_get_filesystem_and_folder() {
		$filesystem    = masteriyo_get_filesystem();
		$upload_dir    = wp_upload_dir();
		$export_folder = $upload_dir['basedir'] . '/masteriyo';

		if ( $filesystem && ! $filesystem->is_dir( $export_folder ) ) {
			$filesystem->mkdir( $export_folder );
		}

		return array( $filesystem, $export_folder );
	}
}
