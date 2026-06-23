<?php
/**
 * Masteriyo elementor integration addon setup.
 *
 * @package Masteriyo\Addons\ElementorIntegration
 *
 * @since 1.6.12
 */
namespace Masteriyo\Addons\ElementorIntegration;

use Masteriyo\Addons\ElementorIntegration\DocumentTypes\CourseArchivePageDocumentType;
use Masteriyo\Addons\ElementorIntegration\DocumentTypes\SingleCoursePageDocumentType;
use Masteriyo\Addons\ElementorIntegration\Widgets\CategoriesOfCourseWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CategoryCarouselWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseArchivePaginationWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseArchiveViewModeWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseAuthorWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseCarouselWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseCategoriesWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseContentsWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseCurriculumWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseEnrollButtonWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseExpirationInfoWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseFeaturedImageWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseHighlightsWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseListWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseOverviewWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CoursePriceWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseProgressWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseRatingWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseRetakeWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseReviewsWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseSearchFormWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CoursesToolbarWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseStatsWidget;
use Masteriyo\Addons\ElementorIntegration\Widgets\CourseTitleWidget;
use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo elementor integration class.
 *
 * @class Masteriyo\Addons\ElementorIntegration\ElementorIntegrationAddon
 */
class ElementorIntegrationAddon {

