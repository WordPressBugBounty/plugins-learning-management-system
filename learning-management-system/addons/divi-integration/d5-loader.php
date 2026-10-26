<?php
/**
 * Native Divi 5 module registration entry point — the Divi 5 sibling of loader.php's Divi 4 entry point.
 *
 * @package Masteriyo\Addons\DiviIntegration
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'divi_module_library_modules_dependency_tree',
	function ( $dependency_tree ) {
		// Guard inside the callback, not around add_action(): we bootstrap before Divi 5 declares this.
		if ( ! interface_exists( 'ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
			return;
		}

		require_once __DIR__ . '/D5Modules/CourseListModule/Module.php';
		require_once __DIR__ . '/D5Modules/CourseCategoriesModule/Module.php';

		$dependency_tree->add_dependency( new \Masteriyo\Addons\DiviIntegration\D5Modules\CourseListModule\Module() );
		$dependency_tree->add_dependency( new \Masteriyo\Addons\DiviIntegration\D5Modules\CourseCategoriesModule\Module() );
	}
);

add_action(
	'divi_visual_builder_assets_before_enqueue_scripts',
	function () {
		if ( ! class_exists( 'ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) {
			return;
		}

		// One shared script for every native module; the app window iframe needs PackageBuildManager, not wp_enqueue_script().
		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			array(
				'name'    => 'masteriyo-divi5-modules',
				'version' => MASTERIYO_VERSION,
				'script'  => array(
					'src'                => plugins_url( '/addons/divi-integration/visual-builder/build/divi5-modules.js', MASTERIYO_PLUGIN_FILE ),
					'deps'               => array( 'divi-module-library', 'divi-hooks' ),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
					// Localized here (Divi's PascalCase( name + 'Data' ) convention) since the app window iframe has no wpApiSettings or @wordpress/i18n catalogue of its own.
					'data_app_window'    => array(
						'restRoot' => esc_url_raw( rest_url() ),
						'i18n'     => array(
							'newBadge'              => __( 'New', 'learning-management-system' ),
							/* translators: %s: module name, e.g. "Course List". */
							'previewFailed'         => __( '%s preview failed:', 'learning-management-system' ),
							'courseListTitle'       => __( 'Masteriyo Course List', 'learning-management-system' ),
							'courseListTitles'      => __( 'Masteriyo Course Lists', 'learning-management-system' ),
							'courseCategoriesTitle' => __( 'Masteriyo Course Categories', 'learning-management-system' ),
							'courseList'            => __( 'Course List', 'learning-management-system' ),
							'courseCategories'      => __( 'Course Categories', 'learning-management-system' ),
						),
					),
				),
			)
		);
	}
);
