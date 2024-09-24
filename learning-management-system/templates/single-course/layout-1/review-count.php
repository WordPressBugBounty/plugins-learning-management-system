<?php

/**
 * The Template for displaying review count for single course.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/review_count.php
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

$review_distribution = masteriyo_get_course_review_distribution_by_rating( $course->get_id() );


/**
 * Fires before rendering author and rating section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_before_single_course_layout_1_review_count' );

?>
<div class="masteriyo-single-body__main--review-count">
	<div class="masteriyo-single-body__main--review-count__rating">
		<h1 class="masteriyo-single-body-rating"><?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?></h1>

		<div class="masteriyo-single-body__main--review-count__rating-star">
			<?php masteriyo_render_stars( $course->get_average_rating() ); ?>
		</div>

		<span class="masteriyo-single-body-rating-count"><?php echo esc_html( $course->get_review_count() ); ?> <?php esc_html_e( 'Ratings', 'learning-management-system' ); ?></span>
	</div>

	<div class="masteriyo-single-body__main--review-showcase">
		<?php if ( empty( $review_distribution ) ) : ?>
			<p class="masteriyo-single-body__main--review-showcase__empty">
				<?php esc_html_e( 'No reviews yet.', 'learning-management-system' ); ?>
			</p>
		<?php else : ?>
			<?php foreach ( $review_distribution as $rating => $percentage ) : ?>
				<div class="masteriyo-single-body__main--review-showcase__<?php echo esc_attr( $rating ); ?>">
					<div class="masteriyo-single-body__main--review-showcase__text">
						<span class="masteriyo-single-body__main--review-showcase__star-count">
							<?php
							echo esc_html( $rating );
							esc_html_e( ' Star', 'learning-management-system' );
							?>

						</span>

						<span class="masteriyo-single-body__main--review-percent">
							<?php echo esc_html( $percentage ); ?>
						</span>
					</div>

					<div class="masteriyo-single-body__main--review-progress">
						<div class="masteriyo-single-body__main--review-progress-value" style="--value: <?php echo esc_attr( $percentage ); ?>;"></div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php

/**
 * Fires after rendering author and rating section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_after_single_course_layout_1_review_count' );
