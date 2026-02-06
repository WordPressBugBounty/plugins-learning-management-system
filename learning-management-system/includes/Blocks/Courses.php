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
		$attr = $this->attributes;
		$args = array(
			'limit'    => absint( $attr['count'] ),
			'order'    => isset( $attr['sortOrder'] ) ? strtoupper( $attr['sortOrder'] ) : 'DESC',
			'orderby'  => isset( $attr['sortBy'] ) ? $attr['sortBy'] : 'date',
			'category' => empty( $attr['categoryIds'] ) ? array() : $attr['categoryIds'],
			'status'   => PostStatus::PUBLISH,
		);

		/**
		 * filters courses args to add or make changes to the course query args.
		 *
		 * @since 2.0.2
		 */
		$args = apply_filters( 'masteriyo_blocks_courses_query_args', $args, $attr );

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

		// Set template class and variables
		$template       = isset( $attr['template'] ) && ! empty( $attr['template'] ) ? $attr['template'] : 'simple';
		$template_class = 'masteriyo-template-' . esc_attr( $template );

		// Set number of columns for the loop.
		masteriyo_set_loop_prop( 'columns', absint( $attr['columns'] ) );
		if ( isset( $attr['viewType'] ) && $attr['viewType'] ) {
			$GLOBALS['course_archive_view'] = 'list' === $attr['viewType'] ? 'list-view' : 'grid-view';
		}

		// Pass template selection to loop-start.php
		if ( 'modern' === $template ) {
			$GLOBALS['masteriyo_block_template'] = 'layout1';
		} elseif ( 'overlay' === $template ) {
			$GLOBALS['masteriyo_block_template'] = 'layout2';
		} else {
			$GLOBALS['masteriyo_block_template'] = 'default';
		}

		// Determine which template file to use and layout attribute
		$template_suffix  = '';
		$layout_data_attr = '';
		$layout_class     = '';

		if ( 'modern' === $template ) {
			$template_suffix  = '-1'; // Uses content-course-1.php
			$layout_data_attr = 'layout_1'; // SCSS expects data-layout="layout_1"
		} elseif ( 'overlay' === $template ) {
			$template_suffix  = '-2'; // Uses content-course-2.php
			$layout_class     = ' layout_2'; // SCSS expects class "layout_2"
			$layout_data_attr = 'layout_2'; // Add data attribute for overlay
		}
		// 'simple' uses default content-course.php (no suffix)

		\ob_start();

		// ALWAYS set block rendering context so filters and display options can use block attributes
		$GLOBALS['masteriyo_is_block_rendering'] = true;
		$GLOBALS['masteriyo_block_filter_attrs'] = $attr;

		// Set display options as global variables for templates to use
		$GLOBALS['masteriyo_block_display_options'] = array(
			'showThumbnail'         => isset( $attr['showThumbnail'] ) ? $attr['showThumbnail'] : true,
			'showDifficultyBadge'   => isset( $attr['showDifficultyBadge'] ) ? $attr['showDifficultyBadge'] : true,
			'showCategories'        => isset( $attr['showCategories'] ) ? $attr['showCategories'] : true,
			'showCourseTitle'       => isset( $attr['showCourseTitle'] ) ? $attr['showCourseTitle'] : true,
			'showAuthor'            => isset( $attr['showAuthor'] ) ? $attr['showAuthor'] : true,
			'showAuthorAvatar'      => isset( $attr['showAuthorAvatar'] ) ? $attr['showAuthorAvatar'] : true,
			'showAuthorName'        => isset( $attr['showAuthorName'] ) ? $attr['showAuthorName'] : true,
			'showRating'            => isset( $attr['showRating'] ) ? $attr['showRating'] : true,
			'showCourseDescription' => isset( $attr['showCourseDescription'] ) ? $attr['showCourseDescription'] : true,
			'showMetadata'          => isset( $attr['showMetadata'] ) ? $attr['showMetadata'] : true,
			'showCourseDuration'    => isset( $attr['showCourseDuration'] ) ? $attr['showCourseDuration'] : true,
			'showStudentsCount'     => isset( $attr['showStudentsCount'] ) ? $attr['showStudentsCount'] : true,
			'showLessonsCount'      => isset( $attr['showLessonsCount'] ) ? $attr['showLessonsCount'] : true,
			'showCardFooter'        => isset( $attr['showCardFooter'] ) ? $attr['showCardFooter'] : true,
			'showPrice'             => isset( $attr['showPrice'] ) ? $attr['showPrice'] : true,
			'showEnrollButton'      => isset( $attr['showEnrollButton'] ) ? $attr['showEnrollButton'] : true,
		);

		/**
		 * Fires before main content - includes search form and sorting.
		 * This renders the same search/sort section as the courses archive page.
		 *
		 * @since 2.0.6
		 */
		do_action( 'masteriyo_before_main_content' );

		echo '<div class="masteriyo-w-100 masteriyo-course-list-display-section masteriyo-block-' . esc_attr( $client_id ) . ' ' . esc_attr( $template_class ) . esc_attr( $layout_class ) . '"' . ( ! empty( $layout_data_attr ) ? ' data-layout="' . esc_attr( $layout_data_attr ) . '"' : '' ) . '>';
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

				// Use different template based on selection
				if ( ! empty( $template_suffix ) ) {
					\masteriyo_get_template( 'content-course' . $template_suffix . '.php' );
				} else {
					\masteriyo_get_template_part( 'content', 'course' );
				}
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

			// Clean up global template variable
			unset( $GLOBALS['masteriyo_block_template'] );
		} else {
			/**
			 * Fires when no courses are found for the courses block.
			 *
			 * @since 1.3.0
			 */
			do_action( 'masteriyo_blocks_no_courses_found' );
		}

		echo '</div>';

		// Clean up block rendering context and display options
		if ( isset( $GLOBALS['masteriyo_is_block_rendering'] ) ) {
			unset( $GLOBALS['masteriyo_is_block_rendering'] );
		}
		if ( isset( $GLOBALS['masteriyo_block_filter_attrs'] ) ) {
			unset( $GLOBALS['masteriyo_block_filter_attrs'] );
		}
		if ( isset( $GLOBALS['masteriyo_block_display_options'] ) ) {
			unset( $GLOBALS['masteriyo_block_display_options'] );
		}

		return \ob_get_clean();
	}
}
