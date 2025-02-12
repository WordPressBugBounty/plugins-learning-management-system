<?php

/**
 * The Template for course filter sidebar toggle button.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filter-sidebar-toggle.php.
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
<button class="masteriyo-toggle-course-filters-sidebar">
	<?php masteriyo_get_svg( 'filter-toggle', true ); ?>
	<span class="text">
		<?php esc_html_e( 'Filters', 'learning-management-system' ); ?>
	</span>
</button>
<?php
