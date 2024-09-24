<?php

/**
 * The Template for displaying course review filters in single course page.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/review-filters.php.
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
<div class="masteriyo-single-body__main--user-review__search-rating">
	<div class="masteriyo-single-body__main--user-review__search">
		<?php masteriyo_get_svg( 'search', true ); ?>
		<input type="text" name="search" placeholder=<?php echo esc_attr__( 'Search reviews&hellip;', 'learning-management-system' ); ?> id="masteriyo-course-reviews-search-field" />
		<button class="masteriyo-single-search-btn" id="masteriyo-course-reviews-search-button"><?php esc_html_e( 'Search', 'learning-management-system' ); ?></button>
	</div>

	<div class="masteriyo-single-body__main--user-review__rating">
		<select name="rating" id="masteriyo-course-reviews-ratings-select">
			<option value="all"><?php esc_html_e( 'All Ratings', 'learning-management-system' ); ?></option>
			<option value="5"><?php esc_html_e( 'Five Star', 'learning-management-system' ); ?></option>
			<option value="4"><?php esc_html_e( 'Four Star', 'learning-management-system' ); ?></option>
			<option value="3"><?php esc_html_e( 'Three Star', 'learning-management-system' ); ?></option>
			<option value="2"><?php esc_html_e( 'Two Star', 'learning-management-system' ); ?></option>
			<option value="1"><?php esc_html_e( 'One Star', 'learning-management-system' ); ?></option>
		</select>
	</div>
</div>
