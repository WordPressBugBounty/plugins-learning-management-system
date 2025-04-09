<?php
/**
 * Handles file downloads within the admin area.
 *
 * @since 1.14.0
 *
 * @package Masteriyo
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


use Exception;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Admin file download handler.
 *
 * @since 1.14.0
 */
class AdminFileDownloadHandler {

	/**
	 * Action name for file downloads.
	 *
	 * @since 1.14.0
	 * @var string
	 */
	const FILE_DOWNLOAD_ACTION = 'masteriyo_file_download';

	/**
	 * List of file paths to download.
	 *
	 * @since 1.14.0
	 *
	 * @var array $file_paths [$file_path_id] => $file_path.
	 */
	private static $file_paths = array();

	/**
	 * Registers a file path for download.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path_id Unique ID for the file path.
	 * @param string $file_path    File path to download.
	 */
	public static function register_file_path( string $file_path_id, string $file_path ) {
		self::$file_paths[ $file_path_id ] = $file_path;

		/**
		 * Filters the list of file paths to download.
		 *
		 * @since 1.14.0
		 *
		 * @param array  $file_paths    List of file paths to download.
		 * @param string $file_path_id  Unique ID for the file path.
		 * @param string $file_path     File path to download.
		 */
		self::$file_paths = apply_filters( 'masteriyo_register_file_paths', self::$file_paths, $file_path_id, $file_path );
	}

	/**
	 * Generates the download URL for a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path_id  The file path ID.
	 * @param string $file_name     The file name.
	 *
	 * @return string Download URL.
	 *
	 * @throws Exception If the file path ID is not registered.
	 */
	public static function get_download_url( string $file_path_id, string $file_name ) {
		if ( ! isset( self::$file_paths[ $file_path_id ] ) ) {
			/* translators: placeholder: file path ID. */
			throw new Exception( sprintf( __( 'File path "%s" is not registered', 'learning-management-system' ), $file_path_id ) );
		}

		$download_url = add_query_arg(
			array(
				'action'       => self::FILE_DOWNLOAD_ACTION,
				'nonce'        => wp_create_nonce( self::FILE_DOWNLOAD_ACTION . $file_path_id . $file_name ),
				'file_path_id' => $file_path_id,
				'file_name'    => $file_name,
			),
			admin_url( 'admin-post.php' )
		);

		return $download_url;
	}

	/**
	 * Tries to protect a file path from being downloaded directly.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path.
	 *
	 * @return string Empty string if file is protected, protect instructions if not protected.
	 */
	public static function try_to_protect_file_path( string $file_path ) {
		try {
			$htaccess_path = self::write_htaccess( $file_path );
		} catch ( Throwable $th ) {
			masteriyo_get_logger()->error( $th->getMessage(), array( 'source' => 'admin-file-download' ) );

			return ''; // Fail silently.
		}

		$server_software = self::get_current_server_software();

		switch ( $server_software ) {
			case 'apache':
				return empty( $htaccess_path ) ? '' : self::get_apache_protection_message( $file_path, $htaccess_path );
			case 'nginx':
				return self::get_nginx_protection_message( $file_path );
			default:
				return self::get_generic_protection_message( $file_path );
		}
	}

