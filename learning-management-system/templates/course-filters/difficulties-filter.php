<?php

/**
 * The Template for displaying difficulties filter.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/difficulties-filter.php.
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
<div class="masteriyo-filter-section masteriyo-difficulties-filter-section">
	<h5><?php esc_html_e( 'Difficulty', 'learning-management-system' ); ?></h5>
	<?php
	foreach ( $difficulties as $difficulty ) {
		$input_id = 'masteriyo-difficulty-filter-' . $difficulty->get_id();
		$label    = $difficulty->get_name();
		$value    = $difficulty->get_id();
		$checked  = in_array( $value, $selected_difficulties, true ) ? 'checked' : '';

		printf( '<div class="masteriyo-difficulty-filter">' );
		printf(
			'<input type="checkbox" id="%s" name="difficulties[]" value="%s" %s />',
			esc_attr( $input_id ),
			esc_attr( $value ),
			esc_attr( $checked )
		);
		printf( ' <label for="%s">%s</label>', esc_attr( $input_id ), esc_html( $label ) );
		printf( '</div>' );
	}
	?>
</div>
<?php
