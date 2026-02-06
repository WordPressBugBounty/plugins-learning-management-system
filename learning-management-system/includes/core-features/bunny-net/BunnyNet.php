<?php

/**
 * Masteriyo Bunny Net setup.
 *
 * @package Masteriyo\BunnyNet
 *
 * @since 2.11.0
 */

namespace Masteriyo\CoreFeatures\BunnyNet;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Bunny Net class.
 *
 * @class Masteriyo\CoreFeatures\BunnyNet
 */

class BunnyNet {

	const BUNNY_NET = 'bunny-net';

	/**
	 * Initialize the application.
	 *
	 * @since 2.11.0
	 */
	public function init() {
		
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.11.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_lesson_video_sources', array( $this, 'add_bunny_net_video_source' ), 10, 1 );
		add_filter( 'masteriyo_video_sources', array( $this, 'add_bunny_net_video_source_key' ), 10, 1 );

	}


	/**
	 * Add bunny net video source.
	 *
	 * @since 2.11.0
	 *
	 * @param array $sources Video sources.
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 * @return array
	 */
	public function add_bunny_net_video_source( $sources ) {

		$sources[ self::BUNNY_NET ] = __( 'Bunny Net', 'learning-management-system' );

		return $sources;
	}

	/**
	 * Adds 'bunny-net' as a video source key to the provided sources array.
	 *
	 * This method appends 'bunny-net' to the list of video sources and ensures
	 * that the resulting array contains only unique values.
	 *
	 * @since 2.11.0
	 *
	 * @param array $sources The existing array of video source keys.
	 * @return array The modified array of video source keys with 'bunny-net' added.
	 */
	public function add_bunny_net_video_source_key( $sources ) {
		if ( ! in_array( self::BUNNY_NET, $sources, true ) ) {
			$sources[] = self::BUNNY_NET;
		}
		return $sources;
	}
}