	/**
	 * Writes an .htaccess file to protect the given directory.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path.
	 *
	 * @return string Path to the .htaccess file or empty if not created.
	 */
	private static function write_htaccess( string $file_path ): string {
		$htaccess_path = $file_path . DIRECTORY_SEPARATOR . '.htaccess';

		if ( file_exists( $htaccess_path ) ) {
			return $htaccess_path;
		}

		if ( ! is_dir( $file_path ) || ! is_writable( $file_path ) ) {
			throw new Exception( 'Directory not writable for .htaccess file creation.' );
		}

		$htaccess_file = fopen( $htaccess_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $htaccess_file ) {
			throw new Exception( 'Unable to create .htaccess file.' );
		}

		fwrite( $htaccess_file, "Order Allow,Deny\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fclose( $htaccess_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $htaccess_path;
	}

	/**
	 * Returns protection message for Apache server.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path    The file path being protected.
	 * @param string $htaccess_path Path to the .htaccess file.
	 *
	 * @return string Apache-specific protection message.
	 */
	private static function get_apache_protection_message( string $file_path, string $htaccess_path ): string {
		return sprintf(
			/* translators: placeholder: file path. */
			__( 'Apache server protection: A .htaccess file has been placed in the directory %s. This file prevents direct access to the contents of that directory.', 'learning-management-system' ),
			esc_html( $file_path )
		) . ' ' . sprintf(
			/* translators: placeholder: .htaccess path. */
			__( 'Path to the .htaccess file: %s', 'learning-management-system' ),
			esc_html( $htaccess_path )
		);
	}

	/**
	 * Returns protection message for Nginx server.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path being protected.
	 *
	 * @return string Nginx-specific protection message.
	 */
	private static function get_nginx_protection_message( string $file_path ): string {
		return sprintf(
			/* translators: placeholder: file path. */
			__( 'Nginx server protection: You will need to add specific rules in your Nginx configuration to prevent direct access to the directory %s. Contact your server administrator for assistance.', 'learning-management-system' ),
			esc_html( $file_path )
		);
	}

	/**
	 * Returns a generic protection message for other server types.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path The file path being protected.
	 *
	 * @return string Generic protection message.
	 */
	private static function get_generic_protection_message( string $file_path ): string {
		return sprintf(
			/* translators: placeholder: file path. */
			__( 'File protection: To protect the directory %s, you may need to configure your web server manually. Please consult your server documentation or contact your server administrator for assistance.', 'learning-management-system' ),
			esc_html( $file_path )
		);
	}

	/**
	 * Returns the current server software name.
	 *
	 * @since 1.14.0
	 *
	 * @return string The server software name.
	 */
	private static function get_current_server_software() {
		$server_software = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? '' ) );

		if ( stripos( $server_software, 'apache' ) !== false ) {
			return 'apache';
		}

		if ( stripos( $server_software, 'nginx' ) !== false ) {
			return 'nginx';
		}

		return 'other';
	}

	/**
	 * Initializes the file download handler.
	 *
	 * @since 1.14.0
	 */
	public static function init() {
		add_action( 'admin_post_' . self::FILE_DOWNLOAD_ACTION, array( self::class, 'handle_file_download' ) );
	}

	/**
		 * Handles the file download action.
		 *
		 * @since 1.14.0
		 */
	public static function handle_file_download() {
		$file_path_id = filter_input( INPUT_GET, 'file_path_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$file_name    = filter_input( INPUT_GET, 'file_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$nonce        = filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! wp_verify_nonce( sanitize_key(wp_unslash($nonce)), self::FILE_DOWNLOAD_ACTION . $file_path_id . $file_name ) ) {
			self::send_error( __( 'URL expired. Please refresh the page and try again.', 'learning-management-system' ) );
		}

		if ( ! isset( self::$file_paths[ $file_path_id ] ) ) {
			self::send_error( __( 'Invalid URL.', 'learning-management-system' ) );
		}

		$is_admin_or_manager             = masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager();
		$is_instructor_exporting_courses = ( 'export_courses' === $file_path_id && masteriyo_is_current_user_instructor() );

		if ( ! $is_admin_or_manager && ! $is_instructor_exporting_courses ) {
			self::send_error( __( 'You do not have sufficient permissions to download this file.', 'learning-management-system' ) );
		}

		$file_path = self::$file_paths[ $file_path_id ] . DIRECTORY_SEPARATOR . $file_name;
		if ( ! file_exists( $file_path ) ) {
			self::send_error( __( 'File does not exist.', 'learning-management-system' ) );
		}

		self::send_file( $file_path );
	}

	/**
	 * Sends the error message and exits.
	 *
	 * @since 1.14.0
	 *
	 * @param string $message Error message.
	 */
	private static function send_error( string $message ) {
		echo esc_html( $message );
		exit;
	}

	/**
	 * Sends the file for download.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path Path of the file to download.
	 */
	private static function send_file( string $file_path ) {
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . basename( $file_path ) );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile -- readfile is faster
		exit;
	}
}
