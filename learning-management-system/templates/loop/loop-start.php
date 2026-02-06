<?php
/**
 * Product Loop Start
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/loop/loop-start.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.masteriyo.com/document/template-structure/
 * @package     Masteriyo\Templates
 * @version     1.0.0
 */

defined( 'ABSPATH' ) || exit;
$is_slider_enabled = masteriyo_is_course_carousel_enabled();

$slider_class = '';
if ( $is_slider_enabled ) {
	$slider_class = 'swiper';
}
$class = '';
// Check for block template first, then fall back to global setting
if ( isset( $GLOBALS['masteriyo_block_template'] ) ) {
	$layout = $GLOBALS['masteriyo_block_template'];
} else {
	$layout = masteriyo_get_setting('course_archive.display.template.layout') ?  masteriyo_get_setting('course_archive.display.template.layout') : 'default';
}

$col_class = '';
if ('layout1' === $layout){
	$class .= 'masteriyo-archive-cards';
	$col_class = ' col-' . esc_attr( masteriyo_get_loop_prop( 'columns' ) );
} elseif('layout2' === $layout){
	$class .= 'masteriyo-course-cards';
	$col_class = ' col-' . esc_attr( masteriyo_get_loop_prop( 'columns' ) );
}
?>
<div class="masteriyo-courses-wrapper masteriyo-course <?php  echo esc_attr($class) . esc_attr($col_class) ?> <?php echo esc_attr( masteriyo_get_courses_view_mode() ); ?> columns-<?php echo esc_attr( masteriyo_get_loop_prop( 'columns' ) ); ?> <?php echo ' ' . esc_attr( $slider_class ); ?>">

<?php
if ( $is_slider_enabled ) {
	echo '<div class="swiper-wrapper">';
}
?>
