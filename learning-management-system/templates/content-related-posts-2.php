<?php
/**
 * The Template for displaying related courses in single course page (Layout 2 parity)
 *
 * Override by copying to:
 * yourtheme/masteriyo/single-course/content-related-posts-2.php
 *
 * @package Masteriyo\Templates
 * @version 1.10.0 [Free]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Before rendering related courses on single course.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_related_posts' );

$parent_course   = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;
$related_courses = $parent_course ? masteriyo_get_related_courses( $parent_course ) : array();

if ( empty( $related_courses ) ) {
	/**
	 * When there is no related posts (i.e. courses).
	 *
	 * @since 1.0.0
	 */
	do_action( 'masteriyo_no_related_posts' );
	return;
}

/**
 * Before related posts content wrapper.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_related_posts_content' );
?>
<div class="masteriyo-related-post" data-layout="layout_2">
	<h3 class="masteriyo-related-post__title">
		<?php esc_html_e( 'Related Courses', 'learning-management-system' ); ?>
	</h3>

	<div class="masteriyo-course-cards masteriyo-courses-wrapper col-3">
		<?php
		foreach ( $related_courses as $course ) {
			// Ensure visibility (same as archive).
			if ( empty( $course ) || ! $course->is_visible() ) {
				continue;
			}

			$author     = masteriyo_get_user( $course->get_author_id() ); // not rendered in layout 2, but available to hooks.
			$difficulty = $course->get_difficulty(); // not rendered in layout 2 by default.
			$categories = $course->get_categories( 'name' );

			/**
			 * Parity with archive filter so third parties can modify the object.
			 *
			 * @since 1.11.0 [free]
			 */
			$course = apply_filters( 'masteriyo_course_archive_course', $course );
			?>
			<div class="masteriyo-course-card">
				<?php
				/**
				 * Before layout 2 course image block.
				 *
				 * @since 1.10.0 [Free]
				 */
				do_action( 'masteriyo_before_course_archive_layout_2_course_image', $course );

				/**
				 * Before layout 2 course thumbnail.
				 *
				 * @since 1.10.0 [Free]
				 */
				do_action( 'masteriyo_before_layout_2_course_thumbnail', $course );
				?>

				<!-- Course Image -->
				<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ) ) : ?>
					<img class="masteriyo-course-card__thumbnail-image" src="<?php echo esc_attr( $course->get_featured_image_url( 'masteriyo_medium' ) ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>">
				<?php endif; ?>

				<?php
				/**
				 * After layout 2 course thumbnail.
				 *
				 * @since 1.10.0 [Free]
				 */
				//do_action( 'masteriyo_after_layout_2_course_thumbnail', $course );

				/**
				 * After layout 2 course image block.
				 *
				 * @since 1.10.0 [Free]
				 */
				do_action( 'masteriyo_after_course_archive_layout_2_course_image', $course );
				?>

				<div class="masteriyo-course-card__content">
					<!-- Course category -->
					<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.categories' ) && ! empty( $categories ) ) : ?>
						<?php //do_action( 'masteriyo_course_category', $course ); ?>
					<?php endif; ?>

					<div class="masteriyo-course-title-wrapper">
						<?php
						/**
						 * Before layout 2 title.
						 *
						 * @since 1.9.5 [Free]
						 */
						do_action( 'masteriyo_before_layout_2_course_title', $course );
						?>

						<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.course_title' ) ) : ?>
							<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-course-card__content--course-title">
								<h3 class="masteriyo-course-title"><?php echo esc_html( $course->get_title() ); ?></h3>
							</a>
						<?php endif; ?>

						<?php
						/**
						 * After layout 2 title.
						 *
						 * @since 1.9.5 [Free]
						 */
						do_action( 'masteriyo_after_layout_2_course_title', $course );
						?>
					</div>

					<div class="masteriyo-course-card__content--rating-amount">
						<?php
						if ( masteriyo_get_setting( 'course_archive.components_visibility.rating' ) && $course->is_review_allowed() ) :
							$review_count  = $course->get_review_count();
							$visibility_on = masteriyo_get_setting( 'single_course.display.enable_review_visibility_control' );

							if ( $visibility_on ) :
								if ( is_user_logged_in() ) :
									if ( $review_count > 0 ) :
										?>
											<div class="masteriyo-course-card__content--rating">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
												</svg>
											<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
											<?php echo '(' . esc_html( $review_count ) . ')'; ?>
											</div>
											<?php
										endif;
									elseif ( $review_count > 0 ) :
										?>
											<div class="masteriyo-course-card__content--rating">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
												</svg>
												<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
												<?php echo '(' . esc_html( $review_count ) . ')'; ?>
											</div>
											<?php

									endif;

								else :
									?>
									<div class="masteriyo-course-card__content--rating">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
											<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
										</svg>
										<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
										<?php echo '(' . esc_html( $review_count ) . ')'; ?>
									</div>
									<?php
								endif;
							endif;
						?>
						<?php
						if ( masteriyo_get_setting( 'course_archive.components_visibility.price' ) ) :
							if ( ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) || ! masteriyo_is_course_order( $course->get_id() ) ) :
								?>
								<div class="masteriyo-course-card__content--amount">
									<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
										<div class="masteriyo-course-card__content--amount-offer-price">
											<?php echo wp_kses_post( masteriyo_price( $course->get_regular_price(), array( 'currency' => $course->get_currency() ) ) ); ?>
										</div>
									<?php endif; ?>
									<span class="masteriyo-course-card__content--amount-sale-price">
										<?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?>
									</span>
								</div>
								<?php
							endif;
						endif;
						?>
					</div>

					<div class="masteriyo-course-card__content--container d-none">
						<?php
						/**
						 * Before layout 2 description.
						 *
						 * @since 1.10.0 [Free]
						 */
						do_action( 'masteriyo_before_layout_2_course_description', $course );
						?>

						<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.course_description' ) ) : ?>
							<!-- <p class="masteriyo-course-card__content--desc">
								<?php //echo wp_kses_post( wp_trim_words( $course->get_description(), 20, '...' ) ); ?>
							</p> -->
						<?php endif; ?>

						<?php
						/**
						 * After layout 2 description.
						 *
						 * @since 1.10.0 [Free]
						 */
						do_action( 'masteriyo_after_layout_2_course_description', $course );
						?>

						<?php
						/**
						 * Layout 2 meta data (duration, students, lessons, etc. via hook handlers).
						 *
						 * @since 2.13.0
						 */
						//do_action( 'masteriyo_course_archive_layout_2_meta_data', $course );

						/**
						 * Course progress (same as archive).
						 *
						 * @since 1.20.0 [Free]
						 */
						//do_action( 'masteriyo_course_progress', $course );
						?>

						<?php
						/**
						 * Enroll button (respect archive visibility).
						 *
						 * @since 1.0.0
						 */
						if ( masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ) ) {
								//do_action( 'masteriyo_template_enroll_button', $course );
						}
						?>
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
				</div>
			</div>
			<?php
		} // endforeach
		?>
	</div>
</div>
<?php
/**
 * After related posts content wrapper.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_related_posts_content' );

/**
 * After related courses template.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_related_posts' );
