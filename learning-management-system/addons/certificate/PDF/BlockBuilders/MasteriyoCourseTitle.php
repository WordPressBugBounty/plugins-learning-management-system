<?php
/**
 * Masteriyo course title block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

class MasteriyoCourseTitle extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$pdf          = $this->get_pdf();
		$block_data   = $this->get_block_data();
		$course       = masteriyo_get_course( $pdf->get_course_id() );
		$course_title = __( 'Course Title', 'learning-management-system' );

		if ( ! is_null( $course ) ) {
			$course_title = $course->get_title();
		}

		$html  = $block_data['innerHTML'];
		$html  = str_replace( '{{masteriyo_course_title}}', $course_title, $html );
		$html .= '<style>' . masteriyo_array_get( $block_data, 'attrs.blockCSS', '' ) . '</style>';

		return $html;
	}
}
