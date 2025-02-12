<?php
/**
 * Courses exporter class.
 *
 * @since 1.6.0
 * @package Masteriyo\Exporter
 */

namespace Masteriyo\Exporter;

defined( 'ABSPATH' ) || exit;

use Masteriyo\FileHandler;
use Masteriyo\Addons\GoogleMeet\Enums\GoogleMeetStatus;
use Masteriyo\AdminFileDownloadHandler;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Utils;
use Masteriyo\Jobs\CoursesExportJob;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;
use ZipArchive;

/**
 * Export class.
 *
 * @since 1.6.0
 */
class CourseExporter {

	/**
	 * The directory where export files will be stored.
	 *
	 * @since 1.14.0
	 */
	const EXPORT_DIRECTORY = 'export/courses';

	/**
	 * The ID used to generate download URL for exported courses file.
	 *
	 * @since 1.14.0
	 */
	const FILE_PATH_ID = 'export_courses';

	/**
	 * Get exportable post types related to courses.
	 *
	 * @since 1.6.0
	 *
	 * @param array $course_items Optional. Specific course items to export (LESSON, QUIZ, ASSIGNMENT, GOOGLEMEET, ZOOM).
	 *
	 * @return array
	 */
	public static function get_post_types( $course_items = array() ) {
		$post_types = array( PostType::COURSE );

		if ( ! empty( $course_items ) ) {
			$post_types[] = PostType::SECTION;

			$post_types = array_merge( $post_types, $course_items );

			if ( in_array( PostType::QUIZ, $course_items, true ) ) {
				$post_types[] = PostType::QUESTION;
			}
		} else {
			$post_types = array_merge(
				$post_types,
				CourseChildrenPostType::all(),
				array( PostType::QUESTION )
			);
		}

		return array_unique( $post_types );
	}

