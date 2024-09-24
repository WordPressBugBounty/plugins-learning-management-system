<?php
/**
 * Masteriyo beaver integration addon setup.
 *
 * @package Masteriyo\Addons\BeaverIntegration
 *
 * @since 1.10.0
 */
namespace Masteriyo\Addons\BeaverIntegration;

use Masteriyo\Addons\BeaverIntegration\CoursesCategories\CoursesCategoriesModule;
use Masteriyo\Addons\BeaverIntegration\CoursesLists\CoursesListsModule;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo beaver integration class.
 *
 * @class Masteriyo\Addons\BeaverIntegration\BeaverIntegrationAddon
 */
class BeaverIntegrationAddon {

	/**
	 * Initialize module.
	 *
	 * @since 1.10.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.10.0
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'init_elements' ) );
	}

	/**
	 * Initialized modules.
	 *
	 * @since 1.10.0
	 */
	public function init_elements() {

		if ( class_exists( 'FLBuilder' ) ) {
			\FLBuilder::register_module(
				CoursesListsModule::class,
				Helper::get_courses_setting()
			);
			\FLBuilder::register_module(
				CoursesCategoriesModule::class,
				Helper::get_courses_categories_setting()
			);
		}
	}
}
