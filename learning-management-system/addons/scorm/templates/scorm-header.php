<?php

/**
 * Scorm learn page header template content.
 *
 * @version 1.8.3
 */

use Masteriyo\Enums\CourseProgressStatus;

defined( 'ABSPATH' ) || exit;
?>
<div class="masteriyo-scorm-course-header">
	<div class="masteriyo-scorm-course-header__course">
		<span class="masteriyo-scorm-course-header__course-name"><?php esc_html_e( 'Course Name:', 'learning-management-system' ); ?></span>
		<h5 class="masteriyo-scorm-course-header__course-title"><?php echo esc_html( $course->get_title() ); ?></h5>
	</div>

	<div>
		<?php
		if ( is_user_logged_in() ) :
			if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() && $is_certificate_addon_enabled ) :
				$enabled        = get_post_meta( $course->get_id(), '_certificate_enabled', true );
				$certificate_id = get_post_meta( $course->get_id(), '_certificate_id', true );
				if ( $enabled && $certificate_id ) :
					?>
					<a style="margin-right: 10px;" class="masteriyo-scorm-course-header__button-download" href="<?php echo esc_url( $certificate_url ); ?>"><?php esc_html_e( 'Download Certificate', 'learning-management-system' ); ?></a>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) : ?>
				<a href="javascript:void(0);" class="masteriyo-scorm-course-header__button-complete"><?php esc_html_e( 'Completed', 'learning-management-system' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( get_home_url() . '?masteriyo_scorm_complete=' . $course->get_id() ); ?>" class="masteriyo-scorm-course-header__button-continue"><?php esc_html_e( 'Complete Course', 'learning-management-system' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>
		<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-scorm-course-header__button-exit"><?php esc_html_e( 'Exit', 'learning-management-system' ); ?></a>
	</div>
</div>
