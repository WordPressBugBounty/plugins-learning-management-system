<?php
/**
 * Full site import export controller class.
 *
 * @package Masteriyo\RestApi\Controllers\Version1
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Exporter\SiteExporter;
use Masteriyo\Importer\SiteImporter;

/**
 * Exports and imports settings, users and courses as one archive.
 *
 * Reuses the course import's chunked upload for the archive.
 */
class SiteImportExportController extends CoursesImportExportController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'site';

	/**
	 * Upload folder for chunked imports, relative to the Masteriyo upload directory.
	 *
	 * @var string
	 */
	protected $import_folder = SiteImporter::IMPORT_DIRECTORY;

	/**
	 * The only file extension an import may have.
	 *
	 * @var string
	 */
	protected $import_extension = 'zip';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'export_items' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
				'args'                => array(
					'types' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => array_keys( SiteExporter::ENTRIES ),
						),
						'minItems'    => 1,
						'uniqueItems' => true,
						'default'     => array_keys( SiteExporter::ENTRIES ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export/progress-status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_export_progress_status' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import/progress-status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_import_progress_status' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_items' ),
				'permission_callback' => array( $this, 'import_items_permission_check' ),
			)
		);
	}

	/**
	 * Export the requested types as one archive.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function export_items( \WP_REST_Request $request ) {
		return rest_ensure_response( ( new SiteExporter() )->export( $request->get_param( 'types' ) ) );
	}

	/**
	 * Report on the current user's export.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_export_progress_status() {
		return rest_ensure_response( ( new SiteExporter() )->get_progress_status() );
	}

	/**
	 * Report on the courses part of the current user's import.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_import_progress_status() {
		return rest_ensure_response( ( new SiteImporter() )->get_progress_status() );
	}

	/**
	 * Only administrators may move a whole site: the archive holds every user and every setting.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|true
	 */
	public function import_items_permission_check( $request ) {
		if ( masteriyo_is_current_user_admin() ) {
			return true;
		}

		return new \WP_Error(
			'masteriyo_rest_cannot_import',
			__( 'Sorry, you are not allowed to perform this action.', 'learning-management-system' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Import an uploaded archive.
	 *
	 * @param string $file Path to the uploaded archive.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	protected function import_file( string $file ) {
		return ( new SiteImporter() )->import( $file );
	}
}
