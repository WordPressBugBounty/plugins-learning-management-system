<?php

/**
 * The template for displaying course content within loops
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/content-course-1.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0
 */

defined( 'ABSPATH' ) || exit;

global $course;

// Ensure visibility.
if ( empty( $course ) || ! $course->is_visible() ) {
	return;
}

$author     = masteriyo_get_user( $course->get_author_id() );
$difficulty = $course->get_difficulty();
$categories = $course->get_categories( 'name' );

?>
<div class="masteriyo-archive-card">
	<div class="masteriyo-archive-card__image">

	<?php
	/**
	 * Fires an action before the layout 1 course thumbnail is displayed.
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @since 1.10.0
	 */
	do_action( 'masteriyo_before_layout_1_course_thumbnail', $course );
	?>
		<!-- Course Image -->
		<img class="masteriyo-course-thumbnail" src="<?php echo esc_attr( $course->get_featured_image_url( 'masteriyo_medium' ) ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>">

		<?php
		/**
		 * Fires an action after the layout 1 course thumbnail is displayed.
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 *
		 * @since 1.10.0
		 */
		do_action( 'masteriyo_after_layout_1_course_thumbnail', $course );
		?>

		<!-- Author Image -->
		<img class="masteriyo-author-image" src="<?php echo esc_attr( $author->profile_image_url() ); ?>" alt="<?php echo esc_html( $author->get_display_name() ); ?>">

		<!-- Preview Course Button -->
		<a href="<?php echo esc_attr( $course->get_permalink() ); ?>" class="masteriyo-archive-card__image-preview-button">
			<div class="masteriyo-archive-card__image-preview-button--icon">
				<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
					<path d="M3 11h15.59l-7.3-7.29a1.004 1.004 0 1 1 1.42-1.42l9 9a.93.93 0 0 1 .21.33c.051.11.378.25.08.38a1.09 1.09 0 0 1-.08.39c-.051.115-.122.22-.21.31l-9 9a1.002 1.002 0 0 1-1.639-.325 1 1 0 0 1 .219-1.095l7.3-7.28H3a1 1 0 0 1 0-2Z" />
				</svg>
			</div>
			<?php
			echo esc_html( __( 'Preview Course', 'learning-management-system' ) );
			?>

		</a>
	</div>

	<div class="masteriyo-archive-card__content">
		<!-- Course category -->
		<div class="masteriyo-archive-card__content--category">
			<?php if ( ! empty( $categories ) ) : ?>
				<div class="masteriyo-course--content__category">
					<?php foreach ( $categories as $category ) : ?>
						<a href="<?php echo esc_attr( $category->get_permalink() ); ?>" alt="<?php echo esc_attr( $category->get_name() ); ?>" class="masteriyo-category">
							<?php echo esc_html( $category->get_name() ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Fires an action before the layout 1 course title is displayed.
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 *
		 * @since 1.10.0
		 */
		do_action( 'masteriyo_before_layout_1_course_title', $course );
		?>

		<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-archive-card__content--course-title">
			<h3 class="masteriyo-course-title"><?php echo esc_html( $course->get_title() ); ?></h3>
		</a>

		<?php
		/**
		 * Fires an action after the layout 1 course title is displayed.
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 *
		 * @since 1.10.0
		 */
		do_action( 'masteriyo_after_layout_1_course_title', $course );
		?>

		<div class="masteriyo-archive-card__content--rating-amount">
			<div class="masteriyo-archive-card__content--rating">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
				</svg>
				<?php echo wp_kses_post( masteriyo_get_svg( 'full_star' ) ); ?> <?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?> <?php echo '(' . esc_html( $course->get_review_count() . ')' ); ?>
			</div>

			<div class="masteriyo-archive-card__content--amount">
			<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
					<div class="masteriyo-offer-price"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price() ) ); ?></div>
				<?php endif; ?>
				<span class="masteriyo-sale-price"><?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?></span>
			</div>
		</div>

		<?php
				/**
				 * Fire for masteriyo archive course meta data layout 1.
				 *
				 * @since 1.12.0
				 *
				 * @param \Masteriyo\Models\Course $course Course object.
				 */
				do_action( 'masteriyo_course_archive_layout_1_meta_data', $course );
		?>
	</div>
</div>

<?php
