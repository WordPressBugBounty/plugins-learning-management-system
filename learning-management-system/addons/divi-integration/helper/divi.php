<?php
/**
 * Helper functions for Divi Integration.
 */

if ( ! function_exists( 'masteriyo_divi_get_user_selected_values' ) ) {
	function masteriyo_divi_get_user_selected_values( $available_options, $selected_options ) {
		$available_options_id = array_keys( $available_options );
		$selected_options     = array_filter(
			$selected_options,
			function( $option ) {
				if ( 'on' === $option ) {
					return $option;
				}
			}
		);

		$selected_option_ids = array();
		foreach ( $selected_options as $k => $v ) {
			array_push( $selected_option_ids, $available_options_id[ $k ] );
		}
		return $selected_option_ids;
	}
}
