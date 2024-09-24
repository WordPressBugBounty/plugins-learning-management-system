<?php

/**
 * The Template for displaying aside items in single course page.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/aside--items.php.
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
<div class="masteriyo-single-body__aside--items-wrapper">

  <?php
	/**
	 * Fires to allow adding additional content in the aside section on single course pages using layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	do_action( 'masteriyo_layout_1_single_course_aside_items', $course );
	?>
</div>
<?php
