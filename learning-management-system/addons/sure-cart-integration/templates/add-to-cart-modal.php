<?php
/**
 *
 * Dialog modals for surecart courses for courses page.
 *
 * @package Masteriyo\Addons\SureCartIntegration\Templates
 * @version 1.12.0
 *
 */

use Masteriyo\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="masteriyo-surecart-course-modal masteriyo-hidden" id="masteriyoSurecartModal">
	<div class="masteriyo-overlay">
		<div class="masteriyo-surecart-course-popup">
			<div class="masteriyo-surecart-course__wrapper">
				<h2 class="masteriyo-surecart-course__heading">
					<?php
					$heading = __( 'Choose a price', 'learning-management-system' );
					/**
					 * Filter the heading text in surecart courses modal.
					 *
					 * @since 1.9.0
					 *
					 * @param string $heading The default heading text.
					 */
					echo esc_html( apply_filters( 'masteriyo_surecart_courses_modal_heading', $heading ) );
					?>
				</h2>

				<div class="masteriyo-surecart-course__exit-popup">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#7A7A7A" d="m13.4 12 8.3-8.3c.4-.4.4-1 0-1.4-.4-.4-1-.4-1.4 0L12 10.6 3.7 2.3c-.4-.4-1-.4-1.4 0-.4.4-.4 1 0 1.4l8.3 8.3-8.3 8.3c-.4.4-.4 1 0 1.4.2.2.4.3.7.3.3 0 .5-.1.7-.3l8.3-8.3 8.3 8.3c.2.2.5.3.7.3.2 0 .5-.1.7-.3.4-.4.4-1 0-1.4L13.4 12Z" />
					</svg>
				</div>

				<h5 class="masteriyo-surecart-course__title">
				</h5>

				<div class="masteriyo-notify-message masteriyo-alert masteriyo-info-msg masteriyo-hidden"></div>
			</div>

					<!-- Empty State Image and Content Here -->
				<div class="masteriyo-surecart-course__empty-state">
				<div class="masteriyo-surecart-course__empty-state--content">
					<h3 class="masteriyo-surecart-course__empty-state--content-title">
						<?php esc_html_e( "You don't have any prices yet", 'learning-management-system' ); ?>
					</h3>

					<a href="javascript:;" class="masteriyo-surecart-course__lists--loading-text" data-fetching-text="<?php esc_attr_e( 'Fetching...', 'learning-management-system' ); ?>" data-fetch-text="<?php esc_attr_e( 'Fetch Prices', 'learning-management-system' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#7A7A7A" d="M21.48 9a9.1 9.1 0 0 0-15-3.43l-2.66 2.5V4.72a.91.91 0 0 0-1.82 0v5.45a.5.5 0 0 0 0 .13.78.78 0 0 0 0 .21.87.87 0 0 0 .11.18c.025.035.048.072.07.11a.672.672 0 0 0 .19.14l.1.07h.12a.863.863 0 0 0 .23.05h5.54a.91.91 0 0 0 0-1.82H5.2l2.57-2.39a7.28 7.28 0 1 1-1.72 7.57.911.911 0 0 0-1.72.6 9.12 9.12 0 0 0 8.59 6.07 8.83 8.83 0 0 0 3-.52A9.09 9.09 0 0 0 21.48 9Z" />
						</svg>
						<?php esc_html_e( 'Fetch Prices', 'learning-management-system' ); ?>
					</a>
				</div>
			</div>


			<!-- product prices list  -->
			<div class="masteriyo-surecart-course__lists">
				<div class="masteriyo-surecart-course__lists--list">
				</div>
			</div>
		</div>

	</div>

</div>
