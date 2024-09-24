<?php
/**
 * Masteriyo certificate block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use Masteriyo\Addons\Certificate\Models\Setting;

class MasteriyoCertificate extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$page_size        = masteriyo_sanitize_pdf_page_size( $this->block['attrs']['pageSize'], 'Letter' );
		$page_orientation = masteriyo_sanitize_pdf_page_orientation( $this->block['attrs']['pageOrientation'], 'L' );

		$this->pdf->mpdf->_setPageSize( $page_size, $page_orientation );

		$convert          = 3.7795275591;
		$pdf_page_width   = $this->pdf->mpdf->w; // NOTE: The page size should be set before accessing the 'w' property.
		$page_width       = absint( $pdf_page_width * $convert );
		$container_width  = absint( masteriyo_array_get( $this->block, 'attrs.containerWidth.value', 0 ) );
		$container_width  = $container_width > 0 ? $container_width : 100;
		$background_image = masteriyo_array_get( $this->block, 'attrs.backgroundImageURL', '' );
		$padding_top      = masteriyo_array_get( $this->block, 'attrs.paddingTop.value' );

		/**
		 * Added setting to enable absolute image path due to the issue with the image not showing in the PDF.
		 *
		 * @since 1.13.0
		 */
		$use_absolute_path = masteriyo_bool_to_string( Setting::get( 'use_absolute_img_path' ) );

		if ( masteriyo_is_certificate_html_inspection_mode() ) {
			$a4_paper_ratio = 'Letter' === $page_size ? 1.33214920071 : 1.4142;
			$aspect_ratio   = 'L' === $page_orientation ? "{$a4_paper_ratio} / 1" : "1 / {$a4_paper_ratio}";

			$this->pdf->add_style( 'body', 'padding', 0 );
			$this->pdf->add_style( 'body', 'margin', 0 );
			$this->pdf->add_style( 'body', 'box-sizing', 'border-box' );
			$this->pdf->add_style( 'body', 'width', '100%' );
			$this->pdf->add_style( 'body', 'background-size', 'cover' );
			$this->pdf->add_style( 'body', 'aspect-ratio', $aspect_ratio );
		}

		if ( ! empty( $background_image ) ) {
			if ( 'yes' === $use_absolute_path ) {
				$this->pdf->add_style( 'body', 'background-image', 'url("' . $background_image . '")' );
			} else {
				$this->pdf->add_style( 'body', 'background-image', 'url("' . masteriyo_get_image_relative_path( $background_image ) . '")' );
			}
		}

		$this->pdf->add_style( 'body', 'background-position', 'top left' );
		$this->pdf->add_style( 'body', 'background-repeat', 'no-repeat' );
		$this->pdf->add_style( 'body', 'background-image-resize', '6' );
		$this->pdf->add_style( 'body', 'background-image-resolution', 'from-image' );
		$this->pdf->add_style( 'body', 'font-size', '18px' );
		$this->pdf->add_style( 'body', 'line-height', '1' );
		$this->pdf->add_style( '#wrap', 'width', $container_width . '%' );
		$this->pdf->add_style( '#wrap', 'margin', '0px auto' );
		$this->pdf->add_style( '#wrap', 'position', 'relative' );

		if ( is_numeric( $padding_top ) ) {
			$this->pdf->add_style( '#wrap', 'padding-top', $padding_top );
		}

		$html = '<div id="wrap">';

		foreach ( $this->block['innerBlocks'] as $index => &$block ) {
			$block_builder = masteriyo_make_block_builder_instance( $block, $this->pdf );

			$block_builder->set_available_width( masteriyo_percent_to_amount( $container_width, $page_width ) );
			$block_builder->set_sibling_index( $index );
			$block_builder->set_siblings_count( count( $this->block['innerBlocks'] ) );

			$html .= $block_builder->build();
		}

		$html .= '</div>';

		return $html;
	}
}
