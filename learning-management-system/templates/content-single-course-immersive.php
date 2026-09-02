<?php
/**
 * The Template for displaying single course details with the immersive layout.
 *
 * A landing-page style layout: a full-bleed hero band, a floating enroll card,
 * then one scrolling story — what you'll learn, description, curriculum,
 * instructor, reviews — closed by a CTA band. Sections render only when they
 * have content, and every block reuses the same renderers and hooks the other
 * layouts use, so every commerce state and addon works unchanged.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/content-single-course-immersive.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseProgressQuery;

defined( 'ABSPATH' ) || exit;

global $course;

if ( empty( $course ) || ! $course->is_visible() ) {
	return;
}

/**
 * Fires before rendering single course page content.
 *
 * @since 3.0.0
 */
do_action( 'masteriyo_before_single_course_content' );

$course_id = $course->get_id();
$author    = masteriyo_get_user( $course->get_author_id() );
$author    = is_wp_error( $author ) ? null : $author;

$query    = new CourseProgressQuery(
	array(
		'course_id' => $course_id,
		'user_id'   => get_current_user_id(),
	)
);
$progress = current( $query->get_course_progress() );

$description = $course->get_description();
$highlights  = masteriyo_get_setting( 'single_course.components_visibility.course_description' )
	? masteriyo_format_course_highlights( $course->get_highlights() )
	: '';

$average_rating = (float) $course->get_average_rating();
$review_count   = (int) $course->get_review_count();
$show_rating    = masteriyo_get_setting( 'single_course.components_visibility.rating' )
	&& $course->is_review_allowed() && $review_count > 0;

$show_title          = (bool) masteriyo_get_setting( 'single_course.components_visibility.course_title' );
$show_students_count = (bool) masteriyo_get_setting( 'single_course.components_visibility.students_count' );
$show_lessons_count  = (bool) masteriyo_get_setting( 'single_course.components_visibility.lessons_count' );
$show_seats          = (bool) masteriyo_get_setting( 'single_course.components_visibility.seats_for_students' );
$show_price          = (bool) masteriyo_get_setting( 'single_course.components_visibility.price' );

$enrolled_count  = absint( masteriyo_count_enrolled_users( $course_id ) );
$duration_string = masteriyo_get_setting( 'single_course.components_visibility.course_duration' ) && $course->get_duration()
	? masteriyo_minutes_to_time_length_string( $course->get_duration() )
	: '';
$difficulty      = masteriyo_get_setting( 'single_course.components_visibility.difficulty_badge' ) ? $course->get_difficulty() : null;
$date_modified   = masteriyo_get_setting( 'single_course.components_visibility.date_updated' ) ? $course->get_date_modified() : null;
$date_started    = masteriyo_get_setting( 'single_course.components_visibility.date_started' ) && ! empty( $progress ) && $progress->get_started_at()
	? strtotime( $progress->get_started_at() )
	: 0;

$lesson_count = $show_lessons_count ? get_course_section_children_count_by_course( $course_id, PostType::LESSON ) : 0;
$quiz_count   = $show_lessons_count ? get_course_section_children_count_by_course( $course_id, PostType::QUIZ ) : 0;

$hero_image_url = masteriyo_get_setting( 'single_course.components_visibility.thumbnail' ) && $course->get_featured_image()
	? wp_get_attachment_image_url( $course->get_featured_image(), 'full' )
	: '';

$sale_percent = 0;
if ( $show_price && $course->is_on_sale() && (float) $course->get_regular_price() > 0 ) {
	$sale_percent = (int) round( 100 - ( (float) $course->get_sale_price() / (float) $course->get_regular_price() ) * 100 );
}

$enrollment_limit = $course->get_enrollment_limit();
$remaining_seats  = $enrollment_limit > 0 ? max( 0, $enrollment_limit - $enrolled_count ) : 0;