	/**
	 * Return post type label.
	 *
	 * @since 1.6.0
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	public static function get_post_type_label( $post_type ) {
		$original_labels = array(
			PostType::COURSE   => 'Courses',
			PostType::LESSON   => 'Lessons',
			PostType::QUIZ     => 'Quizzes',
			PostType::SECTION  => 'Sections',
			PostType::QUESTION => 'Questions',
		);

		$original_labels = apply_filters( 'masteriyo_post_type_default_labels', $original_labels );

		return strtolower( $original_labels[ $post_type ] ?? '' );
	}

	/**
	 * Export courses using background processing using Action Schedular.
	 *
	 * @since 1.6.0
	 *
	 * @param array $course_items List of course items to export.
	 * @param array $course_ids List of course ids to export.
	 * @param bool  $compress Whether to compress the export file.
	 *
	 * @return array|WP_Error
	 */
	public function export( $course_items = array(), $course_ids = array(), bool $compress = false ) {
		wp_raise_memory_limit( 'admin' );

		$export_file = $this->create_export_file();

		if ( empty( $export_file ) || is_wp_error( $export_file ) ) {
			return new \WP_Error(
				'export_error',
				__( 'Unable to create export file.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$file_path = $export_file['filepath'];

		if ( ! $file_path ) {
			return new \WP_Error(
				'file_open_error',
				__( 'Unable to open export file for writing.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		if ( empty( $course_ids ) ) {
			$course_ids = $this->get_all_course_ids();
		}

		if ( empty( $course_ids ) ) {
			return new \WP_Error( 'course_not_found', 'No courses found to export.', array( 'status' => 404 ) );
		}

		Utils::set_cookie( 'masteriyo_course_export_is_in_progress_' . get_current_user_id(), '1', time() + ( HOUR_IN_SECONDS ) );

		// Start the JSON structure
		self::append( $file_path, '{' );

		// Write manifest data
		self::append( $file_path, '"manifest": ' . wp_json_encode( $this->get_export_meta_data() ) . ',' );

		// Write terms data
		self::append( $file_path, '"terms": ' . wp_json_encode( self::get_terms( $course_ids ) ) . ',' );

		$post_types = self::get_post_types( $course_items );

		if ( 10 >= count( $course_ids ) ) { // Only 10 courses can be exported at a time
			return $this->write_posts_to_json( $course_ids, $post_types, $export_file );
		}

		$this->schedule_tasks( $course_ids, $post_types, $file_path );

		return rest_ensure_response(
			array(
				'status'  => 'progress',
				'message' => __( 'Export in progress. Please wait...', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Write posts data to the export file in chunks.
	 *
	 * @since 1.14.0
	 *
	 * @param array $course_ids The course IDs.
	 * @param array $post_types The post types to export.
	 * @param array $export_file The export file data.
	 *
	 * @return array The export file data.
	 */
	public function write_posts_to_json( $course_ids, $post_types, $export_file ) {
		$file_path = $export_file['filepath'];

		$is_last_post_type = false;
		$post_types_count  = count( $post_types );

		foreach ( $post_types as $index => $post_type ) {
			$label = self::get_post_type_label( $post_type );

			CoursesExportJob::start_post_type_section( $file_path, $label );

			CoursesExportJob::append_posts_data( $course_ids, $post_type, $file_path );

			if ( $index + 1 === $post_types_count ) {
				$is_last_post_type = true;
			}

			self::end_post_type_section( $file_path, $is_last_post_type );
		}

		self::append( $file_path, '}' );

		$created_at = self::get_file_creation_time();

		return rest_ensure_response(
			array(
				'status'       => 'completed',
				'download_url' => self::get_download_url(),
				'message'      => sprintf(
					/* translators: %s: date and time. */
					__( 'Export completed on %s.', 'learning-management-system' ),
					$created_at
				),
			)
		);
	}

	/**
	 * Schedule batches for background processing.
	 *
	 * @since 1.14.0
	 *
	 * @param array $course_ids List of course IDs.
	 * @param array $post_types List of post types to export.
	 * @param string $file_path Path to the export file.
	 */
	protected function schedule_tasks( $course_ids, $post_types, $file_path ) {
		$first_post_type = array_shift( $post_types );

		$args            = array( $course_ids, $first_post_type, $post_types, $file_path );
		$current_user_id = get_current_user_id();

		update_option( 'masteriyo_exporting_courses_args_' . $current_user_id, wp_json_encode( $args ) );

		as_enqueue_async_action( CoursesExportJob::NAME, array( $current_user_id ), CoursesExportJob::GROUP_NAME );
	}

	/**
	 * Fetches posts and their metadata based on given post types.
	 *
	 * @since 1.14.0
	 *
	 * @param array  $course_ids      The course IDs to fetch posts for.
	 * @param string $post_type       The post type to fetch.
	 * @param bool   $get_attachments Whether to fetch attachments for the fetched posts.
	 *
	 * @return \Generator
	 */
	public static function get_posts_data( array $course_ids, string $post_type, bool $get_attachments = false ) : \Generator {
		if ( empty( $course_ids ) ) {
			return;
		}

		$get_attachments = in_array( $post_type, array( PostType::COURSE, PostType::LESSON ), true ) ? true : $get_attachments;

		if ( PostType::COURSE === $post_type || '' === $post_type ) {
			foreach ( $course_ids as $course_id ) {
				$post = self::fetch_post_data( $course_id, $get_attachments );
				if ( $post ) {
					yield $post;
				}
			}
			return;
		}

		$paged          = 1;
		$posts_per_page = 100;
		$has_more_posts = true;

		$args = self::prepare_query_args( $course_ids, $post_type, $posts_per_page );

		while ( $has_more_posts ) {
			$args['paged'] = $paged;

			$post_ids = get_posts( $args );

			if ( empty( $post_ids ) ) {
				break;
			}

			foreach ( $post_ids as $post_id ) {
				$post = self::fetch_post_data( $post_id, $get_attachments );
				if ( $post ) {
					yield $post;
				}
			}

			$paged++;
			$has_more_posts = count( $post_ids ) === $posts_per_page;
		}
	}

	/**
	 * Fetch post data, including meta, terms, and attachments (if requested).
	 *
	 * @since 1.14.0
	 *
	 * @param int    $post_id      Post ID.
	 * @param bool   $get_attachments Whether to fetch attachments.
	 *
	 * @return array|null Post data, or null if post does not exist.
	 */
	private static function fetch_post_data( int $post_id, bool $get_attachments ) {
		$post = get_post( $post_id, ARRAY_A );
		if ( ! $post ) {
			return null;
		}

		$post['postmeta'] = get_post_meta( $post_id );
		$post['terms']    = self::get_post_terms( $post );

		if ( $get_attachments ) {
			$post['attachments'] = self::get_post_attachments( $post_id );
		}

		return $post;
	}

	/**
	 * Prepare query arguments for fetching posts associated with courses.
	 *
	 * @since 1.14.0
	 *
	 * @param array $course_ids Course IDs.
	 * @param string $post_type Post type.
	 * @param int $posts_per_page Number of posts per page.
	 *
	 * @return array Query arguments.
	 */
	public static function prepare_query_args( array $course_ids, string $post_type, int $posts_per_page ) : array {
		$args = array(
			'fields'         => 'ids',
			'post_type'      => $post_type,
			'post_status'    => array_diff( PostStatus::all(), array( PostStatus::TRASH ) ),
			'posts_per_page' => $posts_per_page,
			'meta_query'     => array(
				array(
					'key'     => '_course_id',
					'value'   => $course_ids,
					'compare' => 'IN',
				),
			),
		);

		if ( PostType::GOOGLEMEET === $post_type ) {
			$args['post_status'] = array_merge( GoogleMeetStatus::all(), $args['post_status'] );
		}

		return $args;
	}

	/**
	 * Get the IDs and count of items for a given post type.
	 *
	 * @since 1.14.0
	 *
	 * @param array  $course_ids List of course IDs.
	 * @param string $post_type  The post type.
	 *
	 * @return array Array of post IDs.
	 */
	public static function get_post_type_data( $course_ids, $post_type ) {
		$args = self::prepare_query_args( $course_ids, $post_type, -1 );

		return get_posts( $args );
	}

	/**
	 * Get all course IDs.
	 *
	 * @since 1.14.0
	 *
	 * @return array Array of course IDs.
	 */
	protected function get_all_course_ids() {
		$args = array(
			'post_type'      => PostType::COURSE,
			'post_status'    => array_diff( PostStatus::all(), array( PostStatus::TRASH ) ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
		);

		return get_posts( $args );
	}

	/**
	 * Compress.
	 *
	 * @since 1.6.0
	 * @param string $filepath Path to file for compress.
	 * @return \WP_Error|array Array of data on success or WP_Error on failure.
	 */
	protected function compress( string $filepath ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error(
				'missing_zip_package',
				__( 'Zip Export not supported.', 'learning-management-system' )
			);
		}

		$upload_dir   = wp_upload_dir();
		$archiver     = new ZipArchive();
		$filename     = pathinfo( $filepath, PATHINFO_FILENAME );
		$zip_filename = $filename . '.zip';
		$zip_filepath = $upload_dir['basedir'] . '/masteriyo/' . $zip_filename;

		if ( true !== $archiver->open( $zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new \WP_Error(
				'unable_to_create_zip',
				__( 'Unable to open export file (archive) for writing.', 'learning-management-system' )
			);
		}

		$archiver->addFile( $filepath, $filename . '.json' );
		$archiver->close();

		// Delete json file after compress.
		$filesystem = masteriyo_get_filesystem();
		if ( $filesystem ) {
			$filesystem->delete( $filepath );
		}

		return array(
			'filepath'     => $zip_filepath,
			'filename'     => $zip_filename,
			'download_url' => $upload_dir['baseurl'] . '/masteriyo/' . $zip_filename,
		);
	}

	/**
	 * Append content to a file using core PHP functions.
	 *
	 * @since 1.14.0
	 *
	 * @param string $filepath The file path.
	 * @param array|string $contents The content to append.
	 */
	public static function append( $filepath, $contents ) {
		if ( is_array( $contents ) ) {
			$contents = implode( PHP_EOL, $contents );
		}

		$file = fopen( $filepath, 'a' ); // phpcs:ignore

		if ( ! $file ) {
			return;
		}

		fwrite( $file, $contents ); // phpcs:ignore

		fclose( $file ); // phpcs:ignore
	}

	/**
	 * Create and return export file path.
	 *
	 * @since 1.6.0
	 *
	 * @return array|WP_Error Array of data on success or WP_Error on failure.
	 */
	protected function create_export_file() {
		$file_handler = new FileHandler();

		$prefix     = sprintf( 'masteriyo-courses-export-%s-', get_current_user_id() );
		$old_prefix = sprintf( 'masteriyo-export-%s-', get_current_user_id() );

		$this->cleanup_old_files( $file_handler, $prefix, self::EXPORT_DIRECTORY );
		$this->cleanup_old_files( $file_handler, $old_prefix, '' ); // This is for compatibility with old versions because export path is changed since 1.14.0.

		$filename = sprintf( 'masteriyo-courses-export-%s-%s.json', get_current_user_id(), gmdate( 'Y-m-d-H-i-s' ) );
		$result   = $file_handler->create_file( self::EXPORT_DIRECTORY, $filename );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$file_info = array(
			'filepath' => $result['file_path'],
			'filename' => $result['filename'],
		);

		$file_info['download_url'] = AdminFileDownloadHandler::get_download_url( self::FILE_PATH_ID, $result['filename'] );

		return $file_info;
	}

	/**
	 * Clean up old files to avoid clutter.
	 *
	 * @since 1.14.0
	 *
	 * @param FileHandler $file_handler
	 * @param string      $prefix
	 * @param string      $directory
	 */
	protected function cleanup_old_files( $file_handler, $prefix, $directory = '' ) {
		$old_files = $file_handler->search_files( $prefix, $directory );

		if ( is_wp_error( $old_files ) ) {
			return;
		}

		if ( ! empty( $old_files ) ) {
			foreach ( $old_files as $old_file ) {
				$file_handler->delete( $directory, $old_file['name'] );
			}
		}
	}

	/**
	 * Get the download URL for the exported courses file.
	 *
	 * @since 1.14.0
	 *
	 * @return string|false The download URL if exists, otherwise false.
	 */
	public static function get_download_url() {
		$file_handler   = new FileHandler();
		$prefix         = sprintf( 'masteriyo-courses-export-%s-', get_current_user_id() );
		$exported_files = $file_handler->search_files( $prefix, self::EXPORT_DIRECTORY );

		if ( empty( $exported_files ) ) {
			return false;
		}

		foreach ( $exported_files as $file ) {
			$prefix = sprintf( 'masteriyo-courses-export-%s-', get_current_user_id() );

			if ( ! isset( $file['name'] ) ) {
				return false;
			}

			if ( masteriyo_starts_with( $file['name'], $prefix ) ) {
				return AdminFileDownloadHandler::get_download_url( self::FILE_PATH_ID, $file['name'] );
			}
		}
	}

	/**
	 * Get the full path of the exported courses file.
	 *
	 * @since 1.14.0
	 *
	 * @return string|false The full path of the exported courses file if exists, otherwise false.
	 */
	public static function get_full_file_path() {
		$file_handler   = new FileHandler();
		$prefix         = sprintf( 'masteriyo-courses-export-%s-', get_current_user_id() );
		$exported_files = $file_handler->search_files( $prefix, self::EXPORT_DIRECTORY );

		if ( empty( $exported_files ) ) {
			return false;
		}

		foreach ( $exported_files as $file ) {
			$prefix = sprintf( 'masteriyo-courses-export-%s-', get_current_user_id() );
			if ( isset( $file['name'] ) && masteriyo_starts_with( $file['name'], $prefix ) ) {
				return self::get_file_path() . DIRECTORY_SEPARATOR . $file['name'];
			}
		}
	}

	/**
	 * Return the folder name where exported courses files are stored.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_file_path() {
		$upload_dir = wp_upload_dir();
		$export_dir = DIRECTORY_SEPARATOR . MASTERIYO_UPLOAD_DIR . DIRECTORY_SEPARATOR . self::EXPORT_DIRECTORY;

		return $upload_dir['basedir'] . $export_dir;
	}

	/**
	 * Get the export file creation time.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	public static function get_file_creation_time() {
		$file_time = filemtime( self::get_full_file_path() );

		return masteriyo_rest_prepare_date_response( $file_time );
	}

	/**
	 * Return all the terms associated with taxonomies.
	 *
	 * @since 1.6.0
	 *
	 * @param array $course_ids Course IDs.
	 *
	 * @return array
	 */
	protected static function get_terms( $course_ids ) {
		$taxonomies = Taxonomy::all();
		$all_terms  = array();

		foreach ( $course_ids as $course_id ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( $course_id, $taxonomy );

				if ( ! $terms || is_wp_error( $terms ) ) {
					continue;
				}

				$all_terms = array_merge( $all_terms, $terms );
			}
		}

		$data = array(
			'terms'       => array(),
			'attachments' => array(),
		);

		if ( empty( $all_terms ) ) {
			return $all_terms;
		}

		foreach ( $all_terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$term = (array) $term;

			$term_meta         = get_term_meta( $term['term_id'] );
			$term['termmeta']  = $term_meta ?? array();
			$featured_image_id = get_term_meta( $term['term_id'], '_featured_image', true );

			if ( $featured_image_id ) {
				$attachment = get_post( $featured_image_id, ARRAY_A );
				if ( $attachment ) {
					$data['attachments'][] = $attachment + array(
						'postmeta' => get_post_meta( $featured_image_id ),
					);
				}
			}

			$data['terms'][] = $term;
		}

		return $data;
	}

	/**
	 * Return terms related to posts.
	 *
	 * @since 1.6.0
	 *
	 * @param array $post Post array.
	 *
	 * @return array
	 */
	public static function get_post_terms( $post ) {
		$taxonomies = get_object_taxonomies( $post['post_type'] );

		if ( empty( $taxonomies ) ) {
			return array();
		}

		$terms = wp_get_object_terms( $post['ID'], $taxonomies );

		if ( empty( $terms ) ) {
			return array();
		}

		return array_map(
			function( $term ) {
				return array(
					'slug'     => $term->slug,
					'name'     => $term->name,
					'taxonomy' => $term->taxonomy,
				);
			},
			$terms
		);
	}

	/**
	 * Get featured attachments.
	 *
	 * @since 1.6.0
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_post_attachments( $post_id ) {
		$meta_keys = array(
			'_thumbnail_id',
		);

		if ( 'self-hosted' === get_post_meta( $post_id, '_video_source', true ) ) {
			$meta_keys[] = '_video_source_url';
		}

		if ( 'self-hosted' === get_post_meta( $post_id, '_featured_video_source', true ) ) {
			$meta_keys[] = '_featured_video_url';
		}

		$attachments = array();

		foreach ( $meta_keys as $meta_key ) {
			$id = get_post_meta( $post_id, $meta_key, true );
			if ( ! $id ) {
				continue;
			}
			$attachment = get_post( $id, ARRAY_A );
			if ( ! $attachment ) {
				continue;
			}
			$attachments[] = $attachment + array(
				'postmeta' => get_post_meta( $id ),
			);
		}

		return $attachments;
	}

	/**
	 * Return export meta data.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	protected function get_export_meta_data() {
		return array(
			'version'    => masteriyo_get_version(),
			'created_at' => gmdate( 'D, d M Y H:i:s +0000' ),
			'base_url'   => home_url(),
		);
	}

	/**
	 * End the current section for a post type in the JSON file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file_path        The file path for export.
	 * @param bool   $is_last_post_type If this is the last post type in the export.
	 */
	public static function end_post_type_section( $file_path, $is_last_post_type ) {
		$separator = $is_last_post_type ? ']' : '],';
		self::append( $file_path, $separator );
	}
}
