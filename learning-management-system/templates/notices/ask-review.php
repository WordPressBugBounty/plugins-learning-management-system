<?php
/**
 * Admin notice to ask for review.
 *
 * @since 1.4.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="masteriyo-notice masteriyo-review-notice updated">
	<div class="masteriyo-notice-main-content">
		<?php masteriyo_get_svg( 'heart-outline', true ); ?>
		<div class="masteriyo-notice-main-content-wrapper">
			<p class="masteriyo-notice__title">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: the product's name */
						__( 'Love using LMS by %s?', 'learning-management-system' ),
						masteriyo_get_plugin_name()
					)
				);
				?>
			</p>
			<div class="masteriyo-notice__description">
				<?php esc_html_e( 'Please do us a favor by providing 5-star', 'learning-management-system' ); ?> <div class="star-icons">
					<?php masteriyo_get_svg( 'full_star', true ); ?>
					<?php masteriyo_get_svg( 'full_star', true ); ?>
					<?php masteriyo_get_svg( 'full_star', true ); ?>
					<?php masteriyo_get_svg( 'full_star', true ); ?>
					<?php masteriyo_get_svg( 'full_star', true ); ?>
				</div>
				<?php
				printf(
					/* translators: %1$s, %2$s: link markup around "here", %3$s: the product's name */
					esc_html__( 'rating at WordPress.org. Let us know %1$shere%2$s if you have any query. - %3$s Team', 'learning-management-system' ),
					'<a href="https://masteriyo.com/contact/" class="masteriyo-notice-link" target="_blank" rel="noopener noreferrer">',
					'</a>',
					esc_html( masteriyo_get_plugin_name() )
				);
				?>
			</div>
		</div>
		<div class="masteriyo-x-icon-container">
			<?php masteriyo_get_svg( 'x', true ); ?>
		</div>
	</div>
	<div class="masteriyo-notice__actions submit">
		<a href="https://wordpress.org/support/plugin/learning-management-system/reviews/?rate=5#new-post" class="button button-primary masteriyo-leave-review" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Sure, I\'d love to', 'learning-management-system' ); ?>
		</a>
		<button class="button button-secondary masteriyo-remind-me-later">
			<?php esc_html_e( 'Maybe later', 'learning-management-system' ); ?>
		</button>
		<button class="button button-secondary masteriyo-already-reviewed">
			<?php esc_html_e( 'I already did', 'learning-management-system' ); ?>
		</button>
	</div>
</div>