?>
<div id="course-<?php the_ID(); ?>" class="masteriyo-single masteriyo-single-course masteriyo-single-course--wrapper" data-layout="immersive">
	<section
		class="masteriyo-immersive-hero<?php echo $hero_image_url ? ' masteriyo-immersive-hero--has-image' : ''; ?>"
		<?php if ( $hero_image_url ) : ?>
			style="--masteriyo-immersive-hero-image: url('<?php echo esc_url( $hero_image_url ); ?>');"
		<?php endif; ?>
	>
		<div class="masteriyo-immersive-hero__inner">
			<div class="masteriyo-immersive-hero__content">
				<?php if ( masteriyo_get_setting( 'single_course.components_visibility.categories' ) && ! empty( $course->get_categories() ) ) : ?>
					<div class="masteriyo-immersive-hero__badges">
						<?php masteriyo_single_course_categories( $course ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $show_title ) : ?>
					<h1 class="masteriyo-single-course--title masteriyo-immersive-hero__title"><?php echo esc_html( $course->get_name() ); ?></h1>
				<?php endif; ?>

				<div class="masteriyo-immersive-hero__meta">
					<?php if ( $show_rating ) : ?>
						<span class="masteriyo-immersive-hero__meta-item masteriyo-immersive-hero__meta-item--rating">
							<span class="masteriyo-immersive-hero__rating-value"><?php echo esc_html( masteriyo_format_decimal( $average_rating, 1, true ) ); ?></span>
							<?php masteriyo_render_stars( $average_rating, 'masteriyo-immersive-hero__stars' ); ?>
							<span class="masteriyo-immersive-hero__rating-count">
								<?php
								printf(
									/* translators: %s: reviews count */
									esc_html( _n( '(%s review)', '(%s reviews)', $review_count, 'learning-management-system' ) ),
									esc_html( number_format_i18n( $review_count ) )
								);
								?>
							</span>
						</span>
					<?php endif; ?>

					<?php if ( $show_students_count && $enrolled_count > 0 ) : ?>
						<span class="masteriyo-immersive-hero__meta-item">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3Zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"/></svg>
							<?php
							printf(
								/* translators: %s: enrolled students count */
								esc_html( _n( '%s student', '%s students', $enrolled_count, 'learning-management-system' ) ),
								esc_html( number_format_i18n( $enrolled_count ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php if ( $duration_string ) : ?>
						<span class="masteriyo-immersive-hero__meta-item">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8Z"/><path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
							<?php echo esc_html( $duration_string ); ?>
						</span>
					<?php endif; ?>

					<?php if ( ! empty( $difficulty ) && ! empty( $difficulty['name'] ) ) : ?>
						<span
							class="masteriyo-immersive-hero__meta-item masteriyo-immersive-hero__meta-item--difficulty difficulty <?php echo esc_attr( $difficulty['slug'] ); ?>"
							<?php if ( ! empty( $difficulty['color'] ) ) : ?>
								style="background-color: <?php echo esc_attr( $difficulty['color'] ); ?>;"
							<?php endif; ?>
						>
							<?php echo esc_html( $difficulty['name'] ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $date_modified ) : ?>
						<span class="masteriyo-immersive-hero__meta-item last-updated">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M17.65 6.35A7.958 7.958 0 0 0 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0 1 12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35Z"/>
							</svg>
							<?php
							printf(
								/* translators: %s: date of the last course update */
								esc_html__( 'Updated %s', 'learning-management-system' ),
								esc_html( $date_modified->date_i18n( 'F j, Y' ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php if ( $date_started ) : ?>
						<span class="masteriyo-immersive-hero__meta-item course-started-at">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.545 6.545V2.91a.91.91 0 0 1 1.819 0v3.636a.91.91 0 0 1-1.819 0Zm7.273 0V2.91a.91.91 0 1 1 1.818 0v3.636a.91.91 0 0 1-1.818 0Z"/><path d="M19.364 6.545a.91.91 0 0 0-.91-.909H5.727a.91.91 0 0 0-.909.91v12.727a.91.91 0 0 0 .91.909h12.726a.91.91 0 0 0 .91-.91V6.546Zm1.818 12.728A2.727 2.727 0 0 1 18.455 22H5.726A2.727 2.727 0 0 1 3 19.273V6.545a2.727 2.727 0 0 1 2.727-2.727h12.727a2.727 2.727 0 0 1 2.728 2.727v12.728Z"/><path d="M20.273 9.273a.91.91 0 1 1 0 1.818H3.909a.91.91 0 1 1 0-1.818h16.364Z"/></svg>
							<?php
							printf(
								/* translators: %s: date the current user started the course */
								esc_html__( 'Started %s', 'learning-management-system' ),
								esc_html( date_i18n( get_option( 'date_format' ), $date_started ) )
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( $author && ( masteriyo_get_setting( 'single_course.components_visibility.author_avatar' ) || masteriyo_get_setting( 'single_course.components_visibility.author_name' ) ) ) : ?>
					<div class="masteriyo-immersive-hero__author masteriyo-single--author">
						<?php if ( masteriyo_get_setting( 'single_course.components_visibility.author_avatar' ) ) : ?>
							<img
								class="masteriyo-immersive-hero__author-avatar masteriyo-single--author-img"
								src="<?php echo esc_url( $author->profile_image_url() ); ?>"
								alt="<?php echo esc_attr( $author->get_display_name() ); ?>"
							>
							<?php
							/** This action is documented in templates/single-course/author-and-rating.php */
							do_action( 'masteriyo_after_course_author_image', $course, 'avatar' );
							?>
						<?php endif; ?>
						<?php if ( masteriyo_get_setting( 'single_course.components_visibility.author_name' ) ) : ?>
							<span class="masteriyo-immersive-hero__author-name masteriyo-single--author-name">
								<?php
								printf(
									/* translators: %s: course author display name */
									esc_html__( 'Created by %s', 'learning-management-system' ),
									esc_html( $author->get_display_name() )
								);
								// Separator before the additional instructors, as in the other layouts.
								$additional_authors = (array) $course->get_meta( '_additional_authors', false );
								if ( ! empty( $additional_authors ) ) {
									echo ', ';
								}
								?>
							</span>
							<?php
							/** This action is documented in templates/single-course/author-and-rating.php */
							do_action( 'masteriyo_after_course_author_text', $course, 'name' );
							?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="masteriyo-immersive-body">
		<aside class="masteriyo-immersive-body__aside">
			<div class="masteriyo-immersive-enroll-card">
				<div class="masteriyo-immersive-enroll-card__media">
					<?php masteriyo_single_course_featured_image( $course ); ?>
				</div>
				<div class="masteriyo-immersive-enroll-card__body">
					<?php if ( $sale_percent > 0 ) : ?>
						<span class="masteriyo-immersive-enroll-card__sale-badge">
							<?php
							printf(
								/* translators: %s: sale discount percentage */
								esc_html__( '%s%% off', 'learning-management-system' ),
								esc_html( number_format_i18n( $sale_percent ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php
					$unhooked = array(
						array( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_stats', 30 ),
						array( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_highlights', 40 ),
						array( 'masteriyo_single_course_sidebar_content_after_progress', 'masteriyo_single_course_stats', 40 ),
						array( 'masteriyo_single_course_sidebar_content_after_progress', 'masteriyo_single_course_highlights', 50 ),
					);

					foreach ( $unhooked as $key => $hook ) {
						if ( ! remove_action( $hook[0], $hook[1], $hook[2] ) ) {
							unset( $unhooked[ $key ] );
						}
					}

					if ( ! empty( $progress ) ) {
						do_action( 'masteriyo_single_course_sidebar_content_after_progress', $course );
					} else {
						do_action( 'masteriyo_single_course_sidebar_content', $course );
					}

					foreach ( $unhooked as $hook ) {
						add_action( $hook[0], $hook[1], $hook[2] );
					}
					?>

					<?php if ( $show_seats && $enrollment_limit > 0 && $remaining_seats > 0 ) : ?>
						<p class="masteriyo-immersive-enroll-card__seats">
							<?php
							printf(
								/* translators: %s: remaining available seats count */
								esc_html( _n( 'Only %s seat left', 'Only %s seats left', $remaining_seats, 'learning-management-system' ) ),
								esc_html( number_format_i18n( $remaining_seats ) )
							);
							?>
						</p>
					<?php endif; ?>

					<?php if ( $duration_string || $lesson_count > 0 || $quiz_count > 0 ) : ?>
						<div class="masteriyo-immersive-enroll-card__includes">
							<h3 class="masteriyo-immersive-enroll-card__includes-title"><?php esc_html_e( 'This course includes', 'learning-management-system' ); ?></h3>
							<ul class="masteriyo-immersive-enroll-card__includes-list">
								<?php if ( $duration_string ) : ?>
									<li class="masteriyo-immersive-enroll-card__includes-item">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8Z"/><path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
										<span>
											<?php
											printf(
												/* translators: %s: course duration, e.g. 1h 30m */
												esc_html__( '%s of content', 'learning-management-system' ),
												esc_html( $duration_string )
											);
											?>
										</span>
									</li>
								<?php endif; ?>
								<?php if ( $lesson_count > 0 ) : ?>
									<li class="masteriyo-immersive-enroll-card__includes-item">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1Zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5Z"/></svg>
										<span>
											<?php
											printf(
												/* translators: %s: lessons count */
												esc_html( _nx( '%s lesson', '%s lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
												esc_html( number_format_i18n( $lesson_count ) )
											);
											?>
										</span>
									</li>
								<?php endif; ?>
								<?php if ( $quiz_count > 0 ) : ?>
									<li class="masteriyo-immersive-enroll-card__includes-item">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 18h2v-2h-2v2Zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8Z"/><path d="M12 6c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4Z"/></svg>
										<span>
											<?php
											printf(
												/* translators: %s: quizzes count */
												esc_html( _nx( '%s quiz', '%s quizzes', $quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
												esc_html( number_format_i18n( $quiz_count ) )
											);
											?>
										</span>
									</li>
								<?php endif; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php
					/** This action is documented in templates/single-course/highlights.php */
					do_action( 'masteriyo_after_single_course_highlights', $course );
					?>
				</div>
			</div>
			<?php do_action( 'masteriyo_after_course_content', $course ); ?>
		</aside>

		<main class="masteriyo-immersive-body__main">
			<?php if ( $highlights ) : ?>
				<section class="masteriyo-immersive-section masteriyo-immersive-learn-points masteriyo-course-highlights">
					<h2 class="masteriyo-immersive-section__title"><?php esc_html_e( "What you'll learn", 'learning-management-system' ); ?></h2>
					<div class="masteriyo-immersive-learn-points__grid">
						<?php
						/** This filter is documented in templates/single-course/highlights.php */
						echo wp_kses_post( apply_filters( 'masteriyo_single_course_highlights_content', $highlights ) );
						?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( trim( wp_strip_all_tags( $description, true ) ) ) : ?>
				<section class="masteriyo-immersive-section masteriyo-immersive-description course-overview">
					<h2 class="masteriyo-immersive-section__title"><?php esc_html_e( 'About this course', 'learning-management-system' ); ?></h2>
					<div class="masteriyo-immersive-description__content">
						<?php echo wp_kses_post( masteriyo_format_content_for_view( $description ) ); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php
			$show_curriculum = masteriyo_should_show_curriculum( $course ) && ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) );
			$sections        = $show_curriculum ? masteriyo_get_course_structure( $course_id ) : array();
			?>
			<?php if ( $show_curriculum && ! empty( $sections ) ) : ?>
				<section class="masteriyo-immersive-section masteriyo-immersive-curriculum">
					<h2 class="masteriyo-immersive-section__title"><?php esc_html_e( 'Course content', 'learning-management-system' ); ?></h2>
					<?php
					masteriyo_get_template(
						'single-course/immersive/curriculum.php',
						array(
							'course'              => $course,
							'sections'            => $sections,
							'lesson_progress_map' => get_course_lesson_progress_map( $course_id ),
							'section_count'       => masteriyo_get_sections_count_by_course( $course_id ),
							'lesson_count'        => $lesson_count,
							'quiz_count'          => $quiz_count,
							'duration_string'     => $duration_string,
						)
					);
					?>
				</section>
			<?php endif; ?>

			<?php if ( $author && ( masteriyo_get_setting( 'single_course.components_visibility.author_avatar' ) || masteriyo_get_setting( 'single_course.components_visibility.author_name' ) ) ) : ?>
				<section class="masteriyo-immersive-section masteriyo-immersive-instructor">
					<h2 class="masteriyo-immersive-section__title"><?php esc_html_e( 'Your instructor', 'learning-management-system' ); ?></h2>
					<div class="masteriyo-immersive-instructor__card">
						<?php if ( masteriyo_get_setting( 'single_course.components_visibility.author_avatar' ) ) : ?>
							<img
								class="masteriyo-immersive-instructor__avatar"
								src="<?php echo esc_url( $author->profile_image_url() ); ?>"
								alt="<?php echo esc_attr( $author->get_display_name() ); ?>"
							>
						<?php endif; ?>
						<div class="masteriyo-immersive-instructor__details">
							<?php if ( masteriyo_get_setting( 'single_course.components_visibility.author_name' ) ) : ?>
								<h3 class="masteriyo-immersive-instructor__name"><?php echo esc_html( $author->get_display_name() ); ?></h3>
							<?php endif; ?>
							<span class="masteriyo-immersive-instructor__role"><?php esc_html_e( 'Course instructor', 'learning-management-system' ); ?></span>
							<div class="masteriyo-immersive-instructor__stats">
								<?php $instructor_course_count = absint( count_user_posts( $author->get_id(), PostType::COURSE, true ) ); ?>
								<?php if ( $instructor_course_count > 0 ) : ?>
									<span class="masteriyo-immersive-instructor__stat">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1Z"/></svg>
										<?php
										printf(
											/* translators: %s: instructor courses count */
											esc_html( _n( '%s course', '%s courses', $instructor_course_count, 'learning-management-system' ) ),
											esc_html( number_format_i18n( $instructor_course_count ) )
										);
										?>
									</span>
								<?php endif; ?>
								<?php $instructor_student_count = absint( masteriyo_count_all_enrolled_users( $author ) ); ?>
								<?php if ( $instructor_student_count > 0 ) : ?>
									<span class="masteriyo-immersive-instructor__stat">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3Zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Z"/><path d="M8 13c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"/></svg>
										<?php
										printf(
											/* translators: %s: instructor students count */
											esc_html( _n( '%s student', '%s students', $instructor_student_count, 'learning-management-system' ) ),
											esc_html( number_format_i18n( $instructor_student_count ) )
										);
										?>
									</span>
								<?php endif; ?>
							</div>
							<?php if ( $author->get_description() ) : ?>
								<p class="masteriyo-immersive-instructor__bio"><?php echo esc_html( wp_strip_all_tags( $author->get_description() ) ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $course->is_review_allowed() ) : ?>
				<section class="masteriyo-immersive-section masteriyo-immersive-reviews course-reviews">
					<h2 class="masteriyo-immersive-section__title"><?php esc_html_e( 'What students say', 'learning-management-system' ); ?></h2>
					<?php
					$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course_id, 1 );
					/**
					 * Fires to render review content on single course page layout 1.
					 *
					 * @since 1.10.0 [Free]
					 *
					 * @param \Masteriyo\Models\Course $course The course object.
					 * @param array $reviews_and_replies Reviews and replies of the course.
					 */
					do_action( 'masteriyo_layout_1_single_course_review_content', $course, $reviews_and_replies );
					?>
				</section>
			<?php endif; ?>

			<?php
			/** This action is documented in templates/single-course/layout-1/main-content-tab.php */
			do_action( 'masteriyo_layout_1_single_course_tabbar_content', $course );
			?>
		</main>
	</div>

	<?php if ( masteriyo_get_setting( 'single_course.components_visibility.enroll_button' ) ) : ?>
		<section class="masteriyo-immersive-cta-band">
			<div class="masteriyo-immersive-cta-band__inner">
				<h2 class="masteriyo-immersive-cta-band__heading"><?php esc_html_e( 'Ready to start learning?', 'learning-management-system' ); ?></h2>
				<p class="masteriyo-immersive-cta-band__subheading"><?php echo esc_html( $course->get_name() ); ?></p>
				<div class="masteriyo-immersive-cta-band__action">
					<?php
					/**
					 * Fires to render the enroll button.
					 *
					 * @param \Masteriyo\Models\Course $course The course object.
					 */
					do_action( 'masteriyo_template_enroll_button', $course );
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
/**
 * Fires after rendering single course page content.
 *
 * @since 3.0.0
 */
do_action( 'masteriyo_after_single_course_content' );
