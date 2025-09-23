<?php
defined( 'ABSPATH' ) || exit;

do_action( 'masteriyo_before_related_posts' );

$parent_course   = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;
$related_courses = $parent_course ? masteriyo_get_related_courses( $parent_course ) : array();

if ( empty( $related_courses ) ) {
	do_action( 'masteriyo_no_related_posts' );
	return;
}

do_action( 'masteriyo_before_related_posts_content' );
?>
<div class="masteriyo-related-post">
	<h3 class="masteriyo-related-post__title">
		<?php esc_html_e( 'Related Courses', 'learning-management-system' ); ?>
	</h3>

	<div class="masteriyo-item--wrap masteriyo-w-100">
		<?php
		$default_card_class = apply_filters( 'masteriyo_related_course_card_class', '' );

		foreach ( $related_courses as $course ) {
			if ( empty( $course ) || ! $course->is_visible() ) {
				continue;
			}

			$course         = apply_filters( 'masteriyo_course_archive_course', $course );
			$author         = masteriyo_get_user( $course->get_author_id() );
			$comments_count = masteriyo_count_course_comments( $course );
			$difficulty     = $course->get_difficulty();
			$card_class     = $default_card_class;
			?>
			<div class="masteriyo-col masteriyo-col-4">
				<div class="masteriyo-course-item--wrapper masteriyo-course--card <?php echo esc_attr( $card_class ); ?>">
					<a href="<?php echo esc_url( $course->get_permalink() ); ?>" title="<?php esc_attr( $course->get_name() ); ?>">
						<div class="masteriyo-course--img-wrap">
							<?php
							if (
								( masteriyo_get_setting( 'course_archive.components_visibility.difficulty_badge' ) && $difficulty )
								|| ( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) && $difficulty )
							) :
								?>
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
										( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' )
											&& masteriyo_get_setting( 'course_archive.components_visibility.course_badge' )
											&& masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' )
											&& ! empty( $course->get_course_badge() )
										)
										||
										( ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' )
											&& masteriyo_get_setting( 'course_archive.components_visibility.course_badge' )
											&& masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' )
											&& ! empty( $course->get_course_badge() )
										)
									) :
										?>
										<div class="masteriyo-single-course--badge">
											<span class="masteriyo-badge"><?php echo esc_html( $course->get_course_badge() ); ?></span>
										</div>
									<?php endif; ?>
								</div><!-- /.masteriyo-course--badges -->
							<?php endif; ?>

							<?php echo wp_kses_post( $course->get_image( 'masteriyo_thumbnail' ) ); ?>
						</div>
					</a>

					<div class="masteriyo-course--content">
						<div class="masteriyo-course--content__category masteriyo-course-category">
							<?php foreach ( $course->get_categories( 'name' ) as $category ) : ?>
								<a href="<?php echo esc_url( $category->get_permalink() ); ?>" alt="<?php echo esc_attr( $category->get_name() ); ?>">
									<span class="masteriyo-course--content__category-items masteriyo-tag masteriyo-course-category--item">
										<?php echo esc_html( $category->get_name() ); ?>
									</span>
								</a>
							<?php endforeach; ?>
						</div>

						<h2 class="masteriyo-course--content__title">
							<?php
							do_action( 'masteriyo_before_course_archive_title_link', $course );
							printf(
								'<a href="%s" title="%s">%s</a>',
								esc_url( $course->get_permalink() ),
								esc_html( $course->get_title() ),
								esc_html( $course->get_title() )
							);
							do_action( 'masteriyo_after_course_archive_title_link', $course );
							?>
						</h2>

						<?php masteriyo_get_template( 'course-expiration-info.php', array( 'course' => $course ) ); ?>

						<div class="masteriyo-course--content__rt masteriyo-course-author-rating-wrapper">
							<div class="masteriyo-course-author">
								<?php if ( $author && is_a( $author, 'masteriyo-course-author--name' ) ) : ?>
									<a href="<?php echo esc_url( $author->get_course_archive_url() ); ?>">
										<img src="<?php echo esc_attr( $author->profile_image_url() ); ?>"
											alt="<?php echo esc_attr( $author->get_display_name() ); ?>"
											title="<?php echo esc_attr( $author->get_display_name() ); ?>">
										<span class="masteriyo-course-author--name">
											<?php echo esc_html( $author->get_display_name() ); ?>
										</span>
									</a>
								<?php endif; ?>
							</div>

							<?php do_action( 'masteriyo_after_course_author', $course ); ?>

							<?php
							if ( masteriyo_get_setting( 'course_archive.components_visibility.rating' ) && $course->is_review_allowed() ) :
								if ( is_user_logged_in() && ! masteriyo_get_setting( 'single_course.enable_review_visibility_control' ) ) :
									if ( $course->get_review_count() > 0 ) :
										?>
											<span class="masteriyo-icon-svg masteriyo-rating">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
												<path d="M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z"></path>
												</svg>
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

						<div class="masteriyo-course--content__description masteriyo-course-highlights">
							<?php echo wp_kses_post( masteriyo_trim_course_highlights( $course->get_highlights() ) ); ?>
						</div>

						<?php do_action( 'masteriyo_course_meta_data', $course ); ?>
						<?php do_action( 'masteriyo_course_progress', $course ); ?>

						<div class="masteriyo-course-card-footer masteriyo-time-btn masteriyo-course-pricing--wrapper">
							<?php
							/**
							 * Enroll button (same as archive card).
							 *
							 * @since 1.0.0
							 */
							if ( masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ) ) {
										do_action( 'masteriyo_template_enroll_button', $course );
							}
							?>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<?php
do_action( 'masteriyo_after_related_posts_content' );
do_action( 'masteriyo_after_related_posts' );
