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
	<h5><?php esc_html_e( 'Price Type', 'learning-management-system' ); ?></h5>
	<div>
		<select name="price-type">
			<option value="" <?php echo esc_html( empty( $selected ) ? 'selected' : '' ); ?>>
				<?php esc_html_e( 'All', 'learning-management-system' ); ?>
			</option>

			<option value="free" <?php echo esc_html( 'free' === $selected ? 'selected' : '' ); ?>>
				<?php esc_html_e( 'Free', 'learning-management-system' ); ?>
			</option>

			<option value="paid" <?php echo esc_html( 'paid' === $selected ? 'selected' : '' ); ?>>
				<?php esc_html_e( 'Paid', 'learning-management-system' ); ?>
			</option>
		</select>
	</div>
</div>
<?php
