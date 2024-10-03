<?php

/**
 * The Template for displaying certificate sharing button section in single course page
 *
 *
 * @package Masteriyo\Templates
 * @version 1.13.3
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering certificate share button section in single course page.
 *
 * @since 1.13.3
 *
 * @param \Masteriyo\Models\Course $course
 */
do_action( 'masteriyo_before_single_course_certificate_share', $course );

$button_text = __( 'Certificate Share', 'learning-management-system' );
?>

<div class="masteriyo-certificate-share-container" id="masteriyoCertificateShareButton">
	<button href="javascript:;" class="masteriyo-certificate-share__share-button">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-2">
			<circle cx="18" cy="5" r="3" />
			<circle cx="6" cy="12" r="3" />
			<circle cx="18" cy="19" r="3" />
			<line x1="8.59" x2="15.42" y1="13.51" y2="17.49" />
			<line x1="15.41" x2="8.59" y1="6.51" y2="10.49" />
		</svg>
		<?php
		$heading = __( 'Share your certificate', 'learning-management-system' );

		/**
		 * Filter the display text in certificate share button in single course page.
		 *
		 * @since 1.13.3
		 *
		 * @param string $heading The default display text.
		 */
		echo esc_html( apply_filters( 'masteriyo_certificate_share_button_text', $heading ) );
		?>
	</button>
</div>

<?php
/**
 * Fires after rendering certificate share button section in single course page.
 *
 * @since 1.13.3
 *
 * @param \Masteriyo\Models\Course $course
 */
do_action( 'masteriyo_after_single_course_certificate_share', $course );
