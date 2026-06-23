<?php
/**
 * Masteriyo H5P addon.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\H5P
 */

namespace Masteriyo\Addons\H5P;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\H5P\Compatibility\H5PRenderer;

/**
 * Main H5P addon class.
 *
 * @since x.x.x
 */
class H5PAddon {

	/**
	 * Initialize the addon.
	 *
	 * @since x.x.x
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since x.x.x
	 */
	public function init_hooks() {
		// Boot the H5P compatibility layer (iframe renderer + shortcode replacement + xAPI) that makes [h5p] work on the learn page.
		( new H5PRenderer() )->init();
	}
}
