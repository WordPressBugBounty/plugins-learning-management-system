<?php
/**
 * Video source enums.
 *
 * @since 2.2.5
 *
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

class VideoSource {
	/**
	 * Self hosted video.
	 *
	 * @since 2.2.5
	 *
	 * @var string
	 */
	const SELF_HOSTED = 'self-hosted';

	/**
	 * YouTube video.
	 *
	 * @since 2.2.5
	 *
	 * @var string
	 */
	const YOUTUBE = 'youtube';

	/**
	 * Vimeo video.
	 *
	 * @since 2.2.5
	 *
	 * @var string
	 */
	const VIMEO = 'vimeo';

	/**
	 * External video. A video URL with a different domain.
	 *
	 * @since 2.5.20
	 *
	 * @var string
	 */
	const EXTERNAL = 'external';

	/**
	 * Embedded video. A video source for embedded videos.
	 *
	 * @since 1.11.0 [Free]
	 *
	 * @var string
	 */
	const EMBED = 'embed-video';

	/**
	 * Embedded video. A video source for embedded videos.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @var string
	 */
	const LIVE_STREAM = 'live-stream';


	/**
	 * Return all the video sources.
	 *
	 * @since 2.2.5
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filter video sources.
			 *
			 * @since 2.2.5
			 */
			apply_filters(
				'masteriyo_video_sources',
				array(
					self::SELF_HOSTED,
					self::YOUTUBE,
					self::VIMEO,
					self::EXTERNAL,
					self::EMBED,
					self::LIVE_STREAM,
				)
			)
		);
	}
}
