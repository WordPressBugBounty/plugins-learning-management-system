<?php

/**
 * The Template for displaying course stats in archive courses page layout 1
 *
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.12.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_before_course_archive_layout_1_meta_data' );

?>

  <div class="masteriyo-archive-card__content--info">
			<div class="masteriyo-archive-card__content--info-duration">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#646464" fill-rule="evenodd" d="M3 12a9 9 0 1 1 18 0 9 9 0 0 1-18 0Zm9-11C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1Zm1 5a1 1 0 1 0-2 0v6a1 1 0 0 0 .553.894l4 2a1 1 0 1 0 .894-1.788L13 11.382V6Z" clip-rule="evenodd" />
				</svg>

				<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?></span>
			</div>

			<div class="masteriyo-archive-card__content--info-students">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#646464" fill-rule="evenodd" d="M6.5 7.583a2.75 2.75 0 1 1 5.5 0 2.75 2.75 0 0 1-5.5 0ZM9.25 3a4.583 4.583 0 1 0 0 9.167A4.583 4.583 0 0 0 9.25 3ZM5.583 14A4.583 4.583 0 0 0 1 18.583v1.834a.917.917 0 0 0 1.833 0v-1.834a2.75 2.75 0 0 1 2.75-2.75h7.334a2.75 2.75 0 0 1 2.75 2.75v1.834a.917.917 0 0 0 1.833 0v-1.834A4.584 4.584 0 0 0 12.917 14H5.583Zm12.863.807a.917.917 0 0 1 1.116-.659A4.583 4.583 0 0 1 23 18.582v1.835a.917.917 0 0 1-1.833 0v-1.833a2.75 2.75 0 0 0-2.063-2.66.917.917 0 0 1-.658-1.117Zm-2.552-11.66a.917.917 0 0 0-.455 1.777 2.75 2.75 0 0 1 0 5.328.917.917 0 0 0 .455 1.776 4.583 4.583 0 0 0 0-8.88Z" clip-rule="evenodd" />
				</svg>

				<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count() ); ?></span>
			</div>

			<div class="masteriyo-archive-card__content--info-lessons">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path stroke="#646464" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Zm0 0A2.5 2.5 0 0 1 6.5 17H20" />
				</svg>

				<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_get_lessons_count( $course ) ); ?></span>
			</div>
		</div>

<?php

/**
 * Fires after rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_after_course_archive_layout_1_meta_data' );
