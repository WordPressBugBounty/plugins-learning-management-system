<?php
/**
 * Masteriyo courses toolbar elementor widget class.
 *
 * Renders the course archive toolbar (search, sorting, view mode) with
 * per-widget show/hide toggles. Supersedes the Course Search Form widget.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\ElementorIntegration\Widgets;

use Elementor\Controls_Manager;
use Masteriyo\Addons\ElementorIntegration\ElementorIntegrationAddon;
use Masteriyo\Addons\ElementorIntegration\Helper;
use Masteriyo\Addons\ElementorIntegration\WidgetBase;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo courses toolbar elementor widget class.
 *
 * @package Masteriyo\Addons\ElementorIntegration\Widgets
 *
 * @since x.x.x
 */
class CoursesToolbarWidget extends WidgetBase {

	/**
	 * Get widget name.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_name() {
		return 'masteriyo-courses-toolbar';
	}

	/**
	 * Get widget title.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Courses Toolbar', 'learning-management-system' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since x.x.x
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'masteriyo-courses-toolbar-widget-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'search', 'sort', 'sorting', 'view', 'view mode', 'mode', 'toolbar' );
	}

	/**
	 * Register controls configuring widget content.
	 *
	 * @since x.x.x
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'components_section',
			array(
				'label' => __( 'Components', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_on_off_switch_control(
			'show_search',
			__( 'Search', 'learning-management-system' ),
			array(
				'default' => 'yes',
			),
			array(
				'{{WRAPPER}} .masteriyo-search' => 'display: none !important;',
			)
		);

		$this->add_on_off_switch_control(
			'show_sorting',
			__( 'Sorting', 'learning-management-system' ),
			array(
				'default' => '',
			),
			array(
				'{{WRAPPER}} .masteriyo-courses-sorting-section' => 'display: none !important;',
			)
		);

		// Which sort options appear in the dropdown; applies to this widget only.
		$sort_options = array(
			'sort_by_date'   => __( 'Sort by Date', 'learning-management-system' ),
			'sort_by_price'  => __( 'Sort by Price', 'learning-management-system' ),
			'sort_by_rating' => __( 'Sort by Rating', 'learning-management-system' ),
			'sort_by_title'  => __( 'Sort by Title', 'learning-management-system' ),
		);

		foreach ( $sort_options as $control_name => $label ) {
			$this->add_control(
				$control_name,
				array(
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'learning-management-system' ),
					'label_off'    => __( 'Hide', 'learning-management-system' ),
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => array(
						'show_sorting' => 'yes',
					),
				)
			);
		}

		// The switcher itself only renders on the Default layout; the layout
		// restriction is enforced server-side in masteriyo_courses_view_mode().
		$this->add_control(
			'show_view_mode',
			array(
				'label'        => __( 'View Mode', 'learning-management-system' ),
				'type'         => Controls_Manager::SWITCHER,
				'description'  => __( 'Show the view mode switcher (list/grid) in the toolbar. Note: Only available on the Default layout.', 'learning-management-system' ),
				'label_on'     => __( 'Show', 'learning-management-system' ),
				'label_off'    => __( 'Hide', 'learning-management-system' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing widget styles.
	 *
	 * @since x.x.x
	 */
	protected function register_style_controls() {
		$this->register_container_style_controls();
		$this->register_search_icon_style_controls();
		$this->register_search_input_style_controls();
		$this->register_search_button_style_controls();
	}

