<?php

/**
 * The Template for displaying course filters.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters.php.
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
<div class="masteriyo-courses-filters">
	<button class="masteriyo-close-filters-sidebar">
		<?php masteriyo_get_svg( 'cross', true ); ?>
	</button>
	<form method="get" action="<?php echo esc_url( $form_action_url ); ?>">
		<?php masteriyo_render_query_string_form_fields( null, $exclude_query_string_render ); ?>

		<?php
		/**
		 * Fires inside the course filter form.
		 *
		 * @since 1.16.0
		 */
		do_action( 'masteriyo_course_filter_form_content' );
		?>
	</form>
</div>
<div class="masteriyo-course-filter-sidebar-overlay"></div>
<?php
