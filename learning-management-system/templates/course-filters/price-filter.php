<?php

/**
 * The Template for displaying price filter.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/price-filter.php.
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
$course_query = new \WP_Query(
	array(
		'post_type'      => 'mto-course',
		's'              => '',
		'posts_per_page' => -1,
	)
);

if ( ( isset( $course_query->posts ) ) && ( ! empty( $course_query->posts ) ) ) {
	$courses = array_map(
		function ( $post ) {
			return (object) array(
				'ID'    => $post->ID,
				'price' => get_post_meta( $post->ID, '_price', true ),
			);
		},
		$course_query->posts
	);
}
$min_price = 0;
$max_price = ceil(
	max(
		array_map(
			function ( $course ) {
				return floatval( $course->price );
			},
			$courses
		)
	)
);

?>
<div class="masteriyo-filter-section masteriyo-price-filter-section">
	<div class="masteriyo-filter-section--heading">
		<h5><?php esc_html_e( 'Price', 'learning-management-system' ); ?></h5>

		<svg class="toggle-arrow"  xmlns="http://www.w3.org/2000/svg" fill="#1E293B" viewBox="0 0 24 24">
			<path fill-rule="evenodd" d="M21.582 6.403a1.468 1.468 0 0 0-2.02 0L12 13.68 4.439 6.403a1.468 1.468 0 0 0-2.02 0 1.339 1.339 0 0 0 0 1.944l8.57 8.25A1.46 1.46 0 0 0 12 17c.379 0 .742-.145 1.01-.403l8.572-8.25a1.339 1.339 0 0 0 0-1.944Z" clip-rule="evenodd"/>
		</svg>
	</div>
	<div class="masteriyo-filter-wrapper">
	<div class="masteriyo-price-filter">
		<div class="masteriyo-price-filter--input">
			<input
				class="masteriyo-price-from-filter"
				type="number"
				min="0"
				name="price-from"
				placeholder="<?php esc_attr_e( 'From', 'learning-management-system' ); ?>"
				value="<?php echo esc_attr( $price_from ); ?>"
				/>
			<span class="masteriyo-price-filter-separator">-</span>
			<input
				class="masteriyo-price-to-filter"
				type="number"
				min="0"
				name="price-to"
				placeholder="<?php esc_attr_e( 'To', 'learning-management-system' ); ?>"
				value="<?php echo esc_attr( $price_to ); ?>"
				/>
		</div>

		<div class="masteriyo-price-range-slider-wrapper">
			<div class="masteriyo-price-range-slider">
				<div class="masteriyo-price-progress"></div>
				</div>
				<div class="masteriyo-price-range-input">
				<input type="range" class="range-min" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" value="<?php echo esc_attr( $min_price ); ?>" step="1">
				<input type="range" class="range-max" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" value="<?php echo esc_attr( $max_price ); ?>" step="1">
				</div>
		</div>
	</div>
	</div>
</div>
<?php
