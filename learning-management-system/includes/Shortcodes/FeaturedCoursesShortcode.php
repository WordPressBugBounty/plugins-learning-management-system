<?php
/**
 * Shortcode for listing featured courses.
 *
 * @since 2.2.9
 * @package Masteriyo\Shortcodes
 */

namespace Masteriyo\Shortcodes;

use Masteriyo\Abstracts\Shortcode;
use Masteriyo\Query\CourseQuery;

defined( 'ABSPATH' ) || exit;

class FeaturedCoursesShortcode extends Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @since 2.2.9
	 *
	 * @var string
	 */
	protected $tag = 'masteriyo_featured_courses';

	/**
	 * Shortcode default attributes.
	 *
	 * @since 2.2.9
	 *
	 * @var array
	 */
	protected $default_attributes = array(
		'max'     => 6,
		'columns' => 3,
	);

	/**
	 * Get shortcode content.
	 *
	 * @since 2.2.9
	 *
	 * @return string
	 */
	public function get_content() {
		$attr         = $this->get_attributes();
		$args         = array(
			'limit'    => absint( $attr['max'] ),
			'featured' => true,
		);
		$course_query = new CourseQuery( $args );
		$courses      = $course_query->get_courses();

		/**
		 * Filters courses that will be displayed in featured courses shortcode.
		 *
		 * @since 2.2.9
		 *
		 * @param Masteriyo\Models\Course[] $courses The courses objects.
		 * @param \Masteriyo\Query\CourseQuery $course_query Course query object.
		 * @param array $attr Shortcode attributes.
		 */
		$courses = apply_filters( 'masteriyo_shortcode_featured_courses', $course_query->get_courses(), $course_query, $attr );

		masteriyo_set_loop_prop( 'columns', absint( $attr['columns'] ) );

		\ob_start();

		echo '<div class="masteriyo-w-100">';

		if ( count( $courses ) > 0 ) {
			$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

			/**
			 * Fires before course loop in featured courses shortcode.
			 *
			 * @since 2.2.9
			 *
			 * @param Masteriyo\Models\Course[] $courses The courses objects.
			 * @param \Masteriyo\Query\CourseQuery $course_query Course query object.
			 * @param array $attr Shortcode attributes.
			 */
			do_action( 'masteriyo_shortcode_before_featured_courses_loop', $courses, $course_query, $attr );

			masteriyo_course_loop_start();

			foreach ( $courses as $course ) {
				$GLOBALS['course'] = $course;

				\masteriyo_get_template_part( 'content', 'course' );
			}

			$GLOBALS['course'] = $original_course;

			masteriyo_course_loop_end();

			/**
			 * Fires after course loop in featured courses shortcode.
			 *
			 * @since 2.2.9
			 *
			 * @param Masteriyo\Models\Course[] $courses The courses objects.
			 * @param \Masteriyo\Query\CourseQuery $course_query Course query object.
			 * @param array $attr Shortcode attributes.
			 */
			do_action( 'masteriyo_shortcode_after_featured_courses_loop', $courses, $course_query, $attr );

			masteriyo_reset_loop();
		} else {
			/**
			 * Fires when there is no course to display in featured courses shortcode.
			 *
			 * @since 2.2.9
			 *
			 * @param Masteriyo\Models\Course[] $courses The courses objects.
			 * @param \Masteriyo\Query\CourseQuery $course_query Course query object.
			 * @param array $attr Shortcode attributes.
			 */
			do_action( 'masteriyo_featured_courses_shortcode_no_courses_found', $courses, $course_query, $attr );
		}

		echo '</div>';

		return \ob_get_clean();
	}
}
