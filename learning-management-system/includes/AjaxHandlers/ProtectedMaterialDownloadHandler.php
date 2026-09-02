<?php
/**
 * Protected Material Download Ajax handler.
 *
 * Serves lesson download materials through a protected endpoint that
 * verifies the user's enrollment status before streaming the file.
 *
 * @package Masteriyo\AjaxHandlers
 */

namespace Masteriyo\AjaxHandlers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\AjaxHandler;
use Masteriyo\Enums\CourseAccessMode;

/**
 * Protected Material Download ajax handler.
 */
class ProtectedMaterialDownloadHandler extends AjaxHandler {

	/**
	 * Ajax action name.
	 *
	 * @var string
	 */
	public $action = 'masteriyo_download_material';

	/**
	 * Register ajax hooks.
	 */
	public function register() {
		add_action( "wp_ajax_{$this->action}", array( $this, 'handle' ) );
		add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'handle_no_priv' ) );
	}

	/**
	 * Handle download request for logged-in users.
	 */
	public function handle() {
		$lesson_id     = absint( isset( $_GET['lesson_id'] ) ? $_GET['lesson_id'] : 0 );
		$attachment_id = absint( isset( $_GET['attachment_id'] ) ? $_GET['attachment_id'] : 0 );
		$nonce         = isset( $_GET['_nonce'] ) ? sanitize_key( wp_unslash( $_GET['_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'masteriyo_download_material_' . $lesson_id ) ) {
			wp_die(
				esc_html__( 'Invalid security token. Please refresh and try again.', 'learning-management-system' ),
				esc_html__( 'Access Denied', 'learning-management-system' ),
				array( 'response' => 403 )
			);
		}

		$lesson = masteriyo_get_lesson( $lesson_id );

		if ( ! $lesson ) {
			wp_die(
				esc_html__( 'Invalid lesson.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		$course = masteriyo_get_course( $lesson->get_course_id() );

		if ( ! $course ) {
			wp_die(
				esc_html__( 'Invalid course.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		if ( ! masteriyo_can_access_lesson_download_materials( $lesson, $course ) ) {
			wp_die(
				esc_html__( 'You do not have access to this material. Please enroll in the course first.', 'learning-management-system' ),
				esc_html__( 'Access Denied', 'learning-management-system' ),
				array( 'response' => 403 )
			);
		}

		if ( ! $this->attachment_belongs_to_lesson( $attachment_id, $lesson ) ) {
			wp_die(
				esc_html__( 'Invalid attachment.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		$this->serve_file( $attachment_id, $this->is_preview_request() );
	}

	/**
	 * Handle download request for non-logged-in users.
	 * Only OPEN access courses are allowed without login.
	 */
	public function handle_no_priv() {
		$lesson_id     = absint( isset( $_GET['lesson_id'] ) ? $_GET['lesson_id'] : 0 );
		$attachment_id = absint( isset( $_GET['attachment_id'] ) ? $_GET['attachment_id'] : 0 );
		$nonce         = isset( $_GET['_nonce'] ) ? sanitize_key( wp_unslash( $_GET['_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'masteriyo_download_material_' . $lesson_id ) ) {
			wp_die(
				esc_html__( 'Invalid security token. Please refresh and try again.', 'learning-management-system' ),
				esc_html__( 'Access Denied', 'learning-management-system' ),
				array( 'response' => 403 )
			);
		}

		$lesson = masteriyo_get_lesson( $lesson_id );

		if ( ! $lesson ) {
			wp_die(
				esc_html__( 'Invalid lesson.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		$course = masteriyo_get_course( $lesson->get_course_id() );

		if ( ! $course ) {
			wp_die(
				esc_html__( 'Invalid course.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		if ( CourseAccessMode::OPEN !== $course->get_access_mode() ) {
			wp_die(
				esc_html__( 'Please log in and enroll in the course to access this material.', 'learning-management-system' ),
				esc_html__( 'Access Denied', 'learning-management-system' ),
				array( 'response' => 403 )
			);
		}

		if ( ! $this->attachment_belongs_to_lesson( $attachment_id, $lesson ) ) {
			wp_die(
				esc_html__( 'Invalid attachment.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		$this->serve_file( $attachment_id, $this->is_preview_request() );
	}

	/**
	 * Whether the current request is for an inline preview rather than a download.
	 *
	 * @return bool
	 */
	protected function is_preview_request() {
		return isset( $_GET['preview'] ) && 1 === absint( wp_unslash( $_GET['preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Verify that the given attachment ID belongs to the lesson's download materials.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 *
	 * @return bool
	 */
	protected function attachment_belongs_to_lesson( $attachment_id, $lesson ) {
		$materials = array_map( 'absint', (array) $lesson->get_download_materials() );
		return in_array( absint( $attachment_id ), $materials, true );
	}

	/**
	 * Stream the requested file to the browser.
	 *
	 * @param int  $attachment_id Attachment post ID.
	 * @param bool $inline        Whether to serve the file inline (preview) instead of forcing a download.
	 */
	protected function serve_file( $attachment_id, $inline = false ) {
		$file_path  = get_attached_file( $attachment_id );
		$attachment = get_post( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) || ! $attachment ) {
			wp_die(
				esc_html__( 'File not found.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		$filename    = basename( $file_path );
		$mime_type   = $attachment->post_mime_type ? $attachment->post_mime_type : 'application/octet-stream';
		$file_size   = (int) filesize( $file_path );
		$disposition = $inline ? 'inline' : 'attachment';

		// Clean any existing output buffers so the binary stream is not corrupted.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Resolve the byte range to serve. Supports media seeking and resumable
		// downloads; falls back to the full file when no Range header is present.
		$start      = 0;
		$end        = $file_size - 1;
		$is_partial = false;
		$range      = isset( $_SERVER['HTTP_RANGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ) : '';

		if ( '' !== $range && preg_match( '/bytes=(\d*)-(\d*)/i', $range, $matches ) ) {
			$range_start = '' === $matches[1] ? null : (int) $matches[1];
			$range_end   = '' === $matches[2] ? null : (int) $matches[2];

			if ( is_null( $range_start ) && ! is_null( $range_end ) ) {
				// Suffix range: the final N bytes of the file.
				$start = max( 0, $file_size - $range_end );
			} else {
				$start = is_null( $range_start ) ? 0 : $range_start;
				$end   = is_null( $range_end ) ? $file_size - 1 : min( $range_end, $file_size - 1 );
			}

			// Unsatisfiable range.
			if ( $start > $end || $start >= $file_size ) {
				header( 'Content-Range: bytes */' . $file_size );
				status_header( 416 );
				exit;
			}

			$is_partial = true;
		}

		$length = $end - $start + 1;

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: ' . $disposition . '; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Accept-Ranges: bytes' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . $length );

		if ( $is_partial ) {
			status_header( 206 );
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $file_size );
		}

		$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			wp_die(
				esc_html__( 'File not found.', 'learning-management-system' ),
				esc_html__( 'Not Found', 'learning-management-system' ),
				array( 'response' => 404 )
			);
		}

		if ( $start > 0 ) {
			fseek( $handle, $start );
		}

		// Stream the requested bytes in chunks to keep memory usage flat for large files.
		$buffer_size = 8192;
		$bytes_left  = $length;

		while ( $bytes_left > 0 && ! feof( $handle ) ) {
			$read = ( $bytes_left > $buffer_size ) ? $buffer_size : $bytes_left;
			echo fread( $handle, $read ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped
			flush();
			$bytes_left -= $read;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
