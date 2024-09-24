<?php
/**
 * The Template for displaying course reviews filters in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/reviews-filters.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.9.3
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<div class="masteriyo-course-reviews-filters">
	<div class="masteriyo-course-reviews-search">
	<span class="masteriyo-course-reviews-search__icon">
		<?php masteriyo_get_svg( 'search', true ); ?>
	</span>
	<input type="search" id="masteriyo-course-reviews-search-field" class="search-field masteriyo-input" placeholder="<?php echo esc_attr__( 'Search reviews&hellip;', 'learning-management-system' ); ?>" name="search" />
	<button type="button" value="<?php esc_attr_e( 'Search', 'learning-management-system' ); ?>" id="masteriyo-course-reviews-search-button">
		<?php esc_html_e( 'Search', 'learning-management-system' ); ?>
	</button>
	</div>
	<div class="masteriyo-course-reviews-ratings">
		<select name="rating" id="masteriyo-course-reviews-ratings-select">
			<option value="all"><?php esc_html_e( 'All Ratings', 'learning-management-system' ); ?></option>
			<?php
			for ( $i = 5; $i >= 1; $i-- ) {
				?>
				<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
				<?php
			}
			?>
		</select>
	</div>
</div>
<?php
