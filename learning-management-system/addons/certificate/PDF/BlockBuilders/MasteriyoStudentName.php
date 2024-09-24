<?php
/**
 * Masteriyo student name block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

class MasteriyoStudentName extends BlockBuilder {

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
		$student      = masteriyo_get_user( $pdf->get_student_id() );
		$student_name = __( 'Student Name', 'learning-management-system' );
		$name_format  = masteriyo_array_get( $block_data, 'attrs.nameFormat', 'fullname' );

		if ( ! is_null( $student ) && ! is_wp_error( $student ) ) {
			$full_name = trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) );

			if ( 'fullname' === $name_format ) {
				$student_name = $full_name;
			} elseif ( 'first-name' === $name_format ) {
				$student_name = $student->get_first_name();
			} elseif ( 'last-name' === $name_format ) {
				$student_name = $student->get_last_name();
			} elseif ( 'display-name' === $name_format ) {
				$student_name = $student->get_display_name();
			} else {
				$student_name = $full_name;
			}
		}

		/**
		 * Filter student name before using.
		 *
		 * @since 1.13.0
		 *
		 * @param string $student_name Student name.
		 * @param string $name_format Name format.
		 * @param \Masteriyo\Models\User $student Student object.
		 */
		$student_name = apply_filters( 'masteriyo_pro_certificate_student_name', $student_name, $name_format, $student );

		$html  = $block_data['innerHTML'];
		$html  = str_replace( '{{masteriyo_student_name}}', $student_name, $html );
		$html .= '<style>' . masteriyo_array_get( $block_data, 'attrs.blockCSS', '' ) . '</style>';
		return $html;
	}
}
