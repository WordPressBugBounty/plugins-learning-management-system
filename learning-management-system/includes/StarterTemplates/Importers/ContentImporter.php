<?php

namespace Masteriyo\StarterTemplates\Importers;

use Masteriyo\StarterTemplates\Importers\WXRImporter\WXRImporter;

use WP_Error;
use WP_Query;
use WP_REST_Response;

/**
 * Class ContentImporter
 *
 * Handles the import of demo content, including XML content and core options.
 *
 * @package Masteriyo\StarterTemplates\Importer\Importers
 * @since 2.0.0
 */
class ContentImporter {


	/**
	 * Imports content based on the provided demo configuration and pages.
	 *
	 * @param array $demo The demo configuration containing content details.
	 * @param array $pages List of pages to import.
	 * @return WP_REST_Response|WP_Error The response of the import operation.
	 * @since 2.0.0
	 */
	public function import( $demo, $pages ) {
		do_action( 'themegrill_ajax_before_demo_import' );

		if ( $pages ) {
			foreach ( $pages as $page ) {
				$this->import_xml( $page['content'] );
			}
		} else {
			$content = $demo['content'];
			if ( ! $content ) {
				return new WP_Error( 'no_content_file', 'No content file.', array( 'status' => 500 ) );
			}
			$response = $this->import_xml( $content );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}
		$this->import_core_options( $demo );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Content Imported.',
			),
			200
		);
	}

	/**
	 * Imports the XML content using the WXR Importer.
	 *
	 * @param string $content The XML content to import.
	 * @return bool|WP_Error True on success or WP_Error on failure.
	 * @since 2.0.0
	 */
	public function import_xml( $content ) {
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		ob_start();
		$importer = new WXRImporter();
		$data     = $importer->import( $content );
		ob_end_clean();

		update_option( 'themegrill_demo_importer_mapping', $importer->get_mapping_data() );

		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'import_content_failed', 'Error importing content:' . $data->get_error_message(), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Imports core options such as front page and posts page.
	 *
	 * @param array $demo The demo configuration.
	 * @return bool True on success.
	 * @since 2.0.0
	 */

	public function import_core_options( $demo ) {
		$show_on_front  = $demo['show_on_front'] ?? '';
		$page_on_front  = $demo['page_on_front'] ?? '';
		$page_for_posts = $demo['page_for_posts'] ?? '';
		if ( $show_on_front ) {
			if ( in_array( $show_on_front, array( 'posts', 'page' ), true ) ) {
				update_option( 'show_on_front', $show_on_front );
			}
		}

		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		if ( $page_on_front ) {
			$page_on_front_remapped_id = ! empty( $mapping_data['post'][ $page_on_front ] ) ? $mapping_data['post'][ $page_on_front ] : $page_on_front;
			if ( get_post_status( $page_on_front_remapped_id ) === 'publish' ) {
				update_option( 'page_on_front', $page_on_front_remapped_id );
			}
		}
		if ( $page_for_posts ) {
			$page_for_posts_remapped_id = ! empty( $mapping_data['post'][ $page_for_posts ] ) ? $mapping_data['post'][ $page_for_posts ] : $page_for_posts;
			if ( get_post_status( $page_for_posts_remapped_id ) === 'publish' ) {
				update_option( 'page_for_posts', $page_for_posts_remapped_id );
				update_option( 'show_on_front', 'page' );
			}
		}

		return true;
	}

	/**
	 * Retrieves a page by its title.
	 *
	 * @param string $title The title of the page.
	 * @return WP_Post|null The page object if found, null otherwise.
	 * @since 2.0.0
	 */
	public function get_page_by_title( $title ) {
		if ( ! $title ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		return current( $query->posts );
	}
}
