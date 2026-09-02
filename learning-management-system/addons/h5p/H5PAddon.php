<?php
/**
 * Masteriyo H5P addon.
 *
 * @package Masteriyo\Addons\H5P
 */

namespace Masteriyo\Addons\H5P;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\H5P\Compatibility\H5PRenderer;

/**
 * Main H5P addon class.
 */
class H5PAddon {

	/**
	 * Initialize the addon.
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		// Boot the H5P compatibility layer (iframe renderer + shortcode replacement + xAPI) that makes [h5p] work on the learn page.
		( new H5PRenderer() )->init();

		/**
		 * Fires once the H5P addon has initialised.
		 *
		 * The H5P Quiz — its post type, REST routes, curriculum integration and
		 * attempt recording — ships only with pro and joins here. Reaching this
		 * point already means the addon is active and the H5P plugin is active,
		 * because `main.php` returns before it otherwise.
		 *
		 * @param \Masteriyo\Addons\H5P\H5PAddon $addon The addon instance.
		 */
		do_action( 'masteriyo_h5p_addon_initialized', $this );
	}
}
