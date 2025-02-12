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
	<h5><?php esc_html_e( 'Categories', 'learning-management-system' ); ?></h5>
	<?php
	foreach ( $categories as $index => $category ) {
		$input_id = 'masteriyo-category-filter-' . $category->get_id();
		$label    = $category->get_name();
		$value    = $category->get_id();
		$checked  = in_array( $value, $selected_categories, true ) ? 'checked' : '';

		printf( '<div class="masteriyo-category-filter %s">', $index >= $initially_visible_categories_limit ? 'masteriyo-overflowed-category masteriyo-hidden' : '' );
		printf(
			'<input type="checkbox" id="%s" name="categories[]" value="%s" %s />',
			esc_attr( $input_id ),
			esc_attr( $value ),
			esc_attr( $checked )
		);
		printf( ' <label for="%s">%s</label>', esc_attr( $input_id ), esc_html( $label ) );
		printf( '</div>' );
	}

	if ( count( $categories ) > $initially_visible_categories_limit ) {
		printf( '<a href="#" class="masteriyo-see-more-categories">%s</a>', esc_html__( 'See More', 'learning-management-system' ) );
		printf( '<a href="#" class="masteriyo-see-less-categories masteriyo-hidden">%s</a>', esc_html__( 'See Less', 'learning-management-system' ) );
	}
	?>
</div>
<?php
