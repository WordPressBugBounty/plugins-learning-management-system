<?php

/**
 * The Template for displaying course stats in archive courses page
 *
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.11.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_before_course_layout_1_meta_data' );

?>
<div class="masteriyo-single-header__content--info-items duration">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#646464" fill-rule="evenodd" d="M3 12a9 9 0 1 1 18 0 9 9 0 0 1-18 0Zm9-11C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1Zm1 5a1 1 0 1 0-2 0v6a1 1 0 0 0 .553.894l4 2a1 1 0 1 0 .894-1.788L13 11.382V6Z" clip-rule="evenodd"></path>
				</svg>

				<div class="masteriyo-single-header__content--info-items-label">
					<h6 class="masteriyo-single-heading"><?php esc_html_e( 'Duration', 'learning-management-system' ); ?></h6>
					<p class="masteriyo-single-desc"><?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?></p>
				</div>
			</div>

			<div class="masteriyo-single-header__content--info-items student">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#646464" fill-rule="evenodd" d="M6.5 7.583a2.75 2.75 0 1 1 5.5 0 2.75 2.75 0 0 1-5.5 0ZM9.25 3a4.583 4.583 0 1 0 0 9.167A4.583 4.583 0 0 0 9.25 3ZM5.583 14A4.583 4.583 0 0 0 1 18.583v1.834a.917.917 0 0 0 1.833 0v-1.834a2.75 2.75 0 0 1 2.75-2.75h7.334a2.75 2.75 0 0 1 2.75 2.75v1.834a.917.917 0 0 0 1.833 0v-1.834A4.584 4.584 0 0 0 12.917 14H5.583Zm12.863.807a.917.917 0 0 1 1.116-.659A4.583 4.583 0 0 1 23 18.582v1.835a.917.917 0 0 1-1.833 0v-1.833a2.75 2.75 0 0 0-2.063-2.66.917.917 0 0 1-.658-1.117Zm-2.552-11.66a.917.917 0 0 0-.455 1.777 2.75 2.75 0 0 1 0 5.328.917.917 0 0 0 .455 1.776 4.583 4.583 0 0 0 0-8.88Z" clip-rule="evenodd"></path>
				</svg>

				<div class="masteriyo-single-header__content--info-items-label">
					<h6 class="masteriyo-single-heading"><?php esc_html_e( 'Students', 'learning-management-system' ); ?></h6>
					<p class="masteriyo-single-desc"><?php echo esc_html( masteriyo_count_enrolled_users( $course->get_id() + $course->get_fake_enrolled_count() ) ); ?></p>
				</div>
			</div>

			<?php if ( $difficulty ) : ?>
				<div class="masteriyo-single-header__content--info-items difficulty">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#4E4E4E" fill-rule="evenodd" d="M18.857 2C19.488 2 20 2.497 20 3.111V20.89c0 .614-.512 1.111-1.143 1.111s-1.143-.497-1.143-1.111V3.11c0-.614.512-1.111 1.143-1.111ZM12 8.667c.631 0 1.143.497 1.143 1.11V20.89c0 .613-.513 1.11-1.143 1.11s-1.143-.497-1.143-1.111V9.778c0-.614.512-1.111 1.143-1.111Zm-5.714 7.777c0-.613-.512-1.11-1.143-1.11S4 15.83 4 16.443v4.445C4 21.503 4.512 22 5.143 22s1.143-.497 1.143-1.111v-4.445Z" clip-rule="evenodd" />
					</svg>

					<div class="masteriyo-single-header__content--info-items-label">
						<h6 class="masteriyo-single-heading"><?php esc_html_e( 'Level', 'learning-management-system' ); ?></h6>
						<p class="masteriyo-single-desc"><?php echo esc_html( $difficulty['name'] ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<div class="masteriyo-single-header__content--info-items last-updated">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#4E4E4E" fill-rule="evenodd" d="M6.343 6.343A8 8 0 0 1 11.998 4a8.75 8.75 0 0 1 6.041 2.453l.547.547H16a1 1 0 1 0 0 2h5a.997.997 0 0 0 1-1V3a1 1 0 1 0-2 0v2.586l-.553-.553-.012-.012A10.75 10.75 0 0 0 12.004 2H12A10 10 0 0 0 2 12a1 1 0 1 0 2 0 8 8 0 0 1 2.343-5.657ZM22 12a10 10 0 0 1-10 10h-.004a10.75 10.75 0 0 1-7.431-3.021l-.012-.012L4 18.414V21a1 1 0 1 1-2 0v-5a.997.997 0 0 1 .29-.705l.005-.004A.997.997 0 0 1 2.997 15H8a1 1 0 1 1 0 2H5.414l.547.547A8.75 8.75 0 0 0 12.002 20 8 8 0 0 0 20 12a1 1 0 1 1 2 0Z" clip-rule="evenodd" />
				</svg>

				<div class="masteriyo-single-header__content--info-items-label">
					<h6 class="masteriyo-single-heading"><?php esc_html_e( 'Last Updated', 'learning-management-system' ); ?></h6>
					<p class="masteriyo-single-desc">
						<?php
						$modified_date  = strtotime( $course->get_date_modified() );
						$formatted_date = gmdate( 'F j, Y', $modified_date );
						echo esc_html( $formatted_date );
						?>
					</p>
				</div>
			</div>

			<!-- Available seats for students-->
			<?php if ( $course->get_enrollment_limit() > 0 ) : ?>
				<div class="masteriyo-single-header__content--info-items available-seats">
					<?php masteriyo_get_svg( 'available-seats-for-students', true ); ?>
					<div class="masteriyo-single-header__content--info-items-label">
						<h6 class="masteriyo-single-heading"><?php echo esc_html( _n( 'Available Seat', 'Available Seats', absint( $remaining_available_seats ), 'learning-management-system' ) ); ?></h6>
						<p class="masteriyo-single-desc">
							<?php echo esc_html( $remaining_available_seats ); ?>
						</p>
					</div>
				</div>
			<?php endif; ?>

<?php

/**
 * Fires after rendering stats section in archive course page.
 *
 * @since 1.11.0
 */
do_action( 'masteriyo_after_course_layout_1_meta_data' );
