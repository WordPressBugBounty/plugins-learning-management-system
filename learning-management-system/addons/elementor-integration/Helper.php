<?php
/**
 * Elementor Integration helper functions.
 *
 * @package Masteriyo\Addons\ElementorIntegration
 *
 * @since 1.6.12
 */

namespace Masteriyo\Addons\ElementorIntegration;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

/**
 * Elementor Integration helper functions.
 *
 * @package Masteriyo\Addons\ElementorIntegration
 *
 * @since 1.6.12
 */
class Helper {

	/**
	 * Return if Elementor is active.
	 *
	 * @since 1.6.12
	 *
	 * @return boolean
	 */
	public static function is_elementor_active() {
		if ( masteriyo_check_plugin_active_in_network( 'elementor/elementor.php' ) ) {
			return true;
		}
		return in_array( 'elementor/elementor.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Get a svg file contents.
	 *
	 * @since 1.6.12
	 *
	 * @param string $name SVG filename.
	 *
	 * @return string
	 */
	public static function get_svg( $name ) {
		global $wp_filesystem;

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}

		$credentials = request_filesystem_credentials( '', 'direct' );

		// Bail early if the credentials is wrong.
		if ( ! $credentials ) {
			return '';
		}

		\WP_Filesystem( $credentials );

		$file_name     = Constants::get( 'MASTERIYO_ELEMENTOR_INTEGRATION_DIR' ) . "/svg/{$name}.svg";
		$file_contents = '';

		if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
			$file_contents = $wp_filesystem->get_contents( $file_name );
		}

		/**
		 * Filters svg file content.
		 *
		 * @since 1.6.12
		 *
		 * @param string $file_content SVG file content.
		 * @param string $name SVG file name.
		 */
		return apply_filters( 'masteriyo_elementor_integration_svg_file', $file_contents, $name );
	}

