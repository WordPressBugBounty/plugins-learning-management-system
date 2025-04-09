<?php
/**
 * Handles file management operations.
 *
 * @since 1.14.0
 *
 * @package Masteriyo
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * File management class.
 *
 * Handles file management operations like creating files, copying, deleting, and listing files.
 *
 * @since 1.14.0
 */
class FileHandler {

	/**
	 * Base directory for file operations.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $base_dir;

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 *
	 * @param string|null $base_dir Optional base directory.
	 */
	public function __construct( $base_dir = null ) {
		$upload_dir     = wp_upload_dir();
		$this->base_dir = $base_dir ? trailingslashit( $base_dir ) : trailingslashit( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . MASTERIYO_UPLOAD_DIR );

		$this->initialize_base_dir();
	}

	/**
	 * Initialize base directory if it doesn't exist.
	 *
	 * @since 1.14.0
	 */
	protected function initialize_base_dir() {
		if ( ! is_dir( $this->base_dir ) ) {
			$result = wp_mkdir_p( $this->base_dir );
			if ( ! $result ) {
				$this->log_error( 'Unable to create directory ' . $this->base_dir );
			}
		}
	}

	/**
	 * Get the WordPress FileSystem object.
	 *
	 * @since 1.14.0
	 *
	 * @return \WP_Filesystem_Direct|WP_Error
	 */
	protected function get_filesystem() {
		$filesystem = masteriyo_get_filesystem();

		if ( ! $filesystem ) {
			return new WP_Error( 'filesystem_failed', __( 'Filesystem failed to initialize', 'learning-management-system' ) );
		}

		return $filesystem;
	}

	/**
	 * Create a file in a custom folder structure.
	 *
	 * @since 1.14.0
	 *
	 * @param string $folder_structure Custom folder structure.
	 * @param string $filename File name.
	 * @return array|WP_Error
	 */
	public function create_file( $folder_structure, $filename ) {
		$filesystem = $this->get_filesystem();

		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$folder_path = trailingslashit( $this->base_dir . $folder_structure );
		$file_path   = $folder_path . $filename;

		$this->create_directory_recursive( $folder_path );

		if ( ! $filesystem->touch( $file_path ) ) {
			$this->log_error( 'Failed to create file: ' . $file_path );
			return new WP_Error( 'create_failed', __( 'Failed to create file', 'learning-management-system' ) );
		}

		return array(
			'file_path' => $file_path,
			'file_url'  => $this->get_file_url( $file_path ),
			'filename'  => $filename,
		);
	}

	/**
	 * Create directories recursively.
	 *
	 * @since 1.14.0
	 *
	 * @param string $path Directory path.
	 */
	protected function create_directory_recursive( $path ) {
		if ( ! is_dir( $path ) ) {
			$result = wp_mkdir_p( $path );
			if ( ! $result ) {
				$this->log_error( 'Failed to create directory: ' . $path );
			}
		}
	}

	/**
	 * Delete a file or folder.
	 *
	 * @since 1.14.0
	 *
	 * @param string $path Path relative to base_dir.
	 * @param string $file_or_folder_name  File or folder name.
	 * @param bool $recursive Whether to delete recursively (for directories).
	 * @param bool $type Whether to delete a file or folder.
	 * @return bool|WP_Error
	 */
	public function delete( $path, $file_or_folder_name, $recursive = false, $type = false ) {
		$filesystem = $this->get_filesystem();

		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		if ( empty( $path ) ) {
			$full_path = $this->base_dir . ltrim( $file_or_folder_name, DIRECTORY_SEPARATOR );
		} else {
			$full_path = $this->base_dir . trim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . ltrim( $file_or_folder_name, DIRECTORY_SEPARATOR );
		}

		if ( ! $filesystem->exists( $full_path ) ) {
			return new WP_Error( 'file_not_found', __( 'File or directory not found', 'learning-management-system' ) );
		}

		return $filesystem->delete( $full_path, $recursive, $type );
	}

	/**
	 * Get list of files in a specific folder (optionally recursive).
	 *
	 * @since 1.14.0
	 *
	 * @param string $folder Folder path relative to base directory.
	 * @param bool $include_hidden Whether to include hidden files.
	 * @param bool $recursive Whether to list files recursively.
	 * @return array|WP_Error
	 */
	public function list_files( $folder = '', $include_hidden = true, $recursive = false ) {
		$filesystem = $this->get_filesystem();

		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		if ( empty( $folder ) ) {
			$path = trailingslashit( $this->base_dir );
		} else {
			$path = trailingslashit( $this->base_dir . ltrim( $folder, DIRECTORY_SEPARATOR ) );
		}

		if ( ! is_dir( $path ) ) {
			return new WP_Error( 'directory_not_found', __( 'Directory not found', 'learning-management-system' ) );
		}

		return $filesystem->dirlist( $path, $include_hidden, $recursive );
	}

	/**
	 * Search files in the directory with name starting with.
	 *
	 * @since 1.14.0
	 *
	 * @param string $prefix Filename prefix.
	 * @param string $folder Folder path relative to base directory.
	 * @param bool $include_hidden Whether to include hidden files.
	 * @param bool $recursive Whether to search recursively.
	 *
	 * @return array|WP_Error List of matching files or WP_Error.
	 */
	public function search_files( $prefix, $folder = '', $include_hidden = true, $recursive = false ) {
		$files = $this->list_files( $folder, $include_hidden, $recursive );

		if ( is_wp_error( $files ) ) {
			return $files;
		}

		return array_filter(
			$files,
			function( $file ) use ( $prefix ) {
				return masteriyo_starts_with( $file['name'], $prefix );
			}
		);
	}

