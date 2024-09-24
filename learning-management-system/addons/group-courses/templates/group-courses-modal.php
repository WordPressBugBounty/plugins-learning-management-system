<?php

/**
 * The Template for displaying group buy button.
 *
 * @version 1.9.0
 */

use Masteriyo\Constants;

?>
<div class="masteriyo-group-courses-modal masteriyo-hidden" id="masteriyoGroupCoursesEnrollModal">
	<div class="masteriyo-overlay">
		<div class="masteriyo-group-course-popup">
			<div class="masteriyo-group-course__wrapper">
				<h2 class="masteriyo-group-course__heading">
					<?php
					$heading = __( 'Buy for group', 'learning-management-system' );
					/**
					 * Filter the heading text in group courses modal.
					 *
					 * @since 1.9.0
					 *
					 * @param string $heading The default heading text.
					 */
					echo esc_html( apply_filters( 'masteriyo_group_courses_modal_heading', $heading ) );
					?>
				</h2>

				<div class="masteriyo-group-course__exit-popup">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#7A7A7A" d="m13.4 12 8.3-8.3c.4-.4.4-1 0-1.4-.4-.4-1-.4-1.4 0L12 10.6 3.7 2.3c-.4-.4-1-.4-1.4 0-.4.4-.4 1 0 1.4l8.3 8.3-8.3 8.3c-.4.4-.4 1 0 1.4.2.2.4.3.7.3.3 0 .5-.1.7-.3l8.3-8.3 8.3 8.3c.2.2.5.3.7.3.2 0 .5-.1.7-.3.4-.4.4-1 0-1.4L13.4 12Z" />
					</svg>
				</div>

				<h5 class="masteriyo-group-course__title">
					<?php echo esc_html( $course->get_title() ); ?>
				</h5>

				<div class="masteriyo-notify-message masteriyo-alert masteriyo-info-msg"></div>
			</div>

			<!-- Empty State Image and Content Here -->
			<div class="masteriyo-group-course__empty-state">
				<div class="masteriyo-group-course__empty-state--image">
					<img src="<?php echo esc_url( Constants::get( 'MASTERIYO_GROUP_COURSES_ADDON_ASSETS_URL' ) . '/images/empty-state.png' ); ?>" alt="<?php esc_attr_e( 'No groups Found', 'learning-management-system' ); ?>">
				</div>

				<div class="masteriyo-group-course__empty-state--content">
					<h3 class="masteriyo-group-course__empty-state--content-title">
						<?php esc_html_e( "You don't have any groups yet", 'learning-management-system' ); ?>
					</h3>

					<span class="masteriyo-group-course__empty-state--content-desc">
						<a href="<?php echo esc_url( masteriyo_get_account_url() . '#/groups' ); ?>" class="masteriyo-group-course--link" target="_blank"><?php esc_html_e( 'Create a group', 'learning-management-system' ); ?></a><?php esc_html_e( '&nbsp;and add group members.', 'learning-management-system' ); ?>
					</span>

					<a href="javascript:;" class="masteriyo-group-course__lists--sync-button" data-fetching-text="<?php esc_attr_e( 'Syncing...', 'learning-management-system' ); ?>" data-fetch-text="<?php esc_attr_e( 'Sync Group(s)', 'learning-management-system' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#7A7A7A" d="M21.48 9a9.1 9.1 0 0 0-15-3.43l-2.66 2.5V4.72a.91.91 0 0 0-1.82 0v5.45a.5.5 0 0 0 0 .13.78.78 0 0 0 0 .21.87.87 0 0 0 .11.18c.025.035.048.072.07.11a.672.672 0 0 0 .19.14l.1.07h.12a.863.863 0 0 0 .23.05h5.54a.91.91 0 0 0 0-1.82H5.2l2.57-2.39a7.28 7.28 0 1 1-1.72 7.57.911.911 0 0 0-1.72.6 9.12 9.12 0 0 0 8.59 6.07 8.83 8.83 0 0 0 3-.52A9.09 9.09 0 0 0 21.48 9Z" />
						</svg>
						<?php esc_html_e( 'Sync Group(s)', 'learning-management-system' ); ?>
					</a>
				</div>
			</div>

			<!-- Has Group Lists and Contents Here -->
			<div class="masteriyo-group-course__lists">
				<div class="masteriyo-group-course__lists--label">
					<h5 class="masteriyo-group-course__lists--heading">
						<?php esc_html_e( 'Choose Group(s)', 'learning-management-system' ); ?>
					</h5>

					<a href="javascript:;" class="masteriyo-group-course__lists--sync-button" data-fetching-text="<?php esc_attr_e( 'Syncing...', 'learning-management-system' ); ?>" data-fetch-text="<?php esc_attr_e( 'Sync Group(s)', 'learning-management-system' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#7A7A7A" d="M21.48 9a9.1 9.1 0 0 0-15-3.43l-2.66 2.5V4.72a.91.91 0 0 0-1.82 0v5.45a.5.5 0 0 0 0 .13.78.78 0 0 0 0 .21.87.87 0 0 0 .11.18c.025.035.048.072.07.11a.672.672 0 0 0 .19.14l.1.07h.12a.863.863 0 0 0 .23.05h5.54a.91.91 0 0 0 0-1.82H5.2l2.57-2.39a7.28 7.28 0 1 1-1.72 7.57.911.911 0 0 0-1.72.6 9.12 9.12 0 0 0 8.59 6.07 8.83 8.83 0 0 0 3-.52A9.09 9.09 0 0 0 21.48 9Z" />
						</svg>
						<?php esc_html_e( 'Sync Group(s)', 'learning-management-system' ); ?>
					</a>
				</div>

				<ul class="masteriyo-group-course__lists--list">
				</ul>

				<div class="masteriyo-group-course__lists--footer">
					<a href="<?php echo esc_url( masteriyo_get_account_url() . '#/groups' ); ?>" class="masteriyo-group-course__lists--footer_link" target="_blank"><?php esc_html_e( 'Create a new group', 'learning-management-system' ); ?></a>
					<a href="javascript:;" class="masteriyo-group-course__lists--footer_checkout-button masteriyo-group-course__lists--footer_checkout-button--disabled">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path stroke="#000" d="m8.46 14.534-.003-.014-.003-.013-1.253-5.979v-.081l-.007-.041-.632-3.69-.071-.416H3.903c-.158 0-.25-.05-.302-.102-.051-.05-.101-.142-.101-.298s.05-.247.101-.298c.052-.052.144-.102.302-.102h2.979c.089 0 .18.035.262.105.08.068.13.153.151.222l.628 3.755.07.418h12.069c.081 0 .143.014.188.034a.274.274 0 0 1 .118.104l.028.041.035.035c.066.066.081.143.06.207l-.011.032-.006.033-1.172 6.11c-.222.942-.924 1.494-1.858 1.494h-7.132c-.938 0-1.712-.641-1.853-1.556ZM8.686 8.8h-.619l.13.605 1.084 5.04.002.008.002.009c.073.294.234.514.445.654.2.133.415.174.581.174h7.137c.06 0 .27 0 .489-.109.254-.126.463-.37.538-.743v-.001l.993-5.04.118-.597h-10.9Zm9.208 11.7a1.116 1.116 0 0 1-1.125-1.12c0-.622.497-1.12 1.125-1.12s1.125.498 1.125 1.12c0 .622-.497 1.12-1.125 1.12Zm0-1.8h-.009c-.035 0-.096 0-.15.006a.581.581 0 0 0-.352.162.581.581 0 0 0-.163.353 1.376 1.376 0 0 0-.006.15v.019c0 .034 0 .095.006.15a.581.581 0 0 0 .163.352.581.581 0 0 0 .352.162c.054.006.115.006.15.006h.019c.035 0 .096 0 .15-.006a.581.581 0 0 0 .352-.162.58.58 0 0 0 .164-.353c.006-.054.006-.115.006-.15v-.019c0-.034 0-.095-.006-.15a.58.58 0 0 0-.164-.352.581.581 0 0 0-.351-.162 1.376 1.376 0 0 0-.15-.006h-.01Zm.195.483a.439.439 0 0 1 .115.197.439.439 0 0 1-.31.31.44.44 0 0 1-.31-.31.439.439 0 0 1 .31-.31.44.44 0 0 1 .195.113ZM9.86 20.5a1.116 1.116 0 0 1-1.124-1.12c0-.622.497-1.12 1.125-1.12s1.125.498 1.125 1.12c0 .622-.497 1.12-1.125 1.12Zm0-1.8h-.008c-.035 0-.096 0-.15.006a.581.581 0 0 0-.352.162.58.58 0 0 0-.164.353 1.39 1.39 0 0 0-.006.15v.019c0 .034 0 .095.006.15a.58.58 0 0 0 .164.352.581.581 0 0 0 .351.162c.055.006.116.006.15.006h.019c.035 0 .096 0 .15-.006a.581.581 0 0 0 .352-.162.581.581 0 0 0 .164-.353c.006-.054.005-.115.005-.15v-.019c0-.034 0-.095-.005-.15a.581.581 0 0 0-.164-.352.581.581 0 0 0-.352-.162 1.376 1.376 0 0 0-.15-.006h-.01Zm.196.483a.439.439 0 0 1 .115.197.439.439 0 0 1-.31.31.44.44 0 0 1-.31-.31.44.44 0 0 1 .31-.31.44.44 0 0 1 .195.113Zm7.84.017c-.181 0-.181 0-.181.18 0 .09 0 .135.022.157.023.023.068.023.158.023.18 0 .18 0 .18-.18s0-.18-.18-.18Zm-8.035 0c-.18 0-.18 0-.18.18s0 .18.18.18c.09 0 .135 0 .158-.023.022-.022.022-.067.022-.157 0-.18 0-.18-.18-.18Z" />
						</svg>
						<?php esc_html_e( 'Go to Checkout', 'learning-management-system' ); ?>
					</a>
				</div>

			</div>
		</div>
	</div>
</div>
<?php