	/**
	 * Convert SVG content to base64.
	 *
	 * @since 1.6.12
	 *
	 * @param string $content SVG content.
	 *
	 * @return string
	 */
	public static function svg_to_base64( $content ) {
		return 'data:image/svg+xml;base64,' . base64_encode( $content ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Get icon URLs for a widget.
	 *
	 * @since 1.6.12
	 *
	 * @param string $icon_name SVG filename.
	 * @param string $color_attr Attribute that sets the color of the SVG, eg. "fill", "stroke".
	 *
	 * @return string
	 */
	public static function get_widget_icon_urls( $icon_name, $color_attr = '' ) {
		$svg = self::get_svg( $icon_name );

		if ( 'stroke' === $color_attr ) {
			$icons = array(
				'normal_state_icon'            => self::svg_to_base64( str_replace( 'stroke="#000"', 'stroke="#556068"', $svg ) ),
				'hover_state_icon'             => self::svg_to_base64( str_replace( 'stroke="#000"', 'stroke="#93003c"', $svg ) ),
				'normal_state_dark_theme_icon' => self::svg_to_base64( str_replace( 'stroke="#000"', 'stroke="#E0E1E3"', $svg ) ),
				'hover_state_dark_theme_icon'  => self::svg_to_base64( str_replace( 'stroke="#000"', 'stroke="#71D7F7"', $svg ) ),
			);
		} else {
			$icons = array(
				'normal_state_icon'            => self::svg_to_base64( str_replace( 'fill="#000"', 'fill="#556068"', $svg ) ),
				'hover_state_icon'             => self::svg_to_base64( str_replace( 'fill="#000"', 'fill="#93003c"', $svg ) ),
				'normal_state_dark_theme_icon' => self::svg_to_base64( str_replace( 'fill="#000"', 'fill="#E0E1E3"', $svg ) ),
				'hover_state_dark_theme_icon'  => self::svg_to_base64( str_replace( 'fill="#000"', 'fill="#71D7F7"', $svg ) ),
			);
		}

		return $icons;
	}

	/**
	 * Get the default layout Elementor template of widgets of single course page.
	 *
	 * @since 1.6.12
	 *
	 * @return array
	 */
	public static function get_single_course_page_default_layout_elementor_template() {
		$template = array(
			array(
				'elType'   => 'section',
				'isInner'  => false,
				'isLocked' => false,
				'settings' => array(
					'structure'     => '22',
					'content_width' => array(
						'unit'  => 'px',
						'size'  => 1240,
						'sizes' => array(),
					),
				),
				'elements' => array(
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size'  => 66,
							'_inline_size'  => 67,
							'border_border' => 'solid',
							'border_width'  => array(
								'unit'     => 'px',
								'top'      => '1',
								'right'    => '1',
								'bottom'   => '1',
								'left'     => '1',
								'isLinked' => true,
							),
							'border_color'  => '#EBECF2',
							'border_radius' => array(
								'unit'     => 'px',
								'top'      => '8',
								'right'    => '8',
								'bottom'   => '8',
								'left'     => '8',
								'isLinked' => true,
							),
							'margin'        => array(
								'unit'     => 'px',
								'top'      => '0',
								'right'    => '30',
								'bottom'   => '0',
								'left'     => '0',
								'isLinked' => false,
							),
							'padding'       => array(
								'unit'     => 'px',
								'top'      => '0',
								'right'    => '0',
								'bottom'   => '0',
								'left'     => '0',
								'isLinked' => true,
							),
						),
						'elements' => array(
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-featured-image',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-categories-of-course',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-title',
							),
							array(
								'elType'   => 'section',
								'isInner'  => true,
								'isLocked' => false,
								'settings' => array(
									'structure' => '20',
									'padding'   => array(
										'unit'     => 'px',
										'top'      => '0',
										'right'    => '25',
										'bottom'   => '0',
										'left'     => '25',
										'isLinked' => false,
									),
									'layout'    => 'boxed',
								),
								'elements' => array(
									array(
										'elType'   => 'column',
										'isInner'  => true,
										'isLocked' => false,
										'settings' => array(
											'_column_size' => 50,
											'_inline_size' => 35.638,
										),
										'elements' => array(
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-author',
											),
										),
									),
								),
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-contents',
							),
						),
					),
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size' => 33,
							'_inline_size' => 32.263,
							'padding'      => array(
								'unit'     => 'px',
								'top'      => '0',
								'right'    => '0',
								'bottom'   => '0',
								'left'     => '0',
								'isLinked' => true,
							),
						),
						'elements' => array(
							array(
								'elType'   => 'section',
								'isInner'  => true,
								'isLocked' => false,
								'settings' => array(),
								'elements' => array(
									array(
										'elType'   => 'column',
										'isInner'  => true,
										'isLocked' => false,
										'settings' => array(
											'_column_size' => 100,
											'_inline_size' => null,
											'border_border' => 'solid',
											'border_width' => array(
												'unit'     => 'px',
												'top'      => '1',
												'right'    => '1',
												'bottom'   => '1',
												'left'     => '1',
												'isLinked' => true,
											),
											'border_color' => '#EBECF2',
											'border_radius' => array(
												'unit'     => 'px',
												'top'      => '8',
												'right'    => '8',
												'bottom'   => '8',
												'left'     => '8',
												'isLinked' => true,
											),
											'padding'      => array(
												'unit'     => 'px',
												'top'      => '25',
												'right'    => '25',
												'bottom'   => '25',
												'left'     => '25',
												'isLinked' => true,
											),
										),
										'elements' => array(
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(
													'_margin' => array(
														'unit'     => 'px',
														'top'      => '0',
														'right'    => '0',
														'bottom'   => '15',
														'left'     => '0',
														'isLinked' => false,
													),
												),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-progress',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(
													'_margin' => array(
														'unit'     => 'px',
														'top'      => '0',
														'right'    => '0',
														'bottom'   => '10',
														'left'     => '0',
														'isLinked' => false,
													),
												),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-enroll-button',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-expiration-info',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(
													'_padding' => array(
														'unit'     => 'px',
														'top'      => '0',
														'right'    => '0',
														'bottom'   => '15',
														'left'     => '0',
														'isLinked' => false,
													),
												),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-retake',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(
													'stats_padding' => array(
														'unit' => 'px',
														'top'  => '0',
														'right' => '0',
														'bottom' => '0',
														'left' => '0',
														'isLinked' => true,
													),
													'stats_margin' => array(
														'unit' => 'px',
														'top'  => '5',
														'right' => '0',
														'bottom' => '0',
														'left' => '0',
														'isLinked' => false,
													),
												),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-stats',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(
													'title' => 'This course includes',
													'header_size' => 'h3',
													'title_color' => '#000000',
													'typography_typography' => 'custom',
													'typography_font_size' => array(
														'unit' => 'px',
														'size' => 16,
														'sizes' => array(),
													),
													'typography_font_weight' => '600',
													'_margin' => array(
														'unit' => 'px',
														'top'  => '8',
														'right' => '0',
														'bottom' => '0',
														'left' => '0',
														'isLinked' => false,
													),
												),
												'elements' => array(),
												'widgetType' => 'heading',
											),
											array(
												'elType'   => 'widget',
												'isInner'  => false,
												'isLocked' => false,
												'settings' => array(),
												'elements' => array(),
												'widgetType' => 'masteriyo-course-highlights',
											),
										),
									),
								),
							),
						),
					),
				),
			),
			array(
				'elType'   => 'section',
				'isInner'  => false,
				'isLocked' => false,
				'settings' => array(
					'structure'     => '22',
					'content_width' => array(
						'unit'  => 'px',
						'size'  => 1240,
						'sizes' => array(),
					),
				),
				'elements' => array(
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size' => 100,
							'_inline_size' => null,
						),
						'elements' => array(
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(
									'title'       => 'Related Courses',
									'title_color' => '#000000',
									'_margin'     => array(
										'unit'     => 'px',
										'top'      => '80',
										'right'    => '0',
										'bottom'   => '0',
										'left'     => '0',
										'isLinked' => false,
									),
								),
								'elements'   => array(),
								'widgetType' => 'heading',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(
									'per_page'        => 3,
									'source'          => 'related',
									'columns_per_row' => 3,
								),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-list',
							),
						),
					),
				),
			),
		);

			/**
			 * Filters the default layout Elementor template of widgets of single course page.
			 *
			 * @since 1.6.12
			 *
			 * @param array $template
			 */
			return apply_filters( 'masteriyo_single_course_page_default_layout_elementor_template', $template );
	}

	/**
	 * Get the default layout Elementor template of widgets of course archive page.
	 *
	 * @since 1.6.12
	 *
	 * @return array
	 */
	public static function get_course_archive_page_default_layout_elementor_template() {
		$template = array(
			array(
				'elType'   => 'section',
				'isInner'  => false,
				'isLocked' => false,
				'settings' => array(),
				'elements' => array(
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size' => 100,
							'_inline_size' => null,
						),
						'elements' => array(
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-courses-toolbar',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(
									'show_pagination' => 'yes',
								),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-list',
							),
						),
					),
				),
			),
		);

		/**
		 * Filters the default layout Elementor template of widgets of course archive page.
		 *
		 * @since 1.6.12
		 *
		 * @param array $template
		 */
		return apply_filters( 'masteriyo_course_archive_page_default_layout_elementor_template', $template );
	}

	/**
	 * Get the template for library modal open button.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	public static function get_library_modal_open_btn_template() {
		ob_start();
		include __DIR__ . '/templates/library-btn.php';
		return ob_get_clean();
	}

	/**
	 * Check if the current request is for elementor editor.
	 *
	 * @since 1.6.12
	 *
	 * @return boolean
	 */
	public static function is_elementor_editor() {
		return isset( $_REQUEST['action'] ) && ( in_array( $_REQUEST['action'], array( 'elementor', 'elementor_ajax' ), true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Check if the current request is for elementor preview.
	 *
	 * @since 1.6.12
	 *
	 * @return boolean
	 */
	public static function is_elementor_preview() {
		return isset( $_GET['elementor_library'] ) && isset( $_GET['preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get a course to use for preview in elementor editor.
	 *
	 * @since 1.6.12
	 *
	 * @return \Masteriyo\Models\Course|null
	 */
	public static function get_elementor_preview_course() {
		global $masteriyo_elementor_preview_course;

		if ( empty( $masteriyo_elementor_preview_course ) ) {
			$posts = get_posts(
				array(
					'posts_per_page' => 1,
					'post_type'      => PostType::COURSE,
					'post_status'    => array( PostStatus::PUBLISH, PostStatus::DRAFT ),
					'author'         => masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ? null : get_current_user_id(),
				)
			);

			$masteriyo_elementor_preview_course = empty( $posts ) ? null : masteriyo_get_course( $posts[0] );
		}

		return apply_filters( 'masteriyo_elementor_preview_course', $masteriyo_elementor_preview_course );
	}

	/**
	 * Shared widget/column/inner-column builders for the layout templates.
	 *
	 * @since x.x.x
	 *
	 * @return callable[] [ $widget, $column, $inner_column ]
	 */
	private static function get_template_element_builders() {
		$widget = function( $type, $settings = array() ) {
			return array(
				'elType'     => 'widget',
				'isInner'    => false,
				'isLocked'   => false,
				'settings'   => $settings,
				'elements'   => array(),
				'widgetType' => $type,
			);
		};

		$make_column = function( $is_inner ) {
			return function( $size, $elements, $settings = array() ) use ( $is_inner ) {
				return array(
					'elType'   => 'column',
					'isInner'  => $is_inner,
					'isLocked' => false,
					'settings' => array_merge(
						array(
							'_column_size' => $size,
							'_inline_size' => null,
						),
						$settings
					),
					'elements' => $elements,
				);
			};
		};

		return array( $widget, $make_column( false ), $make_column( true ) );
	}

	/**
	 * Get the Modern (layout1) Elementor template for the single course page.
	 *
	 * Two-column layout matching core layout1: header section (full-width banner with
	 * featured image, title, categories, author, rating, stats), then main content
	 * left column (overview, curriculum, reviews) with sidebar right column
	 * (price/enroll, retake, highlights).
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function get_single_course_page_layout1_elementor_template() {
		list( $w, $col, $inner_col ) = self::get_template_element_builders();

		// ── Header section: full-width banner ──────────────────────────────────
		$header_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'        => '10',
				'background_color' => '#1A1A2E',
				'content_width'    => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
				'padding'          => array(
					'unit'     => 'px',
					'top'      => '40',
					'right'    => '0',
					'bottom'   => '40',
					'left'     => '0',
					'isLinked' => false,
				),
			),
			'elements' => array(
				$col(
					100,
					array(
						// Inner two-column: left text, right featured image
						array(
							'elType'   => 'section',
							'isInner'  => true,
							'isLocked' => false,
							'settings' => array(
								'structure' => '20',
							),
							'elements' => array(
								$inner_col(
									60,
									array(
										$w(
											'masteriyo-categories-of-course',
											array(
												'category_color'            => '#ffffff',
												'category_background_color' => 'rgba(255,255,255,0.15)',
											)
										),
										$w(
											'masteriyo-course-title',
											array(
												'title_color' => '#ffffff',
											)
										),
										$w(
											'masteriyo-course-author',
											array(
												'author_name_color' => '#cccccc',
											)
										),
										$w( 'masteriyo-course-stats' ),
									)
								),
								$inner_col(
									40,
									array(
										$w( 'masteriyo-course-featured-image' ),
									)
								),
							),
						),
					)
				),
			),
		);

		// ── Main content + sidebar ─────────────────────────────────────────────
		$body_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '22',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
				'padding'       => array(
					'unit'     => 'px',
					'top'      => '40',
					'right'    => '0',
					'bottom'   => '40',
					'left'     => '0',
					'isLinked' => false,
				),
			),
			'elements' => array(
				// Left: main content ~66%
				$col(
					66,
					array(
						$w( 'masteriyo-course-contents' ),
					),
					array(
						'_inline_size' => 67,
						'padding'      => array(
							'unit'     => 'px',
							'top'      => '0',
							'right'    => '30',
							'bottom'   => '0',
							'left'     => '0',
							'isLinked' => false,
						),
					)
				),
				// Right: sidebar ~33%
				$col(
					33,
					array(
						array(
							'elType'   => 'section',
							'isInner'  => true,
							'isLocked' => false,
							'settings' => array(
								'border_border' => 'solid',
								'border_width'  => array(
									'unit'     => 'px',
									'top'      => '1',
									'right'    => '1',
									'bottom'   => '1',
									'left'     => '1',
									'isLinked' => true,
								),
								'border_color'  => '#EBECF2',
								'border_radius' => array(
									'unit'     => 'px',
									'top'      => '8',
									'right'    => '8',
									'bottom'   => '8',
									'left'     => '8',
									'isLinked' => true,
								),
								'padding'       => array(
									'unit'     => 'px',
									'top'      => '25',
									'right'    => '25',
									'bottom'   => '25',
									'left'     => '25',
									'isLinked' => true,
								),
							),
							'elements' => array(
								$inner_col(
									100,
									array(
										$w(
											'masteriyo-course-progress',
											array(
												'_margin' => array(
													'unit' => 'px',
													'top'  => '0',
													'right' => '0',
													'bottom' => '15',
													'left' => '0',
													'isLinked' => false,
												),
											)
										),
										$w( 'masteriyo-course-enroll-button' ),
										$w( 'masteriyo-course-expiration-info' ),
										$w( 'masteriyo-course-retake' ),
										$w(
											'heading',
											array(
												'title'   => __( 'This course includes', 'learning-management-system' ),
												'header_size' => 'h3',
												'title_color' => '#000000',
												'typography_typography' => 'custom',
												'typography_font_size' => array(
													'unit' => 'px',
													'size' => 16,
													'sizes' => array(),
												),
												'typography_font_weight' => '600',
												'_margin' => array(
													'unit' => 'px',
													'top'  => '12',
													'right' => '0',
													'bottom' => '0',
													'left' => '0',
													'isLinked' => false,
												),
											)
										),
										$w( 'masteriyo-course-highlights' ),
									)
								),
							),
						),
					),
					array( '_inline_size' => 32.263 )
				),
			),
		);

		// ── Related courses ────────────────────────────────────────────────────
		$related_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '10',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
			),
			'elements' => array(
				$col(
					100,
					array(
						$w(
							'heading',
							array(
								'title'                  => __( 'Related Courses', 'learning-management-system' ),
								'header_size'            => 'h2',
								'title_color'            => '#000000',
								'typography_typography'  => 'custom',
								'typography_font_size'   => array(
									'unit' => 'px',
									'size' => 18,
								),
								'typography_font_weight' => '600',
								'_margin'                => array(
									'unit'     => 'px',
									'top'      => '40',
									'right'    => '0',
									'bottom'   => '12',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w(
							'masteriyo-course-list',
							array(
								'per_page'        => 3,
								'source'          => 'related',
								'columns_per_row' => 3,
								'show_filter'     => 'no',
								'show_pagination' => 'no',
							)
						),
					)
				),
			),
		);

		$template = array( $header_section, $body_section, $related_section );

		/**
		 * Filters the Modern layout Elementor template for the single course page.
		 *
		 * @since x.x.x
		 *
		 * @param array $template
		 */
		return apply_filters( 'masteriyo_single_course_page_layout1_elementor_template', $template );
	}

	/**
	 * Get the Minimal Elementor template for the single course page.
	 *
	 * Mirrors the core minimal layout: full-width featured image, two-column body
	 * (left: meta/highlights, right: enroll card), tab content, related courses.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function get_single_course_page_minimal_elementor_template() {
		list( $w, $col, $inner_col ) = self::get_template_element_builders();

		// ── Section 1: Full-width featured image ──────────────────────────────
		$image_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '10',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
			),
			'elements' => array(
				$col( 100, array( $w( 'masteriyo-course-featured-image' ) ) ),
			),
		);

		// ── Section 2: Two-column body (left meta + right sidebar card) ───────
		$body_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '22',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
				'padding'       => array(
					'unit'     => 'px',
					'top'      => '32',
					'right'    => '0',
					'bottom'   => '40',
					'left'     => '0',
					'isLinked' => false,
				),
			),
			'elements' => array(
				// Left column ~66%
				$col(
					66,
					array(
						$w(
							'masteriyo-categories-of-course',
							array(
								'_padding' => array(
									'unit'     => 'px',
									'top'      => '0',
									'right'    => '0',
									'bottom'   => '8',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w(
							'masteriyo-course-title',
							array(
								'title_typography_typography' => 'custom',
								'title_typography_font_size' => array(
									'unit'  => 'px',
									'size'  => 32,
									'sizes' => array(),
								),
								'title_typography_font_weight' => '700',
								'_padding' => array(
									'unit'     => 'px',
									'top'      => '0',
									'right'    => '0',
									'bottom'   => '12',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w(
							'masteriyo-course-author',
							array(
								'_padding' => array(
									'unit'     => 'px',
									'top'      => '0',
									'right'    => '0',
									'bottom'   => '12',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w(
							'masteriyo-course-stats',
							array(
								'stats_padding' => array(
									'unit'     => 'px',
									'top'      => '0',
									'right'    => '0',
									'bottom'   => '0',
									'left'     => '0',
									'isLinked' => true,
								),
								'_margin'       => array(
									'unit'     => 'px',
									'top'      => '0',
									'right'    => '0',
									'bottom'   => '20',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w( 'masteriyo-course-highlights' ),
					),
					array(
						'_inline_size' => 67,
						'padding'      => array(
							'unit'     => 'px',
							'top'      => '0',
							'right'    => '30',
							'bottom'   => '0',
							'left'     => '0',
							'isLinked' => false,
						),
					)
				),
				// Right sidebar ~33% with card border
				$col(
					33,
					array(
						array(
							'elType'   => 'section',
							'isInner'  => true,
							'isLocked' => false,
							'settings' => array(
								'border_border' => 'solid',
								'border_width'  => array(
									'unit'     => 'px',
									'top'      => '1',
									'right'    => '1',
									'bottom'   => '1',
									'left'     => '1',
									'isLinked' => true,
								),
								'border_color'  => '#EBECF2',
								'border_radius' => array(
									'unit'     => 'px',
									'top'      => '8',
									'right'    => '8',
									'bottom'   => '8',
									'left'     => '8',
									'isLinked' => true,
								),
								'padding'       => array(
									'unit'     => 'px',
									'top'      => '25',
									'right'    => '25',
									'bottom'   => '25',
									'left'     => '25',
									'isLinked' => true,
								),
							),
							'elements' => array(
								$inner_col(
									100,
									array(
										$w(
											'masteriyo-course-progress',
											array(
												'_margin' => array(
													'unit' => 'px',
													'top'  => '0',
													'right' => '0',
													'bottom' => '15',
													'left' => '0',
													'isLinked' => false,
												),
											)
										),
										$w( 'masteriyo-course-enroll-button' ),
										$w( 'masteriyo-course-expiration-info' ),
										$w( 'masteriyo-course-retake' ),
									)
								),
							),
						),
					),
					array( '_inline_size' => 32.263 )
				),
			),
		);

		// ── Section 3: Tab content (Overview / Curriculum / Reviews) ──────────
		$content_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '10',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
			),
			'elements' => array(
				$col( 100, array( $w( 'masteriyo-course-contents' ) ) ),
			),
		);

		// ── Section 4: Related courses ─────────────────────────────────────────
		$related_section = array(
			'elType'   => 'section',
			'isInner'  => false,
			'isLocked' => false,
			'settings' => array(
				'structure'     => '10',
				'content_width' => array(
					'unit'  => 'px',
					'size'  => 1240,
					'sizes' => array(),
				),
			),
			'elements' => array(
				$col(
					100,
					array(
						$w(
							'heading',
							array(
								'title'                  => __( 'Related Courses', 'learning-management-system' ),
								'header_size'            => 'h2',
								'title_color'            => '#000000',
								'typography_typography'  => 'custom',
								'typography_font_size'   => array(
									'unit' => 'px',
									'size' => 18,
								),
								'typography_font_weight' => '600',
								'_margin'                => array(
									'unit'     => 'px',
									'top'      => '40',
									'right'    => '0',
									'bottom'   => '12',
									'left'     => '0',
									'isLinked' => false,
								),
							)
						),
						$w(
							'masteriyo-course-list',
							array(
								'per_page'        => 3,
								'source'          => 'related',
								'columns_per_row' => 3,
								'show_filter'     => 'no',
								'show_pagination' => 'no',
							)
						),
					)
				),
			),
		);

		$template = array( $image_section, $body_section, $content_section, $related_section );

		/**
		 * Filters the Minimal layout Elementor template for the single course page.
		 *
		 * @since x.x.x
		 *
		 * @param array $template
		 */
		return apply_filters( 'masteriyo_single_course_page_minimal_elementor_template', $template );
	}

	/**
	 * Get the Modern (layout1) Elementor template for the course archive page.
	 *
	 * Identical structure to the default layout but with layout1 set on the
	 * course list widget so cards render in the Modern card style.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function get_course_archive_page_layout1_elementor_template() {
		$template = array(
			array(
				'elType'   => 'section',
				'isInner'  => false,
				'isLocked' => false,
				'settings' => array(),
				'elements' => array(
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size' => 100,
							'_inline_size' => null,
						),
						'elements' => array(
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-courses-toolbar',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(
									'layout'          => 'layout1',
									'show_pagination' => 'yes',
								),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-list',
							),
						),
					),
				),
			),
		);

		return apply_filters( 'masteriyo_course_archive_page_layout1_elementor_template', $template );
	}

	/**
	 * Get the Overlay (layout2) Elementor template for the course archive page.
	 *
	 * Identical structure to the default layout but with layout2 set on the
	 * course list widget so cards render in the Overlay card style.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function get_course_archive_page_layout2_elementor_template() {
		$template = array(
			array(
				'elType'   => 'section',
				'isInner'  => false,
				'isLocked' => false,
				'settings' => array(),
				'elements' => array(
					array(
						'elType'   => 'column',
						'isInner'  => false,
						'isLocked' => false,
						'settings' => array(
							'_column_size' => 100,
							'_inline_size' => null,
						),
						'elements' => array(
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(),
								'elements'   => array(),
								'widgetType' => 'masteriyo-courses-toolbar',
							),
							array(
								'elType'     => 'widget',
								'isInner'    => false,
								'isLocked'   => false,
								'settings'   => array(
									'layout'          => 'layout2',
									'show_pagination' => 'yes',
								),
								'elements'   => array(),
								'widgetType' => 'masteriyo-course-list',
							),
						),
					),
				),
			),
		);

		return apply_filters( 'masteriyo_course_archive_page_layout2_elementor_template', $template );
	}

	/**
	 * Get Elementor templates of all type or a specific type.
	 *
	 * @since 1.6.12
	 *
	 * @param string $template_type
	 *
	 * @return array
	 */
	public static function get_elementor_templates( $template_type = '' ) {
		$query_args = array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(),
		);

		if ( ! empty( $template_type ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_elementor_template_type',
				'value' => $template_type,
			);
		}

		$templates_query = new \WP_Query( $query_args );
		$templates       = array();

		if ( $templates_query->have_posts() ) {
			foreach ( $templates_query->get_posts() as $post ) {
				$templates[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title,
				);
			}
		}

		return $templates;
	}
}
