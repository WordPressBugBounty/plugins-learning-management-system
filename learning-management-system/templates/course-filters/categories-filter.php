<?php

/**
 * The Template for displaying categories filter.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/categories-filter.php.
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
<div class="masteriyo-filter-section masteriyo-categories-filter-section">
	<div class="masteriyo-filter-section--heading">
		<h5><?php esc_html_e( 'Categories', 'learning-management-system' ); ?></h5>

		<svg class="toggle-arrow" xmlns="http://www.w3.org/2000/svg" fill="#1E293B" viewBox="0 0 24 24">
  			<path fill-rule="evenodd" d="M21.582 6.403a1.468 1.468 0 0 0-2.02 0L12 13.68 4.439 6.403a1.468 1.468 0 0 0-2.02 0 1.339 1.339 0 0 0 0 1.944l8.57 8.25A1.46 1.46 0 0 0 12 17c.379 0 .742-.145 1.01-.403l8.572-8.25a1.339 1.339 0 0 0 0-1.944Z" clip-rule="evenodd"/>
		</svg>
	</div>
	<?php
	foreach ( $categories as $index => $category ) {
		$input_id = 'masteriyo-category-filter-' . $category->get_id();
		$label    = $category->get_name();
		$value    = $category->get_id();
		$checked  = in_array( $value, $selected_categories, true ) ? 'checked' : '';

		printf( '<div class="masteriyo-filter-wrapper"><div class="masteriyo-category-filter %s">', $index >= $initially_visible_categories_limit ? 'masteriyo-overflowed-category masteriyo-hidden' : '' );
		printf(
			'<input type="checkbox" id="%s" name="categories[]" value="%s" %s />',
			esc_attr( $input_id ),
			esc_attr( $value ),
			esc_attr( $checked )
		);
		printf( ' <label for="%s">%s</label>', esc_attr( $input_id ), esc_html( $label ) );
		printf( '</div></div>' );
	}

	if ( count( $categories ) > $initially_visible_categories_limit ) {
		printf( '<a href="#" class="masteriyo-see-more-categories">%s</a>', esc_html__( 'See More', 'learning-management-system' ) );
		printf( '<a href="#" class="masteriyo-see-less-categories masteriyo-hidden">%s</a>', esc_html__( 'See Less', 'learning-management-system' ) );
	}
	?>
</div>
<?php
