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

?>
<div class="masteriyo-filter-section masteriyo-price-filter-section">
	<h5><?php esc_html_e( 'Price', 'learning-management-system' ); ?></h5>
	<div class="masteriyo-price-filter">
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
		<button class="masteriyo-apply-price-filter masteriyo-btn masteriyo-btn-primary mb-0" type="submit">
			<?php esc_html_e( 'Go', 'learning-management-system' ); ?>
		</button>
	</div>
</div>
<?php
