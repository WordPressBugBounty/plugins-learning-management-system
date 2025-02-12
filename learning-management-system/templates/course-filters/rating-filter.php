<?php

/**
 * The Template for displaying rating filter.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-filters/rating-filter.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.16.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="masteriyo-filter-section masteriyo-rating-filter-section">
	<h5>
		<?php esc_html_e( 'Rating', 'learning-management-system' ); ?>
	</h5>

	<?php
	for ( $rating = 5; $rating >= 0; $rating-- ) :
		$filter_url = isset( $filter_urls[ $rating ] ) ? $filter_urls[ $rating ] : '#';
		?>

		<div class="masteriyo-rating-filter-item">
			<a href="<?php echo esc_attr( $filter_url ); ?>" class="masteriyo-rating-filter-link" data-rating="<?php echo esc_attr( $rating ); ?>" >
				<div class="masteriyo-stab-rs border-none">
					<span class="masteriyo-icon-svg masteriyo-flex masteriyo-rstar">
						<?php masteriyo_render_stars( $rating ); ?>
					</span> <?php esc_html_e( '& Up', 'learning-management-system' ); ?>
				</div>
			</a>
		</div>
	<?php endfor; ?>
</div>
<?php