	/**
	 * Copy a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $source Source file relative to base_dir.
	 * @param string $destination Destination path relative to base_dir.
	 * @return bool|WP_Error
	 */
	public function copy_file( $source, $destination ) {
		$filesystem = $this->get_filesystem();

		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$src_file  = $this->base_dir . ltrim( $source, DIRECTORY_SEPARATOR );
		$dest_file = $this->base_dir . ltrim( $destination, DIRECTORY_SEPARATOR );

		if ( ! $filesystem->exists( $src_file ) ) {
			return new WP_Error( 'file_not_found', __( 'Source file not found', 'learning-management-system' ) );
		}

		if ( $filesystem->exists( $dest_file ) ) {
			return new WP_Error( 'file_exists', __( 'Destination file already exists', 'learning-management-system' ) );
		}

		if ( ! $filesystem->copy( $src_file, $dest_file ) ) {
			$this->log_error( 'Failed to copy file from ' . $src_file . ' to ' . $dest_file );
			return new WP_Error( 'copy_failed', __( 'Failed to copy file', 'learning-management-system' ) );
		}

		return true;
	}

	/**
	 * Get the URL of a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path Absolute file path.
	 * @return string File URL.
	 */
	protected function get_file_url( $file_path ) {
		$upload_dir = wp_upload_dir();

		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
	}

	/**
	 * Log an error message.
	 *
	 * @since 1.14.0
	 *
	 * @param string $message Error message.
	 */
	protected function log_error( $message ) {
		masteriyo_get_logger()->error( $message, array( 'source' => 'file-handler' ) );
	}

	/**
	 * Get the base directory.
	 *
	 * @since 1.14.0
	 *
	 * @return string Base directory path.
	 */
	public function get_base_dir() {
		return $this->base_dir;
	}

	/**
	 * Get the base URL of the file directory.
	 *
	 * @since 1.14.0
	 *
	 * @return string Base URL.
	 */
	public function get_base_url() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] . '/' . MASTERIYO_UPLOAD_DIR );
	}

	/**
	 * Normalize file path by ensuring it's relative to the base directory.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path to normalize.
	 * @return string Normalized file path.
	 */
	protected function normalize_file_path( $file_path ) {
		return $this->base_dir . ltrim( $file_path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Check if a file exists.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path relative to base_dir.
	 * @return bool True if the file exists, false otherwise.
	 */
	public function file_exists( $file_path ) {
		$filesystem = $this->get_filesystem();
		return $filesystem->exists( $this->normalize_file_path( $file_path ) );
	}

	/**
	 * Read the contents of a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path relative to base_dir.
	 * @return string|WP_Error File contents or an error if it cannot be read.
	 */
	public function read_file( $file_path ) {
		$filesystem = $this->get_filesystem();
		$full_path  = $this->normalize_file_path( $file_path );

		if ( ! $filesystem->exists( $full_path ) ) {
			return new WP_Error( 'file_not_found', __( 'File not found', 'learning-management-system' ) );
		}

		return $filesystem->get_contents( $full_path );
	}

	/**
	 * Write content to a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path relative to base_dir.
	 * @param string $content Content to write to the file.
	 * @return bool|WP_Error True on success, or an error if it cannot be written.
	 */
	public function write_file( $file_path, $content ) {
		$filesystem = $this->get_filesystem();
		$full_path  = $this->normalize_file_path( $file_path );

		$result = $filesystem->put_contents( $full_path, $content, FS_CHMOD_FILE );

		if ( ! $result ) {
			return new WP_Error( 'write_failed', __( 'Failed to write file', 'learning-management-system' ) );
		}

		return true;
	}

	/**
	 * Append content to an existing file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path relative to base_dir.
	 * @param string $content Content to append.
	 * @return bool|WP_Error True on success, or an error if it cannot be written.
	 */
	public function append_file( $file_path, $content ) {
		$existing_content = $this->read_file( $file_path );

		if ( is_wp_error( $existing_content ) ) {
			return $existing_content;
		}

		return $this->write_file( $file_path, $existing_content . $content );
	}

	/**
	 * Get file size.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path File path relative to base_dir.
	 * @return int|WP_Error File size in bytes, or an error if file not found.
	 */
	public function get_file_size( $file_path ) {
		$filesystem = $this->get_filesystem();
		$full_path  = $this->normalize_file_path( $file_path );

		if ( ! $filesystem->exists( $full_path ) ) {
			return new WP_Error( 'file_not_found', __( 'File not found', 'learning-management-system' ) );
		}

		return filesize( $full_path );
	}

	/**
	 * Get the file creation or modification time.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path Absolute or relative file path to check.
	 * @param string|null $date_format Optional. Custom date format. If null, it uses WordPress settings for date and time.
	 *
	 * @return string|WP_Error Formatted file creation time or an error if the file is not found.
	 */
	public function get_file_creation_time( $file_path, $date_format = null ) {
		$filesystem = $this->get_filesystem();

		$file_path = $this->normalize_file_path( $file_path );

		if ( ! $filesystem->exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'File not found', 'learning-management-system' ) );
		}

		$file_time = filemtime( $file_path );

		if ( false === $file_time ) {
			return new WP_Error( 'file_time_error', __( 'Unable to retrieve file modification time', 'learning-management-system' ) );
		}

		$wp_date_time_format = $date_format ? $date_format : ( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		return wp_date( $wp_date_time_format, $file_time );
	}
}
