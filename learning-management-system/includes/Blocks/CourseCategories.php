<?php

/**
 * Course categories block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\Query\CourseCategoryQuery;

/**
 * Class CourseCategories
 *
 * Displays a list/grid of course categories.
 *
 * @since 1.18.2
 */
class CourseCategories extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-categories';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr    = $this->attributes;
		$columns = absint( $attr['columns'] );

		// Clamp column value between 1 and 4.
		if ( $columns < 1 ) {
			$columns = 1;
		} elseif ( $columns > 4 ) {
			$columns = 4;
		}

		$attr['columns']    = $columns;
		$categories         = $this->get_categories( $attr );
		$attr['categories'] = $categories;

		\ob_start();

		/**
		 * Fires before rendering course categories in the course-categories block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $categories The course categories objects.
		 */
		do_action( 'masteriyo_blocks_before_course_categories', $attr, $categories );

		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attr );

		/**
		 * Fires after rendering course categories in the course-categories block.
		 *
		 * @since 1.18.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $categories The course categories objects.
		 */
		do_action( 'masteriyo_blocks_after_course_categories', $attr, $categories );

		return \ob_get_clean();
	}

	/**
	 * Get course categories to display.
	 *
	 * @since 1.18.2
	 *
	 * @param array $attr Block attributes.
	 * @return \Masteriyo\Models\CourseCategory[]
	 */
	protected function get_categories( $attr ) {
		$args = array(
			'order'   => 'ASC',
			'orderby' => 'name',
			'number'  => absint( $attr['count'] ),
		);

		if ( ! masteriyo_string_to_bool( $attr['include_sub_categories'] ) ) {
			$args['parent'] = 0;
		}

		$query      = new CourseCategoryQuery( $args );
		$categories = $query->get_categories();

		/**
		 * Filters course categories for the course-categories block.
		 *
		 * @since 1.18.2
		 *
		 * @param \Masteriyo\Models\CourseCategory[]      $categories List of categories.
		 * @param array                                    $args       Query arguments.
		 * @param \Masteriyo\Query\CourseCategoryQuery    $query      The query object.
		 */
		return apply_filters( 'masteriyo_shortcode_course_categories', $categories, $args, $query );
	}
}
