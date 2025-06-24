<?php

/**
 * Courses block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Query\CourseQuery;
use Masteriyo\PostType\PostType;

/**
 * Class Courses
 *
 * Renders a grid/list of published courses.
 *
 * @since 1.18.2
 */
class Courses extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'courses';

	/**
	 * Build HTML output for the courses block.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr         = $this->attributes;
		$args         = array(
			'limit'    => absint( $attr['count'] ),
			'order'    => 'DESC',
			'orderby'  => 'date',
			'category' => empty( $attr['categoryIds'] ) ? array() : $attr['categoryIds'],
			'status'   => PostStatus::PUBLISH,
		);
		$course_query = new CourseQuery( $args );
		$client_id    = (string) $attr['clientId'];
		/**
		 * Filters courses to display in courses block.
		 *
		 * @since 1.3.0
		 *
		 * @param \Masteriyo\Models\Course[]|\Masteriyo\Models\Course $courses Course list or object.
		 */
		$courses = apply_filters( 'masteriyo_shortcode_courses_result', $course_query->get_courses() );

		// Set number of columns for the loop.
		masteriyo_set_loop_prop( 'columns', absint( $attr['columns'] ) );
		if ( isset( $attr['viewType'] ) && $attr['viewType'] ) {
			$GLOBALS['course_archive_view'] = 'list' === $attr['viewType'] ? 'list-view' : 'grid-view';
		}

		\ob_start();

		echo '<div class="masteriyo-w-100 masteriyo-course-list-display-section" style="max-width: 1140px">';
			/**
			 * Fires before course loop in course archive template.
			 *
			 * Fires regardless of whether there are courses to be displayed or not.
			 *
			 * @since 1.18.2
			 *
			 * @param string $client_id Client ID.
			 */
			do_action( 'masteriyo_before_course_archive_loop' );

		if ( isset( $attr['enableCourseFilters'] ) && $attr['enableCourseFilters'] == true && ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_filters' ) ) ) {
			$exclude_query_string_render = array( 'categories', 'difficulties', 'price-type', 'price-from', 'price-to' );

			/**
			 * Filters list of query variables to exclude while rendering form fields to include URL query params in course filter form.
			 *
			 * @since 1.18.2
			 *
			 * @param string[] $exclude
			 */
			$exclude_query_string_render = apply_filters( 'masteriyo_exclude_query_string_render_for_course_filter', $exclude_query_string_render );

			masteriyo_get_template(
				'course-filters.php',
				array(
					'exclude_query_string_render' => $exclude_query_string_render,
					'form_action_url'             => masteriyo_get_page_permalink( 'courses' ),
				)
			);
		}

		if ( count( $courses ) > 0 ) {
			$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

			/**
			 * Fires before rendering courses in the courses block.
			 *
			 * @since 1.18.2
			 *
			 * @param array                         $attr    Block attributes.
			 * @param \Masteriyo\Models\Course[]    $courses List of courses.
			 */
			do_action( 'masteriyo_blocks_before_courses_loop', $attr, $courses );

			masteriyo_course_loop_start();

			foreach ( $courses as $course ) {
				$GLOBALS['course'] = $course;
				\masteriyo_get_template_part( 'content', 'course' );
			}

			$GLOBALS['course'] = $original_course;

			masteriyo_course_loop_end();

			/**
			 * Fires after rendering courses in the courses block.
			 *
			 * @since 1.18.2
			 *
			 * @param array                         $attr    Block attributes.
			 * @param \Masteriyo\Models\Course[]    $courses List of courses.
			 */
			do_action( 'masteriyo_blocks_after_courses_loop', $attr, $courses );

			masteriyo_reset_loop();
		} else {
			/**
			 * Fires when no courses are found for the courses block.
			 *
			 * @since 1.3.0
			 */
			do_action( 'masteriyo_blocks_no_courses_found' );
		}

		echo '</div>';

		return \ob_get_clean();
	}
}
