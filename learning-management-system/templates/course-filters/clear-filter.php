<?php

/**
 * The Template for displaying clear-filter button.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/clear-filter.php.
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

<div class="masteriyo-filter-section--buttons">
	<a href="<?php echo esc_url( $clear_url ); ?>" class="masteriyo-clear-filters">
		<?php esc_html_e( 'Reset Filters', 'learning-management-system' ); ?>
	</a>
</div>
<?php
