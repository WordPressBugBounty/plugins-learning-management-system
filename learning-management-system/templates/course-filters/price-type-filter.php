<?php

/**
 * The Template for displaying price type filter.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/price-type-filter.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.16.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="masteriyo-filter-section masteriyo-price-type-filter-section">
	<div class="masteriyo-filter-section--heading">
		<h5><?php esc_html_e( 'Price Type', 'learning-management-system' ); ?></h5>

		<svg class="toggle-arrow"  xmlns="http://www.w3.org/2000/svg" fill="#1E293B" viewBox="0 0 24 24">
			<path fill-rule="evenodd" d="M21.582 6.403a1.468 1.468 0 0 0-2.02 0L12 13.68 4.439 6.403a1.468 1.468 0 0 0-2.02 0 1.339 1.339 0 0 0 0 1.944l8.57 8.25A1.46 1.46 0 0 0 12 17c.379 0 .742-.145 1.01-.403l8.572-8.25a1.339 1.339 0 0 0 0-1.944Z" clip-rule="evenodd"/>
		</svg>
	</div>
	<div class="masteriyo-filter-wrapper">
	<ul class="masteriyo-filter-section--price-type">
		<li class="masteriyo-filter-section--price-type__list">
			<input type="radio" id="all" name="price-type" checked="checked" value="all">
			<label for="all">
				<?php echo esc_html__( 'All', 'learning-management-system' ); ?>
			</label>
		</li>

		<li class="masteriyo-filter-section--price-type__list">
			<input type="radio" id="free" name="price-type" value="free">
			<label for="free">
			<?php echo esc_html__( 'Free', 'learning-management-system' ); ?>
			</label>
		</li>

		<li class="masteriyo-filter-section--price-type__list">
			<input type="radio" id="paid" name="price-type" value="paid">
			<label for="paid">
			<?php echo esc_html__( 'Paid', 'learning-management-system' ); ?>
			</label>
		</li>

	</ul>
	</div>
</div>
<?php
