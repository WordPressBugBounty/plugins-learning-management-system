<?php

/**
 * The Template for displaying main content for single course.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/main-content.php
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0 [Free]
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before the main content for the single course layout 1.
 *
 * @since 1.10.0 [Free]
 *
 * @param \Masteriyo\Models\Course $course The course object.
 */
do_action( 'masteriyo_before_layout_1_single_course_main_content', $course );

?>
	<?php
		/**
		 * Fires to render main tab content in single course page layout 1.
		 *
		 * This action hook allows child themes and plugins to output main tab content
		 * in the single course page using layout 1.
		 *
		 * @since 2.0.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_layout_1_single_course_main_tab_content', $course );
	?>

	</div>

	<div class="masteriyo-single-body__aside">

		<?php
		/**
		 * Fires to render aside content in single course page layout 1.
		 *
		 * This action hook allows child themes and plugins to output aside content
		 * in the single course page using layout 1.
		 *
		 * @since 1.10.0 [Free]
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_layout_1_single_course_aside_content', $course );
		?>

	</div>
</div>
<?php
/**
 * Fires after the main content in the single course page layout 1.
 *
 * @since 1.10.0 [Free]
 *
 * @param \Masteriyo\Models\Course $course The course object.
 */
do_action( 'masteriyo_after_layout_1_single_course_main_content', $course );
