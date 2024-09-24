<?php

/**
 * The Template for displaying course reviews list in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/reviews-list.php.
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

<div class="masteriyo-single-body__main--review-wrapper">
	<ul class="masteriyo-single-body__main--review-lists">
		<?php masteriyo_get_course_reviews_infinite_loading_page_html( $course, 1, true ); ?>
	</ul>
</div>
