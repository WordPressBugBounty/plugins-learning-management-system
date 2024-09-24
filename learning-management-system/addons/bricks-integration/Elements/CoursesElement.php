<?php
/**
 * Masteriyo course list Bricks element class.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\BricksIntegration\Elements;

use Masteriyo\Addons\BricksIntegration\Helper;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseQuery;
use Masteriyo\Taxonomy\Taxonomy;

/**
* Masteriyo courses elements class.
*
* @since 1.9.0
*/
class CoursesElement extends \Bricks\Element {

	public $category     = 'masteriyo';
	public $name         = 'courses';
	public $icon         = 'ti-layout-grid3-alt';
	public $css_selector = '.masteriyo-courses-list';

	/**
	* Bricks courses Label for the element.
	*
	* @since 1.9.0
	*/
	public function get_label() {
		return esc_html__( 'Courses', 'learning-management-system' );
	}

	/**
	* Bricks courses set controls groups for course categories CSS and General controls.
	*
	* @since 1.9.0
	*/
	public function set_control_groups() {
		$this->control_groups['general'] = array(
			'title' => esc_html__( 'General', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['layout'] = array(
			'title' => esc_html__( 'Layout', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['card_styles'] = array(
			'title' => esc_html__( 'Card Styles', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['filters'] = array(
			'title' => esc_html__( 'Filters', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['difficulty_badge'] = array(
			'title' => esc_html__( 'Difficulty Badge', 'learning-management-system' ),
			'tab'   => 'content',
		);
		$this->control_groups['categories']       = array(
			'title' => esc_html__( 'Categories', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['author'] = array(
			'title' => esc_html__( 'Author', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['rating'] = array(
			'title' => esc_html__( 'Rating', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['highlights'] = array(
			'title' => esc_html__( 'Highlight', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['metadata'] = array(
			'title' => esc_html__( 'Metadata', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['price'] = array(
			'title' => esc_html__( 'Price', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['enroll_button'] = array(
			'title' => esc_html__( 'Enroll Button', 'learning-management-system' ),
			'tab'   => 'content',
		);

		$this->control_groups['pagination'] = array(
			'title' => esc_html__( 'Pagination', 'learning-management-system' ),
			'tab'   => 'content',
		);
	}

	public function set_controls() {
		//generals controls
			$this->controls['per_page'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Per Page', 'learning-management-system' ),
				'default'     => 12,
				'type'        => 'number',
				'min'         => 1,
				'max'         => 24,
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['columns'] = array(
				'tab'         => 'content',
				'group'       => 'general',
				'label'       => esc_html__( 'Columns', 'learning-management-system' ),
				'default'     => 3,
				'type'        => 'number',
				'min'         => 1,
				'max'         => 12,
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['order'] = array(
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

			$this->controls['order_by'] = array(
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

			//filter controls
			$this->controls['include_category_ids'] = array(
				'tab'         => 'content',
				'group'       => 'filters',
				'label'       => esc_html__( 'Include Categories', 'learning-management-system' ),
				'type'        => 'select',
				'options'     => Helper::get_categories_options(),
				'placeholder' => esc_html__( 'Select tag', 'learning-management-system' ),
				'multiple'    => true,
				'searchable'  => true,
				'clearable'   => true,
			);

			$this->controls['exclude_category_ids'] = array(
				'tab'         => 'content',
				'group'       => 'filters',
				'label'       => esc_html__( 'Exclude Categories', 'learning-management-system' ),
				'type'        => 'select',
				'options'     => Helper::get_categories_options(),
				'placeholder' => esc_html__( 'Select tag', 'learning-management-system' ),
				'multiple'    => true,
				'searchable'  => true,
				'clearable'   => true,
			);

			$this->controls['include_instructor_ids'] = array(
				'tab'         => 'content',
				'group'       => 'filters',
				'label'       => esc_html__( 'Include Instructors', 'learning-management-system' ),
				'type'        => 'select',
				'options'     => Helper::get_instructors_options(),
				'placeholder' => esc_html__( 'Select tag', 'learning-management-system' ),
				'multiple'    => true,
				'searchable'  => true,
				'clearable'   => true,
			);

			$this->controls['exclude_instructor_ids'] = array(
				'tab'         => 'content',
				'group'       => 'filters',
				'label'       => esc_html__( 'Exclude Instructors', 'learning-management-system' ),
				'type'        => 'select',
				'options'     => Helper::get_instructors_options(),
				'placeholder' => esc_html__( 'Select tag', 'learning-management-system' ),
				'multiple'    => true,
				'searchable'  => true,
				'clearable'   => true,
			);

			//layout controls
			$this->controls['columns_gap'] = array(
				'tab'     => 'content',
				'group'   => 'layout',
				'label'   => esc_html__( 'Padding', 'learning-management-system' ),
				'type'    => 'dimensions',
				'css'     => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-col',
					),
				),
				'default' => array(
					'top'    => '15px',
					'right'  => '15px',
					'bottom' => '15px',
					'left'   => '15px',
				),
			);

			$this->controls['show_thumbnail'] = array(
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
						'selector' => '.masteriyo-course--img-wrap',
					),
				),
			);

			$this->controls['show_categories'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Categories', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course--content__category',
					),
				),
			);

			$this->controls['show_title'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Title', 'learning-management-system' ),
				'options'   => array(
					''     => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course--content__title a',
					),
				),
			);

			$this->controls['show_author'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Author', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.masteriyo-course-author',
					),
				),
			);

			$this->controls['show_author_avatar'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Avatar of Author', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-author img',
						'important' => true,
					),
				),
				'required'  => array( 'show_author', '!=', 'none' ),
			);

			$this->controls['show_author_name'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Name of Author', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-author .masteriyo-course-author--name',
						'important' => true,
					),
				),
				'required'  => array( 'show_author', '!=', 'none' ),
			);

			$this->controls['show_rating'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Show Rating', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-rating',
						'important' => true,
					),
				),
			);

			$this->controls['show_course_description'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Highlights / Description', 'learning-management-system' ),
				'options'   => array(
					'block' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'  => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course--content__description',
						'important' => true,
					),
				),
			);

			$this->controls['show_metadata'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Meta Data', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Yes', 'learning-management-system' ),
					'none' => esc_html__( 'No', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course--content__stats',
						'important' => true,
					),
				),
			);

			$this->controls['show_students_count'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Students Count', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-stats-students',
						'important' => true,
					),
				),
				'required'  => array( 'show_metadata', '!=', 'none' ),
			);

			$this->controls['show_course_duration'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Course Duration', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-stats-duration',
						'important' => true,
					),
				),
				'required'  => array( 'show_metadata', '!=', 'none' ),
			);

			$this->controls['show_lessons_count'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Lessons Count', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-stats-curriculum',
						'important' => true,
					),
				),
				'required'  => array( 'show_metadata', '!=', 'none' ),
			);

			$this->controls['show_card_footer'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Footer', 'learning-management-system' ),
				'options'   => array(
					'flex' => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-card-footer',
						'important' => true,
					),
				),
			);

