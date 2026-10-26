<?php
/**
 * Shared Divi 5 plumbing (wrapper, classnames, styles, script data) so the Design-tab settings reach the published page — module.json's "render" template alone prints none of it.
 *
 * @package Masteriyo\Addons\DiviIntegration
 */

namespace Masteriyo\Addons\DiviIntegration\D5Modules;

use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleUtils\ChildrenUtils;
use WP_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers each native module's Module.php delegates to.
 */
class NativeModuleSupport {

	/**
	 * Module classnames callback (decoration-driven classes, e.g. link/animation).
	 *
	 * @param array $args Divi classnames args.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Module script data callback.
	 *
	 * @param array $args Divi script data args.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * Module styles callback: prints the module's own decoration (background,
	 * border, box shadow, spacing, sizing, visibility) under its order class.
	 *
	 * @param array $args Divi module styles args.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
				),
			)
		);
	}

	/**
	 * Render a native module for the frontend. The block-renderer REST route (used by the VB preview) hands us a bare WP_Block with no Divi parser metadata, so that path returns bare content; a real page render goes through Divi's Module::render().
	 *
	 * @param array         $attrs                       Module attributes.
	 * @param WP_Block      $block                       Block instance.
	 * @param ModuleElements $elements                   Module elements.
	 * @param array         $default_printed_style_attrs Default printed style attrs.
	 * @param string        $module_class                The module's wrapper class, e.g. "masteriyo_divi5_course_list".
	 * @param string        $id_prefix                   Prefix for the unique per-instance id.
	 * @param string        $content_template            Absolute path of the content partial.
	 * @param callable[]    $callbacks                   Static [ class, method ] callables keyed classnames/styles/script_data.
	 *
	 * @return string
	 */
	public static function render(
		array $attrs,
		WP_Block $block,
		ModuleElements $elements,
		array $default_printed_style_attrs,
		string $module_class,
		string $id_prefix,
		string $content_template,
		array $callbacks
	): string {
		$instance_id       = sanitize_html_class( wp_unique_id( $id_prefix ) );
		$instance_selector = '.' . $module_class . '[data-mto-divi5-id="' . esc_attr( $instance_id ) . '"]';
		$parsed_block      = $block->parsed_block;

		// Stable across requests (position on the page), so the request that reads a
		// pager's page number is the one that matches the links it printed.
		$pagination_key = 'mto-course-page' . ( isset( $parsed_block['orderIndex'] ) ? '-' . absint( $parsed_block['orderIndex'] ) : '' );
		$content        = self::render_template( $content_template, $attrs, $instance_selector, $pagination_key );

		if ( empty( $parsed_block['id'] ) ) {
			// Block-renderer / bare context: no Divi wrapper pipeline available.
			return sprintf(
				'<div class="et_pb_module et_module %1$s" data-mto-divi5-id="%2$s">%3$s</div>',
				esc_attr( $module_class ),
				esc_attr( $instance_id ),
				$content
			);
		}

		$parent       = BlockParserStore::get_parent( $parsed_block['id'], $parsed_block['storeInstance'] );
		$children_ids = ChildrenUtils::extract_children_ids( $block );

		return Module::render(
			array(
				// FE only.
				'orderIndex'               => $parsed_block['orderIndex'],
				'storeInstance'            => $parsed_block['storeInstance'],

				// VB equivalent.
				'attrs'                    => $attrs,
				'htmlAttrs'                => array( 'data-mto-divi5-id' => $instance_id ),
				'elements'                 => $elements,
				'defaultPrintedStyleAttrs' => $default_printed_style_attrs,
				'id'                       => $parsed_block['id'],
				'name'                     => $block->block_type->name,
				'classnamesFunction'       => $callbacks['classnames'],
				'moduleCategory'           => $block->block_type->category,
				'stylesComponent'          => $callbacks['styles'],
				'scriptDataComponent'      => $callbacks['script_data'],
				'parentAttrs'              => $parent->attrs ?? array(),
				'parentId'                 => $parent->id ?? '',
				'parentName'               => $parent->blockName ?? '',
				'childrenIds'              => $children_ids,
				'children'                 => $elements->style_components(
					array(
						'attrName' => 'module',
					)
				) . $content,
			)
		);
	}

	/**
	 * Run a content partial and return what it prints.
	 *
	 * @param string $template          Absolute path of the partial.
	 * @param array  $attributes        Raw block attributes (read by the partial).
	 * @param string $instance_selector CSS selector unique to this instance (read by the partial).
	 * @param string $pagination_key    Pagination query parameter unique to this instance (read by the partial).
	 *
	 * @return string
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- $attributes, $instance_selector and $pagination_key are read by the included partial.
	private static function render_template( string $template, array $attributes, string $instance_selector, string $pagination_key ): string {
		ob_start();
		require $template;
		return (string) ob_get_clean();
	}
}
