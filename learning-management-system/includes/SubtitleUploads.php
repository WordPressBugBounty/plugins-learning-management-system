<?php
/**
 * Caption file uploads.
 *
 * @package Masteriyo
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a SubRip caption file be uploaded.
 *
 * WordPress lists `srt` among its default types, mapped to text/plain, but
 * libmagic identifies the format itself as application/x-subrip. Core compares
 * the sniffed type with the one the extension implies, sees "application"
 * against "text", and refuses the upload. Whether that happens depends on how
 * old the host's magic database is, which is why the same file uploads on one
 * server and not on another.
 *
 * @class Masteriyo\SubtitleUploads
 */
class SubtitleUploads {

	/**
	 * What libmagic calls a SubRip file.
	 *
	 * @var string[]
	 */
	const SUBRIP_MIME_TYPES = array(
		'application/x-subrip',
		'application/x-srt',
		'text/x-subrip',
		'text/srt',
	);

	/**
	 * Initialize.
	 */
	public static function init() {
		self::init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private static function init_hooks() {
		add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'allow_subrip_captions' ), 10, 5 );
	}

	/**
	 * Restore the verdict for a caption file core sniffed as SubRip.
	 *
	 * Deliberately narrow: only a file named .srt, only when the content really
	 * is SubRip, and only when the site itself still allows that extension. A
	 * file of any other kind wearing the extension keeps core's refusal.
	 *
	 * @param array        $checked   File data from core: ext, type, proper_filename.
	 * @param string       $file      Full path to the file.
	 * @param string       $filename  The name the file will be given.
	 * @param string[]     $mimes     Allowed mime types, keyed by extension.
	 * @param string|false $real_mime What the content was sniffed as.
	 *
	 * @return array
	 */
	public static function allow_subrip_captions( $checked, $file, $filename, $mimes = null, $real_mime = false ) {
		if ( ! empty( $checked['ext'] ) && ! empty( $checked['type'] ) ) {
			return $checked;
		}

		if ( ! in_array( (string) $real_mime, self::SUBRIP_MIME_TYPES, true ) ) {
			return $checked;
		}

		if ( 'srt' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			return $checked;
		}

		// Whatever the site allows, not what we would like it to allow.
		$allowed = wp_check_filetype( $filename, $mimes );

		if ( empty( $allowed['ext'] ) || empty( $allowed['type'] ) ) {
			return $checked;
		}

		$checked['ext']  = $allowed['ext'];
		$checked['type'] = $allowed['type'];

		return $checked;
	}
}
