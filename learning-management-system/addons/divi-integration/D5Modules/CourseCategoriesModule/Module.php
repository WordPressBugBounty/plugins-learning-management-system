<?php
/**
 * Native Divi 5 Course Categories module registration (see #1540). It
 * coexists with the legacy Divi 4 module (Modules/CourseCategoriesModule.php):
 * both query categories through the shared Helper::query_course_categories(),
 * so the two cannot drift apart.
 *
 * @package Masteriyo\Addons\DiviIntegration
 */

namespace Masteriyo\Addons\DiviIntegration\D5Modules\CourseCategoriesModule;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use Masteriyo\Addons\DiviIntegration\D5Modules\NativeModuleSupport;
use WP_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the native Divi 5 Course Categories module. The Visual Builder
 * script that renders it is registered once, shared with every other native
 * module — see d5-loader.php.
 */
class Module implements DependencyInterface {

	/**
	 * Load the module: register it with Divi 5.
	 *
	 * @return void
	 */
	public function load(): void {
		// module.json + the content partial live under visual-builder/, shared with the JS side.
		$module_folder = __DIR__ . '/../../visual-builder/src/components/course-categories/';

		add_action(
			'init',
			function () use ( $module_folder ) {
				ModuleRegistration::register_module(
					$module_folder,
					array(
						'render_callback' => array( self::class, 'render_callback' ),
						// Here as well as in module.json so the msgids are extractable by make-pot.
						'title'           => __( 'Masteriyo Course Categories', 'learning-management-system' ),
						'titles'          => __( 'Masteriyo Course Categories', 'learning-management-system' ),
					)
				);
			}
		);
	}

	/**
	 * Module classnames callback.
	 *
	 * @param array $args Divi classnames args.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		NativeModuleSupport::module_classnames( $args );
	}

	/**
	 * Module script data callback.
	 *
	 * @param array $args Divi script data args.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		NativeModuleSupport::module_script_data( $args );
	}

	/**
	 * Module styles callback.
	 *
	 * @param array $args Divi module styles args.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		NativeModuleSupport::module_styles( $args );
	}

	/**
	 * Render the Course Categories module on the frontend.
	 *
	 * @param array          $attrs                       Module attributes.
	 * @param string         $child_modules_content       Child modules content (this module has none).
	 * @param WP_Block       $block                       Block instance.
	 * @param ModuleElements $elements                    Module elements.
	 * @param array          $default_printed_style_attrs Default printed style attributes.
	 *
	 * @return string
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, ModuleElements $elements, array $default_printed_style_attrs ): string {
		return NativeModuleSupport::render(
			$attrs,
			$block,
			$elements,
			$default_printed_style_attrs,
			'masteriyo_divi5_course_categories',
			'mto-divi5-course-categories-',
			__DIR__ . '/../../visual-builder/src/components/course-categories/content.php',
			array(
				'classnames'  => array( self::class, 'module_classnames' ),
				'styles'      => array( self::class, 'module_styles' ),
				'script_data' => array( self::class, 'module_script_data' ),
			)
		);
	}
}
