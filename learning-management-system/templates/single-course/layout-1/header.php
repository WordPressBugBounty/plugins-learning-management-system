<?php

/**
 * The Template for displaying header for single course.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/header.php
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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.


if ( ! $course ) {
	return;
}

$author = masteriyo_get_user( $course->get_author_id() );

/**
 * Fires before rendering author and rating section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_before_layout_1_single_course_header' );

?>
<div class="masteriyo-single-header">
	<div class="masteriyo-single-header__content">
		<?php if ( ! empty( $course->get_categories() ) ) : ?>
			<div class="masteriyo-single-header__content--category">
				<?php foreach ( $course->get_categories() as $category ) : ?>
					<span class="masteriyo-single-header__content--category-list"><?php echo esc_html( $category->get_name() ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="masteriyo-single-header__content-title-wrapper">
			<?php
			/**
			 * Fires before rendering course title section in single course page.
			 *
			 * @since 1.12.2
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 */
			do_action( 'masteriyo_before_layout_1_single_course_title', $course );
			?>

			<h2 class="masteriyo-single-header__content--title"><?php echo esc_html( $course->get_name() ); ?></h2>

			<?php
			/**
			 * Fires after rendering course title section in single course page.
			 *
			 * @since 1.12.2
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 */
			do_action( 'masteriyo_after_layout_1_single_course_title', $course );
			?>
		</div>

		<?php
		/**
		 * Fires before rendering author and rating section in single course page.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_before_layout_1_single_course_author_and_rating', $course );
		?>
		<div class="masteriyo-single-header__content--author-rating">
			<div class="masteriyo-single--author">
				<img class="masteriyo-single--author-img" src="<?php echo esc_url( $author->profile_image_url() ); ?>" alt="<?php echo esc_html( $author->get_display_name() ); ?>">
				<span class="masteriyo-single--author-name"><?php echo esc_html( $author->get_display_name() ); ?></span>
			</div>

			<div class="masteriyo-single-header__content--rating">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
				</svg>
				<?php echo wp_kses_post( masteriyo_get_svg( 'full_star' ) ); ?> <?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?> <?php echo '(' . esc_html( $course->get_review_count() . ')' ); ?>
			</div>
		</div>
		<?php
		/**
		 * Fires after rendering author and rating section in single course page.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_after_layout_1_single_course_author_and_rating', $course );
		?>

		<div class="masteriyo-single-header__content--info">
			<?php
			/**
			 * Fires before rendering course info items section in single course page layout 1.
			 *
			 * @since 1.10.0
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 */
			do_action( 'masteriyo_before_layout_1_single_course_info_items', $course );
			?>

			<!-- Five Column( duration, students, difficulty , last updated and seats ) -->
			<?php
				/**
				 * Fire for masteriyo archive course meta data.
				 *
				 * @since 1.12.0
				 *
				 * @param \Masteriyo\Models\Course $course Course object.
				 */
				do_action( 'masteriyo_course_layout_1_meta_data', $course );
			?>

			<?php
			/**
			 * Fires after rendering course info items section in single course page layout 1.
			 *
			 * @since 1.10.0
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 */
			do_action( 'masteriyo_after_layout_1_single_course_info_items', $course );
			?>
		</div>

	</div>
	<?php
	/**
	 * Fires before rendering course info items section in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	do_action( 'masteriyo_before_layout_1_single_course_featured_image', $course );
	?>
	<div class="masteriyo-single-header__image">
		<img src="<?php echo esc_attr( $course->get_featured_image_url( 'masteriyo_single' ) ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>">
	</div>
	<?php
	/**
	 * Fires after rendering course info items section in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	do_action( 'masteriyo_after_layout_1_single_course_featured_image', $course );
	?>
</div>
<?php

/**
 * Fires after rendering info contents in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_after_layout_1_single_course_header' );
