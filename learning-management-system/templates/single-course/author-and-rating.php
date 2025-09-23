<?php
/**
 * Template for displaying Author and Rating in Single Course.
 *
 * Override by placing in yourtheme/masteriyo/single-course/author-and-rating.php.
 *
 * @package Masteriyo\Templates
 * @version 1.5.9 (custom override with block-level attribute support)
 */

defined( 'ABSPATH' ) || exit;

do_action( 'masteriyo_before_single_course_author_and_rating' );

// Provide empty fallback if attributes not passed
$attributes = $attributes ?? array();

// Fallback compatibility helper
if ( ! function_exists( 'is_component_visible' ) ) {
	/**
	 * Helper function to determine visibility from block attributes or global settings.
	 *
	 * @param mixed  $block_attr   Attribute passed from block editor (bool or null).
	 * @param string $global_key   Key in the Masteriyo settings.
	 * @param bool   $default      Default fallback.
	 * @return bool
	 */
	function is_component_visible( $block_attr, $global_key, $default = false ) {
		if ( is_bool( $block_attr ) ) {
			return $block_attr;
		}
		return masteriyo_get_setting( "course_archive.components_visibility.$global_key", $default );
	}
}
?>

<div class="masteriyo-course--content__rt masteriyo-course-author-rating-wrapper">

	<?php
	$show_avatar = is_component_visible( $attributes['enableAuthorsAvatar'] ?? null, 'author_avatar' );
	$show_name   = is_component_visible( $attributes['enableAuthorsName'] ?? null, 'author_name' );
	$show_author = $author && ( $show_avatar || $show_name );
	?>

	<?php if ( $show_author ) : ?>
		<div class="masteriyo-course-author">
			<a href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
				<?php if ( $show_avatar ) : ?>
					<img src="<?php echo esc_attr( $author->profile_image_url() ); ?>"
						alt="<?php echo esc_attr( $author->get_display_name() ); ?>"
						title="<?php echo esc_attr( $author->get_display_name() ); ?>">
						<?php
						/**
						 * Hook: After course author render.
						 */
						do_action( 'masteriyo_after_course_author_image', $course, $context = 'avatar' );
						?>
				<?php endif; ?>
			</a>
			<a href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
				<?php
				if ( $show_name ) :
					?>
					<span class="masteriyo-course-author--name">
					<?php
					echo esc_html( $author->get_display_name() );
					$additional_authors = (array) $course->get_meta( '_additional_authors', false );
					if ( ! empty( $additional_authors ) ) {
						echo ', ';
					}
					?>
					</span>
					<?php
					/**
					 * Hook: After course author render.
					 */
					do_action( 'masteriyo_after_course_author_text', $course, $context = 'name' );
					?>
				<?php endif; ?>
			</a>
		</div>
	<?php endif; ?>
<?php
if ( is_component_visible( $attributes['enableRating'] ?? null, 'rating' ) && $course->is_review_allowed() ) :
	if ( is_user_logged_in() && ! masteriyo_get_setting( 'single_course.enable_review_visibility_control' ) ) :
		if ( $course->get_review_count() > 0 ) :
			?>
			<span class="masteriyo-icon-svg masteriyo-rating">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
				</svg>
				<?php
				echo ' ' . esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) );
				echo ' (' . esc_html( $course->get_review_count() ) . ')';
				?>
			</span>
			<?php
		endif;
	elseif ( masteriyo_get_setting( 'single_course.enable_review_visibility_control' ) ) :
		?>
		<span class="masteriyo-icon-svg masteriyo-rating">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
			</svg>
			<?php
			echo ' ' . esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) );
			echo ' (' . esc_html( $course->get_review_count() ) . ')';
			?>
		</span>
		<?php
	endif;
endif;
?>
</div>
<?php
do_action( 'masteriyo_after_single_course_author_and_rating' );
