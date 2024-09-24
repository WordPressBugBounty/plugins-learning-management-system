<?php

/**
 * The Template for displaying related courses in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/content-related-posts-1.php.
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

/**
 * Fires before rendering related courses template in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_related_posts' );

$related_courses = masteriyo_get_related_courses( $GLOBALS['course'] );

if ( empty( $related_courses ) ) {
	/**
	 * Fires when there is no related posts (i.e. courses) to display.
	 *
	 * @since 1.0.0
	 */
	do_action( 'masteriyo_no_related_posts' );
	return;
}

/**
 * Fires before rendering related posts (i.e. courses).
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_before_related_posts_content' );

?>
<div class="masteriyo-related-post">
	<h3 class="masteriyo-related-post__title"><?php esc_html_e( 'Related Courses', 'learning-management-system' ); ?></h3>

	<div class="masteriyo-archive-cards">
		<?php
		foreach ( $related_courses as $course ) {
			$author         = masteriyo_get_user( $course->get_author_id() );
			$comments_count = masteriyo_count_course_comments( $course );
			$difficulty     = $course->get_difficulty();
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

					<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-archive-card__content--course-title">
						<h3 class="masteriyo-course-title"><?php echo esc_html( $course->get_title() ); ?></h3>
					</a>

					<div class="masteriyo-archive-card__content--rating-amount">
						<div class="masteriyo-archive-card__content--rating">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
							</svg>
							<!-- <strong>4.0</strong>(12 Reviews) -->
							<?php masteriyo_format_rating( $course->get_average_rating(), true ); ?> <?php echo esc_html( masteriyo_format_decimal( $course->get_average_rating(), 1, true ) ); ?> <?php echo '(' . esc_html( $course->get_review_count() ); ?>
						</div>

						<div class="masteriyo-archive-card__content--amount">
							<?php if ( $course->get_regular_price() && ( '0' === $course->get_sale_price() || ! empty( $course->get_sale_price() ) ) ) : ?>
								<div class="masteriyo-offer-price"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price() ) ); ?></div>
							<?php endif; ?>
							<span class="masteriyo-sale-price"><?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?></span>
						</div>
					</div>
					<div class="masteriyo-archive-card__content--info">
						<div class="masteriyo-archive-card__content--info-duration">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<path fill="#646464" fill-rule="evenodd" d="M3 12a9 9 0 1 1 18 0 9 9 0 0 1-18 0Zm9-11C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1Zm1 5a1 1 0 1 0-2 0v6a1 1 0 0 0 .553.894l4 2a1 1 0 1 0 .894-1.788L13 11.382V6Z" clip-rule="evenodd" />
							</svg>

							<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) ); ?></span>
						</div>

						<div class="masteriyo-archive-card__content--info-students">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<path fill="#646464" fill-rule="evenodd" d="M6.5 7.583a2.75 2.75 0 1 1 5.5 0 2.75 2.75 0 0 1-5.5 0ZM9.25 3a4.583 4.583 0 1 0 0 9.167A4.583 4.583 0 0 0 9.25 3ZM5.583 14A4.583 4.583 0 0 0 1 18.583v1.834a.917.917 0 0 0 1.833 0v-1.834a2.75 2.75 0 0 1 2.75-2.75h7.334a2.75 2.75 0 0 1 2.75 2.75v1.834a.917.917 0 0 0 1.833 0v-1.834A4.584 4.584 0 0 0 12.917 14H5.583Zm12.863.807a.917.917 0 0 1 1.116-.659A4.583 4.583 0 0 1 23 18.582v1.835a.917.917 0 0 1-1.833 0v-1.833a2.75 2.75 0 0 0-2.063-2.66.917.917 0 0 1-.658-1.117Zm-2.552-11.66a.917.917 0 0 0-.455 1.777 2.75 2.75 0 0 1 0 5.328.917.917 0 0 0 .455 1.776 4.583 4.583 0 0 0 0-8.88Z" clip-rule="evenodd" />
							</svg>

							<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_count_enrolled_users( $course->get_id() ) ); ?></span>
						</div>

						<div class="masteriyo-archive-card__content--info-lessons">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<path stroke="#646464" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Zm0 0A2.5 2.5 0 0 1 6.5 17H20" />
							</svg>

							<span class="masteriyo-info-label"><?php echo esc_html( masteriyo_get_lessons_count( $course ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<?php
/**
 * Fires after rendering related posts (i.e. courses).
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_related_posts_content' );

/**
 * Fires after rendering related courses template in single course page.
 *
 * @since 1.0.0
 */
do_action( 'masteriyo_after_related_posts' );
