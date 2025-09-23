<?php

namespace Masteriyo\StarterTemplates\Importers;

use WP_Error;
use WP_REST_Response;

/**
 * Class WidgetsImporter
 *
 * Handles the import of widgets for a demo configuration.
 *
 * @package Masteriyo\StarterTemplates\Importer\Importers
 * @since 2.0.0
 */
class WidgetsImporter {


	/**
	 * Imports widgets based on the provided demo configuration.
	 *
	 * @param array $demo The demo configuration containing widget details.
	 * @return WP_REST_Response|WP_Error The response of the import operation.
	 * @since 2.0.0
	 */
	public function import( $demo ) {
		if ( ! $demo['widgets'] ) {
			return true;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
		}
		$import = $this->processImport( $demo['widgets'], $demo['slug'], $demo, $term_id_map );
		if ( is_wp_error( $import ) ) {
			return new WP_Error( 'import_widget_failed', 'Error importing widget.', array( 'status' => 500 ) );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Widget Imported.',
			),
			200
		);
	}


	/**
	 * Import widget JSON data.
	 *
	 * @global array $wp_registered_sidebars
	 * @param  array $data Widgets
	 * @param  string $demo_id     The ID of demo being imported.
	 * @param  array  $demo_data   The data of demo being imported.
	 * @param  array  $term_id_map   Processed Terms Map
	 * @return WP_Error|array WP_Error on failure, $results on success.
	 * @since 2.0.0
	 */
	public static function processImport( $data, $demo_id, $demo_data, $term_id_map ) {
		global $wp_registered_sidebars;
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'themegrill_widget_import_data_error', __( 'Invalid data.', 'learning-management-system' ) );
		}

		do_action( 'themegrill_widget_importer_before_widgets_import' );
		$data = apply_filters( 'themegrill_before_widgets_import_data', $data );

		$available_widgets = self::available_widgets();
		$widget_instances  = array();
		foreach ( $available_widgets as $widget_data ) {
			$widget_instances[ $widget_data['id_base'] ] = get_option( 'widget_' . $widget_data['id_base'] );
		}

		$results = array();

		foreach ( $data as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id ) {
				continue;
			}

			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';
			} else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets';
				$sidebar_message_type = 'error';
				$sidebar_message      = __( 'Sidebar does not exist in theme (moving widget to Inactive)', 'learning-management-system' );
			}

			$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id;
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = array();

			foreach ( $widgets as $widget_instance_id => $widget ) {
				$fail               = false;
				$id_base            = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

				if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = __( 'Site does not support widget', 'learning-management-system' );
				}

				$widget = json_decode( wp_json_encode( $widget ), true );
				$widget = apply_filters( 'themegrill_widget_import_settings', $widget, $id_base );

				if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {
					$sidebars_widgets = get_option( 'sidebars_widgets' );
					$sidebar_widgets  = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : array();

					$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : array();
					foreach ( $single_widget_instances as $check_id => $check_widget ) {
						if ( in_array( "$id_base-$check_id", $sidebar_widgets ) && (array) $widget === $check_widget ) {
							$fail                = true;
							$widget_message_type = 'warning';
							$widget_message      = __( 'Widget already exists', 'learning-management-system' );
							break;
						}
					}
				}

				if ( ! $fail ) {
					$single_widget_instances   = get_option( 'widget_' . $id_base );
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 );
					$single_widget_instances[] = $widget;

					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );

					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number                             = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					update_option( 'widget_' . $id_base, $single_widget_instances );

					$sidebars_widgets                      = get_option( 'sidebars_widgets' );
					$new_instance_id                       = $id_base . '-' . $new_instance_id_number;
					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id;
					update_option( 'sidebars_widgets', $sidebars_widgets );

					$after_widget_import = array(
						'sidebar'           => $use_sidebar_id,
						'sidebar_old'       => $sidebar_id,
						'widget'            => $widget,
						'widget_type'       => $id_base,
						'widget_id'         => $new_instance_id,
						'widget_id_old'     => $widget_instance_id,
						'widget_id_num'     => $new_instance_id_number,
						'widget_id_num_old' => $instance_id_number,
					);
					do_action( 'themegrill_widget_importer_after_single_widget_import', $after_widget_import );

					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = __( 'Imported', 'learning-management-system' );
					} else {
						$widget_message_type = 'warning';
						$widget_message      = __( 'Imported to Inactive', 'learning-management-system' );
					}
				}

				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : __( 'No Title', 'learning-management-system' );
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;
			}
		}

		do_action( 'themegrill_widget_importer_after_widgets_import', $term_id_map );

		return apply_filters( 'themegrill_widget_import_results', $results );
	}

	/**
	 * Available widgets.
	 *
	 * Gather site's widgets into array with ID base, name, etc.
	 *
	 * @global array $wp_registered_widget_controls
	 * @return array Widget information
	 */
	private static function available_widgets() {
		global $wp_registered_widget_controls;

		$widget_controls   = $wp_registered_widget_controls;
		$available_widgets = array();

		foreach ( $widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}

		return apply_filters( 'themegrill_widget_importer_available_widgets', $available_widgets );
	}
}
