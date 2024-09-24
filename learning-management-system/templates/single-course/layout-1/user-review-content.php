<?php

/**
 * The Template for displaying user review content.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/user-review-content.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<div class="masteriyo-single-body__main--user-review">
	<h3 class="masteriyo-single-body__main--user-review-heading"><?php esc_html_e( 'Reviews', 'learning-management-system' ); ?></h3>


	<?php
	/**
	 * Fires to render user reviews content on single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	do_action( 'masteriyo_layout_1_single_course_user_review_content', $course );
	?>
</div>
<?php
