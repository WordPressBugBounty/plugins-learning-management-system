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
	if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) :
		$enabled        = get_post_meta( $course->get_id(), '_certificate_enabled', true );
		$certificate_id = get_post_meta( $course->get_id(), '_certificate_id', true );
		if ( $enabled && $certificate_id ) :
			?>
			<a style="margin-right: 10px;" class="masteriyo-scorm-course-header__button-download" target="_blank" href="<?php echo esc_url( masteriyo_get_page_permalink( 'account' ) . '#/certificates' ); ?>"><?php esc_html_e( 'Download Certificate', 'learning-management-system' ); ?></a>
		<?php endif; ?>
	<?php endif; ?>

	<a href="<?php echo esc_url( $course->get_permalink() ); ?>" class="masteriyo-scorm-course-header__button-exit"><?php esc_html_e( 'Exit', 'learning-management-system' ); ?></a>
</div>
</div>
