<?php
/**
 * Product Loop Start
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/loop/loop-start-1.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.masteriyo.com/document/template-structure/
 * @package     Masteriyo\Templates
 * @version     1.10.0 [Free]
 */

defined( 'ABSPATH' ) || exit;
$layout = masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default';

if ( 'layout1' === $layout ) {
	$courses_class = 'masteriyo-archive-cards';
}
if ( 'layout2' === $layout ) {
	$courses_class = 'masteriyo-course-cards';
}
?>
<div class="masteriyo-courses-wrapper <?php echo esc_attr( $courses_class ); ?> <?php echo esc_attr( masteriyo_get_courses_view_mode() ); ?> col-<?php echo esc_attr( masteriyo_get_loop_prop( 'columns' ) ); ?>">
