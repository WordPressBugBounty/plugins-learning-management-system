<?php
/**
 * Divi Integration helper functions.
 *
 * @package Masteriyo\Addons\DiviIntegration
 *
 * @since 1.6.13
 */

namespace Masteriyo\Addons\DiviIntegration;

defined( 'ABSPATH' ) || exit;


/**
 * Divi Integration helper functions.
 *
 * @package Masteriyo\Addons\DiviIntegration
 *
 * @since 1.6.13
 */
class Helper {

	/**
	 * Return if Divi is active.
	 *
	 * @since 1.6.13
	 *
	 * @return boolean
	 */
	public static function is_divi_active() {
		$theme = wp_get_theme();
		return 'Divi' === $theme->name || 'Divi' === $theme->parent_theme || in_array( 'divi-builder/divi-builder.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Check if the current page is Divi builder.
	 *
	 * @since 1.6.13
	 *
	 * @return boolean
	 */
	public static function is_divi_builder() {
		return isset( $_GET['et_fb'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * CSS selector for each show/hide toggle, matching the Divi 4 module's own
	 * controls (see CourseListModule::init_ui_toggle_controls()).
	 *
	 * @var array<string, string>
	 */
	const COURSE_LIST_TOGGLE_SELECTORS = array(
		'show_thumbnail'        => '.masteriyo-course--img-wrap',
		'show_difficulty_badge' => '.difficulty-badge',
		'show_categories'       => '.masteriyo-course--content__category',
		'show_course_title'     => '.masteriyo-course--content__title',
		'show_author'           => '.masteriyo-course-author',
		'show_author_avatar'    => '.masteriyo-course-author img',
		'show_author_name'      => '.masteriyo-course-author .masteriyo-course-author--name',
		'show_rating'           => '.masteriyo-rating',
		'show_description'      => '.masteriyo-course--content__description',
		'show_metadata'         => '.masteriyo-course--content__stats',
		'show_course_duration'  => '.masteriyo-course-stats-duration',
		'show_students_count'   => '.masteriyo-course-stats-students',
		'show_lessons_count'    => '.masteriyo-course-stats-curriculum',
		'show_footer'           => '.masteriyo-course-card-footer',
		'show_price'            => '.masteriyo-course-price',
		'show_enroll_button'    => '.masteriyo-enroll-btn',
	);

	/**
	 * CSS selector for each show/hide toggle, matching the Divi 4 module's own
	 * controls (see CourseCategoriesModule::init_ui_toggle_controls()).
	 *
	 * @var array<string, string>
	 */
	const COURSE_CATEGORIES_TOGGLE_SELECTORS = array(
		'show_thumbnail'     => '.masteriyo-category-card__image',
		'show_details'       => '.masteriyo-category-card__detail',
		'show_title'         => '.masteriyo-category-card__title',
		'show_courses_count' => '.masteriyo-category-card__courses',
	);

	/**
	 * Read a Divi 5 attribute's desktop innerContent value.
	 *
	 * @param array  $attributes Raw block attributes.
	 * @param string $name       Attribute name.
	 * @param mixed  $default_value Fallback when unset.
	 *
	 * @return mixed
	 */
	public static function get_divi5_attr( array $attributes, string $name, $default_value ) {
		return $attributes[ $name ]['innerContent']['desktop']['value'] ?? $default_value;
	}

	/**
	 * Read every show/hide toggle's current 'on'/'off' value for a native
	 * module instance, keyed the same as $selectors_map.
	 *
	 * @param array                 $attributes    Raw block attributes.
	 * @param array<string, string> $selectors_map Map of toggle key => target CSS selector.
	 *
	 * @return array<string, string>
	 */
	public static function get_divi5_toggles( array $attributes, array $selectors_map ): array {
		$toggles = array();

		foreach ( array_keys( $selectors_map ) as $toggle_key ) {
			$toggles[ $toggle_key ] = sanitize_key( self::get_divi5_attr( $attributes, $toggle_key, 'on' ) );
		}

		return $toggles;
	}

	/**
	 * Build a <style> tag hiding whichever toggled-off elements, scoped to one
	 * module instance (the native module's equivalent of DiviModule's own
	 * add_show_hide_control() style generation).
	 *
	 * @param string                $selector      Unique CSS selector for this module instance.
	 * @param array<string, mixed>  $toggles       Map of toggle key => 'on'/'off'.
	 * @param array<string, string> $selectors_map Map of toggle key => target CSS selector.
	 *
	 * @return string
	 */
	public static function render_toggle_hide_css( string $selector, array $toggles, array $selectors_map ): string {
		$rules = array();

		foreach ( $selectors_map as $key => $target ) {
			if ( isset( $toggles[ $key ] ) && 'off' === $toggles[ $key ] ) {
				$rules[] = $selector . ' ' . $target . ' { display: none !important; }';
			}
		}

		if ( empty( $rules ) ) {
			return '';
		}

		return '<style>' . implode( ' ', $rules ) . '</style>';
	}

	/**
	 * Render a course list. Deliberately independent of ET_Builder_Module, so
	 * the native Divi 5 module can call it without needing that class to exist.
	 *
	 * @param array $props {
	 *     @type int      $per_page           Number of courses to show.
	 *     @type int      $columns            Grid column count.
	 *     @type string   $order              'ASC' or 'DESC'.
	 *     @type string   $order_by           'date', 'price', 'title' or 'rating'.
	 *     @type array<int|string> $include_categories Category term IDs to restrict to.
	 *     @type array<int|string> $exclude_categories Category term IDs to exclude.
	 *     @type int[]    $include_instructors Author user IDs to restrict to.
	 *     @type int[]    $exclude_instructors Author user IDs to exclude.
	 *     @type bool     $show_pagination    Whether to render page-number navigation.
	 *     @type string   $pagination_key     Optional. Query parameter carrying this list's page number; give each
	 *                                        list on a page its own, or paging one pages them all. Default 'mto-course-page'.
	 * }
	 *
	 * @return string
	 */
	public static function render_course_list( array $props = array() ): string {
		$pagination_key = ! empty( $props['pagination_key'] ) ? sanitize_key( $props['pagination_key'] ) : 'mto-course-page';
		$current_page   = isset( $_GET[ $pagination_key ] ) ? max( 1, absint( $_GET[ $pagination_key ] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'post_type'      => \Masteriyo\PostType\PostType::COURSE,
			'post_status'    => \Masteriyo\Enums\PostStatus::PUBLISH,
			'posts_per_page' => ! empty( $props['per_page'] ) ? absint( $props['per_page'] ) : masteriyo_get_setting( 'course_archive.display.per_page' ),
			'paged'          => $current_page,
			'order'          => empty( $props['order'] ) ? 'DESC' : strtoupper( $props['order'] ),
			'orderby'        => 'date',
		);

		if ( ! empty( $props['include_categories'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Masteriyo\Taxonomy\Taxonomy::COURSE_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', (array) $props['include_categories'] ),
				'operator' => 'IN',
			);
		}

		if ( ! empty( $props['exclude_categories'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Masteriyo\Taxonomy\Taxonomy::COURSE_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', (array) $props['exclude_categories'] ),
				'operator' => 'NOT IN',
			);
		}

		if ( ! empty( $props['include_instructors'] ) ) {
			$args['author__in'] = array_map( 'absint', (array) $props['include_instructors'] );
		}

		if ( ! empty( $props['exclude_instructors'] ) ) {
			$args['author__not_in'] = array_map( 'absint', (array) $props['exclude_instructors'] );
		}

		if ( ! empty( $props['order_by'] ) ) {
			$order_by = strtolower( $props['order_by'] );
			$order    = empty( $props['order'] ) ? 'DESC' : strtoupper( $props['order'] );

			switch ( $order_by ) {
				case 'price':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$args['order']    = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
					break;

				case 'title':
					$args['orderby'] = 'title';
					$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
					break;

				case 'rating':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$args['order']    = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
					break;

				case 'date':
				default:
					$args['orderby'] = 'date';
					$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
					break;
			}
		}

		/**
		 * Filters the prepared query args for the course list query in Course List Divi module.
		 *
		 * Shared with the Divi 4 module's own query — see
		 * addons/divi-integration/Modules/CourseListModule.php::get_rendered_course_list().
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'masteriyo_course_list_module_prepare_query_args', $args );

		$query = new \WP_Query( $args );
		$html  = self::render_courses_from_query( $query, $props );

		if ( ! empty( $props['show_pagination'] ) && $query->max_num_pages > 1 ) {
			$html .= wp_kses(
				paginate_links(
					array(
						'base'      => add_query_arg( $pagination_key, '%#%' ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $query->max_num_pages,
						'type'      => 'list',
						'prev_text' => masteriyo_get_svg( 'left-arrow' ),
						'next_text' => masteriyo_get_svg( 'right-arrow' ),
					)
				),
				'masteriyo_pagination'
			);
		}

		return $html;
	}

	/**
	 * Render an executed course query through the course loop.
	 *
	 * @param \WP_Query $query  An already-executed course query.
	 * @param array     $props {
	 *     @type int $columns Grid column count.
	 * }
	 *
	 * @return string
	 */
	public static function render_courses_from_query( \WP_Query $query, array $props = array() ): string {
		$courses = array_filter( array_map( 'masteriyo_get_course', $query->posts ) );
		$columns = empty( $props['columns'] ) ? masteriyo_get_setting( 'course_archive.display.per_row' ) : absint( $props['columns'] );

		masteriyo_set_loop_prop( 'columns', $columns );

		\ob_start();

		if ( count( $courses ) > 0 ) {
			$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

			/**
			 * Fires before course loop in Course List Divi module.
			 *
			 * @param \Masteriyo\Models\Course[] $courses The courses objects.
			 */
			do_action( 'masteriyo_before_courses_loop', $courses );

			masteriyo_course_loop_start();

			foreach ( $courses as $course ) {
				$GLOBALS['course'] = $course;
				\masteriyo_get_template_part( 'content', 'course' );
			}

			$GLOBALS['course'] = $original_course;

			masteriyo_course_loop_end();

			/**
			 * Fires after course loop in Course List Divi module.
			 *
			 * @param \Masteriyo\Models\Course[] $courses The courses objects.
			 */
			do_action( 'masteriyo_after_courses_loop', $courses );
		} else {
			/**
			 * Fires when there is no course to display.
			 */
			do_action( 'masteriyo_no_courses_found' );
		}

		masteriyo_reset_loop();

		return \ob_get_clean();
	}

	/**
	 * Render a course categories list. Deliberately independent of
	 * ET_Builder_Module — mirrors Modules\CourseCategoriesModule::get_rendered_course_categories(),
	 * which is itself already framework-independent, so the native Divi 5
	 * module can call it without needing that class's DiviModule/ET_Builder_Module
	 * parent chain to exist.
	 *
	 * @param array $props {
	 *     @type int    $per_page               Number of categories to show.
	 *     @type int    $columns                Grid column count.
	 *     @type bool   $include_sub_categories Whether to include sub-categories.
	 *     @type string $order                  'ASC' or 'DESC'.
	 *     @type string $order_by               'name' or 'count'.
	 * }
	 *
	 * @return string
	 */
	public static function render_course_categories_list( array $props = array() ): string {
		$limit      = ! empty( $props['per_page'] ) ? absint( $props['per_page'] ) : 12;
		$columns    = ! empty( $props['columns'] ) ? absint( $props['columns'] ) : 3;
		$categories = self::query_course_categories( $props );

		$attrs = array(
			'count'                  => $limit,
			'columns'                => $columns,
			'categories'             => $categories,
			// Course count visibility is a CSS toggle (see COURSE_CATEGORIES_TOGGLE_SELECTORS),
			// same as every other show/hide field, so the template always renders it.
			'hide_courses_count'     => false,
			'include_sub_categories' => ! empty( $props['include_sub_categories'] ),
		);

		\ob_start();

		echo '<div class="masteriyo">';
		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attrs );
		echo '</div>';

		return \ob_get_clean();
	}

	/**
	 * Build and run the course categories taxonomy query, shared by the
	 * native Divi 5 renderer above and the legacy Divi 4 module (see
	 * Modules\CourseCategoriesModule::get_rendered_course_categories()) —
	 * the two modules expose "hide course count" differently (a template
	 * attribute there, a CSS show/hide toggle here), so only the query
	 * itself is shared; each caller keeps building its own template attrs.
	 *
	 * @param array $props {
	 *     @type int    $per_page               Number of categories to show.
	 *     @type bool   $include_sub_categories Whether to include sub-categories.
	 *     @type string $order                  'ASC' or 'DESC'.
	 *     @type string $order_by               'name' or 'count'.
	 * }
	 *
	 * @return \Masteriyo\Models\CourseCategory[]
	 */
	public static function query_course_categories( array $props = array() ): array {
		$args = array(
			'taxonomy'   => \Masteriyo\Taxonomy\Taxonomy::COURSE_CATEGORY,
			'order'      => empty( $props['order'] ) ? 'ASC' : strtoupper( $props['order'] ),
			'orderby'    => empty( $props['order_by'] ) ? 'name' : $props['order_by'],
			'number'     => ! empty( $props['per_page'] ) ? absint( $props['per_page'] ) : 12,
			'hide_empty' => false,
		);

		if ( ! masteriyo_string_to_bool( $props['include_sub_categories'] ?? false ) ) {
			$args['parent'] = 0;
		}

		/**
		 * Filters the prepared query args for the course categories query in Course Categories Divi module.
		 *
		 * Shared with the Divi 4 module's own query — see
		 * addons/divi-integration/Modules/CourseCategoriesModule.php::get_rendered_course_categories().
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'masteriyo_course_categories_module_prepare_query_args', $args );

		$query  = new \WP_Term_Query();
		$result = $query->query( $args );

		return array_filter( array_map( 'masteriyo_get_course_cat', $result ) );
	}
}
