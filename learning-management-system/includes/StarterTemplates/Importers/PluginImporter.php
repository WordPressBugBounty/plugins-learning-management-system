<?php

namespace Masteriyo\StarterTemplates\Importers;

use Exception;

/**
 * Class PluginImporter
 *
 * Handles the installation and activation of plugins.
 *
 * @package Masteriyo\StarterTemplates\Importer\Importers
 * @since 3.0.0
 */
class PluginImporter {

	/**
	 * PluginImporter constructor.
	 *
	 * Initializes the required files for plugin installation and activation.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Includes required WordPress files for plugin installation and activation.
	 *
	 * @since 3.0.0
	 */
	public function includes() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	/**
	 * Installs and activates a list of plugins.
	 *
	 * Silently drops Masteriyo's own basenames (free and pro) since a template's remote plugin
	 * list isn't guaranteed to name the slug actually running, and Masteriyo is always active already.
	 *
	 * @param array $plugins List of plugin slugs to install and activate.
	 * @return array An array containing the installation results of each plugin.
	 * @since 3.0.0
	 */
	public function installPlugins( $plugins ) {
		$masteriyo_basenames = array( 'learning-management-system/lms.php', 'learning-management-system-pro/lms.php' );
		$plugins             = array_values(
			array_filter(
				$plugins,
				function ( $plugin ) use ( $masteriyo_basenames ) {
					return ! in_array( $plugin, $masteriyo_basenames, true );
				}
			)
		);

		return array_map(
			function ( $plugin ) {
				return $this->installActivatePlugin( $plugin );
			},
			$plugins
		);
	}

	/**
	 * Installs and activates a single plugin.
	 *
	 * @param string $plugin The plugin slug.
	 * @return array The result of the plugin installation and activation.
	 * @since 3.0.0
	 */
	private function installActivatePlugin( $plugin ) {
		$pg          = explode( '/', $plugin );
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$results     = array();
		if ( 'companion-elementor/companion-elementor.php' === $plugin ) {
			$response = apply_filters( 'tgda_install_companion_elementor', 'companion-elementor/companion-elementor.php' );
			if ( is_array( $response ) ) {
				if ( isset( $response['success'] ) && $response['success'] ) {
					$results[ $pg[0] ] = array(
						'status'  => 'success',
						'message' => __( 'Companion Elementor installed and activated.', 'learning-management-system' ),
					);
				} else {
					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $response['message'],
					);
				}
			} else {
				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => 'Failed to install Companion Elementor.',
				);
			}
		} else {
			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file );

				if ( is_plugin_active( $plugin ) ) {
					$results[ $pg[0] ] = array(
						'status'  => 'success',
						'message' => $plugin_data['Name'] . ' already activated.',
					);
					return $results;
				}
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					);
				}
				$results[ $pg[0] ] = array(
					'status'  => 'success',
					'message' => $plugin_data['Name'] . ' activated.',
				);
				return $results;
			}
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
				)
			);
			if ( is_wp_error( $api ) ) {
				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $api->get_error_message(),
				);

				return $results;

			}

			$skin      = new \WP_Ajax_Upgrader_Skin();
			$upgrader  = new \Plugin_Upgrader( $skin );
			$installed = $upgrader->install( $api->download_link );

			if ( is_wp_error( $installed ) ) {
				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $installed->get_error_message(),
				);
				return $results;

			}

			$install_status = install_plugin_install_status( $api );

			if ( is_plugin_inactive( $install_status['file'] ) ) {
				$result = activate_plugin( $install_status['file'] );

				if ( is_wp_error( $result ) ) {
					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					);
					return $results;

				}
			}
			$results[ $pg[0] ] = array(
				'status'  => 'success',
				'message' => sprintf(
				/* translators: %s: plugin name */
					__( '%s installed and activated.', 'learning-management-system' ),
					$api->name
				),
			);
		}
		return $results;
	}
}
