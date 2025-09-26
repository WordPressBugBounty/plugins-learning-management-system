<?php
/**
 * The Template for displaying course highlights in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/main-content-tab.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 2.0.0
 */

use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Fires before rendering main content tab in single course page.
 *
 * @since 2.0.0
 */
do_action( 'masteriyo_before_layout_1_single_course_main_tab_content', $course );

?>
	<div class="masteriyo-single-body">
		<div class="masteriyo-single-body__main masteriyo-single-course--main__content">
		<?php do_action( 'masteriyo_layout_1_single_course_main_content_tabbar', $course ); ?>
	<section class="masteriyo-single-body__main--content">

	<!-- Curriculum Content -->
		<?php
		if ( $show_curriculum ) :
			if ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) ) :
				?>
			<div id="masteriyoSingleCourseCurriculumTab" class=" tab-content course-curriculum masteriyo-single-body__main--curriculum-content <?php echo $curriculum_is_hidden ? 'masteriyo-hidden' : ''; ?>">
				<div class="masteriyo-single-body__main--curriculum-content-top">
					<ul class="masteriyo-single-body__main--curriculum-content-top--shortinfo">
						<?php
						$section_count = masteriyo_get_sections_count_by_course( $course->get_id() );

						if ( $section_count > 0 ) :
							?>
							<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
								<?php

								printf(
									/* translators: %1$s: Sections count */
									esc_html( _nx( '%1$s Section', '%1$s Sections', $section_count, 'Sections Count', 'learning-management-system' ) ),
									esc_html( number_format_i18n( $section_count ) )
								);
								?>
							</li>
						<?php endif; ?>

							<?php
							$lesson_count = get_course_section_children_count_by_course( $course->get_id(), PostType::LESSON );

							if ( $lesson_count > 0 ) :
								?>

							<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
								<?php
								// $lesson_count = get_course_section_children_count_by_course( $course->get_id(), PostType::LESSON );

								printf(
									/* translators: %1$s: Lessons count */
									esc_html( _nx( '%1$s Lesson', '%1$s Lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
									esc_html( number_format_i18n( $lesson_count ) )
								);
								?>
							</li>
							<?php endif; ?>

							<?php
							$quiz_count = get_course_section_children_count_by_course( $course->get_id(), PostType::QUIZ );

							if ( $quiz_count > 0 ) :
								?>

							<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
								<?php

								printf(
									/* translators: %1$s: Quizzes count */
									esc_html( _nx( '%1$s Quiz', '%1$s Quizzes', $quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
									esc_html( number_format_i18n( $quiz_count ) )
								);
								?>
							</li>
							<?php endif; ?>

							<?php
							/**
							 * Fires after the tab bar in the main content area for the single course layout 1.
							 *
							 * This action hook allows you to add additional tabs to the tab bar.
							 *
							 * @since 1.10.0 [Free]
							 *
							 * @param \Masteriyo\Models\Course $course The course object.
							 */
							do_action( 'masteriyo_layout_1_single_course_curriculum_shortinfo_item', $course );
							?>

						<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
							<?php
							echo esc_html( masteriyo_minutes_to_time_length_string( $course->get_duration() ) );
							esc_html_e( ' Duration', 'learning-management-system' );
							?>
						</li>
					</ul>

					<span class="masteriyo-single-body__main--curriculum-content-top--expand-btn" data-expand-all-text="<?php esc_html_e( 'Expand All', 'learning-management-system' ); ?>" data-collapse-all-text="<?php esc_html_e( 'Collapse All', 'learning-management-system' ); ?>" data-expanded="false"><?php esc_html_e( 'Expand All', 'learning-management-system' ); ?></span>
				</div>
					<?php if ( ! empty( $sections ) ) : ?>
					<div class="masteriyo-single-body__main--curriculum-content-bottom">
						<?php foreach ( $sections as $index => $section ) : ?>
							<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion">
								<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header">
									<h4 class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header-title"><?php echo esc_html( $section->get_name() ); ?></h4>

									<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header-misc">

									<?php
										$lesson_count = get_course_section_children_count_by_section( $section->get_id(), PostType::LESSON );
									if ( $lesson_count > 0 ) :
										?>

										<span class="masteriyo-single-body-accordion-info">
										<?php
										printf(
											/* translators: %1$s: Lessons count */
											esc_html( _nx( '%1$s Lesson', '%1$s Lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
											esc_html( number_format_i18n( $lesson_count ) )
										);
										?>
										</span>

										<?php endif; ?>

										<?php
										$quiz_count = get_course_section_children_count_by_section( $section->get_id(), PostType::QUIZ );

										if ( $quiz_count > 0 ) :
											?>

										<span class="masteriyo-single-body-accordion-info">
											<?php
											printf(
												/* translators: %1$s: Quizzes count */
												esc_html( _nx( '%1$s Quiz', '%1$s Quizzes', $quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
												esc_html( number_format_i18n( $quiz_count ) )
											);
											?>
										</span>

										<?php endif; ?>

										<?php
										/**
										 * Fires an action to render the curriculum accordion header info item for a single course page layout 1.
										 *
										 * This action hook is used by child themes and plugins to render the curriculum accordion header info item
										 * for a single course page layout 1.
										 *
										 * @since 1.10.0 [Free]
										 *
										 * @param \Masteriyo\Models\Section $section The section object.
										 * @param \Masteriyo\Models\Course $course The course object.
										 */
										do_action( 'masteriyo_layout_1_single_course_curriculum_accordion_header_info_item', $section, $course );
										?>
									</div>
									<span class="masteriyo-single-body-accordion-icon">
											<svg xmlns="http://www.w3.org/2000/svg" fill="#4E4E4E" viewBox="0 0 24 24">
												<path d="M12 17.501c-.3 0-.5-.1-.7-.3l-9-9c-.4-.4-.4-1 0-1.4.4-.4 1-.4 1.4 0l8.3 8.3 8.3-8.3c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4l-9 9c-.2.2-.4.3-.7.3Z" />
											</svg>
									</span>
								</div>
								<?php
								$objects = get_course_section_children_by_section( $section->get_id() );
								?>
								<?php if ( ! empty( $objects ) ) : ?>
									<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body">
										<ul class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-items">
											<?php
											foreach ( $objects as $object ) :
												$lesson_id                          = (int) $object->get_id();
												$status                             = $lesson_progress_map[ $lesson_id ] ?? 'not_started';
												list( $status_class, $status_icon ) = get_lesson_status_class_and_icon( $status );
												?>

												<li class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-item">
													<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-item-icon">
														<?php
														echo $object->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
														?>
															<a href="<?php echo esc_url( home_url( "/learn/course/{$course->get_slug()}/#/course/{$course->get_id()}/{$object->get_object_type()}/{$lesson_id}" ) ); ?>">
															<?php echo esc_html( $object->get_name() ); ?>
														</a>
														<span class="masteriyo-lesson-status-<?php echo esc_attr( $status_class ); ?>">
															<?php echo $status_icon; ?>
														</span>
														<?php
														/**
														 * Fires an action to render the curriculum accordion body item for a single course page layout 1.
														 *
														 * This action hook is used by child themes and plugins to render the curriculum accordion body item
														 * for a single course page layout 1.
														 *
														 * @since 1.10.0 [Free]
														 *
														 * @param \Masteriyo\Models $object The model object.
														 * @param \Masteriyo\Models\Course $course The course object.
														 */
														do_action( 'masteriyo_after_layout_1_single_course_curriculum_accordion_body_item_title', $object, $course );
														?>
													</div>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		<?php endif; ?>

		<!-- Overview Content -->
		<div id="masteriyoSingleCourseOverviewTab" class=" tab-content course-overview  masteriyo-single-body__main--overview-content <?php echo $overview_is_hidden ? 'masteriyo-hidden' : ''; ?>">
			<?php echo do_shortcode( wp_kses_post( ( $course->get_description() ) ) ); ?>
		</div>

		<!-- Review Content -->
		<div id="masteriyoSingleCourseReviewsTab" class="tab-content course-reviews masteriyo-single-body__main--review-content masteriyo-hidden">
			<!-- Review count -->

			<!-- User review content -->

			<!-- Review form -->
			<?php
			$course_id = $course->get_id();

			$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course_id, 1 );
			/**
			 * Fires to render review content on single course page layout 1.
			 *
			 * @since 1.10.0 [Free]
			 *
			 * @param \Masteriyo\Models\Course $course The course object.
			 */
			do_action( 'masteriyo_layout_1_single_course_review_content', $course, $reviews_and_replies );
			?>
		</div>

		<?php
		/**
		 * Fires to render additional tab content in single course page.
		 *
		 * This action hook is used by child themes and plugins to render additional
		 * tab content in single course page.
		 *
		 * @since 1.10.0 [Free]
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		do_action( 'masteriyo_layout_1_single_course_tabbar_content', $course );
		?>
	</section>
<!--  -->
<?php
/**
 * Fires after rendering main content tab in single course page.
 *
 * @since 2.0.0
 */
do_action( 'masteriyo_after_layout_1_single_course_main_tab_content', $course );