	/**
	 * Initialize module.
	 *
	 * @since 1.6.12
	 */
	public function init() {
		$this->init_hooks();

		( new UseTemplateForMasteriyoAction() )->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.6.12
	 */
	public function init_hooks() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'add_additional_editor_css' ) );
		add_action( 'elementor/documents/register', array( $this, 'register_document_types' ) );
		add_action( 'manage_elementor_library_posts_custom_column', array( $this, 'render_document_type_column_info' ), 10, 2 );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_elementor_editor_scripts' ), 20 );
		add_action( 'elementor/editor/footer', array( $this, 'print_editor_views' ) );
		add_action( 'masteriyo_single_course_after_template_content_elementor', 'masteriyo_single_course_modals', 10 );
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'add_backend_script_data' ) );
		add_action( 'masteriyo_course_archive_page_custom_template_render', array( $this, 'render_course_archive_page_template' ), 10, 2 );
		add_action( 'masteriyo_single_course_page_custom_template_render', array( $this, 'render_single_course_page_template' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'add_use_template_for_masteriyo_action' ), 10, 2 );
		add_filter( 'display_post_states', array( $this, 'add_post_states' ), 10, 2 );
		add_action( 'elementor/elements/categories_registered', array( $this, 'reorder_category_to_top' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_template_library_admin_scripts' ) );
		// Runs after ScriptStyle enqueues 'masteriyo-public' (PHP_INT_MAX - 10).
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_styles_and_scripts' ), PHP_INT_MAX - 9 );
		add_action( 'wp', array( $this, 'set_course_list_layout_context' ) );
		add_action( 'wp_footer', array( $this, 'clear_course_list_layout_context' ), PHP_INT_MAX );
		add_filter( 'body_class', array( $this, 'add_layout_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_template_css' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_styles' ) );
	}

	/**
	 * Enqueue styles for the Elementor editor preview iframe.
	 *
	 * Hides the view-mode switcher in real time when a Course List widget on the
	 * canvas uses a non-default layout. CSS handles this because the layout can
	 * change live in the editor, where server-side rendering would be stale.
	 *
	 * @since x.x.x
	 */
	public function enqueue_preview_styles() {
		wp_register_style( 'masteriyo-elementor-preview', false, array(), Constants::get( 'MASTERIYO_VERSION' ) );
		wp_enqueue_style( 'masteriyo-elementor-preview' );
		wp_add_inline_style(
			'masteriyo-elementor-preview',
			'body:has(.masteriyo-course-list-display-section[data-layout]) .masteriyo-courses-view-mode-section{display:none !important;}'
		);
	}

	/**
	 * Enqueue Elementor custom template CSS for Masteriyo pages.
	 *
	 * @since x.x.x
	 */
	public function enqueue_custom_template_css() {
		if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			return;
		}

		$template_id = 0;

		if ( masteriyo_is_courses_page() ) {
			if ( masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.display.template.custom_template.enable' ) ) && 'elementor' === masteriyo_get_setting( 'course_archive.display.template.custom_template.template_source' ) ) {
				$template_id = masteriyo_get_setting( 'course_archive.display.template.custom_template.template_id' );
			}
		} elseif ( masteriyo_is_single_course_page() ) {
			if ( masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.template.custom_template.enable' ) ) && 'elementor' === masteriyo_get_setting( 'single_course.display.template.custom_template.template_source' ) ) {
				$template_id = masteriyo_get_setting( 'single_course.display.template.custom_template.template_id' );
			}
		}

		if ( $template_id ) {
			// Force Elementor to recognize that the page contains Elementor content.
			\Elementor\Plugin::$instance->frontend->has_elementor_in_page( true );

			// Enqueue global Elementor styles first.
			if ( ! \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				\Elementor\Plugin::$instance->frontend->enqueue_styles();
			}

			$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
			$css_file->enqueue();
		}
	}

	/**
	 * Registers scripts and styles needed for carousel widgets, and enqueues the
	 * frontend widget-override stylesheet.
	 *
	 * @since 1.13.0
	 */
	public function register_widget_styles_and_scripts() {
		wp_register_style( 'masteriyo-widget-swiper', plugins_url( 'libs/swiper/swiper-bundle.min.css', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ), array(), Constants::get( 'MASTERIYO_VERSION' ) );

		wp_register_script( 'masteriyo-widget-swiper', plugins_url( 'libs/swiper/swiper-bundle.min.js', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ), array( 'jquery' ), Constants::get( 'MASTERIYO_VERSION' ), true );

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'masteriyo-widget-carousel', MASTERIYO_ELEMENTOR_INTEGRATION_URL . 'js/carousel' . $suffix . '.js', array( 'masteriyo-widget-swiper' ), Constants::get( 'MASTERIYO_VERSION' ), true );

		// Load the widget overrides only where the main stylesheet is present.
		if ( wp_style_is( 'masteriyo-public', 'enqueued' ) ) {
			wp_enqueue_style(
				'masteriyo-elementor-widget-overrides',
				MASTERIYO_ELEMENTOR_INTEGRATION_URL . 'css/widget-overrides.css',
				array( 'masteriyo-public' ),
				Constants::get( 'MASTERIYO_VERSION' )
			);
		}
	}

	/**
	 * Register masteriyo category.
	 *
	 * @since 1.6.12
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'masteriyo',
			array(
				'title' => __( 'Masteriyo LMS', 'learning-management-system' ),
			)
		);
		$elements_manager->add_category(
			'masteriyo-single-course',
			array(
				'title' => __( 'Masteriyo - Single Course', 'learning-management-system' ),
				'icon'  => 'eicon-library-open',
			)
		);
	}

	/**
	 * Register elementor widgets.
	 *
	 * @since 1.6.12
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {
		$widgets = array(
			new CourseListWidget(),
			new CourseCategoriesWidget(),
			new CourseTitleWidget(),
			new CoursePriceWidget(),
			new CourseFeaturedImageWidget(),
			new CourseEnrollButtonWidget(),
			new CourseStatsWidget(),
			new CourseProgressWidget(),
			new CourseHighlightsWidget(),
			new CategoriesOfCourseWidget(),
			new CourseAuthorWidget(),
			new CourseRatingWidget(),
			new CourseContentsWidget(),
			new CourseOverviewWidget(),
			new CourseCurriculumWidget(),
			new CourseReviewsWidget(),
			new CourseArchivePaginationWidget(),
			new CourseSearchFormWidget(), // Hidden from panel; kept for existing pages.
			new CoursesToolbarWidget(),
			new CourseArchiveViewModeWidget(),
			new CourseRetakeWidget(),
			new CourseExpirationInfoWidget(),
			new CourseCarouselWidget(),
			new CategoryCarouselWidget(),
		);

		$widgets = apply_filters( 'elementor_course_widgets', $widgets );

		foreach ( $widgets as $widget ) {
			$widgets_manager->register( $widget );
		}
	}

	/**
	 * Add additional CSS for the elementor editor.
	 *
	 * @since 1.6.12
	 */
	public function add_additional_editor_css() {
		$indent_css       = $this->generate_css_for_indent_control();
		$widget_icons_css = $this->generate_css_for_widget_icons();

		$css = "
			{$widget_icons_css}
			{$indent_css}
		";

		wp_add_inline_style( 'elementor-editor', $css );
	}

	/**
	 * Generate widget icons css.
	 *
	 * @since 1.6.12
	 *
	 * @return string
	 */
	protected function generate_css_for_widget_icons() {
		$css   = '';
		$icons = array(
			array_merge(
				array(
					'class' => 'masteriyo-course-list-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-list-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-carousel-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-carousel-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-categories-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-categories-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-category-carousel-widget-icon',
				),
				Helper::get_widget_icon_urls( 'category-carousel-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-categories-of-course-widget-icon',
				),
				Helper::get_widget_icon_urls( 'categories-of-course-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-archive-view-mode-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-archive-view-mode-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-author-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-author-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-contents-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-contents-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-curriculum-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-curriculum-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-enroll-button-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-enroll-button-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-featured-image-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-featured-image-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-highlights-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-highlights-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-overview-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-overview-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-pagination-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-pagination-widget-icon', 'stroke' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-price-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-price-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-rating-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-rating-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-reviews-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-reviews-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-search-form-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-search-form-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-courses-toolbar-widget-icon',
				),
				Helper::get_widget_icon_urls( 'courses-toolbar-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-stats-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-stats-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-progress-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-progress-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-title-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-title-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'masteriyo-course-retake-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-retake-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'course-google-classroom-meta-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-google-classroom-meta-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'course-coming-soon-widget-icon',
				),
				Helper::get_widget_icon_urls( 'course-coming-soon-widget-icon' )
			),
			array_merge(
				array(
					'class' => 'group-course-meta-widget-icon',
				),
				Helper::get_widget_icon_urls( 'group-course-meta-widget-icon' )
			),
		);

		/**
		 * Filters Elementor widgets svg icons data. It will be used to generate CSS so that the icon class works on widgets.
		 *
		 * @since 1.6.12
		 *
		 * @param array $icons
		 */
		$icons = apply_filters( 'masteriyo_elementor_widgets_svg_icons_data', $icons );

		foreach ( $icons as $icon_data ) {
			$class                        = sanitize_html_class( $icon_data['class'] );
			$normal_state_icon            = $icon_data['normal_state_icon'];
			$hover_state_icon             = $icon_data['hover_state_icon'];
			$normal_state_dark_theme_icon = $icon_data['normal_state_dark_theme_icon'];
			$hover_state_dark_theme_icon  = $icon_data['hover_state_dark_theme_icon'];
			$css                         .= "
				.{$class}:before {
					content: '';
					background-image: url({$normal_state_icon});
					height: 28px;
					display: block;
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center center;
				}
				.elementor-element:hover .{$class}:before {
					background-image: url({$hover_state_icon});
				}
				#elementor-navigator .{$class}:before {
					width: 11px;
					height: 11px;
				}

				@media (prefers-color-scheme: dark) {
					.{$class}:before {
						background-image: url({$normal_state_dark_theme_icon});
					}
					.elementor-element:hover .{$class}:before {
						background-image: url({$hover_state_dark_theme_icon});
					}
				}
			";
		}

		return $css;
	}

	/**
	 * Generate CSS for indent control.
	 *
	 * @since 1.6.12
	 *
	 * @return string.
	 */
	private function generate_css_for_indent_control() {
		/**
		 * Filters elementor widget control names that should be indented.
		 *
		 * @since 1.6.12
		 *
		 * @param string[] $indented_controls
		 */
		$indented_controls = apply_filters(
			'masteriyo_elementor_integration_indented_controls',
			array(
				// Course List widget.
				'show_difficulty_badge',
				'show_featured_ribbon',
				'show_author_avatar',
				'show_author_name',
				'show_course_duration',
				'show_students_count',
				'show_lessons_count',
				'show_price',
				'show_enroll_button',

				// Course List widget — filter components.
				'filter_category',
				'filter_difficulty',
				'filter_price_type',
				'filter_price',
				'filter_rating',

				// Courses Toolbar widget — sort options.
				'sort_by_date',
				'sort_by_price',
				'sort_by_rating',
				'sort_by_title',

				// Course Categories widget.
				'show_category_title',
				'show_courses_count',
			)
		);
		$indent_css        = '';

		foreach ( $indented_controls as $control_name ) {
			$indent_css .= sprintf( '.elementor-control-%s .elementor-control-title {margin-left:12px;}', $control_name );
		}

		return $indent_css;
	}

	/**
	 * Register Elementor document types.
	 *
	 * @since 1.6.12
	 *
	 * @param \Elementor\Core\Documents_Manager $documents_manager
	 */
	public function register_document_types( $documents_manager ) {
		$documents_manager->register_document_type( 'masteriyo-single-course-page', SingleCoursePageDocumentType::get_class_full_name() );
		$documents_manager->register_document_type( 'masteriyo-course-archive-page', CourseArchivePageDocumentType::get_class_full_name() );
	}

	/**
	 * Render document type information in documents list table.
	 *
	 * @since 1.6.12
	 *
	 * @param string $column_name
	 * @param integer $post_id
	 */
	public function render_document_type_column_info( $column_name, $post_id ) {
		if ( 'elementor_library_type' === $column_name ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );

			if ( $document && str_starts_with( $document->get_template_type(), 'learning-management-system' ) ) {
				$document->print_admin_column_type();
			}
		}
	}

	/**
	 * Enqueue scripts for Elementor editor.
	 *
	 * @since 1.6.12
	 */
	public function enqueue_elementor_editor_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'masteriyo-elementor-editor',
			MASTERIYO_ELEMENTOR_INTEGRATION_URL . 'js/elementor-editor' . $suffix . '.js',
			array(
				'elementor-common',
			),
			MASTERIYO_VERSION,
			true
		);

		wp_localize_script(
			'masteriyo-elementor-editor',
			'_MASTERIYO_ELEMENTOR_EDITOR_',
			array(
				'page_templates'               => array(
					'single_course_page'          => Helper::get_single_course_page_default_layout_elementor_template(),
					'single_course_page_layout1'  => Helper::get_single_course_page_layout1_elementor_template(),
					'single_course_page_minimal'  => Helper::get_single_course_page_minimal_elementor_template(),
					'course_archive_page'         => Helper::get_course_archive_page_default_layout_elementor_template(),
					'course_archive_page_layout1' => Helper::get_course_archive_page_layout1_elementor_template(),
					'course_archive_page_layout2' => Helper::get_course_archive_page_layout2_elementor_template(),
				),
				'single_course_layout_images'  => array(
					'default' => masteriyo_get_plugin_url() . '/assets/img/single-course-default-layout.png',
					'layout1' => masteriyo_get_plugin_url() . '/assets/img/single-course-layout1-layout.png',
					'minimal' => masteriyo_get_plugin_url() . '/assets/img/single-course-minimal-layout.png',
				),
				'course_archive_layout_images' => array(
					'default' => masteriyo_get_plugin_url() . '/assets/img/course-default-layout.png',
					'layout1' => masteriyo_get_plugin_url() . '/assets/img/course-layout1-layout.png',
					'layout2' => masteriyo_get_plugin_url() . '/assets/img/course-layout2-layout.png',
				),
				'course_archive_active_layout' => masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default',
				'library_btn_template'         => Helper::get_library_modal_open_btn_template(),
				'is_elementor_template'        => get_post_type() === 'elementor_library',
				'rest_url'                     => rest_url(),
				'nonce'                        => wp_create_nonce( 'wp_rest' ),
				'i18n'                         => array(
					'set_as_single_course_template'  => __( 'Set as active Single Course Page template', 'learning-management-system' ),
					'set_as_course_archive_template' => __( 'Set as active Course Archive Page template', 'learning-management-system' ),
					'template_activated'             => __( 'Template set as active in Masteriyo settings.', 'learning-management-system' ),
				),
			)
		);
	}

	/**
	 * Print the views for the Elementor editor.
	 *
	 * @since 1.6.12
	 */
	public function print_editor_views() {
		include __DIR__ . '/templates/editor-views.php';
	}

	/**
	 * Localize more data to the backend script.
	 *
	 * @since 1.6.12
	 *
	 * @param array $script_data
	 *
	 * @return array
	 */
	public function add_backend_script_data( $script_data ) {
		$script_data['backend']['data']['singleCourseTemplates']['elementor']  = Helper::get_elementor_templates( SingleCoursePageDocumentType::TYPE_SLUG );
		$script_data['backend']['data']['courseArchiveTemplates']['elementor'] = Helper::get_elementor_templates( CourseArchivePageDocumentType::TYPE_SLUG );
		return $script_data;
	}

	/**
	 * Render custom template for the Course Archive page.
	 *
	 * @since 1.6.12
	 *
	 * @param string $template_source
	 * @param integer $template_id
	 */
	public function render_course_archive_page_template( $template_source, $template_id ) {
		if ( 'elementor' !== $template_source ) {
			return;
		}

		$frontend = \Elementor\Plugin::$instance->frontend;

		masteriyo_display_all_notices();
		printf( '<div class="masteriyo-course-list-display-section masteriyo-container">' );
		echo $frontend->get_builder_content_for_display( $template_id );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '</div>' );
	}

	/**
	 * Render custom template for the Single Course page.
	 *
	 * @since 1.6.12
	 *
	 * @param string $template_source
	 * @param integer $template_id
	 */
	public function render_single_course_page_template( $template_source, $template_id ) {
		if ( 'elementor' !== $template_source ) {
			return;
		}

		global $course;

		$frontend = \Elementor\Plugin::$instance->frontend;

		printf( '<div id="%s" class="masteriyo-single-course masteriyo-container">', esc_attr( $course ? 'course-' . $course->get_id() : '' ) );
		echo $frontend->get_builder_content_for_display( $template_id );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '</div>' );
		do_action( 'masteriyo_single_course_after_template_content_elementor' );
	}

	/**
	 * Add an action link for using the current template for Masteriyo in the appropriate place.
	 *
	 * @since 1.7.0
	 *
	 * @param array $actions An array of row action links.
	 * @param \WP_Post $post The post object.
	 *
	 * @return array
	 */
	public function add_use_template_for_masteriyo_action( $actions, $post ) {
		if ( PostStatus::PUBLISH !== $post->post_status ) {
			return $actions;
		}

		global $current_screen;

		if ( ! $current_screen ) {
			return $actions;
		}

		if ( 'edit' !== $current_screen->base || 'elementor_library' !== $current_screen->post_type ) {
			return $actions;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post->ID );

		if ( empty( $document ) ) {
			return $actions;
		}

		$template_type = $document ? $document->get_template_type() : '';
		$action_slug   = 'masteriyo-use-elementor-template-for-masteriyo';
		$label         = esc_html__( 'Use template for Masteriyo', 'learning-management-system' );
		$url           = '';

		if ( SingleCoursePageDocumentType::TYPE_SLUG === $template_type ) {
			$url = add_query_arg(
				array(
					$action_slug    => '1',
					'template-type' => SingleCoursePageDocumentType::TYPE_SLUG,
					'template-id'   => $post->ID,
					'nonce'         => wp_create_nonce( $action_slug ),
				),
				home_url()
			);
		}

		if ( CourseArchivePageDocumentType::TYPE_SLUG === $template_type ) {
			$url = add_query_arg(
				array(
					$action_slug    => '1',
					'template-type' => CourseArchivePageDocumentType::TYPE_SLUG,
					'template-id'   => $post->ID,
					'nonce'         => wp_create_nonce( $action_slug ),
				),
				home_url()
			);
		}

		if ( ! empty( $url ) ) {
			$actions[ $action_slug ] = sprintf( '<a href="%1$s">%2$s</a>', $url, $label );
		}

		return $actions;
	}

	/**
	 * Add post states.
	 *
	 * @since 1.7.0
	 *
	 * @param array $post_states
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function add_post_states( $post_states, $post ) {
		if ( 'elementor_library' !== $post->post_type ) {
			return $post_states;
		}

		$document      = \Elementor\Plugin::$instance->documents->get( $post->ID );
		$template_type = $document ? $document->get_template_type() : '';

		if ( CourseArchivePageDocumentType::TYPE_SLUG === $template_type ) {
			if (
				masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.display.template.custom_template.enable' ) ) &&
				masteriyo_get_setting( 'course_archive.display.template.custom_template.template_source' ) === 'elementor' &&
				absint( masteriyo_get_setting( 'course_archive.display.template.custom_template.template_id' ) ) === absint( $post->ID )
			) {
				$post_states['masteriyo_used_template'] = __( 'Used by Masteriyo', 'learning-management-system' );
			}
		} elseif ( SingleCoursePageDocumentType::TYPE_SLUG === $template_type ) {
			if (
				masteriyo_string_to_bool( masteriyo_get_setting( 'single_course.display.template.custom_template.enable' ) ) &&
				masteriyo_get_setting( 'single_course.display.template.custom_template.template_source' ) === 'elementor' &&
				absint( masteriyo_get_setting( 'single_course.display.template.custom_template.template_id' ) ) === absint( $post->ID )
			) {
				$post_states['masteriyo_used_template'] = __( 'Used by Masteriyo', 'learning-management-system' );
			}
		}

		return $post_states;
	}

	/**
	 * Move the Masteriyo categories to the top of the Elementor widget panel.
	 *
	 * @since x.x.x
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function reorder_category_to_top( $elements_manager ) {
		$categories = $elements_manager->get_categories();

		if ( ! isset( $categories['masteriyo'] ) ) {
			return;
		}

		$masteriyo_cats = array();
		foreach ( array( 'masteriyo', 'masteriyo-single-course' ) as $key ) {
			if ( isset( $categories[ $key ] ) ) {
				$masteriyo_cats[ $key ] = $categories[ $key ];
				unset( $categories[ $key ] );
			}
		}

		$reordered = array_merge( $masteriyo_cats, $categories );

		// No public API to reorder categories; write the private property, guarded
		// so a future Elementor change degrades gracefully instead of a fatal error.
		try {
			$reflection = new \ReflectionClass( $elements_manager );

			if ( ! $reflection->hasProperty( 'categories' ) ) {
				return;
			}

			$property = $reflection->getProperty( 'categories' );
			$property->setAccessible( true );
			$property->setValue( $elements_manager, $reordered );
		} catch ( \ReflectionException $e ) {
			masteriyo_get_logger()->warning(
				'Unable to reorder Elementor widget categories: ' . $e->getMessage(),
				array( 'source' => 'elementor-integration' )
			);
		}
	}

	/**
	 * Set the Elementor Course List widget layout as a global so the view mode switcher
	 * is hidden for non-default layouts even when rendered by a separate Search Form widget.
	 *
	 * @since x.x.x
	 */
	public function set_course_list_layout_context() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() || \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		$settings = $this->get_course_list_settings();
		if ( null === $settings ) {
			return;
		}

		$layout = isset( $settings['layout'] ) ? $settings['layout'] : 'default';
		if ( $layout ) {
			$GLOBALS['masteriyo_elementor_course_list_layout'] = $layout; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Clear the course-list layout global after the page body has rendered.
	 *
	 * @since x.x.x
	 */
	public function clear_course_list_layout_context() {
		unset( $GLOBALS['masteriyo_elementor_course_list_layout'] );
	}

	/**
	 * Add a body class indicating the active Elementor course list layout so CSS
	 * can hide the view-mode switcher for non-default layouts.
	 *
	 * @since x.x.x
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	public function add_layout_body_class( $classes ) {
		// Skip in the editor preview where the saved layout may not match the live
		// canvas. The preview stylesheet handles layout-based hiding there instead.
		if ( class_exists( '\Elementor\Plugin' )
			&& ( \Elementor\Plugin::$instance->preview->is_preview_mode() || \Elementor\Plugin::$instance->editor->is_edit_mode() ) ) {
			return $classes;
		}

		$layout = $this->get_course_list_layout();
		if ( $layout && 'default' !== $layout ) {
			$classes[] = 'masteriyo-elementor-course-layout-' . sanitize_html_class( $layout );
		}
		return $classes;
	}

	/**
	 * Get the course-list widget layout for the current page.
	 *
	 * @since x.x.x
	 *
	 * @return string|null 'default', 'layout1', 'layout2', or null if not found.
	 */
	private function get_course_list_layout() {
		$settings = $this->get_course_list_settings();

		if ( null === $settings ) {
			return null;
		}

		return isset( $settings['layout'] ) ? $settings['layout'] : 'default';
	}

	/**
	 * Get the first course-list widget's settings on the current page (memoized).
	 *
	 * @since x.x.x
	 *
	 * @return array|null Widget settings, or null if no course-list widget is found.
	 */
	private function get_course_list_settings() {
		static $cached = false;

		if ( false !== $cached ) {
			return $cached;
		}

		$cached = $this->resolve_course_list_settings();

		return $cached;
	}

	/**
	 * Resolve the masteriyo-course-list widget settings from the relevant Elementor data.
	 *
	 * @since x.x.x
	 *
	 * @return array|null
	 */
	private function resolve_course_list_settings() {
		// When a Masteriyo custom Elementor template is active for the course archive,
		// read from that template post rather than the queried page.
		if ( masteriyo_is_courses_page() ) {
			$enable = masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.display.template.custom_template.enable' ) );
			$source = masteriyo_get_setting( 'course_archive.display.template.custom_template.template_source' );
			if ( $enable && 'elementor' === $source ) {
				$template_id = (int) masteriyo_get_setting( 'course_archive.display.template.custom_template.template_id' );
				if ( $template_id ) {
					$data = json_decode( get_post_meta( $template_id, '_elementor_data', true ), true );
					if ( is_array( $data ) ) {
						return self::find_course_list_settings( $data );
					}
				}
			}
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return null;
		}

		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

		if ( empty( $elementor_data ) ) {
			return null;
		}

		$data = json_decode( $elementor_data, true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		return self::find_course_list_settings( $data );
	}

	/**
	 * Recursively search Elementor data for the first course-list widget's settings.
	 *
	 * @since x.x.x
	 *
	 * @param array $elements
	 * @return array|null
	 */
	public static function find_course_list_settings( array $elements ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && 'masteriyo-course-list' === $element['widgetType'] ) {
				return isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			}
			if ( ! empty( $element['elements'] ) ) {
				$result = self::find_course_list_settings( $element['elements'] );
				if ( null !== $result ) {
					return $result;
				}
			}
		}
		return null;
	}

	/**
	 * Enqueue script for the Elementor template library admin page.
	 *
	 * @since x.x.x
	 */
	public function enqueue_template_library_admin_scripts() {
		$screen = get_current_screen();

		if ( ! $screen || 'elementor_library' !== $screen->post_type || 'edit' !== $screen->base ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'masteriyo-elementor-new-template',
			MASTERIYO_ELEMENTOR_INTEGRATION_URL . 'js/elementor-new-template' . $suffix . '.js',
			array( 'jquery' ),
			MASTERIYO_VERSION,
			true
		);

		wp_localize_script(
			'masteriyo-elementor-new-template',
			'_MASTERIYO_ELEMENTOR_NEW_TEMPLATE_',
			array(
				'i18n' => array(
					'set_as_single_course_template'  => __( 'Set as active Single Course Page template', 'learning-management-system' ),
					'set_as_course_archive_template' => __( 'Set as active Course Archive Page template', 'learning-management-system' ),
				),
			)
		);
	}
}
