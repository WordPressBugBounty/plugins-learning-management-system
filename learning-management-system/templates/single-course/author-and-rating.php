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

<div class="masteriyo-course--content__rt">

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
				<?php endif; ?>

				<?php if ( $show_name ) : ?>
					<span class="masteriyo-course-author--name"><?php echo esc_html( $author->get_display_name() ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Hook: After course author render.
	 */
	do_action( 'masteriyo_after_course_author', $course );
	?>

<?php if ( is_component_visible( $attributes['enableRating'] ?? null, 'rating' ) && $course->is_review_allowed() ) : ?>
		<span class="masteriyo-icon-svg masteriyo-rating">
			<?php
			masteriyo_format_rating( $course->get_average_rating(), true );
			echo ' ' . esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) );
			echo ' (' . esc_html( $course->get_review_count() ) . ')';
			?>
		</span>
	<?php endif; ?>

</div>

<?php
do_action( 'masteriyo_after_single_course_author_and_rating' );
