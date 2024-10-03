<?php

/**
 * The Template for displaying certificate preview/share modal.
 *
 * @version 1.13.3
 */

use Masteriyo\Constants;

?>
<div class="masteriyo-certificate-share-modal masteriyo-hidden" id="masteriyoCertificateShareModal">
	<div class="masteriyo-overlay">
		<div class="masteriyo-certificate-share-popup">
			<div class="masteriyo-certificate-share__wrapper">
				<h2 class="masteriyo-certificate-share__heading">
					<?php
					$heading = __( 'Certificate Preview', 'learning-management-system' );

					/**
					 * Filter the heading text in certificate preview modal.
					 *
					 * @since 1.13.3
					 *
					 * @param string $heading The default heading text.
					 */
					echo esc_html( apply_filters( 'masteriyo_certificate_share_modal_heading', $heading ) );
					?>
				</h2>

				<div class="masteriyo-certificate-share__exit-popup">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#7A7A7A" d="m13.4 12 8.3-8.3c.4-.4.4-1 0-1.4-.4-.4-1-.4-1.4 0L12 10.6 3.7 2.3c-.4-.4-1-.4-1.4 0-.4.4-.4 1 0 1.4l8.3 8.3-8.3 8.3c-.4.4-.4 1 0 1.4.2.2.4.3.7.3.3 0 .5-.1.7-.3l8.3-8.3 8.3 8.3c.2.2.5.3.7.3.2 0 .5-.1.7-.3.4-.4.4-1 0-1.4L13.4 12Z" />
					</svg>
				</div>

				<h5 class="masteriyo-certificate-share__title">
					<?php echo esc_html( $course->get_name() ); ?>
				</h5>

				<div class="masteriyo-share-container">

				<h5 class="masteriyo-certificate-share__title">
					<?php echo esc_html( $certificate->get_name() ); ?>
				</h5>

				<div class="masteriyo-certificate-share__share-link">

					<!-- facebook -->
					<a
						target="_blank"
						href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_html( rawurlencode( $certificate_url['view_url'] ) ); ?>"
						align="center"
						title="
				<?php
				$facebook_title = __( 'Share on Facebook', 'learning-management-system' );

				/**
				 * Filter the facebook title text in certificate preview modal.
				 *
				 * @since 1.13.3
				 *
				 * @param string $heading The default title text.
				 */
				echo esc_html( apply_filters( 'masteriyo_certificate_share_modal_facebook_title', $facebook_title ) );
				?>
					">
						<?php masteriyo_get_svg( 'facebook', true ); ?>
					</a>

					<!-- twitter -->

					<a
						target="_blank"
						href="https://twitter.com/intent/tweet?url=<?php echo esc_html( rawurlencode( $certificate_url['view_url'] ) ); ?>"
						align="center"
						title="
				<?php
				$twitter_title = __( 'Share on X', 'learning-management-system' );

				/**
				 * Filter the twitter title text in certificate preview modal.
				 *
				 * @since 1.13.3
				 *
				 * @param string $heading The default title text.
				 */
				echo esc_html( apply_filters( 'masteriyo_certificate_share_modal_twitter_title', $twitter_title ) );
				?>
				"
						height="24"
						width="24">
						<?php masteriyo_get_svg( 'twitter', true ); ?>
					</a>

					<!-- linkedin -->

					<a
						style="margin-top: 100px;"
						target="_blank"
						href="https://www.linkedin.com/sharing/share-offsite/?text=<?php echo esc_html( rawurlencode( $certificate_url['view_url'] ) ); ?>"
						align="center"
						title="
				<?php
				$linkedin_title = __( 'Share on LinkedIn', 'learning-management-system' );

				/**
				 * Filter the linkedin title text in certificate preview modal.
				 *
				 * @since 1.13.3
				 *
				 * @param string $heading The default title text.
				 */
				echo esc_html( apply_filters( 'masteriyo_certificate_share_modal_linkedin_title', $linkedin_title ) );
				?>
				">
						<?php masteriyo_get_svg( 'linkedin', true ); ?>
					</a>
				</div>
				</div>

				<!-- preview -->

				<div>
					<iframe src="<?php echo esc_url( $certificate_url['view_url'] ); ?>"
						id="masteriyoCertificateShareImage"
						frameborder="0"
						width="100%"
						height="450px"></iframe>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
