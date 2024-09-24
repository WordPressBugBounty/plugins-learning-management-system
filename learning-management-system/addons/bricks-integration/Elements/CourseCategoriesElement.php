<?php
/**
 * Masteriyo course catagories Bricks element class.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;
use Masteriyo\Taxonomy\Taxonomy;

/**
* Masteriyo course categories class.
*
* @since 1.9.0
*/
class CourseCategoriesElement extends \Bricks\Element {
	public $category = 'masteriyo';
	public $name     = 'course-categories';
	public $icon     = 'ti-layout-grid2-alt';

	/**
	* Bricks Courses Categories Label for the element.
	*
	* @since 1.9.0
	*/
	public function get_label() {
		return esc_html__( 'Course Categories', 'learning-management-system' );
	}

	/**
	* Bricks set controls groups for course categories CSS and General controls.
	*
	* @since 1.9.0
	*/
	public function set_control_groups() {
		$this->control_groups['general']         = array(
			'title' => esc_html__( 'General', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['categories_card'] = array(
			'title' => esc_html__( 'Card', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['layout']          = array(
			'title' => esc_html__( 'Layout', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['pagination']      = array(
			'title' => esc_html__( 'Pagination', 'learning-management-system' ),
			'tab'   => 'content',
		);
	}

	/**
	* Bricks set controls for course categories CSS and General controls.
	*
	* @since 1.9.0
	*/
	public function set_controls() {
			//generals controls
			$this->controls['categories_per_page'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Per Page', 'learning-management-system' ),
				'default'     => 12,
				'type'        => 'number',
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['categories_columns'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Columns', 'learning-management-system' ),
				'default'     => 3,
				'type'        => 'number',
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['categories_order'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Order', 'learning-management-system' ),
				'default'     => 'DESC',
				'options'     => array(
					'ASC'  => esc_html__( 'ASC', 'learning-management-system' ),
					'DESC' => esc_html__( 'DESC', 'learning-management-system' ),
				),
				'type'        => 'select',
				'inline'      => true,
				'pasteStyles' => true,
				'clearable'   => false,
			);

			$this->controls['categories_order_by'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Order By', 'learning-management-system' ),
				'default'     => 'date',
				'options'     => array(
					'date'   => esc_html__( 'Date', 'learning-management-system' ),
					'title'  => esc_html__( 'Title', 'learning-management-system' ),
					'price'  => esc_html__( 'Price', 'learning-management-system' ),
					'rating' => esc_html__( 'Rating', 'learning-management-system' ),
				),
				'type'        => 'select',
				'inline'      => true,
				'pasteStyles' => true,
				'clearable'   => false,
			);

			//layout
			$this->controls['categories_columns_gap'] = array(
				'tab'   => 'content',
				'group' => 'layout',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-col',
					),
				),
			);

			$this->controls['categories_show_thumbnail'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Thumbnail', 'learning-management-system' ),
				'default'   => 'inline',
				'options'   => array(
					'inline' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__image',
					),
				),
			);

			$this->controls['categories_include_sub_categories'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Include Sub-Categories', 'learning-management-system' ),
				'options'   => array(
					'yes' => esc_html__( 'Yes', 'learning-management-system' ),
					'no'  => esc_html__( 'No', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'default'   => 'yes',
			);

			$this->controls['categories_show_thunbnail'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Details', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__detail',
					),
				),
			);

			$this->controls['categories_show_details'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Details', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__detail',
					),
				),
			);

			$this->controls['categories_show_title'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Title', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__title',
					),
				),
				'required'  => array( 'show_details', '!=', 'none' ),
			);

			$this->controls['categories_show_courses_count'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Courses Count', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__courses',
					),
				),
				'required'  => array( 'show_details', '!=', 'none' ),
			);