	/**
	 * Register controls for customizing container styles.
	 *
	 * @since x.x.x
	 */
	protected function register_container_style_controls() {
		$this->start_controls_section(
			'container_styles_section',
			array(
				'label' => esc_html__( 'Container', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_text_region_style_controls(
			'container_',
			'form.masteriyo-course-search',
			array(
				'disable_align'       => true,
				'disable_typography'  => true,
				'disable_text_color'  => true,
				'disable_text_shadow' => true,
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing search icon styles.
	 *
	 * @since x.x.x
	 */
	protected function register_search_icon_style_controls() {
		$this->start_controls_section(
			'search_icon_styles_section',
			array(
				'label' => esc_html__( 'Search Icon', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_responsive_control(
			'search_icon_position_from_left',
			array(
				'label'      => __( 'Position From Left', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-search__icon' => 'left: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_responsive_control(
			'search_icon_position_from_top',
			array(
				'label'      => __( 'Position From Top', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .masteriyo-course-search__icon' => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_text_region_style_controls(
			'search_icon_',
			'.masteriyo-course-search__icon',
			array(
				'disable_align'       => false,
				'disable_typography'  => false,
				'disable_text_color'  => false,
				'disable_text_shadow' => false,
				'normal_state_start'  => function() {
					$this->add_control(
						'icon_color',
						array(
							'label'     => __( 'Icon Color', 'learning-management-system' ),
							'type'      => Controls_Manager::COLOR,
							'selectors' => array(
								'{{WRAPPER}} .masteriyo-course-search__icon svg' => 'fill: {{VALUE}} !important;',
							),
						)
					);
					$this->add_responsive_control(
						'icon_size',
						array(
							'label'      => __( 'Icon Size', 'learning-management-system' ),
							'type'       => Controls_Manager::SLIDER,
							'size_units' => array( 'px' ),
							'range'      => array(
								'px' => array(
									'min' => 0,
									'max' => 300,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .masteriyo-course-search__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
							),
						)
					);
				},
				'hover_state_start'   => function() {
					$this->add_control(
						'hover_icon_color',
						array(
							'label'     => __( 'Icon Color', 'learning-management-system' ),
							'type'      => Controls_Manager::COLOR,
							'selectors' => array(
								'{{WRAPPER}} .masteriyo-course-search__icon:hover svg' => 'fill: {{VALUE}} !important;',
							),
						)
					);
					$this->add_responsive_control(
						'hover_icon_size',
						array(
							'label'      => __( 'Icon Size', 'learning-management-system' ),
							'type'       => Controls_Manager::SLIDER,
							'size_units' => array( 'px' ),
							'range'      => array(
								'px' => array(
									'min' => 0,
									'max' => 300,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .masteriyo-course-search__icon:hover svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
							),
						)
					);
				},
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing search input styles.
	 *
	 * @since x.x.x
	 */
	protected function register_search_input_style_controls() {
		$this->start_controls_section(
			'input_styles_section',
			array(
				'label' => esc_html__( 'Input', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_text_region_style_controls(
			'input_',
			'.search-field.masteriyo-input',
			array()
		);
		$this->end_controls_section();
	}

	/**
	 * Register controls for customizing search button styles.
	 *
	 * @since x.x.x
	 */
	protected function register_search_button_style_controls() {
		$this->start_controls_section(
			'button_styles_section',
			array(
				'label' => esc_html__( 'Button', 'learning-management-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_responsive_control(
			'button_position_from_right',
			array(
				'label'      => __( 'Position From Right', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'max' => 300,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} button' => 'right: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_responsive_control(
			'button_position_from_top',
			array(
				'label'      => __( 'Position From Top', 'learning-management-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'max' => 300,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} button' => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_text_region_style_controls(
			'button_',
			'button',
			array(
				'disable_align' => true,
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render widget output in the editor.
	 *
	 * @since x.x.x
	 */
	protected function content_template() {
		// Intentionally empty: an empty JS template makes the editor fall back to
		// a server-side render(), so the per-widget toggles reflect in the preview.
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since x.x.x
	 */
	protected function render() {
		// get_settings_for_display() drops conditional controls (sort_by_* depend
		// on show_sorting), so read the raw settings instead.
		$settings = $this->get_settings();

		// Expose this widget's toggles to the toolbar templates (overrides globals,
		// scoped to this render).
		$GLOBALS['masteriyo_elementor_sorting'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'enabled' => 'yes' === ( $settings['show_sorting'] ?? '' ),
			'date'    => 'yes' === ( $settings['sort_by_date'] ?? 'yes' ),
			'price'   => 'yes' === ( $settings['sort_by_price'] ?? 'yes' ),
			'rating'  => 'yes' === ( $settings['sort_by_rating'] ?? 'yes' ),
			'title'   => 'yes' === ( $settings['sort_by_title'] ?? 'yes' ),
		);

		$GLOBALS['masteriyo_elementor_search'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'enabled' => 'yes' === ( $settings['show_search'] ?? 'yes' ),
		);

		$GLOBALS['masteriyo_elementor_show_view_mode'] = 'yes' === ( $settings['show_view_mode'] ?? 'yes' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// The view-mode switcher renders only on the Default layout. The layout
		// context is set on the `wp` hook on the frontend but is unavailable in
		// the editor and its AJAX re-renders, so resolve it here. In the editor,
		// force 'default' so the switcher always renders — the preview stylesheet
		// hides it live based on the Course List's current layout.
		$original_layout = isset( $GLOBALS['masteriyo_elementor_course_list_layout'] ) ? $GLOBALS['masteriyo_elementor_course_list_layout'] : null;

		if ( Helper::is_elementor_editor() || Helper::is_elementor_preview() ) {
			$GLOBALS['masteriyo_elementor_course_list_layout'] = 'default'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( null === $original_layout ) {
			$layout = $this->resolve_course_list_layout();

			if ( null !== $layout ) {
				$GLOBALS['masteriyo_elementor_course_list_layout'] = $layout; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		masteriyo_course_search_form();

		// Restore all globals so this widget's settings never leak elsewhere.
		unset( $GLOBALS['masteriyo_elementor_sorting'], $GLOBALS['masteriyo_elementor_search'], $GLOBALS['masteriyo_elementor_show_view_mode'] );

		if ( null === $original_layout ) {
			unset( $GLOBALS['masteriyo_elementor_course_list_layout'] );
		} else {
			$GLOBALS['masteriyo_elementor_course_list_layout'] = $original_layout; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Resolve the layout of the Course List widget on the same document/page.
	 *
	 * Checks the current Elementor document first (covers the editor and its AJAX
	 * widget re-renders), then falls back to the queried page's saved Elementor data.
	 *
	 * @since x.x.x
	 *
	 * @return string|null Layout key ('default', 'layout1', 'layout2') or null if no Course List widget found.
	 */
	private function resolve_course_list_layout() {
		$data = null;

		$document = \Elementor\Plugin::$instance->documents->get_current();

		if ( $document ) {
			$data = $document->get_elements_data();
		}

		if ( empty( $data ) ) {
			$post_id = get_queried_object_id();

			if ( $post_id ) {
				$data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );
			}
		}

		if ( ! is_array( $data ) ) {
			return null;
		}

		$settings = ElementorIntegrationAddon::find_course_list_settings( $data );

		if ( null === $settings ) {
			return null;
		}

		return empty( $settings['layout'] ) ? 'default' : $settings['layout'];
	}
}
