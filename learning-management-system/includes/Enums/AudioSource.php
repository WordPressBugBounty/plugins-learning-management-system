<?php
/**
 * Audio source enums.
 *
 * @since 2.17.0
 *
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

class AudioSource {
	/**
	 * Self hosted audio.
	 *
	 * @since 2.17.0
	 *
	 * @var string
	 */
	const SELF_HOSTED = 'self-hosted';

	/**
	 * External Audio. A audio URL with a different domain.
	 *
	 * @since 2.17.0
	 *
	 * @var string
	 */
	const EXTERNAL = 'external';

	/**
	 * Embedded Audio. A audio source for embedded audios.
	 *
	 * @since 2.17.0
	 *
	 * @var string
	 */
	const EMBED = 'embed-audio';

	/**
	 * Return all the audio sources.
	 *
	 * @since 2.17.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filter audio sources.
			 *
			 * @since 2.17.0
			 */
			apply_filters(
				'masteriyo_audio_sources',
				array(
					self::SELF_HOSTED,
					self::EXTERNAL,
					self::EMBED,
				)
			)
		);
	}
}
