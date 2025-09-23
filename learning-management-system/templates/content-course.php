<?php

/**
 * The template for displaying course content within loops
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/content-course.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.5.9
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

$is_slider_enabled = masteriyo_is_course_carousel_enabled();
$slider_class      = '';
if ( $is_slider_enabled ) {
	$slider_class = 'swiper-slide';
}

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
<div class="masteriyo-col <?php echo esc_attr( $slider_class ); ?>">
	<div class="masteriyo-course-item--wrapper masteriyo-course--card">
	<?php
	if ( masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ) ) :
		?>
		<div class="masteriyo-course--img-wrap">
			<a href="<?php echo esc_attr( $course->get_permalink() ); ?>">

				<!-- Difficulty Badge -->
			<?php if ( ( masteriyo_get_setting( 'course_archive.components_visibility.difficulty_badge' ) && $difficulty ) || ( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && $difficulty ) ) : ?>
		<div class="masteriyo-course--badges">
			<div class="difficulty-badge <?php echo esc_attr( $difficulty['slug'] ); ?>" data-id="<?php echo esc_attr( $difficulty['id'] ); ?>">
				<?php if ( $difficulty['color'] ) : ?>
					<span class="masteriyo-badge" style="background-color: <?php echo esc_attr( $difficulty['color'] ); ?>">
						<?php echo esc_html( $difficulty['name'] ); ?>
					</span>
				<?php else : ?>
					<span class="masteriyo-badge <?php echo esc_attr( masteriyo_get_difficulty_badge_css_class( $difficulty['slug'] ) ); ?>">
						<?php echo esc_html( $difficulty['name'] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php
			if (
				( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) &&
					masteriyo_get_setting( 'course_archive.components_visibility.course_badge' ) &&
					masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ) &&
					! empty( $course->get_course_badge() ) )
				||
				( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) &&
					masteriyo_get_setting( 'course_archive.components_visibility.course_badge' ) &&
					masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ) &&
					! empty( $course->get_course_badge() ) )
			) :
				?>
				<div class="masteriyo-single-course--badge">
					<span class="masteriyo-badge"><?php echo esc_html( $course->get_course_badge() ); ?></span>
				</div>
			<?php endif; ?>

		</div><!-- /.masteriyo-course--badges -->
	<?php endif; ?>

				<!-- Featured Image -->
				<?php echo wp_kses( $course->get_image( 'masteriyo_thumbnail' ), 'masteriyo_image' ); ?>
			</a>
		</div>
		<?php endif; ?>
		<div class="masteriyo-course--content">
			<div class="masteriyo-course--content__wrapper">
				<!-- Course category -->
				<?php if ( ! empty( $categories ) && masteriyo_get_setting( 'course_archive.components_visibility.categories' ) ) : ?>
						<?php do_action( 'masteriyo_course_category', $course ); ?>
				<?php endif; ?>

				<!-- Title of the course -->
				<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.course_title' ) ) : ?>
				<h2 class="masteriyo-course--content__title">
					<?php
					/**
					 * Fires right before rendering the course title link in course archive page.
					 *
					 * @since 1.12.2
					 *
					 * @param \Masteriyo\Models\Course $course Course object.
					 */
					do_action( 'masteriyo_before_course_archive_title_link', $course );

					printf(
						'<a href="%s" title="%s">%s</a>',
						esc_url( $course->get_permalink() ),
						esc_html( $course->get_title() ),
						esc_html( $course->get_title() )
					);

					/**
					 * Fires right after rendering the course title link in course archive page.
					 *
					 * @since 1.12.2
					 *
					 * @param \Masteriyo\Models\Course $course Course object.
					 */
					do_action( 'masteriyo_after_course_archive_title_link', $course );
					?>
				</h2>
				<?php endif; ?>

				
				<!-- Course Expiration Info -->
				<?php masteriyo_get_template( 'course-expiration-info.php', array( 'course' => $course ) ); ?>

				<!-- Course author and course rating -->
				<div class="masteriyo-course--content__rt masteriyo-course-author-rating-wrapper">
				<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.author' ) ) : ?>
					<div class="masteriyo-course-author">
						<?php if ( $author && ! is_wp_error( $author ) ) : ?>
							<a href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
								<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.author_avatar' ) ) : ?>
								<img src="<?php echo esc_attr( $author->profile_image_url() ); ?>" alt="<?php echo esc_attr( $author->get_display_name() ); ?>" title="<?php echo esc_attr( $author->get_display_name() ); ?>">
									<?php
									/**
									 * Hook: After course author render.
									 */
									do_action( 'masteriyo_after_course_author_image', $course, $context = 'archive_avatar' );
									?>
								<?php endif; ?>
								</a>
								<a href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
								<!-- Do not multiline below code, as it will create space around the display name. -->
								<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.author_name' ) && method_exists( $author, 'get_display_name' ) ) : ?>
									<span  class="masteriyo-course-author--name"><?php echo esc_html( $author->get_display_name() ); ?></span>
										<?php
										/**
										 * Hook: After course author render.
										 */
										do_action( 'masteriyo_after_course_author_text', $course, $context = 'archive_name' );
										?>
								<?php endif; ?>
							</a>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				<?php
				if ( masteriyo_get_setting( 'course_archive.components_visibility.rating' ) && $course->is_review_allowed() ) :
					if ( is_user_logged_in() && ! masteriyo_get_setting( 'single_course.enable_review_visibility_control' ) ) :
						if ( $course->get_review_count() > 0 ) :
							?>
						<span class="masteriyo-icon-svg masteriyo-rating">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
							</svg>
											<?php masteriyo_get_svg( 'full_star' ); ?>
										<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
										<?php echo '(' . esc_html( $course->get_review_count() ) . ')'; ?>
						</span>
										<?php
									endif;
								elseif ( masteriyo_get_setting( 'single_course.enable_review_visibility_control' ) ) :
									?>
						<span class="masteriyo-icon-svg masteriyo-rating">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
							</svg>
											<?php masteriyo_get_svg( 'full_star' ); ?>
										<?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?>
										<?php echo '(' . esc_html( $course->get_review_count() ) . ')'; ?>
						</span>
							<?php
					endif;
				endif;
				?>

				</div>

				<!-- Course description -->
				<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.course_description' ) ) : ?>
				<div class="masteriyo-course--content__description masteriyo-course-highlights">
					<?php if ( empty( $course->get_highlights() ) || empty( trim( wp_strip_all_tags( $course->get_highlights(), true ) ) ) ) : ?>
						<span class="masteriyo-course-highlights--description"><?php echo wp_kses_post( $course->get_excerpt() ); ?><span>
					<?php else : ?>
						<?php echo wp_kses_post( masteriyo_trim_course_highlights( $course->get_highlights() ) ); ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
					<!-- Four Column( Course duration, comments, student enrolled and curriculum ) -->
					<?php
					/**
					 * Fire for masteriyo archive course meta data.
					 *
					 * @since 1.11.0
					 *
					 * @param \Masteriyo\Models\Course $course Course object.
					 */
					do_action( 'masteriyo_course_meta_data', $course );
					?>

				<?php
				/**
				 * Fire for masteriyo archive course Progress.
				 *
				 * @since 1.20.0
				 *
				 * @param \Masteriyo\Models\Course $course Course object.
				 */
				do_action( 'masteriyo_course_progress', $course );
				?>

			</div>
			<!-- Border -->
			<!-- Price and Enroll Now Button -->
			<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.card_footer' ) ) : ?>
			<div class="masteriyo-course-card-footer masteriyo-time-btn masteriyo-course-pricing--wrapper">
				<?php if ( masteriyo_get_setting( 'course_archive.components_visibility.price' ) ) : ?>
						<?php if ( ! masteriyo_is_user_enrolled_in_course( $course->get_id() ) || ! masteriyo_is_course_order( $course->get_id() ) ) : ?>
				<div class="masteriyo-course-price">
							<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
						<del class="old-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price(), array( 'currency' => $course->get_currency() ) ) ); ?></del>
					<?php endif; ?>
					<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
				</div>
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
				if ( masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ) ) {
					do_action( 'masteriyo_template_enroll_button', $course );
				}
				?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
