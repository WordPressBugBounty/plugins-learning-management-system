<?php
/**
 * Masteriyo divi integration addon setup.
 *
 * @package Masteriyo\Addons\DiviIntegration
 *
 * @since 1.6.13
 */
namespace Masteriyo\Addons\DiviIntegration;

use Masteriyo\ScriptStyle;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo divi integration class.
 *
 * @class Masteriyo\Addons\DiviIntegration\DiviIntegrationAddon
 *
 * @since 1.6.13
 */
class DiviIntegrationAddon {

	/**
	 * Initialize module.
	 *
	 * @since 1.6.13
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.6.13
	 */
	public function init_hooks() {
		add_action( 'divi_extensions_init', array( $this, 'init_divi_extensions' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		$this->init_divi5_modules();
	}

	/**
	 * Add the "masteriyo" class the course grid's CSS is scoped under when the native
	 * Divi 5 Course List module is on the page (the Divi 4 module gets it via its
	 * shortcode instead, in masteriyo_add_body_class()).
	 *
	 * A plain has_block() check, not core's masteriyo_get_blocks() path: that path
	 * re-renders the_content() to tell a single-course block apart from a list one,
	 * which this module has no need of and would otherwise pay for on every request.
	 *
	 * @param string[] $classes Body classes.
	 *
	 * @return string[]
	 */
	public function add_body_class( $classes ) {
		if ( ScriptStyle::current_page_has_divi_course_list_module() ) {
			$classes[] = 'masteriyo';
		}

		return $classes;
	}

	/**
	 * Initialize Divi extensions.
	 *
	 * @since 1.6.13
	 */
	public function init_divi_extensions() {
		if ( class_exists( 'DiviExtension' ) ) {
			new MasteriyoDiviExtension();
		}
	}

	/**
	 * Register native Divi 5 modules (Divi 5 sibling of init_divi_extensions()).
	 */
	private function init_divi5_modules() {
		require_once __DIR__ . '/d5-loader.php';
	}
}
