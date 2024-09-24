<?php
/**
 * Masteriyo course completion date block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;

class MasteriyoCourseCompletionDate extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$pdf             = $this->get_pdf();
		$completion_date = $pdf->is_preview() ? __( 'Completion Date', 'learning-management-system' ) : '';
		$student_id      = $pdf->get_student_id();
		$course_id       = $pdf->get_course_id();
		$block_data      = $this->get_block_data();
		$date_format     = masteriyo_array_get( $block_data, 'attrs.dateFormat' );
		$date_format     = empty( $date_format ) ? 'F j, Y' : $date_format;

		if ( $student_id && $course_id ) {
			$query      = new CourseProgressQuery(
				array(
					'user_id'   => $student_id,
					'course_id' => $course_id,
					'status'    => CourseProgressStatus::COMPLETED,
				)
			);
			$progresses = $query->get_course_progress();
			$progress   = empty( $progresses ) ? null : $progresses[0];

			if ( $progress ) {
				$completed_at = $progress->get_completed_at();

				if ( $completed_at ) {
					$completion_date = gmdate( $date_format, $completed_at->getTimestamp() );
				}
			}
		}

		$html  = str_replace( '{{masteriyo_course_completion_date}}', $completion_date, $block_data['innerHTML'] );
		$html .= '<style>' . masteriyo_array_get( $block_data, 'attrs.blockCSS', '' ) . '</style>';
		return $html;
	}
}