			$this->controls['categories_show_details_section'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Details Section', 'learning-management-system' ),
				'default'   => 'block',
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-category-card__courses',
					),
				),
				'required'  => array( 'show_details', '!=', 'none' ),
			);

			//card styles
			$this->controls['categories_card_border'] = array(
				'tab'    => 'content',
				'group'  => 'categories_card',
				'label'  => esc_html__( 'Card Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-category-card',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['categories_card_color'] = array(
				'tab'         => 'content',
				'group'       => 'categories_card',
				'label'       => esc_html__( 'Background Color', 'learning-management-system' ),
				'type'        => 'color',
				'css'         => array(
					array(
						'property' => 'background-color',
						'selector' => '.masteriyo-category-card',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['categories_cardbox_shadow'] = array(
				'tab'    => 'content',
				'group'  => 'card_styles',
				'label'  => esc_html__( 'Card BoxShadow', 'learning-management-system' ),
				'type'   => 'box-shadow',
				'css'    => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-category-card',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['categories_card_padding'] = array(
				'tab'   => 'content',
				'group' => 'card_styles',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-category-card',
					),
				),
			);

			$this->controls['categories_details_section_styles'] = array(
				'tab'   => 'content',
				'group' => 'card_styles',
				'label' => esc_html__( 'Details Section Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-category-card__detail',
					),
				),
			);

			$this->controls['categories_courses_count_margin'] = array(
				'tab'   => 'content',
				'group' => 'card_styles',
				'label' => esc_html__( 'Courses Count Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-category-card__courses',
					),
				),
			);

			$this->controls['categories_courses_count_typography'] = array(
				'tab'     => 'content',
				'group'   => 'card_styles',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.difficulty-badge .masteriyo-badge',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'letter-spacing',
					'text-decoration',
				),
				'popup'   => false,
			);

			//pagination
			$this->controls['pagination_visibility'] = array(
				'tab'       => 'content',
				'group'     => 'pagination',
				'label'     => esc_html__( 'Visibility', 'learning-management-system' ),
				'default'   => 'flex',
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.page-numbers',
					),
				),
			);

			$this->controls['pagination_bg'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Background Color', 'learning-management-system' ),
				'type'   => 'color',
				'css'    => array(
					array(
						'property'  => 'background-color',
						'selector'  => '.page-numbers',
						'important' => true,
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['pagination_color'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Color', 'learning-management-system' ),
				'type'   => 'color',
				'css'    => array(
					array(
						'property'  => 'color',
						'selector'  => '.page-numbers',
						'important' => true,
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['pagination_border'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Pagination Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.page-numbers',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['pagination_list_current_border'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Pagination Current Active Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.page-numbers li .current',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['pagination_list_current_active_color'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Pagination Current Active Color', 'learning-management-system' ),
				'type'   => 'color',
				'css'    => array(
					array(
						'property' => 'color',
						'selector' => '.page-numbers li .current',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['pagination_current_active'] = array(
				'tab'    => 'content',
				'group'  => 'pagination',
				'label'  => esc_html__( 'Current Active Background Color', 'learning-management-system' ),
				'type'   => 'color',
				'css'    => array(
					array(
						'property'  => 'background-color',
						'selector'  => '.page-numbers li .current',
						'important' => true,
					),
				),
				'inline' => true,
				'small'  => true,
			);
	}

	/**
	 * Renders the course categories element.
	 *
	 * Retrieves the categories based on the settings, renders the template,
	 * and paginates the results.
	 *
	 * @since 1.9.0
	 */
	public function render() {
		$current_url = $_SERVER['REQUEST_URI'];

		$current_page = Helper::get_page_from_url( $current_url ); // Retrieve the current page number from the URL query parameter.

		$settings               = wp_parse_args(
			array(
				'per_page'               => $this->settings['categories_per_page'] ?? 12,
				'columns'                => $this->settings['categories_columns'] ?? 3,
				'order'                  => $this->settings['categories_order'],
				'order_by'               => $this->settings['categories_order_by'],
				'include_sub_categories' => $this->settings['categories_include_sub_categories'] || 'yes',
				'show_courses_count'     => $this->settings['categories_show_courses_count'] || 'yes',
			)
		);
		$limit                  = max( absint( $settings['per_page'] ), 1 );
		$columns                = max( absint( $settings['columns'] ), 1 );
		$attrs                  = array();
		$include_sub_categories = empty( $settings['include_sub_categories'] ) || 'yes' === $settings['include_sub_categories'];
		$hide_courses_count     = ! ( empty( $settings['show_courses_count'] ) || 'yes' === $settings['show_courses_count'] );

		$args = array(
			'taxonomy'   => Taxonomy::COURSE_CATEGORY,
			'order'      => masteriyo_array_get( $settings, 'order', 'ASC' ),
			'orderby'    => masteriyo_array_get( $settings, 'order_by', 'name' ),
			'number'     => $limit,
			'hide_empty' => false,
			'pagination' => true,
			'page'       => absint( $current_page ), // Add the current page number to the query args.
			'offset'     => ( absint( $current_page ) - 1 ) * absint( $limit ),
		);

		if ( ! masteriyo_string_to_bool( $include_sub_categories ) ) {
			$args['parent'] = 0;
		}

		$query  = new \WP_Term_Query();
		$result = $query->query( $args );
		// Get the total count
		$total_count = wp_count_terms( Taxonomy::COURSE_CATEGORY );

		// Get intended posts per page (adjust how this is derived if needed)
		$posts_per_page = $settings['per_page'];

		// Calculate max pages
		$max_num_pages = ceil( $total_count / $posts_per_page );

		/**
		 * Gets the Course Categories and Adds the attributes for course categories element.
		 *
		 * $since 1.9.0
		 */
		$categories = array_filter( array_map( 'masteriyo_get_course_cat', $result ) );

		$attrs['count']                  = $limit;
		$attrs['columns']                = $columns;
		$attrs['categories']             = $categories;
		$attrs['hide_courses_count']     = $hide_courses_count;
		$attrs['include_sub_categories'] = $include_sub_categories;

		/**
		 *  Wrapper for CourseCategoriesElement and pagination so controls actions reflects.
		 *
		 * @since 1.9.0
		 */
		echo "<div {$this->render_attributes('_root')}>";//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="masteriyo">';
		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attrs );

		echo wp_kses(
			paginate_links(
				array(
					'type'      => 'list',
					'prev_text' => masteriyo_get_svg( 'left-arrow' ),
					'next_text' => masteriyo_get_svg( 'right-arrow' ),
					'total'     => $max_num_pages,
					'current'   => $current_page,
				)
			),
			'masteriyo_pagination'
		);

		echo '</div>';
		echo '</div>';
	}
}
