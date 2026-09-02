<?php

/**
 * The Template for displaying course featured badge in single course page and course archive page.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/featured-badge.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0 [Free]
 */

defined( 'ABSPATH' ) || exit;
$layout = masteriyo_get_setting( 'single_course.display.template.layout' );
?>


<?php if ( $course->is_featured() && 'layout1' !== $layout ) : ?>
	<div class="course-featured">
		<?php echo esc_html( $course->featured_text() ); ?>
	</div>
<?php endif; ?>
