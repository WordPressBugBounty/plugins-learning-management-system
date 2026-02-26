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
/**
 * Filters the course object before rendering it in the course archive.
 *
 * @since 1.11.0
 *
 * @param \Masteriyo\Models\Course $course The course object.
 *
 * @return \Masteriyo\Models\Course The filtered course object.
 */
$course = apply_filters( 'masteriyo_course_archive_course', $course );

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
		<?php if ( masteriyo_should_show_component( 'showThumbnail', 'course_archive.components_visibility.thumbnail' ) ) : ?>
		<img class="masteriyo-course-thumbnail" src="<?php echo esc_attr( $course->get_featured_image_url( 'masteriyo_medium' ) ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>">
		<?php endif; ?>

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
		<?php if ( masteriyo_should_show_component( 'showAuthorAvatar', 'course_archive.components_visibility.author_avatar' ) ) : ?>
			<img class="masteriyo-author-image" src="<?php echo esc_attr( $author->profile_image_url() ); ?>" alt="<?php echo esc_html( $author->get_display_name() ); ?>">
		<?php endif; ?>

		<!-- Preview Course Button -->
		<a href="<?php echo esc_attr( $course->get_permalink() ); ?>" class="masteriyo-btn masteriyo-btn-primary masteriyo-archive-card__image-preview-button">
			<div class="masteriyo-archive-card__image-preview-button--icon">
				<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
					<path d="M3 11h15.59l-7.3-7.29a1.004 1.004 0 1 1 1.42-1.42l9 9a.93.93 0 0 1 .21.33c.051.12.078.25.08.38a1.09 1.09 0 0 1-.08.39c-.051.115-.122.22-.21.31l-9 9a1.002 1.002 0 0 1-1.639-.325 1 1 0 0 1 .219-1.095l7.3-7.28H3a1 1 0 0 1 0-2Z" />
				</svg>
			</div>
			<?php
			echo esc_html( __( 'Preview Course', 'learning-management-system' ) );
			?>

		</a>
	</div>

	<div class="masteriyo-archive-card__content">
		<!-- Course category -->
		<?php if ( masteriyo_should_show_component( 'showCategories', 'course_archive.components_visibility.categories' ) && ! empty( $categories ) ) : ?>
				<?php do_action( 'masteriyo_course_category', $course ); ?>
		<?php endif; ?>
		<?php
		/**
		 * Fires an action before the layout 1 course title wrapper is displayed.
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 *
		 * @since 1.9.5 [Free]
		 */
		do_action( 'masteriyo_before_layout_1_course_title_wrapper', $course );
		?>
		<div class="masteriyo-course-title-wrapper">
			<?php
			/**
			 * Fires an action before the layout 1 course title is displayed.
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 *
			 * @since 1.9.5 [Free]
			 */
			do_action( 'masteriyo_before_layout_1_course_title', $course );
			?>
			<?php if ( masteriyo_should_show_component( 'showCourseTitle', 'course_archive.components_visibility.course_title' ) ) : ?>
			<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-archive-card__content--course-title">
				<h3 class="masteriyo-course-title"><?php echo esc_html( $course->get_title() ); ?></h3>
			</a>
			<?php endif; ?>
			<?php
			/**
			 * Fires an action after the layout 1 course title is displayed.
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 *
			 * @since 1.9.5 [Free]
			 */
			do_action( 'masteriyo_after_layout_1_course_title', $course );
			?>


		</div>

		<?php
		/**
		 * Action hook fired after the course title wrapper in the layout 1 course template.
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_after_layout_1_course_title_wrapper', $course );
		?>


				<div class="masteriyo-modern-layout--stats-rating">
			<?php
			if ( masteriyo_should_show_component( 'showRating', 'course_archive.components_visibility.rating' ) && $course->is_review_allowed() ) :
				$review_count  = $course->get_review_count();
				$visibility_on = masteriyo_get_setting( 'single_course.display.enable_review_visibility_control' );

				if ( $visibility_on ) :
					if ( is_user_logged_in() ) :
						if ( $review_count > 0 ) :
							?>
								<div class="masteriyo-archive-card__content--rating masteriyo-rating">
								<?php masteriyo_get_svg( 'full_star', true ); ?>
								<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
								<?php echo '(' . esc_html( $review_count ) . ')'; ?>
								</div>
								<?php
							endif;
						elseif ( $review_count > 0 ) :
							?>
								<div class="masteriyo-archive-card__content--rating masteriyo-rating">
									<?php masteriyo_get_svg( 'full_star', true ); ?>
									<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
									<?php echo '(' . esc_html( $review_count ) . ')'; ?>
								</div>
								<?php

						endif;
					else :
						?>
						<div class="masteriyo-archive-card__content--rating masteriyo-rating">
							<?php masteriyo_get_svg( 'full_star', true ); ?>
							<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
							<?php echo '(' . esc_html( $review_count ) . ')'; ?>
						</div>
						<?php
					endif;
				endif;

					/**
					 * Fire for masteriyo archive course meta data layout 1.
					 *
					 * @since 2.13.0
					 *
					 * @param \Masteriyo\Models\Course $course Course object.
					 */
					do_action( 'masteriyo_course_archive_layout_1_meta_data', $course );
			?>
					</div>
					<?php
					if ( ! \Masteriyo\CoreFeatures\CourseComingSoon\Helper::should_hide_meta_data( $course ) ) :
						?>
				<?php if ( masteriyo_should_show_component( 'showPrice', 'course_archive.components_visibility.price' ) ) : ?>
							<?php if ( ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) || ! masteriyo_is_course_order( $course->get_id() ) ) : ?>
						<div class="masteriyo-archive-card__content--rating-amount">
						<div class="masteriyo-archive-card__content--amount">
								<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
								<div class="masteriyo-offer-price"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price(), array( 'currency' => $course->get_currency() ) ) ); ?></div>
								<?php endif; ?>
								<span class="masteriyo-sale-price"><?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
							</div>
							</div>
					<?php endif; ?>
				<?php endif; ?>
		<?php endif; ?>
				<div class="masteriyo-course-archive--aside">
				<?php
				/**
				 * Fire for masteriyo archive course Progress.
				 *
				 * @since 1.11.0 [free]
				 *
				 * @param \Masteriyo\Models\Course $course Course object.
				 */
				do_action( 'masteriyo_course_progress', $course );
				?>
					<!-- Price and Enroll Now Button -->
			<?php if ( masteriyo_is_user_enrolled_in_course( $course->get_id() ) ) : ?>
			<div class="masteriyo-course-card-footer masteriyo-time-btn masteriyo-course-pricing--wrapper">
				<?php
				if ( ! \Masteriyo\CoreFeatures\CourseComingSoon\Helper::should_hide_meta_data( $course ) ) :
					?>
				<?php if ( masteriyo_should_show_component( 'showPrice', 'course_archive.components_visibility.price' ) ) : ?>
					<?php if ( ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) || ! masteriyo_is_course_order( $course->get_id() ) ) : ?>
				<div class="masteriyo-course-price">
						<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
						<del class="old-amount">
							<?php
							echo wp_kses_post(
								masteriyo_price(
									$course->get_regular_price(),
									array(
										'currency' => $course->get_currency(),
										'disable_tax_inclusive_label' => true,
									)
								)
							);
							?>
												</del>
					<?php endif; ?>
					<span class="current-amount"><?php echo wp_kses_post( $course->price_html() ); ?></span>
				</div>
				<?php endif; ?>
				<?php endif; ?>
				<?php endif; ?>


				<?php
				/**
				 * Action hook for rendering enroll button template.
				 *
				 * @since 1.0.0
				 *
				 * @param \Masteriyo\Models\Course $course Course object.
				 */
				if ( masteriyo_should_show_component( 'showEnrollButton', 'course_archive.components_visibility.enroll_button' ) ) {
					do_action( 'masteriyo_template_enroll_button', $course );
				}
				?>
			</div>
			<?php endif; ?>
			</div>

	</div>
</div>

<?php
