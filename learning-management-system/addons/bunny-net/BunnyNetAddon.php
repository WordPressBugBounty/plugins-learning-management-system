<?php

/**
 * Masteriyo Bunny Net setup.
 *
 * @package Masteriyo\BunnyNet
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\BunnyNet;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Bunny Net class.
 *
 * @class Masteriyo\Addons\BunnyNet
 */

class BunnyNetAddon {

	/**
	 * Initialize the application.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.11.0
	 */
	public function init_hooks() {

		add_filter( 'masteriyo_lesson_video_sources', array( $this, 'add_bunny_net_video_source' ), 10, 1 );

	}


	/**
	 * Add bunny net video source.
	 *
	 * @since 1.11.0
	 *
	 * @param array $sources Video sources.
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 * @return array
	 */
	public function add_bunny_net_video_source( $sources ) {

		$sources['bunny-net'] = __( 'Bunny Net', 'learning-management-system' );

		return $sources;
	}

}
