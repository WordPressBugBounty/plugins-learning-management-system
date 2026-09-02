<?php

defined( 'ABSPATH' ) || exit;

use Masteriyo\Database\Migration;
use Masteriyo\FileHandler;

/**
 * Guard the course import folder and remove what the unguarded importer left.
 *
 * A site that already ran the vulnerable importer keeps serving those files
 * until something triggers an import, so the remediation cannot wait for one.
 */
class ProtectCourseImportDir extends Migration {

	/**
	 * Run the migration.
	 */
	public function up() {
		$file_handler = new FileHandler();
		$file_handler->protect_directory( 'import/courses' );

		$import_dir = trailingslashit( wp_upload_dir()['basedir'] ) . MASTERIYO_UPLOAD_DIR . '/import/courses/';
		$guards     = array( '.htaccess', 'index.html' );

		foreach ( (array) glob( $import_dir . '*' ) as $file ) {
			$name = basename( $file );

			if ( in_array( $name, $guards, true ) || 'json' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * Reverse the migration.
	 */
	public function down() {
	}
}