			$this->controls['show_price'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Price', 'learning-management-system' ),
				'options'   => array(
					''     => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-course-price',
						'important' => true,
					),
				),
				'required'  => array( 'show_card_footer', '!=', 'none' ),
			);

			$this->controls['show_enroll_button'] = array(
				'tab'       => 'content',
				'group'     => 'layout',
				'label'     => esc_html__( 'Enroll Button', 'learning-management-system' ),
				'options'   => array(
					''     => esc_html__( 'Visible', 'learning-management-system' ),
					'none' => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'display',
						'selector'  => '.masteriyo-enroll-btn',
						'important' => true,
					),
				),
				'required'  => array( 'show_card_footer', '!=', 'none' ),
			);

			//card styles
			$this->controls['course_title_color'] = array(
				'tab'    => 'content',
				'group'  => 'layout',
				'label'  => esc_html__( 'Title Color', 'learning-management-system' ),
				'type'   => 'color',
				'inline' => true,
				'css'    => array(
					array(
						'property' => 'color',
						'selector' => '.masteriyo-course--content__title a',
					),
				),
			);
			$this->controls['card_border']        = array(
				'tab'    => 'content',
				'group'  => 'card_styles',
				'label'  => esc_html__( 'Card Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course--card',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['box_shadow'] = array(
				'tab'    => 'content',
				'group'  => 'card_styles',
				'label'  => esc_html__( 'Card BoxShadow', 'learning-management-system' ),
				'type'   => 'box-shadow',
				'css'    => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.masteriyo-course--card',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['card_padding'] = array(
				'tab'   => 'content',
				'group' => 'card_styles',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.masteriyo-course--card',
					),
				),
			);

			//badge style controls
			$this->controls['difficulty_badge_typography'] = array(
				'tab'     => 'content',
				'group'   => 'difficulty_badge',
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
				'popup'   => false, // Default: true
			);

			$this->controls['difficulty_badge_border'] = array(
				'tab'    => 'content',
				'group'  => 'difficulty_badge',
				'label'  => esc_html__( 'Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.difficulty-badge .masteriyo-badge',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['difficulty_badge_padding'] = array(
				'tab'   => 'content',
				'group' => 'difficulty_badge',
				'label' => esc_html__( 'Padding', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'padding',
						'selector' => '.difficulty-badge .masteriyo-badge',
					),
				),
			);

			$this->controls['difficulty_badge_vertical_position'] = array(
				'tab'       => 'content',
				'group'     => 'difficulty_badge',
				'label'     => esc_html__( 'Vertical Badge Position', 'learning-management-system' ),
				'options'   => array(
					'inline' => esc_html__( 'Visible', 'learning-management-system' ),
					'none'   => esc_html__( 'Invisible', 'learning-management-system' ),
				),
				'default'   => '24px',
				'small'     => true,
				'type'      => 'number',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'top',
						'selector'  => '.difficulty-badge',
						'important' => true,
					),
				),
				'required'  => array( 'show_thumbnail', '!=', 'none' ),
			);

			$this->controls['difficulty_badge_horizontal_position'] = array(
				'tab'       => 'content',
				'group'     => 'difficulty_badge',
				'label'     => esc_html__( 'Horizontal Badge Position', 'learning-management-system' ),
				'default'   => '24px',
				'small'     => true,
				'type'      => 'number',
				'clearable' => false,
				'css'       => array(
					array(
						'property'  => 'left',
						'selector'  => '.difficulty-badge',
						'important' => true,
					),
				),
				'required'  => array( 'show_thumbnail', '!=', 'none' ),
			);

			//categories
			$this->controls['categories_gap'] = array(
				'tab'   => 'content',
				'group' => 'categories',
				'label' => esc_html__( 'Gap', 'learning-management-system' ),
				'type'  => 'number',
				'css'   => array(
					array(
						'property' => 'margin-left',
						'selector' => '.masteriyo-course--content__category .masteriyo-course--content__category-items:not(:first-child)',
					),
				),
				'unit'  => 'px',
			);

			$this->controls['categories_margin'] = array(
				'tab'   => 'content',
				'group' => 'categories',
				'label' => esc_html__( 'Categories Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course--content__category',
					),
				),
			);

			$this->controls['category_typography'] = array(
				'tab'     => 'content',
				'group'   => 'categories',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course--content__category .masteriyo-course--content__category-items',
					),
				),
				'exclude' => array(
					'text-align',
					'line-height',
					'letter-spacing',
					'text-decoration',
					'font-family',
				),
				'popup'   => false, // Default: true
			);

			$this->controls['categories_border'] = array(
				'tab'    => 'content',
				'group'  => 'categories',
				'label'  => esc_html__( 'Border', 'learning-management-system' ),
				'type'   => 'border',
				'css'    => array(
					array(
						'property' => 'border',
						'selector' => '.masteriyo-course--content__category .masteriyo-course--content__category-items',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			//author

			$this->controls['author_typography'] = array(
				'tab'     => 'content',
				'group'   => 'author',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course-author .masteriyo-course-author--name',
					),
				),
				'exclude' => array(
					'text-decoration',
					'font-family',
				),
				'popup'   => false, // Default: true
			);

			$this->controls['author_margin'] = array(
				'tab'   => 'content',
				'group' => 'author',
				'label' => esc_html__( 'Margin', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course-author .masteriyo-course-author--name',
					),
				),
			);

			//rating
			$this->controls['rating_typography'] = array(
				'tab'     => 'content',
				'group'   => 'rating',
				'label'   => esc_html__( 'Rating Count', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course--content__rt .masteriyo-rating',
					),
				),
				'exclude' => array(
					'text-decoration',
					'font-family',
					'text-align',
					'text-transform',
				),
				'popup'   => false, // Default: true
			);

			$this->controls['rating_height'] = array(
				'tab'         => 'content',
				'group'       => 'rating',
				'label'       => esc_html__( 'Rating Height', 'learning-management-system' ),
				'type'        => 'number',
				'css'         => array(
					array(
						'property' => 'height',
						'selector' => '.masteriyo-rating svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['rating_width'] = array(
				'tab'         => 'content',
				'group'       => 'rating',
				'label'       => esc_html__( 'Rating Width', 'learning-management-system' ),
				'type'        => 'number',
				'css'         => array(
					array(
						'property' => 'width',
						'selector' => '.masteriyo-rating svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['rating_color'] = array(
				'tab'         => 'content',
				'group'       => 'rating',
				'label'       => esc_html__( 'Rating Color', 'learning-management-system' ),
				'type'        => 'color',
				'css'         => array(
					array(
						'property' => 'fill',
						'selector' => '.masteriyo-rating svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['rating_gap'] = array(
				'tab'   => 'content',
				'group' => 'rating',
				'label' => esc_html__( 'Icons Gap', 'learning-management-system' ),
				'type'  => 'number',
				'css'   => array(
					array(
						'property' => 'margin-left',
						'selector' => '.masteriyo-rating svg:not(:first-child)',
					),
				),
				'unit'  => 'px',
			);

			//descriptions or highlights
			$this->controls['highlight_gap'] = array(
				'tab'   => 'content',
				'group' => 'highlights',
				'label' => esc_html__( 'Gap', 'learning-management-system' ),
				'type'  => 'dimensions',
				'css'   => array(
					array(
						'property' => 'margin',
						'selector' => '.masteriyo-course--content__description ul li:not(:last-child)',
					),
				),
			);

			$this->controls['highlight_color'] = array(
				'tab'         => 'content',
				'group'       => 'highlights',
				'label'       => esc_html__( 'Color', 'learning-management-system' ),
				'type'        => 'color',
				'css'         => array(
					array(
						'property' => 'color',
						'selector' => '.masteriyo-course--content__description',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			//metadata()
			$this->controls['metadata'] = array(
				'tab'     => 'content',
				'group'   => 'metadata',
				'label'   => esc_html__( 'Typography', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course--content__stats span',
					),
				),
				'exclude' => array(
					'text-decoration',
					'font-family',
					'text-align',
					'text-transform',
				),
				'popup'   => false, // Default: true
			);

			$this->controls['metadata_height'] = array(
				'tab'         => 'content',
				'group'       => 'metadata',
				'label'       => esc_html__( 'Height', 'learning-management-system' ),
				'type'        => 'number',
				'css'         => array(
					array(
						'property' => 'height',
						'selector' => '.masteriyo-course--content__stats svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['metadata_width'] = array(
				'tab'         => 'content',
				'group'       => 'metadata',
				'label'       => esc_html__( 'Width', 'learning-management-system' ),
				'type'        => 'number',
				'css'         => array(
					array(
						'property' => 'width',
						'selector' => '.masteriyo-course--content__stats svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			$this->controls['metadata_color'] = array(
				'tab'         => 'content',
				'group'       => 'metadata',
				'label'       => esc_html__( 'Icon Color', 'learning-management-system' ),
				'type'        => 'color',
				'css'         => array(
					array(
						'property' => 'fill',
						'selector' => '.masteriyo-course--content__stats svg',
					),
				),
				'inline'      => true,
				'pasteStyles' => true,
			);

			//price
			$this->controls['price'] = array(
				'tab'     => 'content',
				'group'   => 'price',
				'label'   => esc_html__( 'Price', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-course-price .current-amount',
					),
				),
				'exclude' => array(
					'text-decoration',
					'font-family',
					'text-align',
					'text-transform',
				),
				'popup'   => false, // Default: true
			);

			//enroll button styles
			$this->controls['enroll_button_style'] = array(
				'tab'     => 'content',
				'group'   => 'enroll_button',
				'label'   => esc_html__( 'Enroll Button', 'learning-management-system' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.masteriyo-enroll-btn',
					),
				),
				'exclude' => array(
					'text-decoration',
					'font-family',
					'text-align',
					'text-transform',
				),
				'popup'   => false, // Default: true
			);

			$this->controls['button_background'] = array(
				'tab'    => 'content',
				'group'  => 'enroll_button',
				'label'  => esc_html__( 'Background Color', 'learning-management-system' ),
				'type'   => 'color',
				'css'    => array(
					array(
						'property'  => 'background-color',
						'selector'  => '.masteriyo-btn.masteriyo-btn-primary',
						'important' => true,
					),
				),
				'inline' => true,
				'small'  => true,
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
	 * Render the element output for the frontend of Courses Element
	 * which contains the courses loop and pagination.
	 *
	 * Includes border, color, and background color etc. options for the
	 * element reflected based on components controls.
	 *
	 * @since 1.9.0
	 */
	public function render() {
		// Get the current page URL.
		$current_url  = $_SERVER['REQUEST_URI'];
		$current_page = Helper::get_page_from_url( $current_url ); // Retrieve the current page number from the URL query parameter.
		$settings     = wp_parse_args(
			array(
				'per_page'               => $this->settings['per_page'] ?? 12,
				'columns'                => $this->settings['columns'] ?? 3,
				'order'                  => $this->settings['order'],
				'order_by'               => $this->settings['order_by'],
				'include_category_ids'   => $this->settings['include_category_ids'] ?? '',
				'exclude_category_ids'   => $this->settings['exclude_category_ids'] ?? '',
				'include_instructor_ids' => $this->settings['include_instructor_ids'] ?? '',
				'exclude_instructor_ids' => $this->settings['exclude_instructor_ids'] ?? '',
			)
		);
		$course       = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;
		$limit        = max( absint( $settings['per_page'] ), 1 );

		$tax_query = array(
			'relation' => 'AND',
		);

		if ( ! empty( $settings['include_category_ids'] ) ) {
			$ids = array_values( $settings['include_category_ids'] );

			$tax_query[] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $ids,
				'field'    => 'term_id',
				'operator' => 'IN',
			);
		}

		if ( ! empty( $settings['exclude_category_ids'] ) ) {
			$ids = array_values( $settings['exclude_category_ids'] );

			$tax_query[] = array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $ids,
				'field'    => 'term_id',
				'operator' => 'NOT IN',
			);
		}

		$args = array(
			'post_type'      => PostType::COURSE,
			'status'         => array( PostStatus::PUBLISH ),
			'posts_per_page' => $limit,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'tax_query'      => $tax_query,
			'page'           => absint( $current_page ), // Add the current page number to the query args.
			'pagination'     => true,
			'page'           => absint( $current_page ), // Add the current page number to the query args.
			'offset'         => ( absint( $current_page ) - 1 ) * absint( $limit ),
		);

		if ( ! empty( $settings['include_instructor_ids'] ) ) {
			$ids                = array_values( $settings['include_instructor_ids'] );
			$args['author__in'] = $ids;
		}

		if ( ! empty( $settings['exclude_instructor_ids'] ) ) {
			$ids                    = array_values( $settings['exclude_instructor_ids'] );
			$args['author__not_in'] = $ids;
		}

		$order = strtoupper( $settings['order'] );

		switch ( $settings['order_by'] ) {
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;

			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price';
				$args['order']    = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;

			case 'rating':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_average_rating';
				$args['order']    = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;

			default:
				$args['orderby'] = 'date';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
		}

		$courses_query = new \WP_Query( $args );
		$courses       = array_filter( array_map( 'masteriyo_get_course', $courses_query->posts ) );
		$count         = count( $courses );
		$columns       = max( absint( $settings['columns'] ), 1 );

		echo "<div {$this->render_attributes( '_root' )}>";//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<div class='<?php echo 'masteriyo'; ?>' style='<?php echo ( ( 1 === $count || 2 === $count ) && 1 === $columns ) ? 'width:1130px;' : ''; ?>'>
	> 
		<?php
		masteriyo_set_loop_prop( 'columns', $columns );
		if ( count( $courses ) > 0 ) {
			$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

			masteriyo_course_loop_start();

			foreach ( $courses as $course ) {
				$GLOBALS['course'] = $course;

				masteriyo_get_template(
					'content-course.php'
				);
			}
			$GLOBALS['course'] = $original_course;
			masteriyo_course_loop_end();
			masteriyo_reset_loop();
		}
			echo wp_kses(
				paginate_links(
					array(
						'type'      => 'list',
						'prev_text' => masteriyo_get_svg( 'left-arrow' ),
						'next_text' => masteriyo_get_svg( 'right-arrow' ),
						'total'     => $courses_query->max_num_pages,
						'current'   => $current_page,
					)
				),
				'masteriyo_pagination'
			);
		echo '</div>';

		echo '</div>';
	}
}
