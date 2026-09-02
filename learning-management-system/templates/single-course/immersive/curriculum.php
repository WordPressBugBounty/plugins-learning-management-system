<?php
/**
 * Course curriculum for the immersive single course layout.
 *
 * The markup and class names mirror layout 1's curriculum block so the
 * existing accordion and expand/collapse behavior applies unchanged.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/immersive/curriculum.php.
 *
 * Template variables:
 *   $course              (\Masteriyo\Models\Course)
 *   $sections            (\Masteriyo\Models\Section[])
 *   $lesson_progress_map (array)
 *   $section_count       (int)
 *   $lesson_count        (int)
 *   $quiz_count          (int)
 *   $duration_string     (string)
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/** @var \Masteriyo\Models\Course|null $course */
$course = isset( $course ) ? $course : null;
/** @var \Masteriyo\Models\Section[] $sections */
$sections            = isset( $sections ) ? $sections : array();
$lesson_progress_map = isset( $lesson_progress_map ) ? (array) $lesson_progress_map : array();
$section_count       = isset( $section_count ) ? (int) $section_count : 0;
$lesson_count        = isset( $lesson_count ) ? (int) $lesson_count : 0;
$quiz_count          = isset( $quiz_count ) ? (int) $quiz_count : 0;
$duration_string     = isset( $duration_string ) ? (string) $duration_string : '';

if ( ! $course ) {
	return;
}

?>
<div class="masteriyo-single-body__main--curriculum-content-top">
	<ul class="masteriyo-single-body__main--curriculum-content-top--shortinfo">
		<?php if ( $section_count > 0 ) : ?>
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

		<?php if ( $lesson_count > 0 ) : ?>
			<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
				<?php
				printf(
					/* translators: %1$s: Lessons count */
					esc_html( _nx( '%1$s Lesson', '%1$s Lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $lesson_count ) )
				);
				?>
			</li>
		<?php endif; ?>

		<?php if ( $quiz_count > 0 ) : ?>
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
		/** This action is documented in templates/single-course/layout-1/main-content-tab.php */
		do_action( 'masteriyo_layout_1_single_course_curriculum_shortinfo_item', $course );
		?>

		<?php if ( $duration_string ) : ?>
			<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">
				<?php
				printf(
					/* translators: %s: course duration, e.g. 1h 30m */
					esc_html__( '%s Duration', 'learning-management-system' ),
					esc_html( $duration_string )
				);
				?>
			</li>
		<?php endif; ?>
	</ul>

	<span class="masteriyo-single-body__main--curriculum-content-top--expand-btn" data-expand-all-text="<?php esc_attr_e( 'Expand All', 'learning-management-system' ); ?>" data-collapse-all-text="<?php esc_attr_e( 'Collapse All', 'learning-management-system' ); ?>" data-expanded="false"><?php esc_html_e( 'Expand All', 'learning-management-system' ); ?></span>
</div>

<div class="masteriyo-single-body__main--curriculum-content-bottom">
	<?php foreach ( $sections as $section ) : ?>
		<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion">
			<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header">
				<h4 class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header-title"><?php echo esc_html( $section->get_name() ); ?></h4>

				<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--header-misc">
					<?php $section_lesson_count = get_course_section_children_count_by_section( $section->get_id(), PostType::LESSON ); ?>
					<?php if ( $section_lesson_count > 0 ) : ?>
						<span class="masteriyo-single-body-accordion-info">
							<?php
							printf(
								/* translators: %1$s: Lessons count */
								esc_html( _nx( '%1$s Lesson', '%1$s Lessons', $section_lesson_count, 'Lessons Count', 'learning-management-system' ) ),
								esc_html( number_format_i18n( $section_lesson_count ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php $section_quiz_count = get_course_section_children_count_by_section( $section->get_id(), PostType::QUIZ ); ?>
					<?php if ( $section_quiz_count > 0 ) : ?>
						<span class="masteriyo-single-body-accordion-info">
							<?php
							printf(
								/* translators: %1$s: Quizzes count */
								esc_html( _nx( '%1$s Quiz', '%1$s Quizzes', $section_quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
								esc_html( number_format_i18n( $section_quiz_count ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php
					/** This action is documented in templates/single-course/layout-1/main-content-tab.php */
					do_action( 'masteriyo_layout_1_single_course_curriculum_accordion_header_info_item', $section, $course );
					?>
				</div>
				<span class="masteriyo-single-body-accordion-icon">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#4E4E4E" viewBox="0 0 24 24">
						<path d="M12 17.501c-.3 0-.5-.1-.7-.3l-9-9c-.4-.4-.4-1 0-1.4.4-.4 1-.4 1.4 0l8.3 8.3 8.3-8.3c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4l-9 9c-.2.2-.4.3-.7.3Z" />
					</svg>
				</span>
			</div>
			<?php $objects = get_course_section_children_by_section( $section->get_id() ); ?>
			<?php if ( ! empty( $objects ) ) : ?>
				<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body">
					<ul class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-items">
						<?php
						foreach ( $objects as $object ) :
							$item_id                            = (int) $object->get_id();
							$lesson_status                      = $lesson_progress_map[ $item_id ] ?? 'not_started';
							list( $status_class, $status_icon ) = get_lesson_status_class_and_icon( $lesson_status );

							$learn_page_url = masteriyo_get_page_permalink( 'learn' );
							$permalink      = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();

							if ( '' === get_option( 'permalink_structure' ) ) {
								$permalink = add_query_arg(
									array(
										'course_name' => $course->get_id(),
									),
									$learn_page_url
								);
							}

							$permalink .= '#/course/' . $course->get_id() . '/' . $object->get_object_type() . '/' . $item_id;
							?>
							<li class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-item">
								<div class="masteriyo-single-body__main--curriculum-content-bottom__accordion--body-item-icon">
									<?php echo $object->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<a href="<?php echo esc_url( $permalink ); ?>">
										<?php echo esc_html( $object->get_name() ); ?>
									</a>
									<span class="masteriyo-lesson-status-<?php echo esc_attr( $status_class ); ?>">
										<?php echo $status_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded internal SVG markup from get_lesson_status_class_and_icon(). ?>
									</span>
									<?php
									/** This action is documented in templates/single-course/layout-1/main-content-tab.php */
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
